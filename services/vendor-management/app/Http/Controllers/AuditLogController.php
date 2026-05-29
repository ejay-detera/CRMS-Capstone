<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class AuditLogController extends Controller
{
    /**
     * Display a listing of aggregated audit logs from CRMS and Auth services.
     */
    public function index(Request $request)
    {
        $token = $request->bearerToken();
        
        // 1. Fetch user profiles from auth service to map user IDs to names, emails, and roles
        $userMap = [];
        $emailMap = [];
        $roleMap = [];
        try {
            $usersResponse = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'X-Internal-Service' => 'vendor-management',
            ])->timeout(3)->get(env('AUTH_SERVICE_URL', 'http://auth-service:8000/api') . '/admin/users', [
                'per_page' => 100
            ]);

            if ($usersResponse->successful() && isset($usersResponse->json()['data'])) {
                foreach ($usersResponse->json()['data'] as $u) {
                    $firstName = isset($u['profile']['first_name']) ? $u['profile']['first_name'] : '';
                    $lastName = isset($u['profile']['last_name']) ? $u['profile']['last_name'] : '';
                    $fullName = trim("{$firstName} {$lastName}");
                    $userId = $u['id'];
                    $userMap[$userId] = !empty($fullName) ? $fullName : ($u['email'] ?? 'Finance User');
                    $emailMap[$userId] = $u['email'] ?? '';
                    $roleMap[$userId] = isset($u['profile']['role']['name']) ? $u['profile']['role']['name'] : 'Finance';
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to fetch user list for audit log mapping: ' . $e->getMessage());
        }

        // 2. Fetch local CRMS logs
        $crmsLogsQuery = AuditLog::query()->where('user_department', 'Finance');
        
        if ($request->has('action') && !empty($request->action)) {
            $crmsLogsQuery->where('action', $request->action);
        }
        if ($request->has('date') && !empty($request->date)) {
            $crmsLogsQuery->whereDate('performed_at', $request->date);
        }

        $crmsLogs = $crmsLogsQuery->orderBy('performed_at', 'desc')->limit(150)->get();

        // 3. Remote Auth logs remote fetch disabled (login/logout events are pushed directly to CRMS)
        $authLogs = [];

        // 4. Merge and normalize both collections
        $merged = [];

        // Process CRMS logs
        foreach ($crmsLogs as $log) {
            $userName = $log->user_name ?? ($userMap[$log->user_id] ?? 'Finance User');
            $userEmail = $log->user_email ?? ($emailMap[$log->user_id] ?? '');
            $userRole = $log->user_role ?? ($roleMap[$log->user_id] ?? 'Finance');

            // Clean action names for readability if needed, but keep original for code logic
            $description = '';
            if ($log->action === 'created') {
                $description = "Created {$log->entity_type} #{$log->entity_id}";
            } elseif ($log->action === 'updated') {
                $description = "Updated {$log->entity_type} #{$log->entity_id}";
            } elseif ($log->action === 'deleted') {
                $description = "Deleted {$log->entity_type} #{$log->entity_id}";
            } elseif ($log->action === 'user_created') {
                $new_data = is_array($log->new_data) ? $log->new_data : [];
                $email = $new_data['email'] ?? '';
                $description = "Created User account for {$email}";
            } else {
                $description = ucfirst($log->action) . " {$log->entity_type}";
            }

            $performedAtStr = $log->performed_at 
                ? ($log->performed_at instanceof Carbon ? $log->performed_at->toIso8601String() : Carbon::parse($log->performed_at)->toIso8601String())
                : now()->toIso8601String();

            $merged[] = [
                'id' => 'crms-' . $log->audit_id,
                'source' => 'crms',
                'user_id' => $log->user_id,
                'user_name' => $userName,
                'user_email' => $userEmail,
                'role' => $userRole,
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'description' => $description,
                'old_data' => $log->old_data,
                'new_data' => $log->new_data,
                'performed_at' => $performedAtStr,
            ];
        }

        // Search filter on merged logs
        if ($request->has('search') && !empty($request->search)) {
            $search = strtolower($request->search);
            $merged = array_filter($merged, function ($item) use ($search) {
                return str_contains(strtolower($item['user_name'] ?? ''), $search) ||
                       str_contains(strtolower($item['user_email'] ?? ''), $search) ||
                       str_contains(strtolower($item['description'] ?? ''), $search) ||
                       str_contains(strtolower($item['action'] ?? ''), $search) ||
                       str_contains(strtolower($item['role'] ?? ''), $search);
            });
            $merged = array_values($merged);
        }

        // 5. Sort by performed_at descending
        usort($merged, function ($a, $b) {
            return strcmp($b['performed_at'], $a['performed_at']);
        });

        // 6. Paginate in-memory
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 20);
        $total = count($merged);

        $offset = ($page - 1) * $perPage;
        $items = array_slice($merged, $offset, $perPage);

        return response()->json([
            'data' => $items,
            'total' => $total,
            'current_page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($total / $perPage)
        ]);
    }
}
