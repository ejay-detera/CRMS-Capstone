<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateInternalSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = env('INTERNAL_SERVICE_SECRET');

        if (!$secret || $request->header('X-Internal-Secret') !== $secret) {
            return response()->json(['message' => 'Unauthorized internal request.'], 401);
        }

        return $next($request);
    }
}
