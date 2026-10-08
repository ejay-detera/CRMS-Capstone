<?php

namespace App\Http\Controllers;

use App\Models\ApprovalTask;
use App\Models\Contract;
use App\Services\ApprovalEngineService;
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
    public function __construct(protected ApprovalEngineService $engine)
    {
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

        return response()->json([
            'data' => [
                'engine' => true,
                'contract_id' => $contract->contract_code ?? (string) $contract->contract_id,
                'runs' => $instances->map(fn ($instance) => $this->formatRun($instance))->values(),
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

    private function formatRun($instance): array
    {
        $steps = $instance->workflow->steps->sortBy('order_index')->values();
        $tasksByStep = $instance->tasks->groupBy('step_id');

        return [
            'run_number' => $instance->run_number,
            'status'     => $instance->status,
            'groups'     => $steps->map(function ($step) use ($tasksByStep) {
                $tasks = $tasksByStep->get($step->id, collect());
                return [
                    'step_id' => $step->id,
                    'mode'    => $step->mode,
                    'tasks'   => $tasks->map(fn ($t) => $this->formatTask($t))->values(),
                ];
            })->values(),
        ];
    }

    private function formatTask(ApprovalTask $task): array
    {
        $stepRole = \App\Models\WorkflowStepRole::where('step_id', $task->step_id)
            ->where('auth_role_id', $task->auth_role_id)
            ->first();

        return [
            'id'                   => $task->id,
            'step_id'              => $task->step_id,
            'auth_role_id'         => $task->auth_role_id,
            'role_name'            => $stepRole?->role_name_snapshot,
            'status'               => $task->status,
            'acted_by_user_id'     => $task->acted_by_user_id,
            'acted_via_delegation' => $task->acted_via_delegation,
            'acted_at'             => $task->acted_at?->toIso8601String(),
            'comment'              => $task->comment,
        ];
    }
}
