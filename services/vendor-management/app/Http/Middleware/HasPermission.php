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

        if (!$this->hasPermission($permissions, $permission)) {
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

    protected function hasPermission(array $userPermissions, string $requiredPermission): bool
    {
        // 1. Direct match
        if (in_array($requiredPermission, $userPermissions)) {
            return true;
        }

        // 2. Resolve requiredPermission alternatives
        $alts = $this->getAlternatives($requiredPermission);
        foreach ($alts as $alt) {
            if (in_array($alt, $userPermissions)) {
                return true;
            }
        }

        // 3. Resolve userPermissions alternatives
        foreach ($userPermissions as $userPerm) {
            $userAlts = $this->getAlternatives($userPerm);
            if (in_array($requiredPermission, $userAlts)) {
                return true;
            }
        }

        return false;
    }

    protected function getAlternatives(string $permission): array
    {
        $alts = [];

        // Match dot format: crms.resource.action
        if (preg_match('/^crms\.([^.]+)\.([^.]+)$/', $permission, $matches)) {
            $resource = $matches[1];
            $action = $matches[2];
            $alts[] = "{$action}-{$resource}";      // view-partners
            $alts[] = "{$resource}.{$action}";      // partners.view
            $alts[] = "{$action}_{$resource}";      // view_partners
            $alts[] = "{$resource}-{$action}";      // partners-view
            $alts[] = "{$action} {$resource}";      // view partners
        }

        // Match dash format: action-resource (e.g. view-partners, view-contracts, view-suppliers)
        if (preg_match('/^([^-]+)-(.+)$/', $permission, $matches)) {
            $action = $matches[1];
            $resource = $matches[2];
            $alts[] = "crms.{$resource}.{$action}";
            $alts[] = "{$resource}.{$action}";
            $alts[] = "{$action}_{$resource}";
            $alts[] = "{$resource}-{$action}";
            $alts[] = "{$action} {$resource}";
        }

        return $alts;
    }
}

