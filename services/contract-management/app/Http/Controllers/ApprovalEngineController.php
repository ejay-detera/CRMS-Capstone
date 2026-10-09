<?php

namespace App\Http\Controllers;

use App\Models\ApprovalTask;
use App\Models\Contract;
use App\Services\ApprovalEngineService;
use App\Services\AuthService;
use App\Services\RoleProxyService;
use Illuminate\Http\Request;

/**
 * Dynamic Approval Workflow Engine runtime endpoints (Phase 5). Entirely
 * additive to the existing contract approve/reject flow in
 * ContractController — these endpoints only ever return data for
 * contracts whose category has (or had) an active workflow when the
 * contract was created.
 */
class ApprovalEngineController extends Controller
{
    public function __construct(
        protected ApprovalEngineService $engine,
        protected RoleProxyService $roleProxy,
        protected AuthService $authService,
    ) {
    }

    /**
     * GET /contracts/{id}/approval-progress — the latest run's steps and
     * task statuses for a contract being routed through the engine.
     * Returns an empty payload (engine: false) for contracts on the
     * legacy flow, rather than a 404, so the frontend can render
     * accordingly either way.
     */
    public function progress(Request $request, $id)
    {
        $contract = Contract::where('contract_code', $id)->orWhere('contract_id', $id)->firstOrFail();

        $instances = \App\Models\ApprovalInstance::with(['tasks', 'workflow.steps.workflowStepRoles'])
            ->where('contract_id', $contract->contract_id)
            ->orderBy('run_number')
            ->get();

        if ($instances->isEmpty()) {
            return response()->json(['data' => ['engine' => false, 'runs' => []]]);
        }

        // Phase 6 live-data wiring: enrich every task once per response so
        // the Visual Tracker can render deleted/renamed-role badges and
        // delegation names without fan-out calls from the browser (which
        // must never hold the X-Internal-Secret). Batched per distinct
        // role/user, degrading to snapshot-only data if auth-module is
        // unreachable (describeRole()/getUsersBatch() already fall back to
        // empty on connection error — never fail the whole response).
        $roleIds = $instances->flatMap(fn ($i) => $i->tasks->pluck('auth_role_id'))
            ->unique()->values()->all();
        $roleMeta = [];
        foreach ($roleIds as $roleId) {
            $roleMeta[$roleId] = $this->roleProxy->describeRole((int) $roleId);
        }

        $delegateIds = $instances->flatMap(fn ($i) => $i->tasks)
            ->filter(fn ($t) => $t->acted_via_delegation && $t->acted_by_user_id)
            ->map(fn ($t) => (int) $t->acted_by_user_id)
            ->unique()->values()->all();
        $userNames = [];
        if (!empty($delegateIds)) {
            foreach ($this->authService->getUsersBatch($delegateIds) as $u) {
                $uid = (int) ($u['id'] ?? 0);
                if ($uid) {
                    $full = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                    $userNames[$uid] = $full !== '' ? $full : ($u['email'] ?? "User #{$uid}");
                }
            }
        }

        // Creator display for the tracker's synthetic "Contract created"
        // node (Phase 6 mocks always start with one; the engine itself
        // only stores workflow steps). Resolved in the same batch style.
        $creatorName = null;
        if ($contract->created_by) {
            foreach ($this->authService->getUsersBatch([(int) $contract->created_by]) as $u) {
                $full = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                $creatorName = $full !== '' ? $full : ($u['email'] ?? null);
            }
        }

        return response()->json([
            'data' => [
                'engine' => true,
                'contract_id' => $contract->contract_code ?? (string) $contract->contract_id,
                'contract_created_at' => $contract->created_at?->toIso8601String(),
                'contract_created_by' => $creatorName,
                'runs' => $instances->map(fn ($instance) => $this->formatRun($instance, $roleMeta, $userNames))->values(),
            ],
        ]);
    }

