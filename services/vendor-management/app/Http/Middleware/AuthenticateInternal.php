<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateInternal
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $authData = $this->authService->verifyToken($token);

        if (!$authData || !($authData['valid'] ?? false)) {
            return response()->json(['message' => 'Unauthenticated or session expired.'], 401);
        }

        // Attach user data to the request for easy access in controllers
        $request->merge([
            'auth_user' => $authData['user'],
            'auth_id' => $authData['user']['id'],
            'auth_role' => $authData['user']['role'],
            'auth_permissions' => $authData['user']['permissions'] ?? [],
            'auth_department' => $authData['user']['department'] ?? null,
        ]);

        // CRMS-capstone Strict Role Isolation
        $role = $authData['user']['role'] ?? '';
        $allowedRoles = ['Admin', 'Manager', 'Sales', 'Employee'];

        if (!in_array($role, $allowedRoles)) {
            return response()->json([
                'message' => 'Forbidden. Your role is not authorized to access this system.'
            ], 403);
        }

        return $next($request);
    }
}
