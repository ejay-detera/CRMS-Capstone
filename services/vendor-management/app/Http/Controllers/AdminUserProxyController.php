<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminUserProxyController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Proxy user creation to auth-service with CRMS-specific constraints.
     */
    public function store(Request $request)
    {
        // 1. Initial Validation
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'role_name' => 'required|string', // We use name for easier logic
            'department_name' => 'required|string',
        ]);

        // 2. Enforce CRMS Constraints
        if (strcasecmp($request->department_name, 'Finance') !== 0) {
            return response()->json([
                'message' => 'Unauthorized. This admin can only create users for the Finance department.'
            ], 403);
        }

        $allowedRoles = ['Employee', 'Manager'];
        $roleMatched = false;
        foreach ($allowedRoles as $role) {
            if (strcasecmp($request->role_name, $role) === 0) {
                $roleMatched = true;
                break;
            }
        }

        if (!$roleMatched) {
            return response()->json([
                'message' => 'Unauthorized. This admin can only assign Employee or Manager roles.'
            ], 403);
        }

        // 3. Resolve IDs from Auth Service
        // We fetch current roles/depts from auth-service to get the correct IDs
        $token = $request->bearerToken();
        $roles = HttpProxy::get('admin/role-options', $token);
        $depts = HttpProxy::get('admin/department-options', $token);

        $roleId = $this->findIdByName($roles, $request->role_name);
        $deptId = $this->findIdByName($depts, $request->department_name);

        if (!$roleId || !$deptId) {
            return response()->json(['message' => 'Failed to resolve Role or Department ID from Auth Service.'], 422);
        }

        // 4. Forward to Auth Service
        $authRequestData = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'role_id' => $roleId,
            'department_id' => $deptId,
        ];

        $result = $this->authService->createUser($authRequestData, $token);

        if (isset($result['error'])) {
            return response()->json($result, $result['status'] ?? 500);
        }

        return response()->json($result, 201);
    }

    private function findIdByName($items, $name) {
        foreach ($items as $item) {
            if (strcasecmp($item['name'], $name) === 0) {
                return $item['id'];
            }
        }
        return null;
    }
}

/**
 * Simple Helper for internal calls
 */
class HttpProxy {
    public static function get($endpoint, $token) {
        $baseUrl = env('AUTH_SERVICE_URL', 'http://auth-service:8000/api');
        $response = \Illuminate\Support\Facades\Http::withToken($token)->get("{$baseUrl}/{$endpoint}");
        return $response->json();
    }
}
