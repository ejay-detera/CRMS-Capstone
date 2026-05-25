<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, FileText, Download, ExternalLink, Loader2, Printer, Menu, ChevronLeft, ChevronRight, ZoomIn, ZoomOut, RotateCcw } from 'lucide-vue-next'
import { useApiCache } from '@/composables/useApiCache'
import { useAuth } from '@/composables/useAuth'
import VuePdfEmbed from 'vue-pdf-embed'

// Import styles for annotation and text layers
import 'vue-pdf-embed/dist/styles/annotationLayer.css'
import 'vue-pdf-embed/dist/styles/textLayer.css'

const route = useRoute()
const router = useRouter()
const { state: cacheState, fetchContracts } = useApiCache()
const { state: authState } = useAuth()

const contractId = route.params.id as string
const docId = route.params.docId as string

const loading = ref(true)
const loadingFile = ref(false)
const contract = ref<any>(null)
const pdfBlobUrl = ref('')

const pageCount = ref(1)
const currentPage = ref(1)
const pdfWidth = ref(800)
const showSidebar = ref(true)

function zoomIn() {
  if (pdfWidth.value < 1600) pdfWidth.value += 100
}

function zoomOut() {
  if (pdfWidth.value > 400) pdfWidth.value -= 100
}

function resetZoom() {
  pdfWidth.value = 800
}

function handlePdfLoaded(pdf: any) {
  pageCount.value = pdf.numPages || 1
}

