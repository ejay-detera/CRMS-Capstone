<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HasPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $permissions = $request->get('auth_permissions', []);

        if (!in_array($permission, $permissions)) {
            return response()->json([
                'message' => 'Forbidden. Required permission: ' . $permission
            ], 403);
        }

        return $next($request);
    }
}
