<script setup lang="ts">
import { watch, onUnmounted } from 'vue'
import { X, GitBranch } from 'lucide-vue-next'
import type { TrackerStatus, WorkflowProgress } from '@/types/contractWorkflow'
import ContractWorkflowTracker from './index.vue'

const props = defineProps<{
  open: boolean
  workflow: WorkflowProgress | null
  status: TrackerStatus
  onRetry?: () => void
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
}>()

function close() {
  emit('update:open', false)
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') close()
}

function lockScroll() {
  document.addEventListener('keydown', onKeydown)
  document.body.style.overflow = 'hidden'
}

function unlockScroll() {
  document.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
}

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    lockScroll()
  } else {
    // Panel leave-transition still plays; overflow restores on after-leave.
    document.removeEventListener('keydown', onKeydown)
  }
})

function onPanelAfterLeave() {
  if (!props.open) unlockScroll()
}

onUnmounted(() => unlockScroll())
</script>

<template>
  <Teleport to="body">
    <!-- Overlay: full-viewport coverage, anchored to body so no ancestor
         transform/filter can offset it and leave a gap. -->
    <Transition name="tracker-overlay">
      <div
        v-if="open"
        class="fixed inset-0 bg-black/30 backdrop-blur-sm z-40"
        @click="close"
      />
    </Transition>

    <!-- Side panel: same shell as the Version History drawer. Teleported
         to body + inset-y-0/h-dvh so top and bottom edges are fully
         covered with no gap. -->
    <Transition name="tracker-panel" @after-leave="onPanelAfterLeave">
      <aside
        v-if="open"
        class="fixed inset-y-0 right-0 h-dvh w-full sm:w-[480px] bg-white border-l border-black/10 shadow-2xl z-50 flex flex-col font-poppins"
        role="dialog"
        aria-modal="true"
        aria-label="Approval Workflow Tracker"
      >
        <!-- Drawer Header -->
        <div class="p-6 border-b border-black/5">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <GitBranch class="w-5 h-5 text-brand-blue" />
              <h3 class="text-base font-bold text-black">Approval Workflow Tracker</h3>
            </div>
            <button
              @click="close"
              class="p-1.5 hover:bg-black/5 rounded-lg text-black/40 hover:text-black transition-colors"
              aria-label="Close workflow tracker"
            >
              <X class="w-5 h-5" />
            </button>
          </div>
          <p class="text-xs text-black/40 mt-1.5">
            See exactly where this contract is in the approval process and whose desk it's on.
          </p>
        </div>

        <!-- Drawer Content: vertical timeline, scrolls independently -->
        <!-- :key re-mounts the timeline on each open so the stagger
             cascade replays every time the drawer opens. -->
        <div class="flex-1 overflow-y-auto p-6">
          <ContractWorkflowTracker
            :key="String(open)"
            :workflow="workflow"
            :status="status"
            :on-retry="onRetry"
            orientation="vertical"
            bare
          />
        </div>
      </aside>
    </Transition>
  </Teleport>
</template>

<style scoped>
.tracker-overlay-enter-active,
.tracker-overlay-leave-active {
  transition: opacity 0.25s ease;
}

.tracker-overlay-enter-from,
.tracker-overlay-leave-to {
  opacity: 0;
}

.tracker-panel-enter-active {
  transition: transform 0.32s cubic-bezier(0.22, 1, 0.36, 1);
}

.tracker-panel-leave-active {
  transition: transform 0.24s ease-in;
}

.tracker-panel-enter-from,
.tracker-panel-leave-to {
  transform: translateX(100%);
}

@media (prefers-reduced-motion: reduce) {
  .tracker-overlay-enter-active,
  .tracker-overlay-leave-active,
  .tracker-panel-enter-active,
  .tracker-panel-leave-active {
    transition: none;
  }
}
</style>
