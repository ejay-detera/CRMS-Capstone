<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Internal lookup of a contract's creator, used to enforce "Sales: view own
 * only" on the Risk Assessment endpoints (US-026's role matrix: Manager
 * decision / Sales view, own only).
 */
class ContractOwnershipService
{
    protected string $baseUrl;
    protected string $secret;

    public function __construct()
    {
        $this->baseUrl = env('CONTRACT_SERVICE_URL', 'http://contract-management:8000/api');
        $this->secret  = env('INTERNAL_SERVICE_SECRET', '');
    }

    public function getCreatedBy(int $contractId): ?int
    {
        try {
            $response = Http::withHeaders([
                'Accept'            => 'application/json',
                'X-Internal-Secret' => $this->secret,
            ])->get("{$this->baseUrl}/internal/contracts/{$contractId}/owner");

            return $response->successful() ? $response->json('created_by') : null;
        } catch (\Exception $e) {
            Log::error('ContractOwnershipService connection error', [
                'contract_id' => $contractId,
                'message'     => $e->getMessage(),
            ]);
            return null;
        }
    }
}
