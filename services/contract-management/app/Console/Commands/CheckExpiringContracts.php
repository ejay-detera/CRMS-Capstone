<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckExpiringContracts extends Command
{
    protected $signature   = 'contracts:check-expiry';
    protected $description = 'Push expiry notifications for contracts expiring in 90, 30, or 1 day(s).';

    public function handle(NotificationService $notif): int
    {
        $today = Carbon::today();

        $thresholds = [
            90 => ['type' => 'expiry_90', 'label' => '3 months'],
            30 => ['type' => 'expiry_30', 'label' => '1 month'],
            1  => ['type' => 'expiry_1',  'label' => 'tomorrow'],
        ];

        $pushed = 0;

        foreach ($thresholds as $days => $meta) {
            $targetDate = $today->copy()->addDays($days);

            $contracts = Contract::whereDate('end_date', '>=', $today)
                ->whereDate('end_date', '<=', $targetDate)
                ->get();

            foreach ($contracts as $contract) {
                $date     = $contract->end_date->format('M d, Y');
                $serial   = $contract->serial_number ?? "Contract #{$contract->contract_id}";
                $daysLeft = (int) $today->diffInDays($contract->end_date);

                $message = $daysLeft <= 1
                    ? "Contract {$serial} is expiring tomorrow on {$date}."
                    : "Contract {$serial} is expiring in {$daysLeft} day(s) on {$date}.";

                $ok = $notif->push($contract->contract_id, $meta['type'], $message);

                if ($ok) {
                    $pushed++;
                    $this->line("  Sent [{$meta['type']}] for contract {$serial} (new or existing)");
                }
            }
        }

        $this->info("Done. {$pushed} notification(s) pushed.");
        return Command::SUCCESS;
    }
}
