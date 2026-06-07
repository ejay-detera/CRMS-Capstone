<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\MeilisearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncContractToMeilisearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly array $contract) {}

    public function handle(MeilisearchService $meili): void
    {
        $meili->upsertContract($this->contract);
    }
}
