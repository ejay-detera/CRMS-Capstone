<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected string $baseUrl;
    protected string $secret;

    public function __construct()
    {
        $this->baseUrl = env('NOTIFICATION_SERVICE_URL', 'http://notification:8000/api');
        $this->secret  = env('INTERNAL_SERVICE_SECRET', '');
    }

    public function push(int $contractId, string $type, string $message): bool
    {
        try {
            $response = Http::withHeaders([
                'Accept'            => 'application/json',
                'X-Internal-Secret' => $this->secret,
            ])->post("{$this->baseUrl}/internal/push", [
                'contract_id'       => $contractId,
                'notification_type' => $type,
                'target_roles'      => 'Admin,Manager,Sales',
                'message'           => $message,
            ]);

            if (!$response->successful()) {
                Log::warning('Notification push failed', [
                    'contract_id' => $contractId,
                    'type'        => $type,
                    'status'      => $response->status(),
                    'body'        => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Notification service connection error', [
                'contract_id' => $contractId,
                'type'        => $type,
                'message'     => $e->getMessage(),
            ]);
            return false;
        }
    }
}
