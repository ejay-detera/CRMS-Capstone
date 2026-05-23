<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.auth.url', env('AUTH_SERVICE_URL', 'http://auth-service:8000/api'));
    }

    /**
     * Verify a token with the auth-service.
     */
    public function verifyToken(string $token): ?array
    {
        $cacheKey = 'token_verify_' . hash('sha256', $token);

        $cached = Cache::store('file')->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'X-Internal-Service' => 'contract-management',
            ])->post("{$this->baseUrl}/internal/verify-token", [
                'token' => $token,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Only cache verified-valid tokens; failures are not cached so
                // transient auth-service errors don't block legitimate retries.
                if ($data['valid'] ?? false) {
                    Cache::store('file')->put($cacheKey, $data, 300);
                }
                return $data;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Auth service connection error in contract-management', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function createUser(array $data, string $token): ?array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'X-Session-ID' => request()->header('X-Session-ID') ?? '',
                'X-Internal-Service' => 'contract-management-admin',
            ])->post("{$this->baseUrl}/admin/users", $data);

            return $response->successful() ? $response->json() : [
                'error' => true, 
                'status' => $response->status(), 
                'message' => $response->json('message') ?? 'Failed to create user',
                'errors' => $response->json('errors') ?? []
            ];
        } catch (\Exception $e) {
            return ['error' => true, 'message' => 'Connection failed'];
        }
    }
}
