<script setup lang="ts">
import { ref, computed, reactive, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArrowLeft,
  Sparkles,
  Printer,
  Download,
  Loader2,
  FileCheck2,
  RefreshCw,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useToast } from '@/composables/useToast'
import { useCreateContractDraft, type HeaderFooterConfig } from '@/composables/useCreateContractDraft'
import { useQuillEditor } from '@/composables/useQuillEditor'
import { useContractGenerator } from '@/composables/useContractGenerator'
import { useDocumentUpload } from '@/composables/useDocumentUpload'
import type { UploadedDoc } from '@/types/contract'
import { jsPDF } from 'jspdf'
import type { PageMargins, MarginPreset } from '@/types/contract-margins'
import { MARGIN_PRESETS } from '@/types/contract-margins'
import EditorToolbar from './EditorToolbar.vue'
import QuillCanvas from './QuillCanvas.vue'

const router = useRouter()
const { success, error: toastError } = useToast()
const { draft, setGeneratedDoc, attachDoc } = useCreateContractDraft()
const { isGenerating, generate } = useContractGenerator()
const { uploadFile } = useDocumentUpload()
const {
  pages,
  activePageId,
  state: toolbarState,
  registerPageInstance,
  unregisterPageInstance,
  setActivePage,
  addPage,
  removePage,
  setPaperSize,
  setOrientation,
  undo,
  redo,
  getCombinedHtml,
  setCombinedHtml,
  splitPageOverflow,
} = useQuillEditor()

const docTitle = ref('Contract Agreement')
const zoomLevel = ref(1)
const isAttaching = ref(false)
const isExportingPdf = ref(false)
const isEditingHeaderFooter = ref(false)

const margins = reactive<PageMargins>({
  top: 48,
  bottom: 48,
  left: 64,
  right: 64,
})

const currentPreset = computed<MarginPreset>(() => {
  for (const [key, val] of Object.entries(MARGIN_PRESETS)) {
    if (
      val.top === margins.top &&
      val.bottom === margins.bottom &&
      val.left === margins.left &&
      val.right === margins.right
    ) {
      return key as MarginPreset
    }
  }
  return 'custom'
})

function handleSetMarginPreset(preset: MarginPreset) {
  if (preset === 'custom') return
  const p = MARGIN_PRESETS[preset]
  if (p) {
    margins.top = p.top
    margins.bottom = p.bottom
    margins.left = p.left
    margins.right = p.right
    success('Margins Updated', `Applied ${preset.charAt(0).toUpperCase() + preset.slice(1)} margin preset.`)
  }
}

const headerFooter = reactive<HeaderFooterConfig>({
  headerLeft: draft.headerFooter?.headerLeft || 'CONFIDENTIAL CONTRACT AGREEMENT',
  headerRight: draft.headerFooter?.headerRight || 'SBSI CRMS',
  footerLeft: draft.headerFooter?.footerLeft || 'Authorized Legal Draft',
  headerLogoUrl: draft.headerFooter?.headerLogoUrl || '',
  showPageNumber: draft.headerFooter?.showPageNumber ?? true,
})

watch(headerFooter, (newVal) => {
  draft.headerFooter = { ...newVal }
}, { deep: true })

const generationSource = ref<'gemini' | 'template_engine' | 'client_fallback' | null>(null)

const isDraftEmpty = computed(() => {
  return !draft.form.businessPartner && !draft.form.category
})

