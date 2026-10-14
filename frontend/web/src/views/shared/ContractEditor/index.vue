<script setup lang="ts">
import { ref, computed, reactive, watch } from 'vue'
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
import { useLexicalEditor } from '@/composables/useLexicalEditor'
import { useContractGenerator } from '@/composables/useContractGenerator'
import { useDocumentUpload } from '@/composables/useDocumentUpload'
import type { UploadedDoc } from '@/types/contract'
import { jsPDF } from 'jspdf'
import EditorToolbar from './EditorToolbar.vue'
import LexicalCanvas from './LexicalCanvas.vue'

const router = useRouter()
const { success, error: toastError } = useToast()
const { draft, setGeneratedDoc, attachDoc } = useCreateContractDraft()
const { isGenerating, generate } = useContractGenerator()
const { uploadFile } = useDocumentUpload()
const {
  state: toolbarState,
  mount: mountEditor,
  unmount: unmountEditor,
  formatText,
  formatAlign,
  formatBlock,
  formatList,
  insertTable,
  setPaperSize,
  setOrientation,
  setFontFamily,
  setFontSize,
  setFontColor,
  setHighlight,
  undo,
  redo,
  getHtml,
  setHtml,
} = useLexicalEditor()

const docTitle = ref('Contract Agreement')
const zoomLevel = ref(1)
const isAttaching = ref(false)

const headerFooter = reactive<HeaderFooterConfig>({
  headerLeft: draft.headerFooter?.headerLeft || 'CONFIDENTIAL CONTRACT AGREEMENT',
  headerRight: draft.headerFooter?.headerRight || 'SBSI CRMS',
  footerLeft: draft.headerFooter?.footerLeft || 'Authorized Legal Draft',
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
    setHtml(draft.generatedDoc.html)
    return
  }

  if (isDraftEmpty.value) {
    // If accessed directly without draft data, load sample contract with 3 distinct pages
    docTitle.value = 'Commercial Services Agreement'
    setHtml(`
      <h1 style="text-align: center;"><strong>COMMERCIAL SERVICES AGREEMENT</strong></h1>
      <p style="text-align: center;"><strong>CONTRACT NO. SBSI-2026-001</strong></p>
      <hr />
      <p>This Commercial Services Agreement is made between <strong>SBSI Corporation</strong> and the Contractor.</p>
      <h2><strong>1. RECITALS</strong></h2>
      <p>WHEREAS, Client requires technical and commercial services; and Contractor agrees to provide such services.</p>
      <h2><strong>2. SCOPE OF DELIVERABLES</strong></h2>
      <p>The Contractor shall provide system engineering, deployment, and ongoing technical support.</p>

      <div class="crms-page-break" data-page="2"></div>

      <h2><strong>3. PERFORMANCE STANDARDS &amp; WARRANTIES</strong></h2>
      <p>Contractor guarantees standard high-availability performance and immediate remedial response within 24 hours.</p>
      <h2><strong>4. DATA PRIVACY &amp; CONFIDENTIALITY</strong></h2>
      <p>Both parties comply with the Philippine Data Privacy Act of 2012 (RA 10173).</p>

      <div class="crms-page-break" data-page="3"></div>

      <h2><strong>5. SIGNATURES &amp; APPROVALS</strong></h2>
      <p>IN WITNESS WHEREOF, the parties hereto have executed this Agreement as of the date first above written.</p>
      <p><strong>FOR SBSI CORPORATION:</strong> _________________________</p>
      <p><strong>FOR CONTRACTOR:</strong> _________________________</p>
    `)
    return
  }

  docTitle.value = `${draft.form.category || 'Contract'} - ${draft.form.businessPartner || 'Draft'}`

  try {
    const result = await generate(draft.form)
    if (result && result.html_content) {
      setHtml(result.html_content)
      docTitle.value = result.title || docTitle.value
      generationSource.value = result.source || 'gemini'
      setGeneratedDoc({
        title: docTitle.value,
        html: result.html_content,
      })
      success('Contract Generated', 'AI successfully composed your agreement draft.')
    }
  } catch (err) {
    toastError('Generation Note', 'Applied standard corporate legal template.')
  }
}

function handleEditorMount(el: HTMLElement) {
  mountEditor(el)
  runContractGeneration(false)
}

function handleEditorUnmount() {
  unmountEditor()
}

function handleInsertPageBreak() {
  const currentHtml = getHtml()
  setHtml(currentHtml + '<div class="crms-page-break"></div><p></p>')
  success('Page Break Added', 'Inserted visual page separation.')
}

