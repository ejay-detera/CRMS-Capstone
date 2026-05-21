<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class InternalAuditController extends Controller
{
    /**
     * Receive an audit event from internal auth service and write it to CRMS audit log.
     */
    public function receive(Request $request)
    {
        $secret = $request->header('X-Internal-Secret');
        $expectedSecret = env('INTERNAL_SERVICE_SECRET');

        if (!$secret || $secret !== $expectedSecret) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->validate([
            'action' => 'required|string',
            'entity_type' => 'required|string',
            'entity_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'old_data' => 'nullable|array',
            'new_data' => 'nullable|array',
        ]);

        $log = AuditLog::create([
            'action' => $request->input('action'),
            'entity_type' => $request->input('entity_type'),
            'entity_id' => $request->input('entity_id') ?? 0,
            'user_id' => $request->input('user_id'),
            'old_data' => $request->input('old_data'),
            'new_data' => $request->input('new_data'),
            'performed_at' => now(),
        ]);

        return response()->json(['ok' => true, 'audit_id' => $log->audit_id]);
    }
}
