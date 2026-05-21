<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminUserProxyController extends Controller
{
    protected AuthService $authService;
    protected AuditLogService $auditLogService;

    public function __construct(AuthService $authService, AuditLogService $auditLogService)
    {
        $this->authService = $authService;
        $this->auditLogService = $auditLogService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'role_name' => 'required|string',
            'department_name' => 'required|string',
        ]);

        // Constraint: Finance Only
        if (strcasecmp($request->department_name, 'Finance') !== 0) {
            return response()->json(['message' => 'Unauthorized. Finance only.'], 403);
        }

        // Constraint: Employee/Manager Only
        if (!in_array(ucfirst(strtolower($request->role_name)), ['Employee', 'Manager'])) {
            return response()->json(['message' => 'Unauthorized. Employee/Manager only.'], 403);
        }

        $token = $request->bearerToken();
        
        // Resolve IDs
        $baseUrl = env('AUTH_SERVICE_URL', 'http://auth-service:8000/api');
        $roles = Http::withToken($token)->get("{$baseUrl}/admin/role-options")->json();
        $depts = Http::withToken($token)->get("{$baseUrl}/admin/department-options")->json();

        $roleId = collect($roles)->firstWhere('name', ucfirst(strtolower($request->role_name)))['id'] ?? null;
        $deptId = collect($depts)->firstWhere('name', 'Finance')['id'] ?? null;

        if (!$roleId || !$deptId) {
            return response()->json(['message' => 'Failed to resolve Role or Department ID.'], 422);
        }

        $result = $this->authService->createUser([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'role_id' => $roleId,
            'department_id' => $deptId,
        ], $token);

        if (isset($result['error'])) {
            return response()->json($result, $result['status'] ?? 500);
        }

        // Audit Log User Creation
        $creatorRole = $request->get('auth_role');
        $creatorDepartment = $request->get('auth_department');
        $shouldLog = ($creatorDepartment === 'Finance') || ($creatorRole === 'IT Admin');

        if ($shouldLog) {
            $this->auditLogService->log(
                'user_created',
                'User',
                $result['user']['id'] ?? 0,
                $request->get('auth_id'),
                [],
                [
                    'email' => $request->email,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'role' => $request->role_name,
                    'department' => $request->department_name
                ],
                null // Skip department filter (IT Admin or creator department log override)
            );
        }

        return response()->json($result, 201);
    }
}