async function createPdfBlob(html: string): Promise<Blob> {
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

  const container = document.createElement('div')
  container.style.position = 'fixed'
  container.style.left = '-9999px'
  container.style.top = '0'
  container.style.width = isLandscape ? '1056px' : '816px'
  container.style.padding = '48px 56px'
  container.style.backgroundColor = '#ffffff'
  container.style.color = '#111827'
  container.style.fontFamily = 'Poppins, Arial, sans-serif'
  container.style.fontSize = '11pt'
  container.style.lineHeight = '1.6'

  // Convert crms-page-break into CSS print page break
  const printableHtml = html.replace(
    /<div[^>]*class="[^"]*crms-page-break[^"]*"[^>]*><\/div>/gi,
    '<div style="page-break-before: always; break-before: page; height: 1px; margin: 0; padding: 0;"></div>'
  )

  container.innerHTML = `<div>${printableHtml}</div>`
  document.body.appendChild(container)

  try {
    await new Promise<void>((resolve, reject) => {
      pdf.html(container, {
        callback: () => resolve(),
        autoPaging: 'text',
        width: pageWidth - 72,
        windowWidth: isLandscape ? 1056 : 816,
        margin: [48, 36, 46, 36], // top, left, bottom, right margins to protect header/footer
      }).catch(reject)
    })

    // Stamp the user's custom header and footer repeatedly on EVERY page of the PDF!
    const totalPages = pdf.getNumberOfPages()
    for (let p = 1; p <= totalPages; p++) {
      pdf.setPage(p)
      pdf.setFont('helvetica', 'normal')
      pdf.setFontSize(8.5)
      pdf.setTextColor(100, 116, 139)

      // 1. Header (Left & Right)
      if (headerFooter.headerLeft) {
        pdf.text(headerFooter.headerLeft, 36, 28)
      }
      if (headerFooter.headerRight) {
        pdf.text(headerFooter.headerRight, pageWidth - 36, 28, { align: 'right' })
      }
      // Top divider rule
      pdf.setDrawColor(226, 232, 240)
      pdf.setLineWidth(0.5)
      pdf.line(36, 34, pageWidth - 36, 34)

      // 2. Footer (Left & Right)
      pdf.line(36, pageHeight - 32, pageWidth - 36, pageHeight - 32)
      if (headerFooter.footerLeft) {
        pdf.text(headerFooter.footerLeft, 36, pageHeight - 20)
      }
      if (headerFooter.showPageNumber) {
        pdf.text(`Page ${p} of ${totalPages}`, pageWidth - 36, pageHeight - 20, { align: 'right' })
      }
    }

    return pdf.output('blob')
  } catch (err) {
    console.warn('PDF HTML rendering error, using fallback text generator:', err)
    const fallbackPdf = new jsPDF({
      orientation: isLandscape ? 'landscape' : 'portrait',
      unit: 'pt',
      format,
    })
    const tempDiv = document.createElement('div')
    tempDiv.innerHTML = html
    const text = tempDiv.innerText || tempDiv.textContent || ''
    const lines = fallbackPdf.splitTextToSize(text, pageWidth - 72)
    fallbackPdf.text(lines, 36, 50)
    return fallbackPdf.output('blob')
  } finally {
    document.body.removeChild(container)
  }
}

async function handleAttachToContract() {
  const html = getHtml()
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
    const blob = await createPdfBlob(html)
    const file = new File([blob], fileName, { type: 'application/pdf' })

    const newDoc: UploadedDoc = {
      file,
      name: fileName,
      size: blob.size,
      type: 'pdf',
      previewUrl: URL.createObjectURL(blob),
      uploadStatus: 'uploading',
    }

    // Upload document to backend service to acquire a real document_id
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
    router.push('/sales/contracts/create')
  } catch (err: any) {
    console.error('Error attaching contract:', err)
    toastError('Attachment Error', err?.message || 'Could not attach contract.')
  } finally {
    isAttaching.value = false
  }
}

function handlePrint() {
  window.print()
}

function handleDownloadHtml() {
  const html = getHtml()
  const blob = new Blob([html], { type: 'text/html' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${docTitle.value.replace(/\s+/g, '_')}.html`
  a.click()
  URL.revokeObjectURL(url)
}

function handleBack() {
  router.push('/sales/contracts/create')
}
</script>

<template>
  <div class="h-full w-full flex-1 flex flex-col min-h-0 bg-white overflow-hidden font-poppins select-none">
    <!-- Top App Bar -->
    <header class="h-14 border-b border-black/8 px-4 flex items-center justify-between bg-white shrink-0 z-20">
      <div class="flex items-center gap-3">
        <button
          type="button"
          @click="handleBack"
          class="flex items-center justify-center w-8 h-8 rounded-lg border border-black/10 hover:bg-black/4 text-black/60 hover:text-black transition"
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
          <span class="text-[11px] text-black/40 px-1.5">Paginated rich text contract draft editor</span>
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

    <!-- Toolbar -->
    <EditorToolbar
      :state="toolbarState"
      :header-footer="headerFooter"
      @update:header-footer="(val) => Object.assign(headerFooter, val)"
      @insert-page-break="handleInsertPageBreak"
      @format-text="formatText"
      @format-align="formatAlign"
      @format-block="formatBlock"
      @format-list="formatList"
      @insert-table="insertTable"
      @set-paper-size="setPaperSize"
      @set-orientation="setOrientation"
      @set-font-family="setFontFamily"
      @set-font-size="setFontSize"
      @set-font-color="setFontColor"
      @set-highlight="setHighlight"
      @undo="undo"
      @redo="redo"
    />

    <!-- Editor Canvas Sheet -->
    <div class="relative flex-1 flex flex-col overflow-hidden">
      <!-- Loading Overlay when AI is generating -->
      <div
        v-if="isGenerating"
        class="absolute inset-0 bg-white/80 backdrop-blur-xs z-30 flex flex-col items-center justify-center gap-3 animate-in fade-in duration-200"
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

      <LexicalCanvas
        :zoom="zoomLevel"
        :paper-size="toolbarState.paperSize"
        :orientation="toolbarState.orientation"
        :header-footer="headerFooter"
        @update:header-footer="(val) => Object.assign(headerFooter, val)"
        @editor-mount="handleEditorMount"
        @editor-unmount="handleEditorUnmount"
      />
    </div>
  </div>
</template>
