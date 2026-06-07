<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateInternalSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('app.internal_service_secret');

        \Illuminate\Support\Facades\Log::info('Internal Secret check', [
            'env_secret' => $secret,
            'header_secret' => $request->header('X-Internal-Secret'),
        ]);

        if (!$secret || $request->header('X-Internal-Secret') !== $secret) {
            return response()->json(['message' => 'Unauthorized internal request.'], 401);
        }

        return $next($request);
    }
}
