<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
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
            'region'          => $contract->region,
            'start_date'      => $contract->start_date?->toDateString(),
            'end_date'        => $contract->end_date?->toDateString(),
            'created_by'      => $contract->created_by,
            'documents'       => $contract->documents->map(fn ($d) => [
                'document_id'  => $d->document_id,
                'file_name'    => $d->file_name,
                'file_type'    => $d->file_type,
                'file_size'    => $d->file_size,
                'document_url' => $d->document_url,
            ])->values(),
        ];
    }

    public function show(int $id)
    {
        $contract = Contract::with(['documents', 'category', 'approvalStatus', 'workflowStatus'])->findOrFail($id);

        return response()->json([
            'data' => $this->formatContract($contract),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'bp_name'       => 'required|string',
            'category'      => 'required|string',
            'item_code'     => 'required|string|max:255',
            'description'   => 'required|string',
            'serial_number' => 'required|string|max:255|unique:contracts,serial_number',
            'sbu_number'    => 'required|string|max:255',
            'region'        => 'required|string|in:Luzon,Visayas,Mindanao',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after:start_date',
            'docs'             => 'nullable|array',
            'docs.*.file_name' => 'required|string',
            'docs.*.file_type' => 'required|string|in:pdf,docx',
            'docs.*.file_size' => 'required|integer',
        ]);

        $categoryId = DB::table('contract_categories')
            ->where('category_name', $request->category)
            ->value('category_id');

        if (!$categoryId) {
            return response()->json(['message' => 'Invalid category.'], 422);
        }

        $approvalStatusId = DB::table('contract_approval_statuses')
            ->where('status_name', 'Pending')
            ->value('approval_status_id');

        $contract = Contract::create([
            'category_id'        => $categoryId,
            'approval_status_id' => $approvalStatusId,
            'bp_name'            => $request->bp_name,
            'item_code'     => $request->item_code,
            'description'   => $request->description,
            'serial_number' => $request->serial_number,
            'sbu_number'    => $request->sbu_number,
            'region'        => $request->region,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'created_by'    => $request->auth_id,
        ]);

        if ($request->filled('docs')) {
            foreach ($request->docs as $doc) {
                Document::create([
                    'contract_id'  => $contract->contract_id,
                    'file_name'    => $doc['file_name'],
                    'file_type'    => $doc['file_type'],
                    'file_size'    => $doc['file_size'],
                    'uploaded_by'  => $request->auth_id,
                    'document_url' => null,
                ]);
            }
        }

        return response()->json([
            'message' => 'Contract created.',
            'data'    => ['contract_id' => $contract->contract_id],
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $contract = Contract::findOrFail($id);

        $request->validate([
            'bp_name'       => 'required|string',
            'category'      => 'required|string',
            'item_code'     => 'required|string|max:255',
            'description'   => 'required|string',
            'serial_number' => "required|string|max:255|unique:contracts,serial_number,{$id},contract_id",
            'sbu_number'    => 'required|string|max:255',
            'region'        => 'required|string|in:Luzon,Visayas,Mindanao',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after:start_date',
        ]);

        $categoryId = DB::table('contract_categories')
            ->where('category_name', $request->category)
            ->value('category_id');

        if (!$categoryId) {
            return response()->json(['message' => 'Invalid category.'], 422);
        }

        $contract->update([
            'category_id'   => $categoryId,
            'bp_name'       => $request->bp_name,
            'item_code'     => $request->item_code,
            'description'   => $request->description,
            'serial_number' => $request->serial_number,
            'sbu_number'    => $request->sbu_number,
            'region'        => $request->region,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
        ]);

        $contract->load(['documents', 'category', 'approvalStatus', 'workflowStatus']);

        return response()->json([
            'message' => 'Contract updated.',
            'data'    => $this->formatContract($contract),
        ]);
    }
}
