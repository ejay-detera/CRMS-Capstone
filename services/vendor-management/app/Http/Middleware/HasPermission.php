<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\AuditLogService;

class HasPermission
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $permissions = $request->get('auth_permissions', []);

        if (!in_array($permission, $permissions)) {
            // Log permission denial
            $userId = $request->get('auth_id');
            $this->auditLogService->log(
                'permission_denied',
                'System',
                0,
                $userId,
                [],
                ['required_permission' => $permission]
            );

            return response()->json([
                'message' => 'Forbidden. You do not have the required permission: ' . $permission
            ], 403);
        }

        return $next($request);
    }
}

