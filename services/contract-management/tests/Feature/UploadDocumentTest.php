<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\ContractCategory;
use App\Models\ContractStatus;
use App\Models\Document;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadDocumentTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(config('filesystems.default', 'local'));
        \Illuminate\Support\Facades\Cache::store('file')->flush();
    }

    /**
     * Helper to mock auth service verification for standard users.
     */
    protected function mockAuth(string $role = 'Sales', array $permissions = []): void
    {
        Http::fake([
            'http://auth-service:8000/api/internal/verify-token' => Http::response([
                'valid' => true,
                'user' => [
                    'id' => 42,
                    'email' => 'test-user@sbsi.com',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'role' => $role,
                    'permissions' => $permissions,
                    'department' => 'Sales',
                ]
            ], 200)
        ]);
    }

    /**
     * Test successful PDF file upload.
     */
    public function test_upload_pdf_document_success()
    {
        $this->mockAuth('Sales', ['create-contracts']);

        // Create PDF with valid %PDF magic bytes
        $file = UploadedFile::fake()->createWithContent('contract.pdf', "%PDF-1.4\n%EOF");

        $response = $this->withHeaders([
            'Authorization' => 'Bearer valid-token',
            'Accept' => 'application/json'
        ])->postJson('/api/documents/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Document uploaded and scanned successfully.');
        $response->assertJsonStructure([
            'data' => [
                'document_id',
                'file_name',
                'file_type',
                'file_size',
                'document_url',
                'uploaded_by',
                'uploaded_at'
            ]
        ]);

        $data = $response->json()['data'];
        $docId = $data['document_id'];

        // Verify Object Storage
        $filePath = Document::find($docId)->file_path;
        Storage::disk(config('filesystems.default'))->assertExists($filePath);

        // Verify MongoDB metadata record exists
        $this->assertNotNull(Document::find($docId));

        // Verify Audit Log is created in MySQL
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document_uploaded',
            'entity_type' => 'Document',
            'entity_id' => $docId,
            'user_id' => 42,
        ]);

        // Cleanup MongoDB record
        Document::destroy($docId);
    }

    /**
     * Test successful DOCX file upload.
     */
    public function test_upload_docx_document_success()
    {
        $this->mockAuth('Manager', ['create-contracts']);

        // Create DOCX with valid PK\x03\x04 zip magic bytes
        $file = UploadedFile::fake()->createWithContent('contract.docx', "PK\x03\x04\n");

        $response = $this->withHeaders([
            'Authorization' => 'Bearer valid-token',
            'Accept' => 'application/json'
        ])->postJson('/api/documents/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(201);
        
        $data = $response->json()['data'];
        $docId = $data['document_id'];

        $this->assertNotNull(Document::find($docId));

        // Cleanup MongoDB record
        Document::destroy($docId);
    }

    /**
     * Test upload failure with invalid magic bytes (e.g. text file renamed to pdf).
     */
    public function test_upload_document_invalid_magic_bytes()
    {
        $this->mockAuth('Sales', ['create-contracts']);

        // Create PDF with plain text content instead of PDF magic bytes
        $file = UploadedFile::fake()->createWithContent('contract.pdf', "This is plain text and not PDF.");

        $response = $this->withHeaders([
            'Authorization' => 'Bearer valid-token',
            'Accept' => 'application/json'
        ])->postJson('/api/documents/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
        $response->assertJsonFragment([
            'errors' => [
                'file' => ['The file has invalid magic bytes for a PDF document.']
            ]
        ]);
    }

    /**
     * Test malware scan hook rejection.
     */
    public function test_upload_malware_detection()
    {
        $this->mockAuth('Sales', ['create-contracts']);

        // Create a PDF containing EICAR malware signature
        $eicarString = 'X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';
        $file = UploadedFile::fake()->createWithContent('contract.pdf', "%PDF-1.4\n" . $eicarString);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer valid-token',
            'Accept' => 'application/json'
        ])->postJson('/api/documents/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
        $response->assertJsonFragment([
            'errors' => [
                'file' => ['Malware detected! This file is blocked.']
            ]
        ]);
    }

    /**
     * Test size limit enforcement (above 10 MB).
     */
    public function test_upload_file_exceeds_size_limit()
    {
        $this->mockAuth('Sales', ['create-contracts']);

        // Create a fake file with 11 MB size
        $file = UploadedFile::fake()->create('heavy.pdf', 11 * 1024, 'application/pdf');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer valid-token',
            'Accept' => 'application/json'
        ])->postJson('/api/documents/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    /**
     * Test linking MongoDB documents to Contract MySQL record.
     */
    public function test_link_documents_to_contract()
    {
        $this->mockAuth('Sales', ['create-contracts']);

        // 1. Create a MongoDB document metadata
        $document = Document::create([
            'file_name' => 'link_test.pdf',
            'file_path' => 'contracts/documents/link_test.pdf',
            'document_url' => 'http://localhost/storage/link_test.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
            'uploaded_by' => 42,
            'uploaded_at' => now(),
        ]);
        $docId = (string) $document->getKey();

        // 2. Create a contract and link the document_id
        $category = ContractCategory::firstOrCreate(['category_name' => 'Service Agreement']);
        $status = ContractStatus::firstOrCreate(['status_name' => 'Notarized PDF']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer valid-token',
            'Accept' => 'application/json'
        ])->postJson('/api/contracts', [
            'businessPartner' => 'Globe Telecom',
            'category' => 'Service Agreement',
            'status' => 'Notarized PDF',
            'itemCode' => 'ITM-99',
            'description' => 'Test Link Contract',
            'serialNo' => 'SN-99',
            'sbuNumber' => 'SBU-99',
            'region' => 'Luzon',
            'startDate' => '2026-06-01',
            'endDate' => '2026-07-01',
            'document_ids' => [$docId]
        ]);

        $response->assertStatus(201);
        $contractId = $response->json()['data']['contract_id'];

        // Verify the MongoDB document now has the correct contract_id
        $updatedDoc = Document::find($docId);
        $this->assertEquals($contractId, $updatedDoc->contract_id);

        // Verify relational retrieval
        $contract = Contract::find($contractId);
        $this->assertCount(1, $contract->documents);
        $this->assertEquals($docId, (string) $contract->documents->first()->getKey());

        // Cleanup
        Document::destroy($docId);
    }
}
