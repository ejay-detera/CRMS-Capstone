<script setup lang="ts">
import { ref, onUnmounted } from 'vue'
import { UploadCloud, FileType2, X, AlertCircle } from 'lucide-vue-next'

export interface UploadedDoc {
  file:       File
  name:       string
  size:       number
  type:       'pdf' | 'docx'
  previewUrl: string
}

const props = defineProps<{ modelValue: UploadedDoc[] }>()
const emit  = defineEmits<{ 'update:modelValue': [v: UploadedDoc[]] }>()

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

function selectFile(f: File) {
  const err = validate(f)
  if (err) { fileError.value = err; return }
  fileError.value = ''
  const type       = detectType(f)!
  const previewUrl = type === 'pdf' ? URL.createObjectURL(f) : ''
  emit('update:modelValue', [...props.modelValue, { file: f, name: f.name, size: f.size, type, previewUrl }])
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
        class="relative w-36 rounded-lg border border-black/8 bg-white shadow-sm overflow-hidden">

        <!-- PDF thumbnail -->
        <iframe
          v-if="doc.type === 'pdf'"
          :src="doc.previewUrl"
          class="w-full h-44 block pointer-events-none"
          style="border: none;"
          title="PDF preview"
        />

        <!-- DOCX card body -->
        <div v-else
          class="flex flex-col items-center justify-center gap-2 h-44 bg-blue-50/50">
          <FileType2 class="w-9 h-9 text-blue-500" />
          <span class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">DOCX</span>
        </div>

        <!-- Filename chip -->
        <div class="px-2 py-1.5 border-t border-black/6">
          <p class="text-[10px] font-medium text-black truncate leading-snug">{{ doc.name }}</p>
          <p class="text-[9px] text-black/35 mt-0.5">{{ fileSizeMB(doc.size) }} MB</p>
        </div>

        <!-- Remove button -->
        <button @click="removeDoc(i)"
          class="absolute top-1.5 right-1.5 w-5 h-5 rounded-full bg-black/50 hover:bg-black/70 flex items-center justify-center text-white transition">
          <X class="w-3 h-3" />
        </button>

      </div>
    </div>

  </div>
</template>