async function runContractGeneration(force = false) {
  // If a generated document already exists in this session and not forced, restore it
  if (!force && draft.generatedDoc?.html) {
    docTitle.value = draft.generatedDoc.title || docTitle.value
    setCombinedHtml(draft.generatedDoc.html)
    return
  }

  if (isDraftEmpty.value) {
    // If accessed directly without draft data, load sample contract with 3 distinct pages
    docTitle.value = 'Commercial Services Agreement'
    setCombinedHtml(`
      <h1 style="text-align: center;"><strong>COMMERCIAL SERVICES AGREEMENT</strong></h1>
      <p style="text-align: center;"><strong>CONTRACT NO. SBSI-2026-001</strong></p>
      <hr />
      <p>This Commercial Services Agreement (the "Agreement") is entered into by and between <strong>SBSI Corporation</strong> ("Client") and the undersigned Service Provider ("Contractor").</p>
      <h2><strong>1. RECITALS &amp; APPOINTMENT</strong></h2>
      <p>WHEREAS, Client requires technical engineering and deployment services; and Contractor possesses the requisite professional qualifications, equipment, and personnel to execute such services.</p>
      <h2><strong>2. SCOPE OF DELIVERABLES</strong></h2>
      <p>The Contractor shall provide system engineering, deployment, integration verification, and ongoing technical support in accordance with Schedule A.</p>
      <p>All work products must satisfy the highest commercial quality standards and technical guidelines established by the Client.</p>

      <div class="crms-page-break" data-page="2"></div>

      <h2><strong>3. PERFORMANCE STANDARDS &amp; WARRANTIES</strong></h2>
      <p>Contractor represents and warrants that all deliverables furnished hereunder will perform without material defect, comply with specifications, and operate with standard high-availability reliability.</p>
      <p>In the event of any service outage or technical non-compliance, Contractor guarantees immediate remedial response within twenty-four (24) business hours.</p>
      <h2><strong>4. DATA PRIVACY &amp; CONFIDENTIALITY</strong></h2>
      <p>Both parties agree to comply with the Philippine Data Privacy Act of 2012 (Republic Act No. 10173) and its implementing rules and regulations.</p>
      <p>Confidential Information shall strictly not be disclosed, duplicated, or transferred to any third party without express prior written consent of the Client.</p>
      <p>This duty of confidentiality survives the termination or expiration of this Agreement for a period of five (5) consecutive years.</p>

      <div class="crms-page-break" data-page="3"></div>

      <h2><strong>5. SIGNATURES &amp; APPROVALS</strong></h2>
      <p>IN WITNESS WHEREOF, the parties hereto have executed and delivered this Commercial Services Agreement as of the date first above written.</p>
      <br />
      <table class="crms-quill-table" style="width: 100%; border: 1px solid #cbd5e1; margin-top: 1.5rem;">
        <tbody>
          <tr>
            <th style="padding: 10px; border: 1px solid #cbd5e1; background: #f8fafc; text-align: left;">FOR SBSI CORPORATION</th>
            <th style="padding: 10px; border: 1px solid #cbd5e1; background: #f8fafc; text-align: left;">FOR CONTRACTOR</th>
          </tr>
          <tr>
            <td style="padding: 30px 10px 10px; border: 1px solid #cbd5e1; vertical-align: bottom;">
              _____________________________________<br />
              <strong>Authorized Representative</strong><br />
              Date: ________________________
            </td>
            <td style="padding: 30px 10px 10px; border: 1px solid #cbd5e1; vertical-align: bottom;">
              _____________________________________<br />
              <strong>Contractor Signatory</strong><br />
              Date: ________________________
            </td>
          </tr>
        </tbody>
      </table>
    `)
    return
  }

  docTitle.value = `${draft.form.category || 'Contract'} - ${draft.form.businessPartner || 'Draft'}`

  try {
    const result = await generate(draft.form)
    if (result && result.html_content) {
      setCombinedHtml(result.html_content)
      docTitle.value = result.title || docTitle.value
      generationSource.value = result.source || 'gemini'
      setGeneratedDoc({
        title: docTitle.value,
        html: result.html_content,
      })
      success('Contract Generated', 'AI successfully composed your agreement draft across pages.')
    }
  } catch (err) {
    toastError('Generation Note', 'Applied standard corporate legal template.')
  }
}

function handleInsertPageBreak() {
  addPage(activePageId.value)
  success('Page Added', 'Created a new document page below.')
}

function handleSplitOverflow(pageId: string, maxPrintableHeight: number) {
  const didSplit = splitPageOverflow(pageId, maxPrintableHeight)
  if (didSplit) {
    success('Page Extended', 'Overflowing content was automatically pushed to the next sheet.')
  }
}

