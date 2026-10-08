<script setup lang="ts">
import { ref } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
import type { WorkflowRun } from '@/types/contractWorkflow'

defineProps<{
  run: WorkflowRun
}>()

const expanded = ref(false)
</script>

<template>
  <div class="border border-black/8 rounded-lg bg-black/[0.015] overflow-hidden">
    <button
      @click="expanded = !expanded"
      class="w-full flex items-center justify-between gap-2 px-4 py-2.5 text-left"
      :aria-expanded="expanded"
    >
      <span class="text-xs font-semibold text-black/50">Previous attempt #{{ run.runNumber }}</span>
      <ChevronDown class="w-3.5 h-3.5 text-black/35 transition-transform" :class="expanded ? 'rotate-180' : ''" />
    </button>

    <ol v-if="expanded" class="px-4 pb-4 pt-1 space-y-0 list-none">
      <slot />
    </ol>
  </div>
</template>
