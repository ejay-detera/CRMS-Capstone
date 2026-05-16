<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.internal'])->group(function () {
    
    Route::get('/search', function (Request $request) {
        return response()->json([
            'message' => 'Search results retrieved',
            'query' => $request->query('q')
        ]);
    });

});
