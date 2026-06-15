<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class NotifyPrsOfContractUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $prsActivityId,
        private readonly int $contractId,
        private readonly string $contractStatus,
        private readonly string $outcome
    ) {}

    public function handle(): void
    {
        $url = env('PRS_WEBHOOK_URL', 'http://prs_api:8000/api/integrations/cms/contract-outcome');
        $apiKey = env('PRS_INTEGRATION_KEY', 'cms-prs-local-service-key');

        try {
            $response = Http::timeout(5)
                ->withToken($apiKey)
                ->acceptJson()
                ->post($url, [
                    'prs_activity_id' => $this->prsActivityId,
                    'contract_id' => $this->contractId,
                    'contract_status' => $this->contractStatus,
                    'outcome' => $this->outcome,
                ]);

            if ($response->failed()) {
                Log::error("Failed to notify PRS of contract update. Status code: " . $response->status() . " Body: " . $response->body());
                throw new \Exception("PRS returned failed status code: " . $response->status());
            }
        } catch (\Exception $e) {
            Log::error("Error in NotifyPrsOfContractUpdateJob: " . $e->getMessage());
            throw $e;
        }
    }
}
