import { ref } from 'vue'
import { useAuth } from './useAuth'

const BASE_URL = (import.meta.env.VITE_AI_API_URL as string) || 'http://localhost:8006/api'

export interface GenerateContractParams {
  businessPartner: string
  category: string
  itemCode: string
  description: string
  serialNo: string
  sbuNumber: string
  region: string
  startDate: string
  endDate: string
  prsActivityId?: number | ''
}

export interface GeneratedContractData {
  title: string
  summary: string
  html_content: string
  source?: 'gemini' | 'template_engine' | 'client_fallback'
}

export function useContractGenerator() {
  const isGenerating = ref(false)
  const error = ref<string | null>(null)
  const result = ref<GeneratedContractData | null>(null)

  async function generate(params: GenerateContractParams): Promise<GeneratedContractData> {
    isGenerating.value = true
    error.value = null

    const payload = {
      business_partner: params.businessPartner,
      category: params.category,
      item_code: params.itemCode,
      description: params.description,
      serial_number: params.serialNo,
      sbu_number: params.sbuNumber,
      region: params.region,
      start_date: params.startDate,
      end_date: params.endDate,
      prs_activity_id: params.prsActivityId ? Number(params.prsActivityId) : null,
    }

    try {
      const { state } = useAuth()
      const res = await fetch(`${BASE_URL}/contracts/generate`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${state.token}`,
        },
        body: JSON.stringify(payload),
      })

      const json = await res.json().catch(() => ({}))

      if (res.ok && json.data) {
        result.value = {
          title: json.data.title || `${params.category} - ${params.businessPartner}`,
          summary: json.data.summary || '',
          html_content: json.data.html_content || '',
          source: json.source || 'gemini',
        }
        return result.value
      }

      console.warn('Backend contract generation returned error, using client fallback:', json.message)
    } catch (err) {
      console.warn('Network issue reaching AI service, using client fallback generator:', err)
    } finally {
      isGenerating.value = false
    }

    // Client fallback to ensure user is never blocked
    const fallback = generateClientFallback(params)
    result.value = fallback
    return fallback
  }

  function generateClientFallback(params: GenerateContractParams): GeneratedContractData {
    const title = `${params.category.toUpperCase()} AGREEMENT`
    const html = `
<h1 style="text-align: center;"><strong>${title}</strong></h1>
<p style="text-align: center; color: #555555;"><strong>CONTRACT NO. SBSI-${params.sbuNumber}-${params.itemCode}</strong></p>
<hr />
<p>This <strong>${params.category}</strong> (the &ldquo;Agreement&rdquo;) is entered into and made effective as of <strong>${params.startDate}</strong>, by and between:</p>

<p><strong>SBSI CORPORATION</strong>, a corporation organized and existing under the laws of the Republic of the Philippines, having its principal office at Metro Manila, Philippines (hereinafter referred to as the &ldquo;First Party&rdquo; or &ldquo;Client&rdquo;);</p>

<p style="text-align: center;">- and -</p>

<p><strong>${params.businessPartner}</strong>, an authorized business entity operating in <strong>${params.region}</strong>, Philippines (hereinafter referred to as the &ldquo;Second Party&rdquo; or &ldquo;Contractor&rdquo;).</p>

<h2><strong>1. RECITALS</strong></h2>
<p><strong>WHEREAS</strong>, the First Party requires delivery, implementation, and servicing of <em>${params.description}</em> for Strategic Business Unit <strong>${params.sbuNumber}</strong>;</p>
<p><strong>WHEREAS</strong>, the Second Party possesses the requisite skill, technical qualifications, and equipment to fulfill these deliverables according to SBSI standards;</p>
<p><strong>NOW, THEREFORE</strong>, the Parties mutually agree to the terms set forth below:</p>

<h2><strong>2. SCOPE OF DELIVERABLES &amp; SPECIFICATIONS</strong></h2>
<p>The Second Party agrees to deliver, install, and support the following item:</p>
<ul>
    <li><strong>Deliverable Description:</strong> ${params.description}</li>
    <li><strong>Designated Item Code:</strong> ${params.itemCode}</li>
    <li><strong>Serial Identification:</strong> ${params.serialNo}</li>
    <li><strong>Assigned Strategic Unit:</strong> ${params.sbuNumber}</li>
    <li><strong>Operational Region:</strong> ${params.region}, Philippines</li>
</ul>

<h2><strong>3. CONTRACT DURATION &amp; TERM</strong></h2>
<p>This Agreement takes effect on <strong>${params.startDate}</strong> and remains effective until <strong>${params.endDate}</strong>, unless terminated earlier in accordance with Section 6.</p>

<h2><strong>4. PERFORMANCE STANDARDS &amp; WARRANTIES</strong></h2>
<ol>
    <li>The Second Party guarantees that all services under Item Code <strong>${params.itemCode}</strong> meet commercial standards of excellence.</li>
    <li>Any identified defects or operational discrepancies shall be remedied within seven (7) business days upon formal notification.</li>
</ol>

<h2><strong>5. DATA PRIVACY &amp; CONFIDENTIALITY</strong></h2>
<p>Both Parties shall protect proprietary trade secrets, customer data, and operational records in compliance with Republic Act No. 10173 (Philippine Data Privacy Act of 2012).</p>

<h2><strong>6. TERMINATION</strong></h2>
<p>Either Party may terminate this Agreement upon thirty (30) days prior written notice in the event of default or uncured breach.</p>

<h2><strong>7. GOVERNING LAW &amp; VENUE</strong></h2>
<p>This Agreement is governed by Philippine law. Any dispute shall be submitted to the competent courts of Metro Manila, Philippines.</p>

<br />
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
${params.businessPartner}<br />
Date: ________________________</p>
`
    return {
      title: `${params.category} - ${params.businessPartner}`,
      summary: `Contract agreement between SBSI and ${params.businessPartner} for ${params.description}.`,
      html_content: html,
      source: 'client_fallback',
    }
  }

  return {
    isGenerating,
    error,
    result,
    generate,
  }
}
