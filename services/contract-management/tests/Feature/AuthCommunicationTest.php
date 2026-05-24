<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthCommunicationTest extends TestCase
{
    public function test_it_communicates_with_auth_service_and_enforces_contract_permissions()
    {
        \Illuminate\Support\Facades\Cache::store('file')->flush();
        // Mock the Auth Service
        Http::fake([
            'http://auth-service:8000/api/internal/verify-token' => Http::response([
                'valid' => true,
                'user' => [
                    'id' => 1,
                    'email' => 'manager@example.com',
                    'role' => 'Manager',
                    'permissions' => ['view-contracts']
                ]
            ], 200)
        ]);

        // Access protected contracts route
        $response = $this->withHeaders([
            'Authorization' => 'Bearer manager-token',
            'Accept' => 'application/json',
        ])->getJson('/api/contracts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'contract_id',
                'businessPartner',
                'category',
                'status',
                'itemCode',
                'description',
                'serialNo',
                'startDate',
                'endDate',
                'createdBy',
            ]
        ]);
        
        Http::assertSent(function ($request) {
            return $request->url() === 'http://auth-service:8000/api/internal/verify-token';
        });
    }

    public function test_it_denies_contract_access_without_permission()
    {
        Http::fake([
            'http://auth-service:8000/api/internal/verify-token' => Http::response([
                'valid' => true,
                'user' => [
                    'id' => 2,
                    'email' => 'sales@example.com',
                    'role' => 'Sales',
                    'permissions' => [] // No permissions
                ]
            ], 200)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer sales-token',
            'Accept' => 'application/json',
        ])->getJson('/api/contracts');

        $response->assertStatus(403);
    }
}
