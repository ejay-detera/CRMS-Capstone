<?php

namespace App\Http\Controllers;

use App\Services\Gemini\GeminiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContractGenerationController extends Controller
{
    public function __construct(protected GeminiClient $geminiClient)
    {
    }

    /**
     * POST /api/contracts/generate
     * Generates a tailored corporate contract draft based on provided contract metadata.
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_partner' => 'required|string|max:255',
            'category'         => 'required|string|max:100',
            'item_code'        => 'required|string|max:50',
            'description'      => 'required|string|max:1000',
            'serial_number'    => 'required|string|max:50',
            'sbu_number'       => 'required|string|max:50',
            'region'           => 'required|string|max:50',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after:start_date',
            'prs_activity_id'  => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed for contract parameters.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $systemInstruction = "You are a senior corporate legal counsel specializing in commercial procurement and contract drafting in the Philippines.
Your objective is to generate a comprehensive, formal, and enforceable commercial agreement between 'SBSI' (First Party / Client) and the designated Business Partner (Second Party / Contractor).
The output must be formatted with structured sections, professional legal phrasing, and clean HTML suitable for a rich text editor.
Ensure references to Philippine Law, Republic Acts, and dispute resolution venues in Metro Manila.
Every contract must clearly articulate:
1. Title and Preamble
2. Recitals (Whereas clauses)
3. Scope of Services & Deliverables (explicitly mentioning Item Code, Description, Serial Number, SBU Number, and Region)
4. Contract Term & Duration (Start Date to End Date)
5. Service Standards, Warranties & Compliance
6. Compensation, Billing & Payment Terms
7. Confidentiality & Data Privacy Act (RA 10173)
8. Termination & Default
9. Governing Law & Dispute Resolution
10. Signatures and Attestation";

        $userPrompt = "Please generate a complete {$data['category']} with the following specifications:
- Business Partner / Contractor: {$data['business_partner']}
- Category / Type: {$data['category']}
- Item / Service Code: {$data['item_code']}
- Description: {$data['description']}
- Serial Number: {$data['serial_number']}
- Strategic Business Unit (SBU): {$data['sbu_number']}
- Region of Execution / Delivery: {$data['region']} (Philippines)
- Effective Date (Start Date): {$data['start_date']}
- Expiration Date (End Date): {$data['end_date']}
" . (!empty($data['prs_activity_id']) ? "- Associated PRS Activity ID: {$data['prs_activity_id']}\n" : "");

        $responseSchema = [
            'type' => 'OBJECT',
            'properties' => [
                'title' => [
                    'type' => 'STRING',
                    'description' => 'Official capitalized title of the agreement.',
                ],
                'summary' => [
                    'type' => 'STRING',
                    'description' => 'A brief 1-2 sentence executive summary of the agreement.',
                ],
                'html_content' => [
                    'type' => 'STRING',
                    'description' => 'Full structured contract text formatted in clean HTML (h1, h2, h3, p, ol, ul, li, strong).',
                ],
            ],
            'required' => ['title', 'summary', 'html_content'],
        ];

        try {
            $geminiResponse = $this->geminiClient->generateJson($systemInstruction, $userPrompt, $responseSchema);

            if ($geminiResponse && !empty($geminiResponse['html_content'])) {
                return response()->json([
                    'status' => 'success',
                    'source' => 'gemini',
                    'data'   => [
                        'title'        => $geminiResponse['title'] ?? ($data['category'] . ' - ' . $data['business_partner']),
                        'summary'      => $geminiResponse['summary'] ?? '',
                        'html_content' => $geminiResponse['html_content'],
                        'metadata'     => $data,
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Gemini contract generation encountered exception, using fallback generator.', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback: Generate a high quality legal template deterministically
        $fallback = $this->buildFallbackContract($data);

        return response()->json([
            'status' => 'success',
            'source' => 'template_engine',
            'data'   => [
                'title'        => $fallback['title'],
                'summary'      => $fallback['summary'],
                'html_content' => $fallback['html_content'],
                'metadata'     => $data,
            ],
        ]);
    }

    /**
     * Fallback template builder producing a complete, formatted Philippine legal contract.
     */
    protected function buildFallbackContract(array $data): array
    {
        $partner     = htmlspecialchars($data['business_partner']);
        $category    = htmlspecialchars($data['category']);
        $itemCode    = htmlspecialchars($data['item_code']);
        $desc        = htmlspecialchars($data['description']);
        $serialNo    = htmlspecialchars($data['serial_number']);
        $sbuNo       = htmlspecialchars($data['sbu_number']);
        $region      = htmlspecialchars($data['region']);
        $startDate   = htmlspecialchars($data['start_date']);
        $endDate     = htmlspecialchars($data['end_date']);
        $contractTitle = strtoupper("{$category} AGREEMENT");

        $html = <<<HTML
<h1 style="text-align: center;"><strong>{$contractTitle}</strong></h1>
<p style="text-align: center;"><strong>CONTRACT NO. SBSI-{$sbuNo}-{$itemCode}</strong></p>
<hr />
<p>This <strong>{$category}</strong> (the &ldquo;Agreement&rdquo;) is entered into and made effective as of <strong>{$startDate}</strong>, by and between:</p>

<p><strong>SBSI CORPORATION</strong>, a corporation duly organized and existing under the laws of the Republic of the Philippines, having its principal business address in the National Capital Region (hereinafter referred to as the &ldquo;First Party&rdquo; or &ldquo;Client&rdquo;);</p>

<p style="text-align: center;">- and -</p>

<p><strong>{$partner}</strong>, a business entity duly organized and licensed to conduct business under the laws of the Republic of the Philippines, operating in <strong>{$region}</strong> (hereinafter referred to as the &ldquo;Second Party&rdquo; or &ldquo;Contractor&rdquo;).</p>

<p>The First Party and the Second Party may collectively be referred to as the &ldquo;Parties&rdquo; and individually as a &ldquo;Party&rdquo;.</p>

<h2><strong>1. RECITALS</strong></h2>
<p><strong>WHEREAS</strong>, the First Party requires professional and specialized deliverables pertaining to <em>{$desc}</em> for its Strategic Business Unit <strong>{$sbuNo}</strong>;</p>
<p><strong>WHEREAS</strong>, the Second Party has represented that it possesses the requisite expertise, personnel, technical capability, and resources to supply and execute said requirements in accordance with corporate standards;</p>
<p><strong>NOW, THEREFORE</strong>, in consideration of the mutual covenants and premises set forth herein, the Parties agree as follows:</p>

<h2><strong>2. SCOPE OF DELIVERABLES &amp; SPECIFICATIONS</strong></h2>
<p>The Second Party agrees to provide, deliver, and maintain the following items and associated services under this Agreement:</p>
<ul>
    <li><strong>Designated Deliverable:</strong> {$desc}</li>
    <li><strong>Standard Item Code:</strong> {$itemCode}</li>
    <li><strong>Registered Serial Identifier:</strong> {$serialNo}</li>
    <li><strong>Allocated Strategic Business Unit:</strong> {$sbuNo}</li>
    <li><strong>Operational Region / Site:</strong> {$region}, Philippines</li>
</ul>
<p>All deliverables must adhere to standard quality benchmarks and manufacturer technical specifications agreed upon by both Parties.</p>

<h2><strong>3. TERM &amp; DURATION</strong></h2>
<p>This Agreement shall commence on <strong>{$startDate}</strong> (the &ldquo;Effective Date&rdquo;) and shall remain in full force and effect until <strong>{$endDate}</strong>, unless earlier terminated in accordance with the provisions hereof or extended by mutual written consent of both Parties.</p>

<h2><strong>4. OBLIGATIONS &amp; PERFORMANCE STANDARDS</strong></h2>
<ol>
    <li>The Second Party shall perform all obligations with utmost diligence, highest professional standard, and in strict compliance with applicable regulatory laws.</li>
    <li>The First Party reserves the right to inspect, verify, and validate all deliverables at any time during the execution period.</li>
    <li>Any defective deliverable or non-compliant service under Item Code <strong>{$itemCode}</strong> shall be rectified within seven (7) business days upon written notice from the First Party.</li>
</ol>

<h2><strong>5. CONFIDENTIALITY &amp; DATA PRIVACY</strong></h2>
<p>Both Parties agree to maintain strict confidentiality regarding all proprietary information, trade secrets, pricing, and operational data disclosed under this Agreement, in accordance with the <strong>Philippine Data Privacy Act of 2012 (Republic Act No. 10173)</strong>.</p>

<h2><strong>6. TERMINATION</strong></h2>
<p>Either Party may terminate this Agreement upon thirty (30) days prior written notice in the event of a material breach by the other Party, provided such breach remains uncured during the notice period.</p>

<h2><strong>7. GOVERNING LAW &amp; DISPUTE RESOLUTION</strong></h2>
<p>This Agreement shall be governed by and construed in accordance with the laws of the Republic of the Philippines. Any legal action or dispute arising from this Agreement shall be brought exclusively before the proper courts of Pasig City / Metro Manila, Philippines.</p>

<h2><strong>8. EXECUTION &amp; SIGNATURES</strong></h2>
<p>IN WITNESS WHEREOF, the Parties hereto have caused this Agreement to be executed by their duly authorized representatives as of the date first above written.</p>

<p><strong>FOR FIRST PARTY (SBSI CORPORATION):</strong><br />
____________________________________________<br />
<strong>AUTHORIZED REPRESENTATIVE</strong><br />
SBSI Corporation<br />
Date: ________________________</p>

<p><strong>FOR SECOND PARTY (CONTRACTOR):</strong><br />
____________________________________________<br />
<strong>AUTHORIZED REPRESENTATIVE</strong><br />
{$partner}<br />
Date: ________________________</p>
HTML;

        return [
            'title'        => "{$category} - {$data['business_partner']}",
            'summary'      => "Formal {$category} for {$data['business_partner']} regarding {$data['description']} in {$data['region']}.",
            'html_content' => $html,
        ];
    }
}