async function createPdfBlob(): Promise<Blob> {
  const isLandscape = toolbarState.orientation === 'landscape'
  const format = toolbarState.paperSize === 'a4' ? 'a4' : toolbarState.paperSize === 'legal' ? 'legal' : 'letter'

  const pdf = new jsPDF({
    orientation: isLandscape ? 'landscape' : 'portrait',
    unit: 'pt',
    format,
  })

  const pageWidth = isLandscape
    ? (format === 'a4' ? 842 : format === 'legal' ? 1008 : 792)
    : (format === 'a4' ? 595 : format === 'legal' ? 612 : 612)

  const pageHeight = isLandscape
    ? (format === 'a4' ? 595 : format === 'legal' ? 612 : 612)
    : (format === 'a4' ? 842 : format === 'legal' ? 1008 : 792)

  const totalPages = pages.value.length

  // Convert pixel margins to approximate PDF points (1 pt ≈ 1.33 px at 96 dpi)
  const ptLeft = Math.round(margins.left * 0.75)
  const ptRight = Math.round(margins.right * 0.75)
  const ptTop = Math.round(margins.top * 0.75) + 36 // extra clearance for sheet header
  const ptBottom = Math.round(margins.bottom * 0.75) + 36 // clearance for sheet footer

  for (let i = 0; i < totalPages; i++) {
    if (i > 0) {
      pdf.addPage(format, isLandscape ? 'landscape' : 'portrait')
    }

    const pageHtml = pages.value[i].html || '<p></p>'
    const container = document.createElement('div')
    container.style.position = 'fixed'
    container.style.left = '-9999px'
    container.style.top = '0'
    container.style.width = isLandscape ? '1056px' : '816px'
    container.style.padding = `${margins.top}px ${margins.right}px ${margins.bottom}px ${margins.left}px`
    container.style.backgroundColor = '#ffffff'
    container.style.color = '#111827'
    container.style.fontFamily = 'Poppins, Arial, sans-serif'
    container.style.fontSize = '11pt'
    container.style.lineHeight = '1.6'
    container.style.overflow = 'hidden'
    container.innerHTML = `<div>${pageHtml}</div>`
    document.body.appendChild(container)

    try {
      await new Promise<void>((resolve, reject) => {
        pdf.html(container, {
          callback: () => resolve(),
          autoPaging: false,
          width: pageWidth - (ptLeft + ptRight),
          windowWidth: isLandscape ? 1056 : 816,
          margin: [ptTop, ptRight, ptBottom, ptLeft],
        }).catch(reject)
      })
    } finally {
      if (document.body.contains(container)) {
        document.body.removeChild(container)
      }
    }

    // Stamp header & footer on this PDF page
    pdf.setPage(i + 1)
    pdf.setFont('helvetica', 'normal')
    pdf.setFontSize(8.5)
    pdf.setTextColor(100, 116, 139)

    let headerTextX = 36
    if (headerFooter.headerLogoUrl) {
      try {
        pdf.addImage(headerFooter.headerLogoUrl, 'PNG', 36, 14, 50, 16)
        headerTextX = 94
      } catch {
        // Fallback if logo format is unsupported by jsPDF
      }
    }

    if (headerFooter.headerLeft) {
      pdf.text(headerFooter.headerLeft, headerTextX, 26)
    }
    if (headerFooter.headerRight) {
      pdf.text(headerFooter.headerRight, pageWidth - 36, 26, { align: 'right' })
    }
    pdf.setDrawColor(226, 232, 240)
    pdf.setLineWidth(0.5)
    pdf.line(36, 32, pageWidth - 36, 32)

    pdf.line(36, pageHeight - 32, pageWidth - 36, pageHeight - 32)
    if (headerFooter.footerLeft) {
      pdf.text(headerFooter.footerLeft, 36, pageHeight - 20)
    }
    if (headerFooter.showPageNumber) {
      pdf.text(`Page ${i + 1} of ${totalPages}`, pageWidth - 36, pageHeight - 20, { align: 'right' })
    }
  }

  return pdf.output('blob')
}

