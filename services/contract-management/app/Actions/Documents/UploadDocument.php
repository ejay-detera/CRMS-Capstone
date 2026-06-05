<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use App\Payloads\Documents\UploadDocumentPayload;
use App\Payloads\Documents\UploadDocumentResult;
use App\Services\AuditLogService;
use App\Services\MalwareScannerService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final readonly class UploadDocument
{
    public function __construct(
        private MalwareScannerService $malwareScanner,
        private AuditLogService $auditLogService
    ) {}

    /**
     * Handle the document upload logic.
     *
     * @throws ValidationException
     */
    public function handle(UploadDocumentPayload $payload): UploadDocumentResult
    {
        // 1. Run Malware Scan (blocks only when a malware signature is detected)
        $scanResult = $this->malwareScanner->scan($payload->file);
        if ($scanResult->isInfected) {
            throw ValidationException::withMessages([
                'file' => ['Malware detected! This file is blocked.'],
            ]);
        }

        // 2. Generate UUID and store file using default filesystem disk (object storage abstraction)
        $disk = config('filesystems.default', 'local');
        $uuid = (string) \Illuminate\Support\Str::uuid();
        $extension = strtolower($payload->file->getClientOriginalExtension());
        $filename = "{$uuid}.{$extension}";
        
        $path = Storage::disk($disk)->putFileAs('contracts/documents', $payload->file, $filename);

        if (!$path) {
            throw ValidationException::withMessages([
                'file' => ['Failed to save the file to object storage.'],
            ]);
        }

        // Generate accessible URL
        $url = Storage::disk($disk)->url($path);

        $uploadedBy = request()->get('auth_id');

        // 3. Store metadata record in MongoDB
        $document = Document::create([
            'contract_id' => $payload->contractId,
            'uuid' => $uuid,
            'file_name' => $payload->file->getClientOriginalName(),
            'file_path' => $path,
            'document_url' => $url,
            'file_type' => $extension,
            'file_size' => $payload->file->getSize(),
            'uploaded_by' => $uploadedBy,
            'uploaded_at' => now(),
        ]);

        // 4. Audit Log Hook on Document Upload
        $this->auditLogService->log(
            'document_uploaded',
            'Document',
            (string) $document->getKey(),
            $uploadedBy,
            [],
            $document->toArray()
        );

        return new UploadDocumentResult($document, $scanResult->warning);
    }
}
