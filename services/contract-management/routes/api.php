<?php

use App\Http\Controllers\ContractController;
use App\Http\Controllers\LookupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.internal'])->group(function () {

    Route::get('/lookups/{type}', [LookupController::class, 'show']);
    
    Route::get('/dashboard',           [ContractController::class, 'dashboardSummary']);
    // Contracts REST endpoints
    Route::get('/contracts', [\App\Http\Controllers\ContractController::class, 'index'])
        ->middleware('permission:cms.contracts.view');
    Route::post('/contracts', [\App\Http\Controllers\ContractController::class, 'store'])
        ->middleware('permission:cms.contracts.create');
    Route::put('/contracts/{id}', [\App\Http\Controllers\ContractController::class, 'update'])
        ->middleware('permission:cms.contracts.edit');
    Route::delete('/contracts/{id}', [\App\Http\Controllers\ContractController::class, 'destroy'])
        ->middleware('permission:cms.contracts.delete');
    Route::get('/contract-requests',  [ContractController::class, 'indexRequests']);

    Route::get('/contracts/{id}',    [ContractController::class, 'show'])
        ->middleware('permission:cms.contracts.view');
    Route::patch('/contracts/{id}/status', [ContractController::class, 'updateStatus'])
        ->middleware('permission:cms.contracts.approve');
    Route::post('/contracts/{id}/notify-manager', [ContractController::class, 'notifyManager'])
        ->middleware('permission:cms.contracts.edit');

    // US-023: High-Risk Contract Approval Gate
    Route::get('/contracts/{id}/high-risk-approval', [\App\Http\Controllers\ContractApprovalController::class, 'show'])
        ->middleware('permission:cms.contracts.view');
    Route::post('/contracts/{id}/high-risk-approval', [\App\Http\Controllers\ContractApprovalController::class, 'store'])
        ->middleware('permission:cms.contracts.approve');

    Route::post('/admin/users', [\App\Http\Controllers\AdminUserProxyController::class, 'store'])
        ->middleware('permission:cms.users.create');

    // Aggregated Audit Logs Route
    Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])
        ->middleware('permission:cms.users.view');

    // Document Upload Route
    Route::post('/documents/upload', \App\Http\Controllers\Api\V1\Documents\UploadController::class)
        ->middleware('permission:cms.contracts.create');

    // Document Retrieve Route
    Route::get('/documents/{id}/file', [\App\Http\Controllers\Api\V1\Documents\DownloadController::class, 'show'])
        ->middleware('permission:cms.contracts.view');
    Route::get('/documents/{id}/presigned-url', [\App\Http\Controllers\Api\V1\Documents\DownloadController::class, 'presignedUrl'])
        ->middleware('permission:cms.contracts.view');

    // Contract Amendments Routes
    Route::apiResource('contract-amendments', \App\Http\Controllers\ContractAmendmentController::class);
    Route::patch('contract-amendments/{id}/status', [\App\Http\Controllers\ContractAmendmentController::class, 'updateStatus']);
    Route::get('contracts/{id}/versions', [\App\Http\Controllers\ContractAmendmentController::class, 'versionHistory']);

});

// Internal webhook for login/logout events from auth-service
Route::post('/internal/audit-event', [\App\Http\Controllers\InternalAuditController::class, 'receive']);

// Internal service-to-service reads for ai-service's RAG risk-assessment pipeline (Feature 1).
Route::middleware(['internal.secret'])->group(function () {
    Route::get('/internal/contracts/{contractId}/documents', [\App\Http\Controllers\Api\V1\Documents\InternalDocumentController::class, 'listForContract']);
    Route::get('/internal/documents/{id}', [\App\Http\Controllers\Api\V1\Documents\InternalDocumentController::class, 'show']);
    Route::get('/internal/documents/{id}/file', [\App\Http\Controllers\Api\V1\Documents\InternalDocumentController::class, 'file']);
    Route::get('/internal/contracts/{id}/owner', function (string $id) {
        $contract = \App\Models\Contract::find($id);
        if (!$contract) {
            return response()->json(['message' => 'Contract not found.'], 404);
        }
        return response()->json(['created_by' => $contract->created_by]);
    });

    // Feature 4: Analytics — descriptive/diagnostic metrics snapshot for analytics-service.
    Route::get('/internal/metrics/contracts', [\App\Http\Controllers\Api\V1\Internal\InternalMetricsController::class, 'contracts']);
    Route::get('/internal/metrics/contracts/by-segment', [\App\Http\Controllers\Api\V1\Internal\InternalMetricsController::class, 'contractsBySegment']);
});