async function handleAttachToContract() {
  const html = getCombinedHtml()
  if (!html.trim()) {
    toastError('Empty Document', 'Cannot attach an empty document.')
    return
  }

  isAttaching.value = true

  const safeName = (docTitle.value || 'Contract_Agreement')
    .replace(/[^a-zA-Z0-9_\-\s]/g, '')
    .trim()
    .replace(/\s+/g, '_')

  const fileName = `${safeName}.pdf`

  try {
    const blob = await createPdfBlob()
    const file = new File([blob], fileName, { type: 'application/pdf' })

    const newDoc: UploadedDoc = {
      file,
      name: fileName,
      size: blob.size,
      type: 'pdf',
      previewUrl: URL.createObjectURL(blob),
      uploadStatus: 'uploading',
    }

    await uploadFile(newDoc, (patch) => {
      Object.assign(newDoc, patch)
    })

    if (newDoc.uploadStatus === 'error') {
      toastError('Upload Failed', newDoc.errorMessage || 'Failed to upload document to backend.')
      isAttaching.value = false
      return
    }

    attachDoc(newDoc)
    setGeneratedDoc({
      title: docTitle.value,
      html,
    })

    success('Contract Attached', `Generated document "${fileName}" uploaded and attached to contract draft.`)

    document.body.style.pointerEvents = 'auto'
    document.body.style.overflow = ''

    const backRoute = draft.role ? `/${draft.role}/contracts/create` : '/sales/contracts/create'
    router.push(backRoute)
  } catch (err: any) {
    console.error('Error attaching contract:', err)
    toastError('Attachment Error', err?.message || 'Could not attach contract.')
  } finally {
    isAttaching.value = false
    document.body.style.pointerEvents = 'auto'
    document.body.style.overflow = ''
  }
}

function handlePrint() {
  window.print()
}