    /**
     * POST /contracts/{id}/approval-tasks/{taskId}/decision
     * body: { decision: 'approved'|'rejected', comment?: string }
     *
     * The acting user must currently hold the task's assigned role
     * (checked live against auth-module) — enforced in
     * ApprovalEngineService::decide(), not here.
     */
    public function decide(Request $request, $id, $taskId)
    {
        $request->validate([
            'decision' => 'required|string|in:approved,rejected',
            'comment'  => 'nullable|string',
        ]);

        $contract = Contract::where('contract_code', $id)->orWhere('contract_id', $id)->firstOrFail();

        $task = ApprovalTask::whereHas('instance', function ($q) use ($contract) {
            $q->where('contract_id', $contract->contract_id);
        })->findOrFail($taskId);

        $actingUserId = (int) $request->get('auth_id');

        $task = $this->engine->decide($task, $actingUserId, $request->input('decision'), $request->input('comment'));

        return response()->json(['data' => $this->formatTask($task)]);
    }

    private function formatRun($instance, array $roleMeta = [], array $userNames = []): array
    {
        $steps = $instance->workflow->steps->sortBy('order_index')->values();
        $tasksByStep = $instance->tasks->groupBy('step_id');

        return [
            'run_number' => $instance->run_number,
            'status'     => $instance->status,
            'current_step_id' => $instance->current_step_id,
            'groups'     => $steps->map(function ($step) use ($tasksByStep, $instance, $roleMeta, $userNames) {
                $tasks = $tasksByStep->get($step->id, collect());
                return [
                    'step_id' => $step->id,
                    'mode'    => $step->mode,
                    'tasks'   => $tasks->map(fn ($t) => $this->formatTask($t, $roleMeta, $userNames, $instance))->values(),
                ];
            })->values(),
        ];
    }

    private function formatTask(ApprovalTask $task, array $roleMeta = [], array $userNames = [], $instance = null): array
    {
        $stepRole = \App\Models\WorkflowStepRole::where('step_id', $task->step_id)
            ->where('auth_role_id', $task->auth_role_id)
            ->first();

        // Decision #14 display state, resolved server-side (Phase 6).
        // describeRole() shape (Phase 1): exists/deleted/current_name/
        // was_renamed/name_history. Deleted takes precedence over renamed.
        $describe = $roleMeta[$task->auth_role_id] ?? null;
        $deleted = (bool) ($describe['deleted'] ?? false);
        if (!$describe) {
            $deleted = $stepRole === null;
        }
        $renamedFrom = null;
        if (!$deleted && ($describe['was_renamed'] ?? false)) {
            $history = $describe['name_history'] ?? [];
            $last = end($history);
            $renamedFrom = is_array($last)
                ? ($last['old_name'] ?? $last['name'] ?? null)
                : (is_string($last) ? $last : null);
        }

        $delegateName = null;
        if ($task->acted_via_delegation && $task->acted_by_user_id) {
            $delegateName = $userNames[(int) $task->acted_by_user_id] ?? null;
        }

        // Explicit currency flag so the frontend never has to guess which
        // pending task is "Now" (Phase 6 decision #3).
        $isCurrent = $instance
            && in_array($instance->status, ['in_progress', 'revisions_required'], true)
            && $instance->current_step_id !== null
            && (int) $task->step_id === (int) $instance->current_step_id
            && in_array($task->status, ['pending', 'on_hold'], true);

        return [
            'id'                   => $task->id,
            'step_id'              => $task->step_id,
            'auth_role_id'         => $task->auth_role_id,
            'role_name'            => $stepRole?->role_name_snapshot,
            'role_deleted'         => $deleted,
            'role_renamed_from'    => $renamedFrom,
            'delegate_name'        => $delegateName,
            'is_current'           => (bool) $isCurrent,
            'status'               => $task->status,
            'acted_by_user_id'     => $task->acted_by_user_id,
            'acted_via_delegation' => $task->acted_via_delegation,
            'acted_at'             => $task->acted_at?->toIso8601String(),
            'comment'              => $task->comment,
        ];
    }
}
