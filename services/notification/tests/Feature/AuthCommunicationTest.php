<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthCommunicationTest extends TestCase
{
    public function test_it_communicates_with_auth_service_for_notifications()
    {
        Http::fake([
            'http://auth-service:8000/api/internal/verify-token' => Http::response([
                'valid' => true,
                'user' => [
                    'id' => 1,
                    'email' => 'user@example.com',
                    'role' => 'Sales',
                    'permissions' => []
                ]
            ], 200)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer user-token',
            'Accept' => 'application/json',
        ])->getJson('/api/notifications');

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Notifications retrieved');
    }

    public function test_it_denies_notifications_with_invalid_token()
    {
        Http::fake([
            'http://auth-service:8000/api/internal/verify-token' => Http::response([
                'valid' => false
            ], 401)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer bad-token',
            'Accept' => 'application/json',
        ])->getJson('/api/notifications');

        $response->assertStatus(401);
    }
}
