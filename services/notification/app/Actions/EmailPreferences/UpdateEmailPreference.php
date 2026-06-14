<?php

declare(strict_types=1);

namespace App\Actions\EmailPreferences;

use App\Models\EmailPreference;
use App\Payloads\EmailPreferences\UpdateEmailPreferencePayload;

final readonly class UpdateEmailPreference
{
    /**
     * Update or create the email preference for the given user.
     */
    public function handle(int $userId, UpdateEmailPreferencePayload $payload): EmailPreference
    {
        return EmailPreference::updateOrCreate(
            ['user_id' => $userId],
            [
                'email_notifications_enabled' => $payload->emailNotificationsEnabled,
                'contract_expiry_alerts' => $payload->contractExpiryAlerts,
                'system_alerts_enabled' => $payload->systemAlertsEnabled,
                'sms_notifications_enabled' => $payload->smsNotificationsEnabled,
                'login_alerts_enabled' => $payload->loginAlertsEnabled,
            ]
        );
    }
}
