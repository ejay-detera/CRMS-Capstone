<script setup lang="ts">
import { ref, onUnmounted } from 'vue'
import { UploadCloud, FileType2, X, AlertCircle } from 'lucide-vue-next'
import type { UploadedDoc } from '@/types/contract'
import { useAuth } from '@/composables/useAuth'

export type { UploadedDoc }

const props = defineProps<{ 
  modelValue: UploadedDoc[]
  onPreview?: (doc: UploadedDoc) => void
}>()
const emit  = defineEmits<{ 'update:modelValue': [v: UploadedDoc[]] }>()

const { state: authState } = useAuth()
const MAX_BYTES = 10 * 1024 * 1024 // 10 MB
const fileInput = ref<HTMLInputElement | null>(null)
const dragOver  = ref(false)
const fileError = ref('')

function detectType(f: File): 'pdf' | 'docx' | null {
  const name = f.name.toLowerCase()
  if (f.type === 'application/pdf' || name.endsWith('.pdf')) return 'pdf'
  if (
    f.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ||
    name.endsWith('.docx')
  ) return 'docx'
  return null
}

function validate(f: File): string {
  if (!detectType(f))     return 'Only PDF or DOCX files are accepted.'
  if (f.size > MAX_BYTES) return `File exceeds the 10 MB limit (${(f.size / 1024 / 1024).toFixed(2)} MB).`
  const isDuplicate = props.modelValue.some(d => d.name === f.name && d.size === f.size)
  if (isDuplicate)        return 'This file has already been added.'
  return ''
}

