<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Documents;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Internal, X-Internal-Secret-authenticated endpoints used by ai-service's
 * RAG risk-assessment pipeline (Feature 1) to fetch a contract's documents
 * and raw file bytes for text extraction. No user token needed — this is
 * service-to-service only, matching the pattern already used for
 * /internal/audit-event and other services' internal.secret routes.
 */
final class InternalDocumentController extends Controller
{
    /**
     * GET /internal/contracts/{contractId}/documents
     */
    public function listForContract(Request $request, int $contractId)
    {
        $documents = Document::where('contract_id', $contractId)
            ->where('scan_status', 'clean')
            ->get(['_id', 'file_name', 'file_type']);

        return response()->json([
            'data' => $documents->map(fn ($d) => [
                'document_id' => (string) $d->getKey(),
                'file_name'   => $d->file_name,
                'file_type'   => $d->file_type,
            ])->values(),
        ]);
    }

    /**
     * GET /internal/documents/{id}/file — streams the raw file bytes.
     */
    public function file(Request $request, string $id)
    {
        $document = Document::find($id);
        if (!$document) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        $disk = config('filesystems.default', 'local');
        if (!Storage::disk($disk)->exists($document->file_path)) {
            return response()->json(['message' => 'File not found on storage disk.'], 404);
        }

        return Storage::disk($disk)->response($document->file_path, $document->file_name);
    }
}
