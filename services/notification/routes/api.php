<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.internal'])->group(function () {
    
    Route::get('/notifications', function (Request $request) {
        return response()->json([
            'message' => 'Notifications retrieved',
            'data' => [
                ['id' => 1, 'message' => 'New contract pending approval'],
                ['id' => 2, 'message' => 'Vendor profile updated']
            ]
        ]);
    }); // No specific permission here, just auth required

});