async function uploadFile(doc: UploadedDoc, index: number) {
  if (!doc.file) return

  // 1. Set status to uploading
  const updatedValue = [...props.modelValue]
  updatedValue[index] = {
    ...doc,
    uploadStatus: 'uploading'
  }
  emit('update:modelValue', updatedValue)

  // Transition to scanning state after 400ms to show the upload step
  const scanTimer = setTimeout(() => {
    if (props.modelValue[index] && props.modelValue[index].uploadStatus === 'uploading') {
      const scanningValue = [...props.modelValue]
      scanningValue[index] = {
        ...scanningValue[index],
        uploadStatus: 'scanning'
      }
      emit('update:modelValue', scanningValue)
    }
  }, 450)

  try {
    const formData = new FormData()
    formData.append('file', doc.file)

    const res = await fetch(`${import.meta.env.VITE_CONTRACT_API_URL}/documents/upload`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${authState.token}`,
      },
      body: formData,
    })

    clearTimeout(scanTimer)
    const data = await res.json()

    const finalValue = [...props.modelValue]
    if (res.ok) {
      // 2. Success (Malware free)
      finalValue[index] = {
        ...finalValue[index],
        id: data.data.document_id,
        uploadStatus: 'success',
        scanWarning: data.scan_warning
      }
    } else {
      // 3. Error (Malware detected or validation failure)
      const msg = data.errors?.file?.[0] || data.message || 'Upload failed.'
      finalValue[index] = {
        ...finalValue[index],
        uploadStatus: 'error',
        errorMessage: msg
      }
    }
    emit('update:modelValue', finalValue)
  } catch (err) {
    clearTimeout(scanTimer)
    const finalValue = [...props.modelValue]
    finalValue[index] = {
      ...finalValue[index],
      uploadStatus: 'error',
      errorMessage: 'Network error. Failed to scan file.'
    }
    emit('update:modelValue', finalValue)
  }
}

function selectFile(f: File) {
  const err = validate(f)
  if (err) { fileError.value = err; return }
  fileError.value = ''
  const type       = detectType(f)!
  const previewUrl = type === 'pdf' ? URL.createObjectURL(f) : ''
  const newDoc: UploadedDoc = {
    file: f,
    name: f.name,
    size: f.size,
    type,
    previewUrl,
    uploadStatus: 'uploading'
  }
  const newList = [...props.modelValue, newDoc]
  emit('update:modelValue', newList)

  const index = newList.length - 1
  uploadFile(newDoc, index)
}

function onFileInput(e: Event) {
  const files = (e.target as HTMLInputElement).files
  if (!files) return
  Array.from(files).forEach(selectFile)
  if (fileInput.value) fileInput.value.value = ''
}

function onDrop(e: DragEvent) {
  dragOver.value = false
  const files = e.dataTransfer?.files
  if (!files) return
  Array.from(files).forEach(selectFile)
}

function removeDoc(index: number) {
  const doc = props.modelValue[index]
  if (doc?.previewUrl) URL.revokeObjectURL(doc.previewUrl)
  emit('update:modelValue', props.modelValue.filter((_, i) => i !== index))
}

onUnmounted(() => {
  props.modelValue.forEach(d => { if (d.previewUrl) URL.revokeObjectURL(d.previewUrl) })
})

const fileSizeMB = (bytes: number) => (bytes / 1024 / 1024).toFixed(2)
</script>

<template>
  <div class="space-y-3">

    <!-- Drop zone (always visible) -->
    <div
      @dragover.prevent="dragOver = true"
      @dragleave.prevent="dragOver = false"
      @drop.prevent="onDrop"
      @click="fileInput?.click()"
      class="flex flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed px-6 py-7 cursor-pointer transition-colors select-none"
      :class="dragOver
        ? 'border-[#2E85D8] bg-[#2E85D8]/4'
        : 'border-black/12 bg-black/1.5 hover:border-[#2E85D8]/50 hover:bg-[#2E85D8]/2'">

      <input
        ref="fileInput"
        type="file"
        multiple
        accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
        class="hidden"
        @change="onFileInput"
      />

      <div class="w-10 h-10 rounded-full bg-white border border-black/8 shadow-sm flex items-center justify-center">
        <UploadCloud class="w-4.5 h-4.5 text-black/35" />
      </div>

      <div class="text-center">
        <p class="text-sm font-medium text-black/70">
          <span class="text-[#2E85D8] font-semibold">Click to upload</span> or drag & drop
        </p>
        <p class="text-xs text-black/35 mt-1">PDF or DOCX · Max 10 MB per file</p>
      </div>
    </div>

    <!-- Validation error -->
    <div v-if="fileError"
      class="flex items-start gap-2.5 rounded-lg bg-red-50 border border-red-200 px-3.5 py-2.5">
      <AlertCircle class="w-4 h-4 text-red-500 shrink-0 mt-0.5" />
      <p class="text-xs font-medium text-red-600">{{ fileError }}</p>
    </div>

    <!-- File card grid -->
    <div v-if="modelValue.length > 0" class="flex flex-wrap gap-3">
      <div v-for="(doc, i) in modelValue" :key="i"
        class="relative w-36 rounded-lg border border-black/8 bg-white shadow-sm overflow-hidden flex flex-col group">

        <!-- PDF thumbnail -->
        <div v-if="doc.type === 'pdf'"
          class="flex flex-col items-center justify-center gap-2 h-44 bg-red-50/50">
          <FileType2 class="w-9 h-9 text-red-500" />
          <span class="text-[10px] font-bold text-red-600 uppercase tracking-widest">PDF</span>
        </div>

        <!-- DOCX card body -->
        <div v-else
          class="flex flex-col items-center justify-center gap-2 h-44 bg-blue-50/50">
          <FileType2 class="w-9 h-9 text-blue-500" />
          <span class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">DOCX</span>
        </div>

        <!-- Filename chip -->
        <div class="px-2 py-1.5 border-t border-black/6 bg-white z-10">
          <p class="text-[10px] font-medium text-black truncate leading-snug">{{ doc.name }}</p>
          <p class="text-[9px] text-black/35 mt-0.5">{{ fileSizeMB(doc.size) }} MB</p>
        </div>

        <!-- Remove button -->
        <button @click="removeDoc(i)"
          class="absolute top-1.5 right-1.5 z-30 w-5 h-5 rounded-full bg-black/50 hover:bg-black/70 flex items-center justify-center text-white transition">
          <X class="w-3 h-3" />
        </button>

        <!-- Status Overlay -->
        <div v-if="doc.uploadStatus && doc.uploadStatus !== 'success'"
          class="absolute inset-0 h-44 z-20 flex flex-col items-center justify-center gap-2 px-2.5 text-center transition-all duration-300"
          :class="{
            'bg-black/70 text-white': doc.uploadStatus === 'uploading',
            'bg-[#FFFbeb] border border-[#fef3c7] text-[#92400e]': doc.uploadStatus === 'scanning',
            'bg-red-500/90 text-white': doc.uploadStatus === 'error'
          }">
          <template v-if="doc.uploadStatus === 'uploading'">
            <div class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent"></div>
            <span class="text-[10px] font-semibold">Uploading...</span>
          </template>
          
          <template v-else-if="doc.uploadStatus === 'scanning'">
            <div class="animate-spin rounded-full h-5 w-5 border-2 border-[#d97706] border-t-transparent"></div>
            <span class="text-[10px] font-semibold text-[#b45309]">Scanning file...</span>
          </template>

          <template v-else-if="doc.uploadStatus === 'error'">
            <AlertCircle class="w-5 h-5 text-white animate-pulse" />
            <span class="text-[9px] font-bold uppercase tracking-wide">Blocked</span>
            <span class="text-[8px] leading-tight max-h-16 overflow-y-auto px-1">{{ doc.errorMessage || 'Malware detected!' }}</span>
          </template>
        </div>

        <!-- Success overlay/badge -->
        <div v-if="doc.uploadStatus === 'success'"
          class="absolute top-1.5 left-1.5 z-20 flex items-center gap-1 rounded bg-[#2E85D8] px-1.5 py-0.5 text-[8px] font-bold text-white uppercase tracking-wider shadow-sm select-none pointer-events-none"
          :title="doc.scanWarning || 'Malware scan completed successfully.'">
          <span>{{ doc.scanWarning ? 'Scan Skipped' : 'Malware Free' }}</span>
        </div>

        <!-- Preview Hover Overlay (only for successful uploads if onPreview exists) -->
        <div v-if="doc.uploadStatus === 'success' && onPreview"
          @click="onPreview(doc)"
          class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-black/0 hover:bg-black/40 opacity-0 hover:opacity-100 transition-all duration-200 cursor-pointer text-white">
          <div class="p-2 rounded-full bg-white/20 backdrop-blur-sm mb-1 transform translate-y-2 group-hover:translate-y-0 transition-all duration-300">
            <svg class="w-5 h-5 text-white drop-shadow-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </div>
          <span class="text-[10px] font-bold uppercase tracking-wider drop-shadow-md transform translate-y-2 group-hover:translate-y-0 transition-all duration-300 delay-75">Preview</span>
        </div>

      </div>
    </div>

  </div>
</template>