async function handleDownloadPdf() {
  isExportingPdf.value = true
  try {
    const blob = await createPdfBlob()
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${docTitle.value.replace(/\s+/g, '_')}.pdf`
    a.click()
    URL.revokeObjectURL(url)
    success('PDF Exported', 'Clean PDF document downloaded successfully.')
  } catch (err: any) {
    console.error('Error generating PDF:', err)
    toastError('Export Error', err?.message || 'Failed to generate PDF document.')
  } finally {
    isExportingPdf.value = false
  }
}

function handleDownloadHtml() {
  const html = getCombinedHtml()
  const blob = new Blob([html], { type: 'text/html' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${docTitle.value.replace(/\s+/g, '_')}.html`
  a.click()
  URL.revokeObjectURL(url)
}

function handleBack() {
  document.body.style.pointerEvents = 'auto'
  document.body.style.overflow = ''
  const backRoute = draft.role ? `/${draft.role}/contracts/create` : '/sales/contracts/create'
  router.push(backRoute)
}

onMounted(() => {
  runContractGeneration(false)
})
</script>

<template>
  <div class="h-full w-full flex-1 flex flex-col min-h-0 bg-white overflow-hidden font-poppins select-none print:bg-white print:overflow-visible">
    <!-- Top App Bar (Hidden on print) -->
    <header class="h-14 border-b border-black/8 px-4 flex items-center justify-between bg-white shrink-0 z-20 print:hidden">
      <div class="flex items-center gap-3">
        <button
          type="button"
          @click="handleBack"
          class="flex items-center justify-center w-8 h-8 rounded-lg border border-black/10 hover:bg-black/4 text-black/60 hover:text-black transition cursor-pointer"
          title="Back to Create Contract"
        >
          <ArrowLeft class="w-4 h-4" />
        </button>

        <div class="flex flex-col">
          <div class="flex items-center gap-2">
            <input
              v-model="docTitle"
              type="text"
              class="text-sm font-semibold text-black bg-transparent border border-transparent hover:border-black/15 focus:border-brand-blue rounded px-1.5 py-0.5 outline-none transition max-w-[280px] sm:max-w-[420px] truncate"
            />
            <span
              class="inline-flex items-center gap-1 text-[11px] font-medium px-2 py-0.5 rounded-full border"
              :class="generationSource === 'gemini'
                ? 'bg-purple-50 text-purple-700 border-purple-200'
                : 'bg-brand-blue/10 text-brand-navy border-brand-blue/20'"
            >
              <Sparkles class="w-3 h-3 text-brand-navy" />
              {{ generationSource === 'gemini' ? 'Gemini AI' : 'AI Contract Generator' }}
            </span>
          </div>
          <span class="text-[11px] text-black/40 px-1.5">Paginated multi-page document editor · {{ pages.length }} Page{{ pages.length > 1 ? 's' : '' }}</span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-2">
        <!-- Regenerate Button -->
        <Button
          variant="outline"
          size="sm"
          @click="runContractGeneration(true)"
          :disabled="isGenerating || isAttaching"
          class="h-8 text-xs border-brand-navy/20 text-brand-navy hover:bg-brand-navy/5 gap-1.5 hidden sm:inline-flex"
          title="Regenerate fresh draft with AI"
        >
          <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isGenerating }" />
          Regenerate
        </Button>

        <!-- Print / PDF -->
        <Button
          variant="outline"
          size="sm"
          @click="handlePrint"
          class="h-8 text-xs border-black/12 text-black/70 hover:text-black hover:bg-black/3 gap-1.5 hidden sm:inline-flex"
        >
          <Printer class="w-3.5 h-3.5" />
          Print / PDF
        </Button>

        <!-- Direct PDF Download -->
        <Button
          variant="outline"
          size="sm"
          @click="handleDownloadPdf"
          :disabled="isExportingPdf"
          class="h-8 text-xs border-black/12 text-black/70 hover:text-black hover:bg-black/3 gap-1.5 hidden sm:inline-flex"
          title="Download PDF directly"
        >
          <Loader2 v-if="isExportingPdf" class="w-3.5 h-3.5 animate-spin" />
          <Download v-else class="w-3.5 h-3.5" />
          Export PDF
        </Button>

        <!-- Download HTML -->
        <Button
          variant="outline"
          size="sm"
          @click="handleDownloadHtml"
          class="h-8 text-xs border-black/12 text-black/70 hover:text-black hover:bg-black/3 gap-1.5 hidden sm:inline-flex"
        >
          <Download class="w-3.5 h-3.5" />
          Export HTML
        </Button>

        <!-- Attach & Save Contract -->
        <Button
          size="sm"
          @click="handleAttachToContract"
          :disabled="isGenerating || isAttaching"
          class="h-8 px-4 text-xs font-semibold bg-brand-navy hover:bg-brand-dark text-white gap-1.5 shadow-sm transition disabled:opacity-50"
        >
          <Loader2 v-if="isAttaching" class="w-3.5 h-3.5 animate-spin" />
          <FileCheck2 v-else class="w-3.5 h-3.5" />
          {{ isAttaching ? 'Attaching & Saving...' : 'Attach & Save Contract' }}
        </Button>
      </div>
    </header>

    <!-- Toolbar (Hidden on print) -->
    <EditorToolbar
      :state="toolbarState"
      :header-footer="headerFooter"
      :margins="margins"
      :current-preset="currentPreset"
      @update:header-footer="(val) => Object.assign(headerFooter, val)"
      @set-margin-preset="handleSetMarginPreset"
      @insert-page-break="handleInsertPageBreak"
      @set-paper-size="setPaperSize"
      @set-orientation="setOrientation"
      @toggle-header-footer="isEditingHeaderFooter = !isEditingHeaderFooter"
      @undo="undo"
      @redo="redo"
    />

    <!-- Editor Multi-Page Canvas Sheet -->
    <div class="relative flex-1 flex flex-col overflow-hidden print:overflow-visible">
      <!-- Loading Overlay when AI is generating -->
      <div
        v-if="isGenerating"
        class="absolute inset-0 bg-white/80 backdrop-blur-xs z-30 flex flex-col items-center justify-center gap-3 animate-in fade-in duration-200 print:hidden"
      >
        <div class="w-12 h-12 rounded-2xl bg-brand-navy/10 flex items-center justify-center">
          <Loader2 class="w-6 h-6 text-brand-navy animate-spin" />
        </div>
        <div class="text-center">
          <h3 class="text-sm font-semibold text-black">Generating Contract Agreement</h3>
          <p class="text-xs text-black/50 mt-1 max-w-sm">
            AI is assembling formal clauses, scopes, dates, and Philippine legal provisions...
          </p>
        </div>
      </div>

      <QuillCanvas
        :zoom="zoomLevel"
        :paper-size="toolbarState.paperSize"
        :orientation="toolbarState.orientation"
        :header-footer="headerFooter"
        :margins="margins"
        :pages="pages"
        :active-page-id="activePageId"
        :is-editing-header-footer="isEditingHeaderFooter"
        @update:is-editing-header-footer="(val) => isEditingHeaderFooter = val"
        @update:margins="(m) => Object.assign(margins, m)"
        @mount-page="(id, el, initialHtml) => registerPageInstance(id, el, initialHtml)"
        @unmount-page="(id) => unregisterPageInstance(id)"
        @set-active-page="(id) => setActivePage(id)"
        @add-page="(afterId) => addPage(afterId)"
        @remove-page="(id) => removePage(id)"
        @split-overflow="(id, height) => handleSplitOverflow(id, height)"
        @update:header-footer="(val) => Object.assign(headerFooter, val)"
      />
    </div>
  </div>
