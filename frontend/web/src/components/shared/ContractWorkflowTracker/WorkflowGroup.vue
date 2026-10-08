<script setup lang="ts">
import { computed } from 'vue'
import type { WorkflowGroup } from '@/types/contractWorkflow'
import WorkflowNode from './WorkflowNode.vue'

const props = defineProps<{
  group: WorkflowGroup
  activeNodeId: string | null // the single "current" node across the whole run, if any
}>()

const parallelLabel = computed(() =>
  `Parallel steps: ${props.group.nodes.map(n => n.title).join(' and ')}`
)
</script>

<template>
  <!-- Sequential: a single node centered on the main line. -->
  <template v-if="group.mode === 'sequential'">
    <WorkflowNode :node="group.nodes[0]" :is-current="group.nodes[0].id === activeNodeId" />
  </template>

  <!-- Parallel: nodes side by side under a labeled container (spec 5.6),
       stacking on narrow screens while staying visually grouped via the
       border/indent (spec 5.2). -->
  <li v-else class="list-none" role="group" :aria-label="parallelLabel">
    <div class="border-l-2 border-dashed border-black/15 pl-4 ml-4 space-y-3 sm:ml-0 sm:pl-0 sm:border-l-0">
      <p class="text-[11px] font-semibold text-black/35 uppercase tracking-wide sm:hidden">Parallel</p>
      <ol class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 list-none">
        <WorkflowNode
          v-for="node in group.nodes"
          :key="node.id"
          :node="node"
          :is-current="node.id === activeNodeId"
        />
      </ol>
    </div>
  </li>
</template>
