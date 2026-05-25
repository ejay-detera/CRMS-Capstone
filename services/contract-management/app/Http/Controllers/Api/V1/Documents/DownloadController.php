<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Documents;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DownloadController extends Controller
{
    /**
     * Serve the document file.
     */
    public function show(string $id)
    {
        $document = Document::findOrFail($id);

        $disk = config('filesystems.default', 'local');
        
        if (!Storage::disk($disk)->exists($document->file_path)) {
            return response()->json(['message' => 'File not found on storage disk.'], 404);
        }

        return Storage::disk($disk)->response($document->file_path, $document->file_name);
    }

    /**
     * Generate a pre-signed URL for secure document download.
     */
    public function presignedUrl(string $id)
    {
        $document = Document::findOrFail($id);

        $disk = config('filesystems.default', 'local');

        if (!Storage::disk($disk)->exists($document->file_path)) {
            return response()->json(['message' => 'File not found on storage disk.'], 404);
        }

        // Generate pre-signed temporary URL valid for 15 minutes
        $expiration = now()->addMinutes(15);
        $url = Storage::disk($disk)->temporaryUrl($document->file_path, $expiration);

        return response()->json([
            'document_id' => (string) $document->getKey(),
            'file_name' => $document->file_name,
            'presigned_url' => $url,
            'expires_at' => $expiration->toISOString(),
        ]);
    }
}
