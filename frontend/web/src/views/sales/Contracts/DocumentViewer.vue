<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, FileText, Download, ExternalLink, Loader2 } from 'lucide-vue-next'
import { useApiCache } from '@/composables/useApiCache'
import { useAuth } from '@/composables/useAuth'

const route = useRoute()
const router = useRouter()
const { state: cacheState, fetchContracts } = useApiCache()
const { state: authState } = useAuth()

const contractId = route.params.id as string
const docId = route.params.docId as string

const loading = ref(true)
const contract = ref<any>(null)

const backPath = computed(() => {
  if (route.path.startsWith('/admin')) return `/admin/contracts/${contractId}`
  if (route.path.startsWith('/manager')) return `/manager/contracts/${contractId}`
  return `/sales/contracts/${contractId}`
})

const document = computed(() => {
  if (!contract.value) return null
  return contract.value.docs.find((d: any) => String(d.id) === docId)
})

onMounted(async () => {
  // Try to find in cache first
  const cached = cacheState.contracts?.find(c => String(c.id) === contractId)
  if (cached) {
    contract.value = cached
    loading.value = false
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
    }
  } catch (err) {
    console.error('Failed to load contract:', err)
  } finally {
    loading.value = false
  }
})

function downloadFile() {
  if (document.value?.previewUrl) {
    window.open(document.value.previewUrl, '_blank')
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
        <button @click="downloadFile"
          class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-black/8 hover:bg-black/2 text-xs font-semibold text-black/60 hover:text-black transition">
          <Download class="w-3.5 h-3.5" />
          Download
        </button>
        <a :href="document.previewUrl" target="_blank"
          class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-[#2E85D8] hover:bg-[#252578] text-xs font-semibold text-white transition shadow-sm">
          <ExternalLink class="w-3.5 h-3.5" />
          Open in New Tab
        </a>
      </div>
    </header>

    <!-- Content Area / PDF Reader -->
    <main class="flex-grow relative overflow-hidden bg-slate-100">
      <div v-if="loading" class="absolute inset-0 flex flex-col items-center justify-center gap-3">
        <Loader2 class="w-8 h-8 text-[#2E85D8] animate-spin" />
        <span class="text-xs font-medium text-black/45">Loading document viewer...</span>
      </div>
      
      <div v-else-if="!document" class="absolute inset-0 flex flex-col items-center justify-center gap-2">
        <FileText class="w-12 h-12 text-black/20" />
        <p class="text-sm font-semibold text-black/55">Document not found</p>
        <p class="text-xs text-black/35">The requested document is not attached to this contract.</p>
      </div>

      <iframe
        v-else-if="document.type === 'pdf'"
        :src="document.previewUrl"
        class="w-full h-full border-none"
        title="PDF Document Viewer"
      />
      
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
</template>
