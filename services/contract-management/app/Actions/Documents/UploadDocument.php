<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use App\Payloads\Documents\UploadDocumentPayload;
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
    public function handle(UploadDocumentPayload $payload): Document
    {
        // 1. Run Malware Scan (throws ValidationException if signature is detected)
        $isClean = $this->malwareScanner->scan($payload->file);
        if (!$isClean) {
            throw ValidationException::withMessages([
                'file' => ['Malware detected! This file is blocked.'],
            ]);
        }

        // 2. Store file using default filesystem disk (object storage abstraction)
        $disk = config('filesystems.default', 'local');
        $path = Storage::disk($disk)->putFile('contracts/documents', $payload->file);

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
            'file_name' => $payload->file->getClientOriginalName(),
            'file_path' => $path,
            'document_url' => $url,
            'file_type' => strtolower($payload->file->getClientOriginalExtension()),
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

        return $document;
    }
}
