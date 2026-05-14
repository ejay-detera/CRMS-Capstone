<script setup lang="ts">
import { CheckCircle2, XCircle, Info, AlertTriangle, X } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

const { toasts, remove } = useToast()

const config = {
  success: { icon: CheckCircle2, iconClass: 'text-emerald-500' },
  error:   { icon: XCircle,      iconClass: 'text-red-500'     },
  info:    { icon: Info,         iconClass: 'text-[#2E85D8]'   },
  warning: { icon: AlertTriangle, iconClass: 'text-amber-500'  },
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed bottom-5 right-5 z-9999 flex flex-col gap-2 pointer-events-none" aria-live="polite">
      <TransitionGroup
        enter-active-class="transition duration-250 ease-out"
        enter-from-class="translate-y-2 opacity-0 scale-95"
        enter-to-class="translate-y-0 opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="pointer-events-auto flex items-center gap-3 w-72 bg-white border border-black/10 rounded-lg shadow-md px-3.5 py-3"
        >
          <component :is="config[toast.type].icon" class="w-4 h-4 shrink-0" :class="config[toast.type].iconClass" />

          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-black leading-snug">{{ toast.title }}</p>
            <p v-if="toast.description" class="text-xs text-black/45 mt-0.5 truncate">{{ toast.description }}</p>
          </div>

          <button @click="remove(toast.id)" class="text-black/25 hover:text-black/55 transition-colors shrink-0">
            <X class="w-3.5 h-3.5" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>