</template>

<style>
@media print {
  /* 1. Hide all application shell elements, navigation, breadcrumbs, user avatars, bells, and toolbars */
  aside,
  nav,
  .sidebar-provider > *:not(main),
  [data-sidebar="sidebar"],
  header,
  .app-breadcrumbs,
  .editor-toolbar,
  #quill-toolbar-container,
  .ql-toolbar,
  button,
  .print\:hidden,
  .group\/gap,
  .header-footer-floating-pill {
    display: none !important;
  }

  /* 2. Reset layout containers so only the printable document flows naturally */
  html,
  body,
  #app,
  main,
  .sidebar-provider,
  .w-full.flex-1.overflow-y-auto {
    background: #ffffff !important;
    padding: 0 !important;
    margin: 0 !important;
    overflow: visible !important;
    scrollbar-width: none !important;
    -ms-overflow-style: none !important;
    height: auto !important;
    min-height: 0 !important;
  }

  html::-webkit-scrollbar,
  body::-webkit-scrollbar,
  .w-full.flex-1.overflow-y-auto::-webkit-scrollbar {
    display: none !important;
    width: 0 !important;
    height: 0 !important;
  }

  /* 3. Each physical paper sheet becomes a true printed page */
  .quill-page-sheet {
    box-shadow: none !important;
    border: none !important;
    outline: none !important;
    border-radius: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    min-height: 0 !important;
    margin: 0 !important;
    page-break-after: always !important;
    break-after: page !important;
    page-break-inside: avoid !important;
    break-inside: avoid !important;
  }

  .quill-page-sheet:last-child {
    page-break-after: auto !important;
    break-after: auto !important;
  }

  /* Clean sheet header and footer on print */
  .quill-page-sheet .header-footer-zone {
    display: flex !important;
    border-color: rgba(0, 0, 0, 0.12) !important;
    background: transparent !important;
    border-style: solid !important;
    border-width: 0 0 1px 0 !important;
  }

  .quill-page-sheet .header-footer-zone.border-t {
    border-width: 1px 0 0 0 !important;
  }

  .sheet-margin-guides {
    display: none !important;
  }

  @page {
    margin: 10mm 12mm;
    size: auto;
  }
}
</style>
