<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\AuditLog;

class AuditLogTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test webhook receiver with correct secret.
     */
    public function test_internal_webhook_receive_success()
    {
        config(['app.internal_service_secret' => 'crms-internal-secret-key-2026']);
        putenv('INTERNAL_SERVICE_SECRET=crms-internal-secret-key-2026');

        $response = $this->withHeaders([
            'X-Internal-Secret' => 'crms-internal-secret-key-2026'
        ])->postJson('/api/internal/audit-event', [
            'action' => 'login',
            'entity_type' => 'Session',
            'user_id' => 3,
            'new_data' => ['email' => 'finance-user@sbsi.com']
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('ok', true);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login',
            'entity_type' => 'Session',
            'user_id' => 3,
        ]);
    }

    /**
     * Test webhook receiver with unauthorized secret.
     */
    public function test_internal_webhook_unauthorized_secret()
    {
        config(['app.internal_service_secret' => 'crms-internal-secret-key-2026']);
        putenv('INTERNAL_SERVICE_SECRET=crms-internal-secret-key-2026');

        $response = $this->withHeaders([
            'X-Internal-Secret' => 'wrong-secret'
        ])->postJson('/api/internal/audit-event', [
            'action' => 'login',
            'entity_type' => 'Session',
            'user_id' => 3,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test log aggregator endpoint.
     */
    public function test_audit_log_aggregator_success()
    {
        // 1. Seed a local audit log
        AuditLog::create([
            'action' => 'created',
            'entity_type' => 'Contract',
            'entity_id' => 12,
            'user_id' => 5,
            'old_data' => null,
            'new_data' => ['title' => 'Important NDA'],
            'performed_at' => now(),
        ]);

        // 2. Mock external requests
        Http::fake([
            // Mock verify token for aggregator accessor (needs manage-users permission)
            'http://auth-service:8000/api/internal/verify-token' => Http::response([
                'valid' => true,
                'user' => [
                    'id' => 1,
                    'email' => 'admin@sbsi.com',
                    'role' => 'Admin',
                    'permissions' => ['manage-users'],
                    'department' => 'Finance'
                ]
            ], 200),
            // Mock auth users mapping API
            'http://auth-service:8000/api/admin/users?per_page=100' => Http::response([
                'data' => [
                    [
                        'id' => 5,
                        'email' => 'contractor@sbsi.com',
                        'profile' => [
                            'first_name' => 'Jane',
                            'last_name' => 'Doe',
                            'role' => ['name' => 'Manager']
                        ]
                    ]
                ]
            ], 200),
            // Mock remote session logs endpoint
            'http://auth-service:8000/api/internal/audit-logs' => Http::response([
                'logs' => [
                    [
                        'id' => 201,
                        'user_id' => 5,
                        'user_email' => 'contractor@sbsi.com',
                        'first_name' => 'Jane',
                        'last_name' => 'Doe',
                        'action' => 'login',
                        'description' => 'Logged in',
                        'ip_address' => '127.0.0.1',
                        'performed_at' => now()->toIso8601String(),
                    ]
                ]
            ], 200)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer admin-token',
            'Accept' => 'application/json'
        ])->getJson('/api/audit-logs');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data', 'total', 'current_page', 'per_page', 'last_page'
        ]);

        // Verify we merged local and remote logs successfully
        $data = $response->json()['data'];
        $this->assertCount(2, $data);

        // Check source markers
        $sources = collect($data)->pluck('source')->toArray();
        $this->assertContains('crms', $sources);
        $this->assertContains('auth', $sources);
    }
}
