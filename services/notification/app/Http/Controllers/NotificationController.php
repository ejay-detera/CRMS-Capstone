<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * POST /internal/push — called by other services (contract-management scheduler).
     * Protected by X-Internal-Secret middleware.
     */
    public function push(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id'       => 'nullable|integer',
            'notification_type' => 'required|string|max:20',
            'target_roles'      => 'required|string|max:255',
            'message'           => 'required|string',
            'target_user_id'    => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $notification = Notification::where('contract_id', $data['contract_id'] ?? null)
            ->where('notification_type', $data['notification_type'])
            ->where('target_user_id', $data['target_user_id'] ?? null)
            ->first();

        if ($notification) {
            $notification->wasRecentlyCreated = false;

            // If the last notification was sent 7 or more days ago, update it to act as a weekly reminder
            if ($notification->notification_date && now()->diffInDays($notification->notification_date) >= 7) {
                $notification->update([
                    'notification_date' => now(),
                    'is_read'           => false,
                ]);

                // Delete reads records to reset unread state for all users
                NotificationRead::where('notification_id', $notification->notification_id)->delete();
            }
        } else {
            $notification = Notification::create([
                'contract_id'       => $data['contract_id'] ?? null,
                'notification_type' => $data['notification_type'],
                'target_user_id'    => $data['target_user_id'] ?? null,
                'target_roles'      => $data['target_roles'],
                'message'           => $data['message'],
                'notification_date' => now(),
                'is_read'           => false,
            ]);
            $notification->wasRecentlyCreated = true;
        }

        if ($notification->wasRecentlyCreated && str_starts_with($notification->notification_type, 'expiry_')) {
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
        }

        $status = $notification->wasRecentlyCreated ? 201 : 200;
        return response()->json(['data' => $notification], $status);
    }

    /**
     * GET /notifications — returns notifications for the authenticated user's role.
     */
    public function index(Request $request)
    {
        $userId   = $request->input('auth_id');
        $userRole = $request->input('auth_role');

        $preference = \App\Models\EmailPreference::where('user_id', $userId)->first();
        $systemAlertsEnabled = $preference ? (bool) $preference->system_alerts_enabled : true;

        $sysConfig = \App\Models\SystemConfiguration::first();
        if ($sysConfig && !$sysConfig->in_app_notifs_enabled) {
            return response()->json(['data' => []]);
        }

        if (!$systemAlertsEnabled) {
            return response()->json(['data' => []]);
        }

        // Get notifications targeting this user's role; for user-scoped ones also match auth_id
        $notifications = Notification::orderByDesc('notification_date')->get()
            ->filter(function (Notification $n) use ($userRole, $userId) {
                if (!$n->target_roles) return false;
                $roles = array_map('trim', explode(',', $n->target_roles));
                if (!in_array($userRole, $roles, true)) return false;
                if ($n->target_user_id !== null && (int) $n->target_user_id !== (int) $userId) return false;
                return true;
            })
            ->values();

        // Load per-user read state
        $notifIds = $notifications->pluck('notification_id')->all();
        $reads = NotificationRead::where('user_id', $userId)
            ->whereIn('notification_id', $notifIds)
            ->get()
            ->keyBy('notification_id');

        $result = $notifications->map(function (Notification $n) use ($reads) {
            $read = $reads->get($n->notification_id);
            return [
                'id'                => (string) $n->notification_id,
                'contract_id'       => $n->contract_id,
                'notification_type' => $n->notification_type,
                'message'           => $n->message,
                'notification_date' => $n->notification_date?->toISOString(),
                'is_read'           => $read?->is_read ?? false,
                'is_archived'       => $read?->is_archived ?? false,
                'is_favorite'       => $read?->is_favorite ?? false,
            ];
        });

        return response()->json(['data' => $result]);
    }

    /**
     * PATCH /notifications/{id}/read — mark one notification as read.
     */
    public function markRead(Request $request, $id)
    {
        $userId = $request->input('auth_id');

        $notification = Notification::find($id);
        if (!$notification) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        NotificationRead::updateOrCreate(
            ['notification_id' => $id, 'user_id' => $userId],
            ['is_read' => true]
        );

        return response()->json(['message' => 'Marked as read.']);
    }

    /**
     * PATCH /notifications/read-all — mark all unread notifications as read for the user.
     */
    public function markAllRead(Request $request)
    {
        $userId   = $request->input('auth_id');
        $userRole = $request->input('auth_role');

        $notifIds = Notification::orderByDesc('notification_date')->get()
            ->filter(function (Notification $n) use ($userRole, $userId) {
                if (!$n->target_roles) return false;
                $roles = array_map('trim', explode(',', $n->target_roles));
                if (!in_array($userRole, $roles, true)) return false;
                if ($n->target_user_id !== null && (int) $n->target_user_id !== (int) $userId) return false;
                return true;
            })
            ->pluck('notification_id')
            ->all();

        foreach ($notifIds as $notifId) {
            NotificationRead::updateOrCreate(
                ['notification_id' => $notifId, 'user_id' => $userId],
                ['is_read' => true]
            );
        }

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /**
     * PATCH /notifications/{id}/state — toggle archive or favorite.
     */
    public function updateState(Request $request, $id)
    {
        $userId = $request->input('auth_id');

        $notification = Notification::find($id);
        if (!$notification) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'is_archived' => 'sometimes|boolean',
            'is_favorite' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $read = NotificationRead::firstOrNew(
            ['notification_id' => $id, 'user_id' => $userId]
        );

        if ($request->has('is_archived')) $read->is_archived = $request->boolean('is_archived');
        if ($request->has('is_favorite')) $read->is_favorite = $request->boolean('is_favorite');
        $read->save();

        return response()->json(['message' => 'State updated.']);
    }
}
