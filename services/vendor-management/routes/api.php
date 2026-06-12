<?php

use Illuminate\Support\Facades\Route;

// Internal webhook — uses X-Internal-Secret, not Bearer token
Route::post('/internal/audit', [\App\Http\Controllers\InternalAuditController::class, 'receive']);

Route::middleware(['auth.internal'])->group(function () {

    // Admin User Creation Proxy (Finance Only)
    Route::post('/admin/users', [\App\Http\Controllers\AdminUserProxyController::class, 'store'])
        ->middleware('permission:crms.users.create');

    // Suppliers Routes
    Route::get('/suppliers/search', [\App\Http\Controllers\SupplierController::class, 'search'])
        ->middleware('permission:crms.partners.view');
    Route::get('/suppliers', [\App\Http\Controllers\SupplierController::class, 'index'])
        ->middleware('permission:crms.partners.view');
    Route::get('/suppliers/{id}', [\App\Http\Controllers\SupplierController::class, 'show'])
        ->middleware('permission:crms.partners.view');
    Route::post('/suppliers', [\App\Http\Controllers\SupplierController::class, 'store'])
        ->middleware('permission:crms.partners.create');
    Route::put('/suppliers/{id}', [\App\Http\Controllers\SupplierController::class, 'update'])
        ->middleware('permission:crms.partners.edit');
    Route::delete('/suppliers/{id}', [\App\Http\Controllers\SupplierController::class, 'destroy'])
        ->middleware('permission:crms.partners.delete');

    // Business Partners Routes
    Route::get('/partners/search', [\App\Http\Controllers\BusinessPartnerController::class, 'search'])
        ->middleware('permission:crms.partners.view');
    Route::get('/partners', [\App\Http\Controllers\BusinessPartnerController::class, 'index'])
        ->middleware('permission:crms.partners.view');
    Route::get('/partners/{id}', [\App\Http\Controllers\BusinessPartnerController::class, 'show'])
        ->middleware('permission:crms.partners.view');
    Route::post('/partners', [\App\Http\Controllers\BusinessPartnerController::class, 'store'])
        ->middleware('permission:crms.partners.create');
    Route::put('/partners/{id}', [\App\Http\Controllers\BusinessPartnerController::class, 'update'])
        ->middleware('permission:crms.partners.edit');
    Route::delete('/partners/{id}', [\App\Http\Controllers\BusinessPartnerController::class, 'destroy'])
        ->middleware('permission:crms.partners.delete');

    // Supplier–Contract Associations
    Route::get('/suppliers/{id}/contracts', \App\Http\Controllers\Api\V1\SupplierContracts\IndexController::class)
        ->middleware('permission:crms.partners.view');
    Route::post('/suppliers/{id}/contracts', \App\Http\Controllers\Api\V1\SupplierContracts\StoreController::class)
        ->middleware('permission:crms.partners.edit');
    Route::delete('/suppliers/{id}/contracts/{contractId}', \App\Http\Controllers\Api\V1\SupplierContracts\DestroyController::class)
        ->middleware('permission:crms.partners.edit');

    // Partner–Contract Associations
    Route::get('/partners/{id}/contracts', \App\Http\Controllers\Api\V1\PartnerContracts\IndexController::class)
        ->middleware('permission:crms.partners.view');
    Route::post('/partners/{id}/contracts', \App\Http\Controllers\Api\V1\PartnerContracts\StoreController::class)
        ->middleware('permission:crms.partners.edit');
    Route::delete('/partners/{id}/contracts/{contractId}', \App\Http\Controllers\Api\V1\PartnerContracts\DestroyController::class)
        ->middleware('permission:crms.partners.edit');

    // Returns all contract IDs already linked to any vendor (used for frontend pre-filtering)
    Route::get('/vendor-contracts/linked-ids', \App\Http\Controllers\Api\V1\LinkedContractIdsController::class)
        ->middleware('permission:crms.partners.view');

    // Vendor eligibility lookup (for integration with other systems)
    Route::get('/vendors/{code}/eligibility', \App\Http\Controllers\Api\V1\VendorEligibilityController::class)
        ->middleware('permission:crms.partners.view');

});
