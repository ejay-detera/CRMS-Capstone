<script setup lang="ts">
import type { NodeStatus } from '@/types/contractWorkflow'

// Connector style is driven by the LOWER/next group's status (spec 5.3),
// not the current one — the parent passes that status in directly.
const props = defineProps<{
  nextGroupStatus: NodeStatus
}>()

const solidStatuses: NodeStatus[] = ['completed', 'active', 'on_hold']
</script>

<template>
  <div class="flex justify-center py-1" aria-hidden="true">
    <!-- Solid colored line for completed/active paths; dashed muted line
         for future paths (spec 5.3) — never color-only, the dash pattern
         itself is the second signal. -->
    <div
      class="h-6"
      :class="solidStatuses.includes(nextGroupStatus)
        ? 'w-0.5 bg-brand-blue'
        : 'w-0 border-l-2 border-dashed border-black/20'"
    />
  </div>
</template>
