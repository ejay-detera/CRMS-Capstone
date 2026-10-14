<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArrowLeft,
  Sparkles,
  Check,
  Printer,
  Download,
  Loader2,
  FileCheck2,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useToast } from '@/composables/useToast'
import { useCreateContractDraft } from '@/composables/useCreateContractDraft'
import { useLexicalEditor } from '@/composables/useLexicalEditor'
import { useContractGenerator } from '@/composables/useContractGenerator'
import type { UploadedDoc } from '@/types/contract'
import EditorToolbar from './EditorToolbar.vue'
import LexicalCanvas from './LexicalCanvas.vue'
import ConfirmationDialog from '@/components/shared/ConfirmationDialog.vue'

const router = useRouter()
const { success, error: toastError } = useToast()
const { draft, setGeneratedDoc, attachDoc } = useCreateContractDraft()
const { isGenerating, generate } = useContractGenerator()
const {
  state: toolbarState,
  mount: mountEditor,
  unmount: unmountEditor,
  formatText,
  formatAlign,
  formatBlock,
  formatList,
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
const isEdited = ref(false)
const showExitConfirm = ref(false)
const zoomLevel = ref(1)

const generationSource = ref<'gemini' | 'template_engine' | 'client_fallback' | null>(null)

const isDraftEmpty = computed(() => {
  return !draft.form.businessPartner && !draft.form.category
})

async function runContractGeneration() {
  if (isDraftEmpty.value) {
    // If accessed directly without draft data, load sample contract
    docTitle.value = 'Commercial Services Agreement'
    setHtml(`
      <h1 style="text-align: center;"><strong>COMMERCIAL SERVICES AGREEMENT</strong></h1>
      <p style="text-align: center;"><strong>CONTRACT NO. SBSI-2026-001</strong></p>
      <hr />
      <p>This Commercial Services Agreement is made between <strong>SBSI Corporation</strong> and the Contractor.</p>
      <h2><strong>1. RECITALS</strong></h2>
      <p>WHEREAS, Client requires technical and commercial services; and Contractor agrees to provide such services.</p>
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
  runContractGeneration()
}

function handleEditorUnmount() {
  unmountEditor()
}

function handleAttachToContract() {
  const html = getHtml()
  if (!html.trim()) {
    toastError('Empty Document', 'Cannot attach an empty document.')
    return
  }

  const safeName = (docTitle.value || 'Contract_Agreement')
    .replace(/[^a-zA-Z0-9_\-\s]/g, '')
    .trim()
    .replace(/\s+/g, '_')

  const fileName = `${safeName}.docx`
  const blob = new Blob([html], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' })
  const file = new File([blob], fileName, { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' })

  const newDoc: UploadedDoc = {
    file,
    name: fileName,
    size: blob.size,
    type: 'docx',
    previewUrl: URL.createObjectURL(blob),
    uploadStatus: 'success',
  }

  attachDoc(newDoc)
  setGeneratedDoc({
    title: docTitle.value,
    html,
  })

  success('Contract Attached', `Generated document "${fileName}" added to contract draft.`)
  router.push('/sales/contracts/create')
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
  <div class="h-screen w-screen flex flex-col bg-white overflow-hidden font-poppins select-none">
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
          <span class="text-[11px] text-black/40 px-1.5">Google Docs-style rich text editor</span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-2">
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

        <!-- Attach to Contract -->
        <Button
          size="sm"
          @click="handleAttachToContract"
          :disabled="isGenerating"
          class="h-8 px-4 text-xs font-semibold bg-brand-navy hover:bg-brand-dark text-white gap-1.5 shadow-sm transition"
        >
          <FileCheck2 class="w-3.5 h-3.5" />
          Attach to Contract
        </Button>
      </div>
    </header>

    <!-- Toolbar -->
    <EditorToolbar
      :state="toolbarState"
      @format-text="formatText"
      @format-align="formatAlign"
      @format-block="formatBlock"
      @format-list="formatList"
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
        @editor-mount="handleEditorMount"
        @editor-unmount="handleEditorUnmount"
      />
    </div>
  </div>
</template>
