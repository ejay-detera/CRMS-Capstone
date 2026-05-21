<script setup lang="ts">
import { FileX, FileType2 } from 'lucide-vue-next'
import type { UploadedDoc } from '@/types/contract'

defineProps<{ docs: UploadedDoc[] }>()

function fmtSize(bytes: number): string {
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

    <!-- Empty state -->
    <div v-if="docs.length === 0"
      class="flex flex-col items-center gap-2 py-14 text-black/25">
      <FileX class="w-8 h-8" />
      <p class="text-sm font-medium">No documents attached</p>
    </div>

    <!-- Document grid -->
    <div v-else class="p-5 flex flex-wrap gap-3">
      <div v-for="doc in docs" :key="doc.name + doc.size"
        class="w-36 rounded-lg border border-black/8 overflow-hidden shadow-sm bg-white flex flex-col">

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
    </div>

  </div>
</template>
