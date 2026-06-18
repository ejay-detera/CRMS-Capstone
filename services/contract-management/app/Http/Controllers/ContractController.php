<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractCategory;
use App\Models\ContractStatus;
use App\Models\ContractApprovalStatus;
use App\Models\ContractRegion;
use App\Services\AuditLogService;
use App\Jobs\SyncContractToMeilisearch;
use App\Jobs\RemoveContractFromMeilisearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ContractController extends Controller
{
    protected AuditLogService $auditLogService;
    protected \App\Services\AuthService $authService;

    public function __construct(AuditLogService $auditLogService, \App\Services\AuthService $authService)
    {
        $this->auditLogService = $auditLogService;
        $this->authService = $authService;
    }

    private function formatContract(Contract $contract): array
    {
        return [
            'contract_id'     => $contract->contract_id,
            'bp_name'         => $contract->bp_name,
            'category'        => $contract->category?->category_name,
            'approval_status' => $contract->approvalStatus?->status_name,
            'workflow_status' => $contract->workflowStatus?->status_name,
            'item_code'       => $contract->item_code,
            'description'     => $contract->description,
            'serial_number'   => $contract->serial_number,
            'sbu_number'      => $contract->sbu_number,
            'region'          => $contract->region?->region_name,
            'start_date'       => $contract->start_date?->toDateString(),
            'end_date'         => $contract->end_date?->toDateString(),
            'lifecycle_status' => $contract->lifecycle_status,
            'created_by'       => $contract->created_by,
            'creator_name'     => $contract->creator_name ?? null,
            'prs_activity_id'  => $contract->prs_activity_id,
            'created_at'       => $contract->created_at?->toISOString(),
            'documents'        => $contract->documents->map(fn ($d) => [
                'document_id'  => (string) ($d->document_id ?? $d->_id),
                'file_name'    => $d->file_name,
                'file_type'    => $d->file_type,
                'file_size'    => $d->file_size,
                'document_url' => $d->document_url,
            ])->values(),
        ];
    }

    public function index(Request $request)
    {
        $query = Contract::with([
            'documents',
            'category',
            'approvalStatus',
            'workflowStatus',
            'region',
        ]);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('bp_name', 'like', $search . '%')
                  ->orWhere('item_code', 'like', $search . '%')
                  ->orWhere('contract_id', 'like', $search . '%');
            });
        }

        if ($request->has('category') && !empty($request->category)) {
            $category = $request->category;
            $query->whereHas('category', function($q) use ($category) {
                $q->where('category_name', $category);
            });
        }

        if ($request->has('region') && !empty($request->region)) {
            $region = $request->region;
            $query->whereHas('region', function($q) use ($region) {
                $q->where('region_name', $region);
            });
        }

        if ($request->has('status') && !empty($request->status)) {
            $status = $request->status;
            $query->where(function($q) use ($status) {
                $q->whereHas('approvalStatus', function($sq) use ($status) {
                    $sq->where('status_name', $status);
                })->orWhereHas('workflowStatus', function($sq) use ($status) {
                    $sq->where('status_name', $status);
                });
            });
        }

        if ($request->has('lifecycle_status') && !empty($request->lifecycle_status)) {
            $lifecycle = $request->lifecycle_status;
            $today = now()->toDateString();
            $thirtyDaysFromNow = now()->addDays(30)->toDateString();
            if ($lifecycle === 'active') {
                $query->where('end_date', '>', $thirtyDaysFromNow);
            } elseif ($lifecycle === 'expiring') {
                $query->whereBetween('end_date', [$today, $thirtyDaysFromNow]);
            } elseif ($lifecycle === 'expired') {
                $query->where('end_date', '<', $today);
            }
        }

        // Sales and Employee (incl. Sales & Marketing dept) can only ever see their own contracts
        $role = $request->get('auth_role');
        if (in_array($role, ['Sales', 'Employee'])) {
            $query->where('created_by', $request->get('auth_id'));
        }

        $query->orderBy('contract_id', 'desc');

        if ($request->has('paginate') && $request->paginate === 'true') {
            $perPage = (int) $request->get('per_page', 10);
            $paginated = $query->paginate($perPage);

            // Calculate stats based on role permissions
            $today = now()->toDateString();
            $thirtyDays = now()->addDays(30)->toDateString();

            $statsQuery = Contract::query();
            if (in_array($role, ['Sales', 'Employee'])) {
                $statsQuery->where('created_by', $request->get('auth_id'));
            }

            $stats = [
                'total'    => (int) (clone $statsQuery)->count(),
                'active'   => (int) (clone $statsQuery)->where('end_date', '>', $thirtyDays)->count(),
                'expiring' => (int) (clone $statsQuery)->whereBetween('end_date', [$today, $thirtyDays])->count(),
                'expired'  => (int) (clone $statsQuery)->where('end_date', '<', $today)->count(),
            ];

            $contractsCollection = collect($paginated->items());
            
            $userIds = $contractsCollection->pluck('created_by')->filter()->unique()->toArray();
            $users = $this->authService->getUsersBatch($userIds);
            $userMap = collect($users)->keyBy('id');

            $contractsCollection->each(function ($c) use ($userMap) {
                if ($c->created_by && $userMap->has($c->created_by)) {
                    $u = $userMap->get($c->created_by);
                    $c->creator_name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                }
            });

            return response()->json([
                'data' => $contractsCollection->map(fn ($c) => $this->formatContract($c))->values(),
                'pagination' => [
                    'total'        => $paginated->total(),
                    'per_page'     => $paginated->perPage(),
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                ],
                'stats' => $stats
            ]);
        }

        $contracts = $query->get();

        $userIds = $contracts->pluck('created_by')->filter()->unique()->toArray();
        $users = $this->authService->getUsersBatch($userIds);
        $userMap = collect($users)->keyBy('id');

        $contracts->each(function ($c) use ($userMap) {
            if ($c->created_by && $userMap->has($c->created_by)) {
                $u = $userMap->get($c->created_by);
                $c->creator_name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
            }
        });

        return response()->json([
            'data' => $contracts->map(fn ($c) => $this->formatContract($c))->values(),
        ]);
    }

    private function vendorIsSuspended(string $bpName): bool
    {
        if (Schema::hasTable('business_partners')) {
            $partner = DB::table('business_partners')->where('partner_name', $bpName)->first();
            if ($partner && $partner->status === 'Suspended') {
                return true;
            }
        }

        if (Schema::hasTable('suppliers')) {
            $supplier = DB::table('suppliers')->where('supplier_name', $bpName)->first();
            if ($supplier && $supplier->status === 'Suspended') {
                return true;
            }
        }

        return false;
    }

    private function autoLinkVendor(int $contractId, string $bpName, ?int $userId): void
    {
        if (!Schema::hasTable('vendor_contract_associations')) {
            return;
        }

        // 1. Try to find in business_partners
        if (Schema::hasTable('business_partners')) {
            $partner = DB::table('business_partners')
                ->where('partner_name', $bpName)
                ->first();

            if ($partner) {
                DB::table('vendor_contract_associations')->updateOrInsert(
                    [
                        'vendor_type' => 'partner',
                        'vendor_id'   => $partner->partner_id,
                        'contract_id' => $contractId,
                    ],
                    [
                        'attached_by' => $userId,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]
                );
                return;
            }
        }

        // 2. Try to find in suppliers
        if (Schema::hasTable('suppliers')) {
            $supplier = DB::table('suppliers')
                ->where('supplier_name', $bpName)
                ->first();

            if ($supplier) {
                DB::table('vendor_contract_associations')->updateOrInsert(
                    [
                        'vendor_type' => 'supplier',
                        'vendor_id'   => $supplier->supplier_id,
                        'contract_id' => $contractId,
                    ],
                    [
                        'attached_by' => $userId,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]
                );
            }
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();

        // Normalize camelCase fields from test to snake_case
        $normalized = [
            'bp_name'         => $data['bp_name'] ?? $data['businessPartner'] ?? null,
            'category'        => $data['category'] ?? null,
            'status'          => $data['status'] ?? $data['workflow_status'] ?? null,
            'item_code'       => $data['item_code'] ?? $data['itemCode'] ?? null,
            'description'     => $data['description'] ?? null,
            'serial_number'   => $data['serial_number'] ?? $data['serialNo'] ?? null,
            'sbu_number'      => $data['sbu_number'] ?? $data['sbuNumber'] ?? null,
            'region'          => $data['region'] ?? null,
            'start_date'      => $data['start_date'] ?? $data['startDate'] ?? null,
            'end_date'        => $data['end_date'] ?? $data['endDate'] ?? null,
            'document_ids'    => $data['document_ids'] ?? null,
            'prs_activity_id' => $data['prs_activity_id'] ?? null,
        ];

        $validator = Validator::make($normalized, [
            'bp_name'         => 'required|string|max:255',
            'category'        => 'required|string|max:255',
            'status'          => 'nullable|string|max:255',
            'item_code'       => 'required|string|max:255',
            'description'     => 'required|string',
            'serial_number'   => 'required|string|max:255|unique:contracts,serial_number',
            'sbu_number'      => 'required|string|max:255',
            'region'          => 'required|string|in:Luzon,Visayas,Mindanao',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after:start_date',
            'document_ids'    => 'nullable|array',
            'document_ids.*'  => 'string',
            'prs_activity_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $incoming = $validator->validated();

        if ($this->vendorIsSuspended($incoming['bp_name'])) {
            return response()->json([
                'message' => 'This business partner/supplier is suspended and cannot be assigned to a new contract.'
            ], 422);
        }

        // Resolve IDs
        $cat = ContractCategory::firstOrCreate(['category_name' => $incoming['category']]);
        $regionId = DB::table('contract_regions')
            ->where('region_name', $incoming['region'])
            ->value('region_id');

        if (!$regionId) {
            return response()->json(['message' => 'Invalid region.'], 422);
        }

        $userId = $request->get('auth_id');
        $role = $request->get('auth_role');
        $isManagerRole = in_array($role, ['Manager', 'Admin']);

        // Resolve Approval Status and Workflow status
        if ($isManagerRole) {
            $approvalStatusName = 'Approved';
            $workflowStatusName = $incoming['status'] ?? 'SBSI Review';
        } else {
            $approvalStatusName = 'Pending';
            $workflowStatusName = $incoming['status'] ?? null;
        }

        $approvalStatus = ContractApprovalStatus::firstOrCreate(['status_name' => $approvalStatusName]);
        
        $workflowStatusId = null;
        if ($workflowStatusName) {
            $stat = ContractStatus::firstOrCreate(['status_name' => $workflowStatusName]);
            $workflowStatusId = $stat->status_id;
        }

        $contract = Contract::create([
            'category_id'        => $cat->category_id,
            'approval_status_id' => $approvalStatus->approval_status_id,
            'workflow_status_id' => $workflowStatusId,
            'bp_name'            => $incoming['bp_name'],
            'item_code'          => $incoming['item_code'],
            'description'        => $incoming['description'],
            'serial_number'      => $incoming['serial_number'],
            'sbu_number'         => $incoming['sbu_number'],
            'region_id'          => $regionId,
            'start_date'         => $incoming['start_date'],
            'end_date'           => $incoming['end_date'],
            'created_by'         => $userId,
            'prs_activity_id'    => $incoming['prs_activity_id'] ?? null,
        ]);

        // Link MongoDB documents
        if (!empty($incoming['document_ids'])) {
            \App\Models\Document::whereIn('_id', $incoming['document_ids'])
                ->update(['contract_id' => $contract->contract_id]);

            // Audit log each linked document
            foreach ($incoming['document_ids'] as $docId) {
                $this->auditLogService->log(
                    'document_linked',
                    'Document',
                    $docId,
                    $userId,
                    [],
                    ['contract_id' => $contract->contract_id],
                    $request->get('auth_department')
                );
            }
        }

        // Auto-link to business partner/supplier if exists
        $this->autoLinkVendor((int) $contract->contract_id, $contract->bp_name, (int) $userId);

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

        $contract->load(['documents', 'category', 'approvalStatus', 'workflowStatus', 'region']);

        SyncContractToMeilisearch::dispatch($this->formatContract($contract));

        // Notify Manager if an Employee in Sales & Marketing department creates a contract
        if ($role === 'Employee' && $request->get('auth_department') === 'Sales & Marketing') {
            $authUser = $request->get('auth_user');
            $userName = trim(($authUser['first_name'] ?? '') . ' ' . ($authUser['last_name'] ?? ''));
            if (empty($userName)) {
                $userName = $authUser['username'] ?? 'A Sales & Marketing Employee';
            }
            $notifMessage = "{$userName} sent a contract and is requesting to review it.";

            try {
                app(\App\Services\NotificationService::class)->push(
                    (int) $contract->contract_id,
                    'sales_marketing_review',
                    $notifMessage,
                    'Manager'
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to push sales & marketing review notification: " . $e->getMessage());
            }
        }

        $this->notifyPrsOfContractLink($contract);

        return response()->json([
            'message' => 'Contract created successfully.',
            'data'    => $this->formatContract($contract)
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

        // Authorization check if Sales / Employee editing another user's contract
        $role = $request->get('auth_role');
        $userId = $request->get('auth_id');
        if (in_array($role, ['Sales', 'Employee']) && $contract->created_by !== $userId) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = $request->all();

        // Normalize
        $normalized = [
            'bp_name'         => $data['bp_name'] ?? $data['businessPartner'] ?? null,
            'category'        => $data['category'] ?? null,
            'status'          => $data['status'] ?? $data['workflow_status'] ?? null,
            'item_code'       => $data['item_code'] ?? $data['itemCode'] ?? null,
            'description'     => $data['description'] ?? null,
            'serial_number'   => $data['serial_number'] ?? $data['serialNo'] ?? null,
            'sbu_number'      => $data['sbu_number'] ?? $data['sbuNumber'] ?? null,
            'region'          => $data['region'] ?? null,
            'start_date'      => $data['start_date'] ?? $data['startDate'] ?? null,
            'end_date'        => $data['end_date'] ?? $data['endDate'] ?? null,
            'document_ids'    => $data['document_ids'] ?? null,
        ];

        $validator = Validator::make($normalized, [
            'bp_name'         => 'required|string|max:255',
            'category'        => 'required|string|max:255',
            'status'          => 'nullable|string|max:255',
            'item_code'       => 'required|string|max:255',
            'description'     => 'required|string',
            'serial_number'   => "required|string|max:255|unique:contracts,serial_number,{$id},contract_id",
            'sbu_number'      => 'required|string|max:255',
            'region'          => 'required|string|in:Luzon,Visayas,Mindanao',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after:start_date',
            'document_ids'    => 'nullable|array',
            'document_ids.*'  => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $incoming = $validator->validated();

        if ($incoming['bp_name'] !== $contract->bp_name && $this->vendorIsSuspended($incoming['bp_name'])) {
            return response()->json([
                'message' => 'This business partner/supplier is suspended and cannot be assigned to a new contract.'
            ], 422);
        }

        $cat = ContractCategory::firstOrCreate(['category_name' => $incoming['category']]);
        $regionId = DB::table('contract_regions')
            ->where('region_name', $incoming['region'])
            ->value('region_id');

        if (!$regionId) {
            return response()->json(['message' => 'Invalid region.'], 422);
        }

        $oldData = $contract->toArray();

        $updatePayload = [
            'category_id'   => $cat->category_id,
            'bp_name'       => $incoming['bp_name'],
            'item_code'     => $incoming['item_code'],
            'description'   => $incoming['description'],
            'serial_number' => $incoming['serial_number'],
            'sbu_number'    => $incoming['sbu_number'],
            'region_id'     => $regionId,
            'start_date'    => $incoming['start_date'],
            'end_date'      => $incoming['end_date'],
        ];

        if (!empty($incoming['status'])) {
            $stat = ContractStatus::firstOrCreate(['status_name' => $incoming['status']]);
            $updatePayload['workflow_status_id'] = $stat->status_id;
        }

        $contract->update($updatePayload);

        // Link MongoDB documents and delete removed ones
        $incomingDocIds = $incoming['document_ids'] ?? [];
        
        $currentDocs = \App\Models\Document::where('contract_id', $contract->contract_id)->get();
        foreach ($currentDocs as $doc) {
            $docIdStr = (string)$doc->getKey();
            if (!in_array($docIdStr, $incomingDocIds)) {
                if ($doc->file_path) {
                    $disk = config('filesystems.default', 'local');
                    \Illuminate\Support\Facades\Storage::disk($disk)->delete($doc->file_path);
                }
                
                $oldDocData = $doc->toArray();
                $doc->delete();
                
                $this->auditLogService->log(
                    'document_deleted',
                    'Document',
                    $docIdStr,
                    $userId,
                    $oldDocData,
                    []
                );
            }
        }

        if (!empty($incomingDocIds)) {
            \App\Models\Document::whereIn('_id', $incomingDocIds)
                ->update(['contract_id' => $contract->contract_id]);

            // Audit log only newly linked documents (not ones that were already linked)
            $previouslyLinkedIds = $currentDocs->map(fn ($d) => (string) $d->getKey())->toArray();
            foreach ($incomingDocIds as $docId) {
                if (!in_array($docId, $previouslyLinkedIds)) {
                    $this->auditLogService->log(
                        'document_linked',
                        'Document',
                        $docId,
                        $userId,
                        [],
                        ['contract_id' => $contract->contract_id],
                        $request->get('auth_department')
                    );
                }
            }
        }

        // Clear old associations and re-link
        if (Schema::hasTable('vendor_contract_associations')) {
            DB::table('vendor_contract_associations')->where('contract_id', $contract->contract_id)->delete();
        }
        $this->autoLinkVendor((int) $contract->contract_id, $contract->bp_name, (int) $userId);

        // Audit Logging
        $this->auditLogService->log(
            'updated',
            'Contract',
            $contract->contract_id,
            $userId,
            $oldData,
            $contract->toArray(),
            $request->get('auth_department')
        );

        $contract->load(['documents', 'category', 'approvalStatus', 'workflowStatus', 'region']);

        $this->notifyPrsOfContractStatus($contract);

        SyncContractToMeilisearch::dispatch($this->formatContract($contract));

        return response()->json([
            'message' => 'Contract updated successfully.',
            'data'    => $this->formatContract($contract),
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

        $userId = $request->get('auth_id');

        // Delete all associated documents
        $associatedDocs = \App\Models\Document::where('contract_id', $contract->contract_id)->get();
        foreach ($associatedDocs as $doc) {
            if ($doc->file_path) {
                $disk = config('filesystems.default', 'local');
                \Illuminate\Support\Facades\Storage::disk($disk)->delete($doc->file_path);
            }
            
            $oldDocData = $doc->toArray();
            $doc->delete();
            
            $this->auditLogService->log(
                'document_deleted',
                'Document',
                (string)$doc->getKey(),
                $userId,
                $oldDocData,
                []
            );
        }

        $oldData = $contract->toArray();
        $contract->delete();

        // Audit Logging
        $this->auditLogService->log(
            'deleted',
            'Contract',
            $id,
            $userId,
            $oldData,
            [],
            $request->get('auth_department')
        );

        RemoveContractFromMeilisearch::dispatch((int) $id);

        return response()->json(['message' => 'Contract deleted successfully.']);
    }

    public function dashboardSummary(Request $request)
    {
        $query = Contract::with([
            'category',
            'approvalStatus',
            'workflowStatus',
            'region',
        ]);

        $role = $request->get('auth_role');
        if (in_array($role, ['Sales', 'Employee'])) {
            $query->where('created_by', $request->get('auth_id'));
        }

        $contracts = $query->orderByDesc('contract_id')->get();

        $userIds = $contracts->pluck('created_by')->filter()->unique()->toArray();
        $users = $this->authService->getUsersBatch($userIds);
        $userMap = collect($users)->keyBy('id');

        return response()->json([
            'data' => $contracts->map(function ($c) use ($userMap) {
                $creatorName = null;
                if ($c->created_by && $userMap->has($c->created_by)) {
                    $u = $userMap->get($c->created_by);
                    $creatorName = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                }

                return [
                    'contract_id'     => $c->contract_id,
                    'bp_name'         => $c->bp_name,
                    'category'        => $c->category?->category_name,
                    'approval_status' => $c->approvalStatus?->status_name,
                    'workflow_status' => $c->workflowStatus?->status_name,
                    'item_code'       => $c->item_code,
                    'description'     => $c->description,
                    'serial_number'   => $c->serial_number,
                    'sbu_number'      => $c->sbu_number,
                    'region'          => $c->region?->region_name,
                    'start_date'       => $c->start_date?->toDateString(),
                    'end_date'         => $c->end_date?->toDateString(),
                    'lifecycle_status' => $c->lifecycle_status,
                    'created_by'       => $c->created_by,
                    'creator_name'     => $creatorName,
                    'prs_activity_id'  => $c->prs_activity_id,
                    'created_at'       => $c->created_at?->toISOString(),
                    'documents'        => [],
                ];
            })->values(),
        ]);
    }

    public function indexRequests(Request $request)
    {
        $query = Contract::with([
            'documents',
            'category',
            'approvalStatus',
            'workflowStatus',
            'region',
        ]);

        $role = $request->get('auth_role');
        if (in_array($role, ['Sales', 'Employee'])) {
            $query->where('created_by', $request->get('auth_id'));
        } elseif ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        if ($request->filled('approval_status')) {
            $approvalStatusId = DB::table('contract_approval_statuses')
                ->where('status_name', $request->approval_status)
                ->value('approval_status_id');

            if ($approvalStatusId) {
                $query->where('approval_status_id', $approvalStatusId);
            }
        }

        $contracts = $query->orderByDesc('created_at')->get();

        $userIds = $contracts->pluck('created_by')->filter()->unique()->toArray();
        $users = $this->authService->getUsersBatch($userIds);
        $userMap = collect($users)->keyBy('id');

        $contracts->each(function ($c) use ($userMap) {
            if ($c->created_by && $userMap->has($c->created_by)) {
                $u = $userMap->get($c->created_by);
                $c->creator_name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
            }
        });

        return response()->json([
            'data' => $contracts->map(fn ($c) => $this->formatContract($c))->values(),
        ]);
    }

    public function show(Request $request, $id)
    {
        $contract = Contract::with([
            'documents',
            'category',
            'approvalStatus',
            'workflowStatus',
            'region',
        ])->findOrFail($id);

        $role = $request->get('auth_role');
        if (in_array($role, ['Sales', 'Employee']) && $contract->created_by !== $request->get('auth_id')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($contract->created_by) {
            $users = $this->authService->getUsersBatch([$contract->created_by]);
            if (!empty($users)) {
                $u = $users[0];
                $contract->creator_name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
            }
        }

        return response()->json([
            'data' => $this->formatContract($contract),
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $contract = Contract::findOrFail($id);

        $request->validate([
            'approval_status' => 'required|string|in:Approved,Rejected',
            'workflow_status' => 'nullable|string',
        ]);

        $approvalStatusId = DB::table('contract_approval_statuses')
            ->where('status_name', $request->approval_status)
            ->value('approval_status_id');

        if (!$approvalStatusId) {
            return response()->json(['message' => 'Invalid approval status.'], 422);
        }

        $workflowStatusId = null;
        if ($request->filled('workflow_status')) {
            $workflowStatusId = DB::table('contract_statuses')
                ->where('status_name', $request->workflow_status)
                ->value('status_id');

            if (!$workflowStatusId) {
                return response()->json(['message' => 'Invalid workflow status.'], 422);
            }
        }

        $contract->update([
            'approval_status_id' => $approvalStatusId,
            'workflow_status_id' => $workflowStatusId,
        ]);

        $contract->load(['documents', 'category', 'approvalStatus', 'workflowStatus', 'region']);

        $this->notifyPrsOfContractStatus($contract);

        SyncContractToMeilisearch::dispatch($this->formatContract($contract));

        return response()->json([
            'message' => 'Contract status updated.',
            'data'    => $this->formatContract($contract),
        ]);
    }

    private function notifyPrsOfContractLink(Contract $contract): void
    {
        if (empty($contract->prs_activity_id)) {
            return;
        }

        $statusName = $contract->approvalStatus?->status_name ?? 'Pending';
        $outcome = in_array($statusName, ['Approved', 'Rejected'], true)
            ? ($statusName === 'Approved' ? 'Won' : 'Loss')
            : 'Follow-up';

        \App\Jobs\NotifyPrsOfContractUpdateJob::dispatch(
            (int) $contract->prs_activity_id,
            (int) $contract->contract_id,
            $statusName,
            $outcome
        );
    }

    private function notifyPrsOfContractStatus(Contract $contract): void
    {
        if (empty($contract->prs_activity_id)) {
            return;
        }

        $statusName = $contract->approvalStatus?->status_name;
        if (!in_array($statusName, ['Approved', 'Rejected'], true)) {
            return;
        }

        $outcome = $statusName === 'Approved' ? 'Won' : 'Loss';

        \App\Jobs\NotifyPrsOfContractUpdateJob::dispatch(
            (int) $contract->prs_activity_id,
            (int) $contract->contract_id,
            $statusName,
            $outcome
        );
    }
}
