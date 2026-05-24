<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractCategory;
use App\Models\ContractStatus;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContractController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Contract::with(['category', 'status']);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('bp_name', 'like', $search . '%')
                  ->orWhere('item_code', 'like', $search . '%');
            });
        }

        $contracts = $query->orderBy('contract_id', 'desc')->get();

        $mapped = $contracts->map(function($c) {
            return [
                'id' => 'CTR-' . str_pad($c->contract_id, 3, '0', STR_PAD_LEFT),
                'contract_id' => $c->contract_id,
                'businessPartner' => $c->bp_name,
                'category' => $c->category?->category_name ?? 'Service Agreement',
                'status' => $c->status?->status_name ?? 'Notarized PDF',
                'itemCode' => $c->item_code,
                'description' => $c->description,
                'serialNo' => $c->serial_number,
                'startDate' => $c->start_date ? $c->start_date->format('Y-m-d') : null,
                'endDate' => $c->end_date ? $c->end_date->format('Y-m-d') : null,
                'createdBy' => $c->created_by ? 'User #' . $c->created_by : 'Admin User',
            ];
        });

        return response()->json($mapped);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'businessPartner' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'itemCode' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'serialNo' => 'nullable|string|max:255',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date',
            'document_ids' => 'nullable|array',
            'document_ids.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $incoming = $validator->validated();

        // Resolve Category and Status IDs from strings
        $cat = ContractCategory::firstOrCreate(['category_name' => $incoming['category']]);
        $stat = ContractStatus::firstOrCreate(['status_name' => $incoming['status']]);
        $pendingStatus = \App\Models\ContractApprovalStatus::firstOrCreate(['status_name' => 'Pending']);

        $userId = $request->get('auth_id');

        $contract = Contract::create([
            'category_id' => $cat->category_id,
            'approval_status_id' => $pendingStatus->approval_status_id,
            'workflow_status_id' => $stat->status_id,
            'bp_name' => $incoming['businessPartner'],
            'item_code' => $incoming['itemCode'] ?? null,
            'description' => $incoming['description'] ?? null,
            'serial_number' => $incoming['serialNo'] ?? null,
            'start_date' => $incoming['startDate'] ?? null,
            'end_date' => $incoming['endDate'] ?? null,
            'created_by' => $userId,
        ]);

        // Link MongoDB documents
        if ($request->has('document_ids') && is_array($request->input('document_ids'))) {
            \App\Models\Document::whereIn('_id', $request->input('document_ids'))
                ->update(['contract_id' => $contract->contract_id]);
        }

        // Audit Logging
        $this->auditLogService->log(
            'created',
            'Contract',
            $contract->contract_id,
            $userId,
            [],
            $contract->toArray(),
            $request->get('auth_department')
        );

        // Map back to format expected by frontend
        $mapped = [
            'id' => 'CTR-' . str_pad($contract->contract_id, 3, '0', STR_PAD_LEFT),
            'contract_id' => $contract->contract_id,
            'businessPartner' => $contract->bp_name,
            'category' => $incoming['category'],
            'status' => $incoming['status'],
            'itemCode' => $contract->item_code,
            'description' => $contract->description,
            'serialNo' => $contract->serial_number,
            'startDate' => $contract->start_date ? $contract->start_date->format('Y-m-d') : null,
            'endDate' => $contract->end_date ? $contract->end_date->format('Y-m-d') : null,
            'createdBy' => $userId ? 'User #' . $userId : 'Admin User',
        ];

        return response()->json([
            'message' => 'Contract created successfully.',
            'data' => $mapped
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $contract = Contract::find($id);

        if (!$contract) {
            return response()->json(['message' => 'Contract not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'businessPartner' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'itemCode' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'serialNo' => 'nullable|string|max:255',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date',
            'document_ids' => 'nullable|array',
            'document_ids.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $incoming = $validator->validated();

        $cat = ContractCategory::firstOrCreate(['category_name' => $incoming['category']]);
        $stat = ContractStatus::firstOrCreate(['status_name' => $incoming['status']]);

        $oldData = $contract->toArray();

        $contract->update([
            'category_id' => $cat->category_id,
            'workflow_status_id' => $stat->status_id,
            'bp_name' => $incoming['businessPartner'],
            'item_code' => $incoming['itemCode'] ?? null,
            'description' => $incoming['description'] ?? null,
            'serial_number' => $incoming['serialNo'] ?? null,
            'start_date' => $incoming['startDate'] ?? null,
            'end_date' => $incoming['endDate'] ?? null,
        ]);

        // Link MongoDB documents
        if ($request->has('document_ids') && is_array($request->input('document_ids'))) {
            \App\Models\Document::whereIn('_id', $request->input('document_ids'))
                ->update(['contract_id' => $contract->contract_id]);
        }

        // Audit Logging
        $userId = $request->get('auth_id');
        $this->auditLogService->log(
            'updated',
            'Contract',
            $contract->contract_id,
            $userId,
            $oldData,
            $contract->toArray(),
            $request->get('auth_department')
        );

        $mapped = [
            'id' => 'CTR-' . str_pad($contract->contract_id, 3, '0', STR_PAD_LEFT),
            'contract_id' => $contract->contract_id,
            'businessPartner' => $contract->bp_name,
            'category' => $incoming['category'],
            'status' => $incoming['status'],
            'itemCode' => $contract->item_code,
            'description' => $contract->description,
            'serialNo' => $contract->serial_number,
            'startDate' => $contract->start_date ? $contract->start_date->format('Y-m-d') : null,
            'endDate' => $contract->end_date ? $contract->end_date->format('Y-m-d') : null,
            'createdBy' => $contract->created_by ? 'User #' . $contract->created_by : 'Admin User',
        ];

        return response()->json([
            'message' => 'Contract updated successfully.',
            'data' => $mapped
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $contract = Contract::find($id);

        if (!$contract) {
            return response()->json(['message' => 'Contract not found.'], 404);
        }

        $oldData = $contract->toArray();
        $contract->delete();

        // Audit Logging
        $userId = $request->get('auth_id');
        $this->auditLogService->log(
            'deleted',
            'Contract',
            $id,
            $userId,
            $oldData,
            [],
            $request->get('auth_department')
        );

        return response()->json(['message' => 'Contract deleted successfully.']);
    }

    public function dashboardSummary()
    {
        return response()->json(['message' => 'Not implemented']);
    }

    public function indexRequests()
    {
        return response()->json(['message' => 'Not implemented']);
    }

    public function show($id)
    {
        return response()->json(['message' => 'Not implemented']);
    }

    public function updateStatus(Request $request, $id)
    {
        return response()->json(['message' => 'Not implemented']);
    }
}
