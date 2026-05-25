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
        ->middleware('permission:view-contracts');
    Route::post('/contracts', [\App\Http\Controllers\ContractController::class, 'store'])
        ->middleware('permission:create-contracts');
    Route::put('/contracts/{id}', [\App\Http\Controllers\ContractController::class, 'update'])
        ->middleware('permission:edit-contracts');
    Route::delete('/contracts/{id}', [\App\Http\Controllers\ContractController::class, 'destroy'])
        ->middleware('permission:delete-contracts');
    Route::get('/contract-requests',  [ContractController::class, 'indexRequests']);

    Route::get('/contracts/{id}',    [ContractController::class, 'show'])
        ->middleware('permission:view-contracts');
    Route::patch('/contracts/{id}/status', [ContractController::class, 'updateStatus'])
        ->middleware('role:Manager,Admin,Finance Manager');

    Route::post('/admin/users', [\App\Http\Controllers\AdminUserProxyController::class, 'store'])
        ->middleware('permission:manage-users');

    // Aggregated Audit Logs Route
    Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])
        ->middleware('permission:manage-users');

    // Document Upload Route
    Route::post('/documents/upload', \App\Http\Controllers\Api\V1\Documents\UploadController::class)
        ->middleware('permission:create-contracts');

    // Document Retrieve Route
    Route::get('/documents/{id}/file', [\App\Http\Controllers\Api\V1\Documents\DownloadController::class, 'show'])
        ->middleware('permission:view-contracts');
    Route::get('/documents/{id}/presigned-url', [\App\Http\Controllers\Api\V1\Documents\DownloadController::class, 'presignedUrl'])
        ->middleware('permission:view-contracts');

});

// Internal webhook for login/logout events from auth-service
Route::post('/internal/audit-event', [\App\Http\Controllers\InternalAuditController::class, 'receive']);
