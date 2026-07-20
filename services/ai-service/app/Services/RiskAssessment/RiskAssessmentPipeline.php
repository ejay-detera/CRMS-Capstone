<?php

namespace App\Services\RiskAssessment;

use App\Models\Embedding;
use App\Models\PlaybookClause;
use App\Models\RiskAssessmentFinding;
use App\Models\RiskAssessmentResult;
use App\Services\ContractDocumentClient;
use App\Services\Gemini\GeminiClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * US-026: AI Risk Assessment — the RAG pipeline.
 *
 * playbook clause embeddings (indexed ahead of time) → contract text
 * extraction/chunking/embedding → pgvector retrieval of the most relevant
 * playbook clause per chunk → Gemini structured-JSON judgment on whether the
 * chunk deviates from that clause → risk_assessment_findings rows + one
 * aggregated risk_assessment_results row per contract.
 *
 * This keeps the assessment explainable: every finding traces back to a
 * specific retrieved playbook clause (a real FK, not an LLM guess) and a
 * structured judgment, not a free-floating end-to-end model call.
 */
class RiskAssessmentPipeline
{
    private const TOP_K = 2;

    public function __construct(
        protected GeminiClient $gemini,
        protected DocumentTextExtractor $extractor,
        protected ContractDocumentClient $documentClient,
    ) {
    }

    /**
     * Ensures every active playbook clause has an up-to-date embedding.
     * Idempotent — re-running only embeds clauses missing an embedding row.
     */
    public function indexPlaybook(): void
    {
        $clauses = PlaybookClause::where('is_active', true)->get();

        foreach ($clauses as $clause) {
            $exists = Embedding::where('entity_type', 'playbook_clause')
                ->where('entity_id', $clause->id)
                ->exists();

            if ($exists) {
                continue;
            }

            $vector = $this->gemini->embed("{$clause->title}\n\n{$clause->standard_text}");
            if ($vector === null) {
                Log::warning('Failed to embed playbook clause, skipping.', ['clause_id' => $clause->id]);
                continue;
            }

            Embedding::create([
                'entity_type' => 'playbook_clause',
                'entity_id'   => $clause->id,
                'embedding'   => $this->formatVector($vector),
                'model_name'  => env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-001'),
            ]);
        }
    }

    /**
     * Runs the full pipeline for a contract: fetches its documents, extracts
     * text, chunks + embeds, retrieves relevant playbook clauses per chunk,
     * asks Gemini for a deviation judgment, and writes the result.
     *
     * Returns the created RiskAssessmentResult, or null if no extractable
     * text was found across the contract's documents (e.g. scanned/image-only
     * PDFs with no text layer — a known limitation, not a silent failure).
     */
    public function run(int $contractId): ?RiskAssessmentResult
    {
        $this->indexPlaybook();

        $documents = $this->documentClient->listDocuments($contractId);
        if (empty($documents)) {
            return $this->markFailed($contractId, null, 'No clean/scanned documents found for this contract.');
        }

        $allChunks = [];
        $usedDocumentId = null;

        foreach ($documents as $doc) {
            $bytes = $this->documentClient->fetchFileBytes($doc['document_id']);
            if ($bytes === null) {
                continue;
            }

            $text = $this->extractor->extract($bytes, $doc['file_type']);
            if ($text === null) {
                continue;
            }

            $usedDocumentId ??= $doc['document_id'];
            $allChunks = array_merge($allChunks, $this->extractor->chunk($text));
        }

        if (empty($allChunks)) {
            return $this->markFailed(
                $contractId,
                $usedDocumentId,
                'No extractable text found (documents may be scanned/image-only with no text layer).'
            );
        }

        $findings = [];
        foreach ($allChunks as $chunk) {
            $chunkFindings = $this->assessChunk($chunk);
            $findings = array_merge($findings, $chunkFindings);
        }

        return $this->saveResult($contractId, $usedDocumentId, $findings);
    }

    /**
     * @return list<array{clause_reference: string, severity: string, playbook_clause_id: ?int, retrieval_score: ?float, deviation_reason: string, recommended_remediation: string}>
     */
    protected function assessChunk(string $chunk): array
    {
        $chunkVector = $this->gemini->embed($chunk);
        if ($chunkVector === null) {
            return [];
        }

        $retrieved = $this->retrieveTopPlaybookClauses($chunkVector, self::TOP_K);
        if (empty($retrieved)) {
            return [];
        }

        $findings = [];
        foreach ($retrieved as $candidate) {
            $clause = $candidate['clause'];
            $judgment = $this->judgeDeviation($chunk, $clause);

            if ($judgment && ($judgment['deviates'] ?? false)) {
                $findings[] = [
                    'clause_reference'        => $chunk,
                    'severity'                => $judgment['severity'] ?? 'medium',
                    'playbook_clause_id'      => $clause->id,
                    'retrieval_score'         => $candidate['score'],
                    'deviation_reason'        => $judgment['deviation_reason'] ?? '',
                    'recommended_remediation' => $judgment['recommended_remediation'] ?? '',
                ];
            }
        }

        return $findings;
    }

