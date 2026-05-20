<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminUserProxyTest extends TestCase
{
    public function test_create_user_finance_employee_success()
    {
        Http::fake([
            'http://auth-service:8000/api/internal/verify-token' => Http::response([
                'valid' => true,
                'user' => ['id' => 1, 'role' => 'Admin', 'permissions' => ['manage-users']]
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
                'user' => ['id' => 1, 'role' => 'Admin', 'permissions' => ['manage-users']]
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
}
