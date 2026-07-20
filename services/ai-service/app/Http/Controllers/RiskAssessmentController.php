<?php

namespace App\Http\Controllers;

use App\Jobs\RunRiskAssessmentPipeline;
use App\Models\RiskAssessmentResult;
use App\Services\ContractOwnershipService;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * US-026: AI Risk Assessment Summary — trigger, read, and PDF-export
 * endpoints on top of the RAG pipeline (RiskAssessmentPipeline). Role
 * matrix: Manager (decision), Sales (view, own contracts only).
 */
class RiskAssessmentController extends Controller
{
    public function __construct(protected ContractOwnershipService $ownership)
    {
    }

    /**
     * POST /contracts/{contractId}/risk-assessment/scan
     */
    public function scan(Request $request, int $contractId)
    {
        if ($denied = $this->denyIfNotAuthorized($request, $contractId)) {
            return $denied;
        }

        RunRiskAssessmentPipeline::dispatch($contractId);

        return response()->json([
            'message' => 'AI Risk Assessment scan queued.',
        ], 202);
    }

    /**
     * GET /contracts/{contractId}/risk-assessment/summary
     */
    public function summary(Request $request, int $contractId)
    {
        if ($denied = $this->denyIfNotAuthorized($request, $contractId)) {
            return $denied;
        }

        $result = $this->latestResult($contractId);

        if (!$result) {
            return response()->json(['message' => 'No risk assessment has been run for this contract yet.'], 404);
        }

        return response()->json(['data' => $this->formatResult($result)]);
    }

    /**
     * GET /contracts/{contractId}/risk-assessment/summary/pdf
     */
    public function summaryPdf(Request $request, int $contractId)
    {
        if ($denied = $this->denyIfNotAuthorized($request, $contractId)) {
            return $denied;
        }

        $result = $this->latestResult($contractId);

        if (!$result) {
            return response()->json(['message' => 'No risk assessment has been run for this contract yet.'], 404);
        }

        $findings = $result->findingRows()->with('playbookClause')->orderByDesc('severity')->get();

        $html = view('pdf.risk_assessment_summary', [
            'result'   => $result,
            'findings' => $findings,
        ])->render();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"risk-assessment-contract-{$contractId}.pdf\"",
        ]);
    }

    /**
     * Manager/Admin can access any contract's assessment. Sales/Employee may
     * only access assessments for contracts they created. Returns a 403
     * JsonResponse if denied, or null if allowed.
     */
    protected function denyIfNotAuthorized(Request $request, int $contractId): ?\Illuminate\Http\JsonResponse
    {
        $role = $request->get('auth_role');

        if (in_array($role, ['Manager', 'Admin'], true)) {
            return null;
        }

        $userId = (int) $request->get('auth_id');
        $createdBy = $this->ownership->getCreatedBy($contractId);

        if ($createdBy !== null && $createdBy === $userId) {
            return null;
        }

        return response()->json(['message' => 'Forbidden. You may only view AI Risk Assessments for your own contracts.'], 403);
    }

    protected function latestResult(int $contractId): ?RiskAssessmentResult
    {
        return RiskAssessmentResult::where('contract_id', $contractId)
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->first();
    }

    protected function formatResult(RiskAssessmentResult $result): array
    {
        $findings = $result->findingRows()->with('playbookClause')->orderByDesc('severity')->get();

        return [
            'contract_id' => $result->contract_id,
            'risk_score'  => $result->risk_score,
            'risk_level'  => $result->risk_level,
            'status'      => $result->status,
            'scanned_at'  => $result->scanned_at?->toISOString(),
            'findings'    => $findings->map(fn ($f) => [
                'id'                       => $f->id,
                'clause_reference'         => $f->clause_reference,
                'severity'                 => $f->severity,
                'playbook_clause_code'     => $f->playbookClause?->clause_code,
                'playbook_clause_title'    => $f->playbookClause?->title,
                'deviation_reason'         => $f->deviation_reason,
                'recommended_remediation'  => $f->recommended_remediation,
            ])->values(),
        ];
    }
}
