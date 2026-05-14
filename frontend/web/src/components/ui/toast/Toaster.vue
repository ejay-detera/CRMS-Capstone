<script setup lang="ts">
import { CheckCircle2, XCircle, Info, AlertTriangle, X } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

const { toasts, remove } = useToast()

const config = {
  success: {
    bar:  'bg-emerald-500',
    icon: CheckCircle2,
    text: 'text-emerald-600',
    bg:   'bg-emerald-50',
  },
  error: {
    bar:  'bg-red-500',
    icon: XCircle,
    text: 'text-red-600',
    bg:   'bg-red-50',
  },
  info: {
    bar:  'bg-[#2E85D8]',
    icon: Info,
    text: 'text-[#2E85D8]',
    bg:   'bg-[#2E85D8]/8',
  },
  warning: {
    bar:  'bg-amber-500',
    icon: AlertTriangle,
    text: 'text-amber-600',
    bg:   'bg-amber-50',
  },
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed bottom-5 right-5 z-[9999] flex flex-col gap-2.5 pointer-events-none" aria-live="polite">
      <TransitionGroup
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-x-full opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-full opacity-0"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="pointer-events-auto flex items-start gap-3 w-80 bg-white rounded-xl shadow-lg border border-black/8 overflow-hidden pl-0 pr-4 py-3.5"
        >
          <!-- Colored left bar -->
          <div class="w-1 self-stretch rounded-r shrink-0" :class="config[toast.type].bar" />

          <!-- Icon -->
          <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5" :class="config[toast.type].bg">
            <component :is="config[toast.type].icon" class="w-4 h-4" :class="config[toast.type].text" />
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-black leading-snug">{{ toast.title }}</p>
            <p v-if="toast.description" class="text-xs text-black/45 mt-0.5 leading-relaxed">{{ toast.description }}</p>
          </div>

          <!-- Close -->
          <button
            @click="remove(toast.id)"
            class="text-black/25 hover:text-black/60 transition-colors mt-0.5 shrink-0"
          >
            <X class="w-3.5 h-3.5" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>