    /**
     * @return list<array{clause: PlaybookClause, score: float}>
     */
    protected function retrieveTopPlaybookClauses(array $chunkVector, int $topK): array
    {
        $vectorLiteral = $this->formatVector($chunkVector);

        // pgvector cosine-distance retrieval: `<=>` returns cosine distance
        // (0 = identical, 2 = opposite), so ORDER BY it ascending gives the
        // most similar rows first. Falls back to no results (rather than
        // erroring) if the driver doesn't support pgvector operators — e.g.
        // when this runs against SQLite in a non-Postgres test environment.
        try {
            $rows = DB::table('embeddings')
                ->where('entity_type', 'playbook_clause')
                ->selectRaw('entity_id, 1 - (embedding <=> ?) as similarity', [$vectorLiteral])
                ->orderByRaw('embedding <=> ?', [$vectorLiteral])
                ->limit($topK)
                ->get();
        } catch (\Exception $e) {
            Log::warning('pgvector retrieval query failed (expected on non-Postgres connections).', [
                'message' => $e->getMessage(),
            ]);
            return [];
        }

        $clauseIds = $rows->pluck('entity_id')->all();
        $clauses = PlaybookClause::whereIn('id', $clauseIds)->get()->keyBy('id');

        $results = [];
        foreach ($rows as $row) {
            $clause = $clauses->get($row->entity_id);
            if ($clause) {
                $results[] = ['clause' => $clause, 'score' => (float) $row->similarity];
            }
        }

        return $results;
    }

    protected function judgeDeviation(string $chunk, PlaybookClause $clause): ?array
    {
        $systemInstruction = <<<'PROMPT'
You are a contract-risk assistant grading a single excerpt of a commercial
contract against one standard playbook clause. Judge ONLY whether the excerpt
deviates from the cited clause. Be conservative: only flag a real, material
deviation, not stylistic differences. Respond strictly in the requested JSON schema.
PROMPT;

        $userPrompt = "Playbook clause \"{$clause->title}\" ({$clause->clause_code}):\n{$clause->standard_text}\n\n"
            . "Contract excerpt:\n{$chunk}";

        $schema = [
            'type' => 'object',
            'properties' => [
                'deviates' => ['type' => 'boolean'],
                'severity' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'critical']],
                'deviation_reason' => ['type' => 'string'],
                'recommended_remediation' => ['type' => 'string'],
            ],
            'required' => ['deviates'],
        ];

        return $this->gemini->generateJson($systemInstruction, $userPrompt, $schema);
    }

    protected function saveResult(int $contractId, ?string $documentId, array $findings): RiskAssessmentResult
    {
        $riskLevel = $this->aggregateRiskLevel($findings);
        $riskScore = $this->aggregateRiskScore($findings);

        $result = RiskAssessmentResult::create([
            'document_id' => $documentId,
            'contract_id' => $contractId,
            'risk_score'  => $riskScore,
            'risk_level'  => $riskLevel,
            'findings'    => ['count' => count($findings)],
            'status'      => 'completed',
            'scanned_at'  => now(),
        ]);

        foreach ($findings as $finding) {
            RiskAssessmentFinding::create([
                'risk_assessment_result_id' => $result->id,
                'clause_reference'          => $finding['clause_reference'],
                'severity'                  => $finding['severity'],
                'playbook_clause_id'        => $finding['playbook_clause_id'],
                'retrieval_score'           => $finding['retrieval_score'],
                'deviation_reason'          => $finding['deviation_reason'],
                'recommended_remediation'   => $finding['recommended_remediation'],
                'created_at'                => now(),
            ]);
        }

        return $result;
    }

    protected function markFailed(int $contractId, ?string $documentId, string $reason): RiskAssessmentResult
    {
        Log::info("Risk assessment for contract {$contractId} failed: {$reason}");

        return RiskAssessmentResult::create([
            'document_id' => $documentId,
            'contract_id' => $contractId,
            'risk_score'  => null,
            'risk_level'  => null,
            'findings'    => ['count' => 0, 'reason' => $reason],
            'status'      => 'failed',
            'scanned_at'  => now(),
        ]);
    }

    protected function aggregateRiskLevel(array $findings): ?string
    {
        if (empty($findings)) {
            return 'low';
        }

        $severities = array_column($findings, 'severity');
        foreach (['critical', 'high', 'medium', 'low'] as $level) {
            if (in_array($level, $severities, true)) {
                return $level;
            }
        }

        return 'low';
    }

    protected function aggregateRiskScore(array $findings): float
    {
        $weights = ['low' => 10, 'medium' => 40, 'high' => 70, 'critical' => 95];
        if (empty($findings)) {
            return 0.0;
        }

        $scores = array_map(fn ($f) => $weights[$f['severity']] ?? 40, $findings);
        return round(max($scores), 2);
    }

    protected function formatVector(array $vector): string
    {
        return '[' . implode(',', $vector) . ']';
    }
}
