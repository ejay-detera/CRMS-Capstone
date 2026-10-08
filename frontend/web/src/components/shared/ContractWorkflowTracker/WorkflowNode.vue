<script setup lang="ts">
import { computed, ref } from 'vue'
import { Check, Clock, X, Minus, AlertTriangle, Pencil } from 'lucide-vue-next'
import type { WorkflowNode } from '@/types/contractWorkflow'

const props = defineProps<{
  node: WorkflowNode
  isCurrent: boolean // true when this node is the single "active" one across the whole run
}>()

// Each status gets a distinct icon SHAPE (not just a color) plus an
// accessible text label, per spec 5.1/5.6 — never color-only.
const statusMeta = computed(() => {
  switch (props.node.status) {
    case 'completed':
      return { icon: Check, label: 'Completed', circle: 'bg-brand-blue border-brand-blue text-white' }
    case 'active':
      return { icon: Clock, label: 'In progress, current step', circle: 'bg-white border-brand-blue text-brand-blue ring-2 ring-brand-blue/15' }
    case 'on_hold':
      return { icon: AlertTriangle, label: 'On hold, no active holder for this role', circle: 'bg-amber-50 border-amber-400 text-amber-600' }
    case 'rejected':
      return { icon: X, label: 'Rejected', circle: 'bg-red-500 border-red-500 text-white' }
    case 'canceled':
      return { icon: Minus, label: 'Canceled', circle: 'bg-white border-black/20 text-black/35' }
    default:
      return { icon: Clock, label: 'Not started', circle: 'bg-white border-black/15 text-black/30' }
  }
})

const longDate = computed(() => {
  if (!props.node.actedAt) return null
  return new Date(props.node.actedAt).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
})

const isoDate = computed(() => props.node.actedAt ? new Date(props.node.actedAt).toISOString().slice(0, 10) : null)

const dateOrWaitingText = computed(() => {
  if (longDate.value) return longDate.value
  if (props.node.status === 'active') return 'Awaiting action'
  if (props.node.status === 'on_hold') return 'On hold'
  return null
})

// Delegation sub-text replaces any generic text (spec 4.3/5.4); if a
// comment also exists, it appears on the next line.
const subTextLines = computed(() => {
  const lines: string[] = []
  if (props.node.delegation) {
    lines.push(`Approved by ${props.node.delegation.delegateName} on behalf of ${props.node.delegation.onBehalfOfRole}`)
  }
  if (props.node.subText) {
    lines.push(props.node.subText)
  }
  return lines
})

// Long-comment clamp with "Show more" (spec 5.4).
const expanded = ref(false)
const isLong = computed(() => subTextLines.value.join(' ').length > 140)
</script>

<template>
  <li class="flex gap-3" :aria-current="isCurrent ? 'step' : undefined">
    <!-- Icon -->
    <div class="flex flex-col items-center shrink-0">
      <div
        class="w-8 h-8 rounded-full border-2 flex items-center justify-center shrink-0"
        :class="statusMeta.circle"
      >
        <component :is="statusMeta.icon" class="w-4 h-4" />
      </div>
      <span class="sr-only">{{ statusMeta.label }}</span>
    </div>

    <!-- Title / date / sub-text, in that order (spec 5.2) -->
    <div class="pb-1 min-w-0 flex-1">
      <div class="flex items-center gap-1.5 flex-wrap">
        <span class="text-sm font-semibold text-black" :class="node.status === 'active' ? 'font-bold' : ''">
          {{ node.title }}
        </span>
        <span v-if="isCurrent" class="text-[10px] font-bold uppercase tracking-wide text-brand-blue bg-brand-blue/10 px-1.5 py-0.5 rounded">
          Current
        </span>

        <!-- Decision #14: deleted role warning -->
        <span v-if="node.roleDeleted" class="inline-flex items-center" title="This role has been deleted">
          <AlertTriangle class="w-3.5 h-3.5 text-amber-500" />
          <span class="sr-only">Role deleted</span>
        </span>
        <!-- Decision #14: renamed role indicator, previous name on hover -->
        <span v-else-if="node.roleRenamedFrom" class="inline-flex items-center" :title="`Previously named: ${node.roleRenamedFrom}`">
          <Pencil class="w-3 h-3 text-black/35" />
          <span class="sr-only">Role renamed from {{ node.roleRenamedFrom }}</span>
        </span>
      </div>

      <time v-if="dateOrWaitingText" :datetime="isoDate ?? undefined" class="text-xs text-black/40 block mt-0.5">
        {{ dateOrWaitingText }}
      </time>

      <div v-if="subTextLines.length" class="text-xs text-black/55 mt-1" :class="!expanded && isLong ? 'line-clamp-2' : ''">
        <p v-for="(line, i) in subTextLines" :key="i">{{ line }}</p>
      </div>
      <button v-if="isLong" @click="expanded = !expanded" class="text-xs font-medium text-brand-blue hover:text-brand-navy mt-0.5">
        {{ expanded ? 'Show less' : 'Show more' }}
      </button>
    </div>
  </li>
</template>