function prevPage() {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

function nextPage() {
  if (currentPage.value < pageCount.value) {
    currentPage.value++
  }
}

function handlePageInput(e: Event) {
  const target = e.target as HTMLInputElement
  let val = parseInt(target.value, 10)
  if (isNaN(val)) return
  if (val < 1) val = 1
  if (val > pageCount.value) val = pageCount.value
  currentPage.value = val
  target.value = String(val)
}

const apiBase = import.meta.env.VITE_CONTRACT_API_URL as string

const backPath = computed(() => {
  if (route.path.startsWith('/admin')) return `/admin/contracts/${contractId}`
  if (route.path.startsWith('/manager')) return `/manager/contracts/${contractId}`
  return `/sales/contracts/${contractId}`
})

const document = computed(() => {
  if (!contract.value) return null
  return contract.value.docs.find((d: any) => String(d.id) === docId)
})

async function fetchFileBlob() {
  if (!docId) return
  loadingFile.value = true
  try {
    const res = await fetch(`${apiBase}/documents/${docId}/file`, {
      headers: {
        'Authorization': `Bearer ${authState.token}`,
        'Accept': 'application/json'
      }
    })
    if (res.ok) {
      const blob = await res.blob()
      if (pdfBlobUrl.value) {
        URL.revokeObjectURL(pdfBlobUrl.value)
      }
      pdfBlobUrl.value = URL.createObjectURL(blob)
    } else {
      console.error('Failed to fetch file from API')
    }
  } catch (err) {
    console.error('Error fetching file blob:', err)
  } finally {
    loadingFile.value = false
  }
}

onMounted(async () => {
  // Try to find in cache first
  const cached = cacheState.contracts?.find(c => String(c.id) === contractId)
  if (cached) {
    contract.value = cached
    loading.value = false
    await fetchFileBlob()
  }

  // Load fresh contract details
  try {
    const isAdmin = route.path.startsWith('/admin')
    const isManager = route.path.startsWith('/manager')
    const userId = (isAdmin || isManager) ? undefined : authState.user?.id
    await fetchContracts(userId, true) // force reload
    const fresh = cacheState.contracts?.find(c => String(c.id) === contractId)
    if (fresh) {
      contract.value = fresh
      await fetchFileBlob()
    }
  } catch (err) {
    console.error('Failed to load contract:', err)
  } finally {
    loading.value = false
  }
})

onUnmounted(() => {
  if (pdfBlobUrl.value) {
    URL.revokeObjectURL(pdfBlobUrl.value)
  }
})

function downloadFile() {
  if (!pdfBlobUrl.value) return

  const a = window.document.createElement('a')
  a.href = pdfBlobUrl.value
  a.download = document.value?.name || 'document'
  window.document.body.appendChild(a)
  a.click()
  window.document.body.removeChild(a)
}

function printFile() {
  if (!pdfBlobUrl.value) return

  const iframe = window.document.createElement('iframe')
  iframe.style.position = 'fixed'
  iframe.style.width = '0'
  iframe.style.height = '0'
  iframe.style.border = 'none'
  iframe.src = pdfBlobUrl.value

  window.document.body.appendChild(iframe)

  iframe.onload = () => {
    iframe.contentWindow?.focus()
    iframe.contentWindow?.print()
    setTimeout(() => {
      window.document.body.removeChild(iframe)
    }, 5000)
  }
}
</script>

<template>
  <div class="h-[calc(100vh-4rem)] flex flex-col bg-slate-50">
    <!-- Header -->
    <header class="h-16 border-b border-black/8 bg-white px-6 flex items-center justify-between shrink-0">
      <div class="flex items-center gap-4">
        <button @click="router.push(backPath)" 
          class="flex items-center justify-center w-8 h-8 rounded-full border border-black/8 hover:bg-black/2 transition text-black/60 hover:text-black">
          <ArrowLeft class="w-4 h-4" />
        </button>
        <div v-if="document" class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded bg-red-50 border border-red-100 flex items-center justify-center text-red-500">
            <FileText class="w-4.5 h-4.5" />
          </div>
          <div>
            <h1 class="text-sm font-semibold text-black leading-tight truncate max-w-md">{{ document.name }}</h1>
            <p class="text-[10px] text-black/35 mt-0.5">{{ (document.size / 1024 / 1024).toFixed(2) }} MB</p>
          </div>
        </div>
      </div>
      
      <div class="flex items-center gap-2" v-if="document">
        <button @click="printFile" :disabled="!pdfBlobUrl"
          class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-black/8 hover:bg-black/2 text-xs font-semibold text-black/60 hover:text-black transition disabled:opacity-50 disabled:cursor-not-allowed">
          <Printer class="w-3.5 h-3.5" />
          Print
        </button>
        <button @click="downloadFile" :disabled="!pdfBlobUrl"
          class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-black/8 hover:bg-black/2 text-xs font-semibold text-black/60 hover:text-black transition disabled:opacity-50 disabled:cursor-not-allowed">
          <Download class="w-3.5 h-3.5" />
          Download
        </button>
        <a :href="pdfBlobUrl" target="_blank" v-if="pdfBlobUrl"
          class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-[#2E85D8] hover:bg-[#252578] text-xs font-semibold text-white transition shadow-sm">
          <ExternalLink class="w-3.5 h-3.5" />
          Open in New Tab
        </a>
      </div>
    </header>

    <!-- Toolbar -->
    <div v-if="document && document.type === 'pdf'" class="h-12 border-b border-black/8 bg-white px-6 flex items-center justify-between shrink-0 shadow-sm z-10">
      <div class="flex items-center gap-3">
        <!-- Sidebar Toggle Button -->
        <button @click="showSidebar = !showSidebar" 
          class="flex items-center justify-center w-8 h-8 rounded-lg border border-black/8 hover:bg-black/2 transition text-black/60 hover:text-black"
          :class="showSidebar ? 'bg-blue-50 border-blue-200 text-[#2E85D8]' : ''"
          title="Toggle sidebar">
          <Menu class="w-4 h-4" />
        </button>
        <div class="h-4 w-px bg-black/10"></div>
        
        <!-- Page Navigation -->
        <div class="flex items-center gap-1.5">
          <button @click="prevPage" :disabled="currentPage <= 1"
            class="flex items-center justify-center w-7 h-7 rounded hover:bg-black/5 text-black/60 hover:text-black transition disabled:opacity-30 disabled:cursor-not-allowed">
            <ChevronLeft class="w-4 h-4" />
          </button>
          
          <div class="flex items-center gap-1">
            <input type="text" :value="currentPage" @change="handlePageInput"
              class="w-10 h-7 text-center text-xs font-semibold border border-black/10 rounded focus:border-[#2E85D8] focus:outline-none" />
            <span class="text-xs text-black/45 font-medium">/ {{ pageCount }}</span>
          </div>
          
          <button @click="nextPage" :disabled="currentPage >= pageCount"
            class="flex items-center justify-center w-7 h-7 rounded hover:bg-black/5 text-black/60 hover:text-black transition disabled:opacity-30 disabled:cursor-not-allowed">
            <ChevronRight class="w-4 h-4" />
          </button>
        </div>
      </div>
      
      <!-- Zoom Controls -->
      <div class="flex items-center gap-2">
        <button @click="zoomOut" :disabled="pdfWidth <= 400"
          class="flex items-center justify-center w-7 h-7 rounded hover:bg-black/5 text-black/60 hover:text-black transition disabled:opacity-30 disabled:cursor-not-allowed"
          title="Zoom out">
          <ZoomOut class="w-4 h-4" />
        </button>
        <span class="text-xs text-black/55 font-semibold select-none w-12 text-center">{{ Math.round((pdfWidth / 800) * 100) }}%</span>
        <button @click="zoomIn" :disabled="pdfWidth >= 1600"
          class="flex items-center justify-center w-7 h-7 rounded hover:bg-black/5 text-black/60 hover:text-black transition disabled:opacity-30 disabled:cursor-not-allowed"
          title="Zoom in">
          <ZoomIn class="w-4 h-4" />
        </button>
        <button @click="resetZoom"
          class="flex items-center justify-center w-7 h-7 rounded hover:bg-black/5 text-black/60 hover:text-black transition"
          title="Reset zoom">
          <RotateCcw class="w-3.5 h-3.5" />
        </button>
      </div>
    </div>

    <!-- Main Workspace -->
    <div class="flex-grow flex overflow-hidden bg-slate-100">
      <!-- Sidebar -->
      <aside v-if="showSidebar && document && document.type === 'pdf'" 
        class="w-60 border-r border-black/8 bg-white flex flex-col shrink-0 overflow-y-auto shadow-inner transition-all duration-300">
        <div class="p-3 border-b border-black/5 bg-slate-50 flex items-center justify-between">
          <span class="text-[10px] font-bold text-black/40 uppercase tracking-wider">Page Outline</span>
          <span class="text-[10px] font-semibold text-black/50 bg-black/5 px-1.5 py-0.5 rounded-full">{{ pageCount }} pages</span>
        </div>
        <div class="p-3 space-y-1">
          <div v-for="page in pageCount" :key="page"
            @click="currentPage = page"
            class="flex items-center gap-3 p-2 rounded-lg cursor-pointer transition select-none"
            :class="currentPage === page ? 'bg-blue-50 border border-blue-100 text-[#2E85D8]' : 'border border-transparent hover:bg-black/[0.02] text-black/60 hover:text-black'">
            <div class="w-7 h-9 rounded bg-white border shadow-sm flex items-center justify-center text-xs font-bold"
              :class="currentPage === page ? 'border-blue-300 text-[#2E85D8]' : 'border-black/10 text-black/40'">
              {{ page }}
            </div>
            <div class="flex flex-col">
              <span class="text-xs font-medium">Page {{ page }}</span>
            </div>
          </div>
        </div>
      </aside>

      <!-- Document Display Area -->
      <main class="flex-grow overflow-y-auto p-8 flex justify-center items-start relative bg-slate-100">
        <div v-if="loading || (document && document.type === 'pdf' && !pdfBlobUrl)" class="absolute inset-0 flex flex-col items-center justify-center gap-3">
          <Loader2 class="w-8 h-8 text-[#2E85D8] animate-spin" />
          <span class="text-xs font-medium text-black/45">Loading document viewer...</span>
        </div>
        
        <div v-else-if="!document" class="absolute inset-0 flex flex-col items-center justify-center gap-2">
          <FileText class="w-12 h-12 text-black/20" />
          <p class="text-sm font-semibold text-black/55">Document not found</p>
          <p class="text-xs text-black/35">The requested document is not attached to this contract.</p>
        </div>

        <div v-else-if="document.type === 'pdf'" class="w-full flex justify-center py-4">
          <!-- White wrapper card that fits the PDF perfectly -->
          <div class="shadow-md border border-black/8 bg-white p-6 rounded-xl flex flex-col items-center transition-all duration-300"
            :style="{ width: `${pdfWidth + 48}px` }">
            <VuePdfEmbed
              :source="pdfBlobUrl"
              :page="currentPage"
              :width="pdfWidth"
              @loaded="handlePdfLoaded"
              class="w-full select-none"
            />
          </div>
        </div>
        
        <div v-else class="absolute inset-0 flex flex-col items-center justify-center gap-4">
          <div class="w-16 h-16 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-500">
            <FileText class="w-8 h-8" />
          </div>
          <div class="text-center space-y-1">
            <p class="text-sm font-semibold text-black/75">Word Document (DOCX)</p>
            <p class="text-xs text-black/40">Preview not supported in-app. Open or download to read.</p>
          </div>
          <button @click="downloadFile"
            class="flex items-center gap-2 px-5 py-2 rounded-lg bg-[#2E85D8] hover:bg-[#252578] text-xs font-semibold text-white transition shadow-sm">
            <Download class="w-4 h-4" />
            Download DOCX
          </button>
        </div>
      </main>
    </div>
  </div>
</template>

<style scoped>
.vue-pdf-embed :deep(canvas) {
  width: 100% !important;
  height: auto !important;
}
</style>
