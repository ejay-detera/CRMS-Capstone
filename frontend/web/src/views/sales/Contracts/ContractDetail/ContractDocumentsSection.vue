<script setup lang="ts">
import { ref } from 'vue'
import { FileX, FileType2, UploadCloud, X, AlertCircle } from 'lucide-vue-next'
import type { UploadedDoc } from '@/types/contract'

const props = defineProps<{
  docs:      UploadedDoc[]
  isEditing: boolean
}>()

const emit = defineEmits<{ 'update:docs': [v: UploadedDoc[]] }>()

const fileInput = ref<HTMLInputElement | null>(null)
const dragOver  = ref(false)
const fileError = ref('')

const MAX_BYTES = 10 * 1024 * 1024

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
  if (props.docs.some(d => d.name === f.name && d.size === f.size)) return 'This file has already been added.'
  return ''
}

function addFile(f: File) {
  const err = validate(f)
  if (err) { fileError.value = err; return }
  fileError.value = ''
  const type       = detectType(f)!
  const previewUrl = type === 'pdf' ? URL.createObjectURL(f) : ''
  emit('update:docs', [...props.docs, { file: f, name: f.name, size: f.size, type, previewUrl }])
}

function onFileInput(e: Event) {
  const files = (e.target as HTMLInputElement).files
  if (!files) return
  Array.from(files).forEach(addFile)
  if (fileInput.value) fileInput.value.value = ''
}

function onDrop(e: DragEvent) {
  dragOver.value = false
  const files = e.dataTransfer?.files
  if (!files) return
  Array.from(files).forEach(addFile)
}

function removeDoc(index: number) {
  const doc = props.docs[index]
  if (doc?.previewUrl) URL.revokeObjectURL(doc.previewUrl)
  emit('update:docs', props.docs.filter((_, i) => i !== index))
}

function fmtSize(bytes: number) {
  return (bytes / 1_048_576).toFixed(2) + ' MB'
}
</script>

<template>
  <div class="bg-white rounded-xl border border-black/8 shadow-sm overflow-hidden">

    <!-- Section header -->
    <div class="px-6 py-4 border-b border-black/6 flex items-center gap-2">
      <h2 class="text-xs font-semibold text-black/40 uppercase tracking-widest">Documents</h2>
      <span class="text-[10px] font-bold text-black/35 bg-black/5 px-1.5 py-0.5 rounded-full tabular-nums">
        {{ docs.length }}
      </span>
    </div>

    <!-- Document grid -->
    <div class="p-5">

      <!-- Empty state (view mode) -->
      <div v-if="!isEditing && docs.length === 0"
        class="flex flex-col items-center gap-2 py-10 text-black/25">
        <FileX class="w-8 h-8" />
        <p class="text-sm font-medium">No documents attached</p>
      </div>

      <!-- Cards + upload zone (edit mode) or cards only (view mode) -->
      <div v-else class="flex flex-wrap gap-3">

        <!-- Document cards -->
        <div v-for="(doc, i) in docs" :key="doc.name + doc.size"
          class="relative w-36 rounded-lg border border-black/8 overflow-hidden shadow-sm bg-white flex flex-col group">

          <!-- Remove button (edit mode only) -->
          <button v-if="isEditing"
            @click="removeDoc(i)"
            class="absolute top-1.5 right-1.5 z-10 w-5 h-5 rounded-full bg-black/50 hover:bg-red-500 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
            <X class="w-3 h-3" />
          </button>

          <!-- PDF preview -->
          <template v-if="doc.type === 'pdf'">
            <div class="w-full h-44 bg-black/4 overflow-hidden">
              <iframe :src="doc.previewUrl" class="w-full h-full pointer-events-none" />
            </div>
          </template>

          <!-- DOCX placeholder -->
          <template v-else>
            <div class="w-full h-44 bg-blue-50 flex flex-col items-center justify-center gap-2">
              <FileType2 class="w-10 h-10 text-blue-400" />
              <span class="text-[10px] font-bold text-blue-400 uppercase tracking-wider">DOCX</span>
            </div>
          </template>

          <!-- Filename strip -->
          <div class="px-2 py-1.5 border-t border-black/6 bg-white">
            <p class="text-[10px] font-medium text-black/60 truncate" :title="doc.name">{{ doc.name }}</p>
            <p class="text-[9px] text-black/30 tabular-nums">{{ fmtSize(doc.size) }}</p>
          </div>

        </div>

        <!-- Upload drop zone (edit mode only) -->
        <div v-if="isEditing"
          class="w-36 rounded-lg border-2 border-dashed flex flex-col items-center justify-center gap-2 cursor-pointer transition-colors select-none"
          style="min-height: 11rem;"
          :class="dragOver ? 'border-[#2E85D8] bg-[#2E85D8]/5' : 'border-black/15 hover:border-[#2E85D8]/50 hover:bg-black/2'"
          @dragover.prevent="dragOver = true"
          @dragleave.prevent="dragOver = false"
          @drop.prevent="onDrop"
          @click="fileInput?.click()">
          <UploadCloud class="w-6 h-6 text-black/25" />
          <span class="text-[10px] font-semibold text-black/35 text-center px-2 leading-tight">Add files</span>
          <input ref="fileInput" type="file" accept=".pdf,.docx" multiple class="hidden" @change="onFileInput" />
        </div>

      </div>

      <!-- File error -->
      <div v-if="isEditing && fileError" class="mt-3 flex items-center gap-2 text-red-500">
        <AlertCircle class="w-3.5 h-3.5 shrink-0" />
        <p class="text-xs">{{ fileError }}</p>
      </div>

    </div>
  </div>
</template>
