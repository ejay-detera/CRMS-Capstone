<?php

use App\Console\Commands\CheckExpiringContracts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily check: push expiry notifications for contracts expiring in 90 / 30 / 1 day(s)
Schedule::command(CheckExpiringContracts::class)->dailyAt('08:00');
