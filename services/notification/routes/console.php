<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail:test {email}', function (string $email) {
    $this->info("Sending test email to {$email}...");
    
    try {
        \Illuminate\Support\Facades\Mail::raw('This is a test email sent from CRMS Capstone using Brevo!', function ($message) use ($email) {
            $message->to($email)
                    ->subject('CRMS Brevo SMTP Test');
        });
        $this->info("Test email successfully dispatched!");
    } catch (\Exception $e) {
        $this->error("Failed to send email: " . $e->getMessage());
    }
})->purpose('Send a test email using configured SMTP credentials');

Artisan::command('notifications:queue-past-emails', function () {
    $this->info("Checking for past notifications without sent email logs...");

    $notifications = \App\Models\Notification::all();
    $dispatchedCount = 0;

    foreach ($notifications as $notification) {
        // We only skip if there is an email log with a status of 'sent' or 'skipped'
        $logExists = \App\Models\EmailSendLog::where('notification_id', $notification->notification_id)
            ->whereIn('status', ['sent', 'skipped'])
            ->exists();

        if (!$logExists) {
            $this->info("Dispatching email for notification ID: {$notification->notification_id} (Type: {$notification->notification_type})");

            if ($notification->target_user_id !== null) {
                \App\Jobs\SendContractExpiryEmail::dispatch(
                    (int) $notification->target_user_id,
                    (int) $notification->notification_id,
                    $notification->contract_id ? (int) $notification->contract_id : null,
                    $notification->message,
                    $notification->notification_type
                );
            } else {
                \App\Jobs\SendBroadcastExpiryEmails::dispatch(
                    $notification->target_roles,
                    (int) $notification->notification_id,
                    $notification->contract_id ? (int) $notification->contract_id : null,
                    $notification->message,
                    $notification->notification_type
                );
            }
            $dispatchedCount++;
        }
    }

    $this->info("Successfully dispatched {$dispatchedCount} email jobs for past notifications.");
})->purpose('Queue emails for past notifications that have not been sent yet');
