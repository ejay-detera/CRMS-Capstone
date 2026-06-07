<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\ContractExpiryMail;
use App\Models\EmailPreference;
use App\Models\EmailSendLog;
use App\Services\AuthService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

final class SendContractExpiryEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public int $notificationId,
        public ?int $contractId,
        public string $messageText,
        public string $notificationType
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AuthService $authService): void
    {
        $userInfo = $authService->getUserInfo($this->userId);

        if (!$userInfo || empty($userInfo['email'])) {
            Log::warning("Cannot send contract expiry email. User info not found or email empty.", [
                'user_id' => $this->userId,
                'notification_id' => $this->notificationId,
            ]);
            return;
        }

        $recipientEmail = $userInfo['email'];
        $recipientName = trim(($userInfo['first_name'] ?? '') . ' ' . ($userInfo['last_name'] ?? ''));
        if (empty($recipientName)) {
            $recipientName = 'User';
        }

        // Avoid sending duplicate emails for the same contract and threshold type to this user
        $alreadySent = EmailSendLog::where('user_id', $this->userId)
            ->whereIn('status', ['sent', 'skipped'])
            ->whereHas('notification', function ($query) {
                $query->where('contract_id', $this->contractId)
                      ->where('notification_type', $this->notificationType);
            })
            ->exists();

        if ($alreadySent) {
            Log::info("Email already sent or skipped for user {$this->userId} for contract {$this->contractId} (Type: {$this->notificationType}). Skipping email send.");
            EmailSendLog::create([
                'notification_id' => $this->notificationId,
                'user_id' => $this->userId,
                'recipient_email' => $recipientEmail,
                'subject' => $this->getSubject(),
                'status' => 'skipped',
                'error_message' => 'Email already sent or skipped for this threshold.',
                'sent_at' => null,
            ]);
            return;
        }

        // Check preferences
        $pref = EmailPreference::where('user_id', $this->userId)->first();
        if ($pref) {
            if (!$pref->email_notifications_enabled) {
                EmailSendLog::create([
                    'notification_id' => $this->notificationId,
                    'user_id' => $this->userId,
                    'recipient_email' => $recipientEmail,
                    'subject' => $this->getSubject(),
                    'status' => 'skipped',
                    'error_message' => 'User opted out of email notifications.',
                    'sent_at' => null,
                ]);
                return;
            }

            if (!$pref->contract_expiry_alerts && str_starts_with($this->notificationType, 'expiry_')) {
                EmailSendLog::create([
                    'notification_id' => $this->notificationId,
                    'user_id' => $this->userId,
                    'recipient_email' => $recipientEmail,
                    'subject' => $this->getSubject(),
                    'status' => 'skipped',
                    'error_message' => 'User opted out of contract expiry alerts.',
                    'sent_at' => null,
                ]);
                return;
            }
        }

        try {
            $mailable = new ContractExpiryMail(
                $recipientName,
                $this->messageText,
                $this->notificationType,
                $this->contractId,
                $userInfo['role'] ?? null
            );

            Mail::to($recipientEmail)->send($mailable);

            EmailSendLog::create([
                'notification_id' => $this->notificationId,
                'user_id' => $this->userId,
                'recipient_email' => $recipientEmail,
                'subject' => $this->getSubject(),
                'status' => 'sent',
                'error_message' => null,
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send contract expiry email via Brevo", [
                'user_id' => $this->userId,
                'notification_id' => $this->notificationId,
                'error' => $e->getMessage(),
            ]);

            EmailSendLog::create([
                'notification_id' => $this->notificationId,
                'user_id' => $this->userId,
                'recipient_email' => $recipientEmail,
                'subject' => $this->getSubject(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'sent_at' => null,
            ]);
        }
    }

    /**
     * Get subject line helper.
     */
    private function getSubject(): string
    {
        return match ($this->notificationType) {
            'expiry_1' => '⚠️ URGENT: Contract Expiring Tomorrow',
            'expiry_30' => '📅 REMINDER: Contract Expiring in Less than 30 Days',
            'expiry_90' => 'ℹ️ NOTICE: Contract Expiring in 90 Days',
            default => '🔔 CRMS Contract Expiry Notice',
        };
    }
}
