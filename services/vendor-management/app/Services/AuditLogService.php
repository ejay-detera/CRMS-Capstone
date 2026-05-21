<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogService
{
    /**
     * Log an action to the audit logs.
     */
    public function log(string $action, string $entityType, int $entityId, ?int $userId, array $old = [], array $new = []): void
    {
        $sensitiveFields = ['contact_number', 'email', 'address'];
        
        $oldClean = $this->cleanData($old, $sensitiveFields);
        $newClean = $this->cleanData($new, $sensitiveFields);

        AuditLog::create([
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $userId,
            'old_data' => !empty($oldClean) ? $oldClean : null,
            'new_data' => !empty($newClean) ? $newClean : null,
            'performed_at' => now(),
        ]);
    }

    /**
     * Clean sensitive fields from data array by replacing them with [REDACTED].
     */
    protected function cleanData(array $data, array $sensitiveFields): array
    {
        foreach ($sensitiveFields as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = '[REDACTED]';
            }
        }
        return $data;
    }
}
