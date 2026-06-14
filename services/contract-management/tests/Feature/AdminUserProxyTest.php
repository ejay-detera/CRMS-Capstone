<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminUserProxyTest extends TestCase
{
    use RefreshDatabase;
    public function test_create_user_finance_employee_success()
    {
        Http::fake([
            'http://auth-service:8000/api/internal/verify-token' => Http::response([
                'valid' => true,
                'user' => ['id' => 1, 'role' => 'Admin', 'permissions' => ['cms.users.create'], 'department' => 'Finance']
            ]),
            'http://auth-service:8000/api/admin/role-options' => Http::response([
                ['id' => 10, 'name' => 'Employee'],
                ['id' => 11, 'name' => 'Manager']
            ]),
            'http://auth-service:8000/api/admin/department-options' => Http::response([
                ['id' => 50, 'name' => 'Finance']
            ]),
            'http://auth-service:8000/api/admin/users' => Http::response([
                'message' => 'User created successfully.',
                'user' => ['email' => 'newuser@finance.com']
            ], 201)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer admin-token',
        ])->postJson('/api/admin/users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'newuser@finance.com',
            'role_name' => 'Employee',
            'department_name' => 'Finance'
        ]);

        $response->assertStatus(201);
    }

    public function test_create_user_wrong_department_blocked()
    {
        Http::fake([
            'http://auth-service:8000/api/internal/verify-token' => Http::response([
                'valid' => true,
                'user' => ['id' => 1, 'role' => 'Admin', 'permissions' => ['cms.users.create'], 'department' => 'Finance']
            ])
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer admin-token',
        ])->postJson('/api/admin/users', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@marketing.com',
            'role_name' => 'Employee',
            'department_name' => 'Marketing'
        ]);

        $response->assertStatus(403);
    }

    public function test_create_user_auth_service_unauthenticated_graceful_error()
    {
        Http::fake([
            'http://auth-service:8000/api/internal/verify-token' => Http::response([
                'valid' => true,
                'user' => ['id' => 1, 'role' => 'Admin', 'permissions' => ['cms.users.create'], 'department' => 'Finance']
            ]),
            'http://auth-service:8000/api/admin/role-options' => Http::response([
                'message' => 'Unauthenticated or session missing.'
            ], 401),
            'http://auth-service:8000/api/admin/department-options' => Http::response([
                'message' => 'Unauthenticated or session missing.'
            ], 401),
            'http://auth-service:8000/api/admin/users' => Http::response([
                'message' => 'Unauthenticated or session missing.'
            ], 401),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer admin-token',
        ])->postJson('/api/admin/users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'newuser@finance.com',
            'role_name' => 'Employee',
            'department_name' => 'Finance'
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment(['message' => 'Unauthenticated or session missing.']);
    }
}
