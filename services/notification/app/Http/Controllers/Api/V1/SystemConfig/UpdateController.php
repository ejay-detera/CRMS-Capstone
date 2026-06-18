<?php

namespace App\Http\Controllers\Api\V1\SystemConfig;

use App\Http\Controllers\Controller;
use App\Models\SystemConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_notifs_enabled' => 'nullable|boolean',
            'in_app_notifs_enabled' => 'nullable|boolean',
            'contract_expiry_alerts' => 'nullable|boolean',
            'approval_alerts' => 'nullable|boolean',
            'renewal_reminders' => 'nullable|boolean',
        ]);

        $config = SystemConfiguration::first();
        if (!$config) {
            $config = new SystemConfiguration();
        }

        $config->fill($validated);
        $config->save();

        return response()->json([
            'message' => 'System configuration updated successfully.',
            'data' => $config,
        ]);
    }
}
