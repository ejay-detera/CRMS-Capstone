<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'bp_name'       => 'required|string',
            'category'      => 'required|string',
            'item_code'     => 'required|string|max:255',
            'description'   => 'required|string',
            'serial_number' => 'required|string|max:255|unique:contracts,serial_number',
            'region'        => 'required|string|in:Luzon,Visayas,Mindanao',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after:start_date',
            'docs'          => 'nullable|array',
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

        $statusId = DB::table('contract_statuses')
            ->where('status_name', 'Notarized PDF')
            ->value('status_id');

        $contract = Contract::create([
            'category_id'   => $categoryId,
            'status_id'     => $statusId,
            'bp_name'       => $request->bp_name,
            'item_code'     => $request->item_code,
            'description'   => $request->description,
            'serial_number' => $request->serial_number,
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
            'data'    => $contract->load('documents'),
        ], 201);
    }
}
