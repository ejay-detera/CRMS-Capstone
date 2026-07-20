<?php

namespace App\Services\VendorSuggestion;

use App\Models\Embedding;
use App\Models\VendorSuggestion;
use App\Models\VendorSuggestionCandidate;
use App\Services\Gemini\GeminiClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Feature 3: Vendor AI Suggestions. Uses pgvector the same way Feature 1
 * does: retrieves embeddings of existing suppliers/business_partners
 * similar to the given industry/region hint, feeds that "vendors we already
 * work with" context into a Gemini prompt (grounded in SBSI's business —
 * see SBSI_COMPANY_PROFILE.md content baked into the system prompt below),
 * and asks for N new candidate PH companies as structured JSON. Candidates
 * are embedded too, so future runs can dedupe near-identical suggestions.
 */
class VendorSuggestionPipeline
{
    private const CANDIDATE_COUNT = 5;
    private const SIMILAR_VENDOR_COUNT = 5;

    public function __construct(protected GeminiClient $gemini)
    {
    }

    public function run(VendorSuggestion $suggestion): void
    {
        $similarVendors = $this->retrieveSimilarVendors($suggestion->industry_hint, $suggestion->region_hint);

        $judgment = $this->generateCandidates($suggestion->industry_hint, $suggestion->region_hint, $similarVendors);

        if ($judgment === null || empty($judgment['candidates'])) {
            $suggestion->update(['status' => 'failed']);
            return;
        }

        foreach ($judgment['candidates'] as $candidate) {
            $candidateRow = VendorSuggestionCandidate::create([
                'vendor_suggestion_id'      => $suggestion->id,
                'candidate_name'            => $candidate['name'] ?? 'Unknown',
                'candidate_industry'        => $candidate['industry'] ?? null,
                'candidate_region'          => $candidate['region'] ?? null,
                'candidate_contact_email'   => $candidate['contact_email'] ?? null,
                'candidate_contact_number'  => $candidate['contact_number'] ?? null,
                'candidate_address'         => $candidate['address'] ?? null,
                'suggestion_score'          => $candidate['confidence_score'] ?? null,
                'suggestion_reason'         => $candidate['reason'] ?? null,
                'gemini_raw_response'       => $candidate,
                'decision'                  => 'pending',
            ]);

            $vector = $this->gemini->embed(trim(($candidate['name'] ?? '') . ' ' . ($candidate['industry'] ?? '') . ' ' . ($candidate['region'] ?? '')));
            if ($vector !== null) {
                Embedding::create([
                    'entity_type' => 'vendor_suggestion_candidate',
                    'entity_id'   => $candidateRow->id,
                    'embedding'   => '[' . implode(',', $vector) . ']',
                    'model_name'  => env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-001'),
                ]);
            }
        }

        $suggestion->update(['status' => 'completed', 'suggested_at' => now()]);
    }

    /**
     * @return list<array{name: string, industry: ?string, region: ?string}>
     */
    protected function retrieveSimilarVendors(?string $industryHint, ?string $regionHint): array
    {
        $queryText = trim(($industryHint ?? '') . ' ' . ($regionHint ?? ''));
        if ($queryText === '') {
            return [];
        }

        $queryVector = $this->gemini->embed($queryText);
        if ($queryVector === null) {
            return [];
        }

        $vectorLiteral = '[' . implode(',', $queryVector) . ']';

        try {
            $rows = DB::table('embeddings')
                ->where('entity_type', 'vendor')
                ->orderByRaw('embedding <=> ?', [$vectorLiteral])
                ->limit(self::SIMILAR_VENDOR_COUNT)
                ->get(['entity_id', 'model_name']);
        } catch (\Exception $e) {
            // Expected on non-Postgres connections (e.g. SQLite in tests) —
            // pgvector's <=> operator isn't available there.
            Log::warning('Vendor similarity retrieval failed (expected on non-Postgres).', ['message' => $e->getMessage()]);
            return [];
        }

        // model_name is tagged "vendor:{type}:{id}" by the backfill command —
        // parse it back out for a lightweight display name in the prompt.
        return $rows->map(function ($row) {
            $parts = explode(':', (string) $row->model_name);
            return [
                'type' => $parts[1] ?? 'vendor',
                'id'   => $parts[2] ?? $row->entity_id,
            ];
        })->values()->all();
    }

    protected function generateCandidates(?string $industryHint, ?string $regionHint, array $similarVendors): ?array
    {
        $systemInstruction = <<<'PROMPT'
You are a vendor-sourcing assistant for Scientific Biotech Specialties, Inc.
(SBSI), a Philippine distributor of in-vitro diagnostic (IVD) products
(clinical hematology, chemistry, coagulation, blood bank, immunology,
molecular, etc.) and industrial instrumentation/QA solutions, headquartered
in Makati City, Metro Manila, with operations across Luzon, Visayas, and
Mindanao. Suggest real, plausible Philippine business partners or suppliers
that could realistically work with SBSI's business. Respond strictly in the
requested JSON schema. Do not fabricate contact details with unwarranted
confidence — provide plausible values but keep confidence_score conservative
if you are not certain a detail is accurate.
PROMPT;

        $userPrompt = "Industry hint: " . ($industryHint ?: 'general vendor/supplier') . "\n"
            . "Region hint: " . ($regionHint ?: 'any region in the Philippines') . "\n"
            . "Suggest " . self::CANDIDATE_COUNT . " candidate companies.";

        $schema = [
            'type' => 'object',
            'properties' => [
                'candidates' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name'             => ['type' => 'string'],
                            'industry'         => ['type' => 'string'],
                            'region'           => ['type' => 'string', 'enum' => ['Luzon', 'Visayas', 'Mindanao']],
                            'contact_email'    => ['type' => 'string'],
                            'contact_number'   => ['type' => 'string'],
                            'address'          => ['type' => 'string'],
                            'confidence_score' => ['type' => 'number'],
                            'reason'           => ['type' => 'string'],
                        ],
                        'required' => ['name', 'reason'],
                    ],
                ],
            ],
            'required' => ['candidates'],
        ];

        return $this->gemini->generateJson($systemInstruction, $userPrompt, $schema);
    }
}
