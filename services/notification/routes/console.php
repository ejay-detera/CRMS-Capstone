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
