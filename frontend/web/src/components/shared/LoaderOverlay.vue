<script setup lang="ts">
import { useLoader } from '@/composables/useLoader'

const { isLoading, label, action } = useLoader()

const icons: Record<string, string> = {
  creating: 'M12 4v16m8-8H4',
  updating: 'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z',
  deleting: 'M3 6h18M8 6V4h8v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6',
  saving:   'M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2zM17 21v-8H7v8M7 3v5h8',
  loading:  'M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83',
}
</script>

<template>
  <Transition name="loader">
    <div v-if="isLoading"
      class="fixed inset-0 z-[9999] flex items-center justify-center"
      style="background: rgba(15, 15, 40, 0.45); backdrop-filter: blur(3px)">
      <div class="bg-white rounded-2xl shadow-2xl px-8 py-7 flex flex-col items-center gap-4 min-w-[180px]">
        <!-- Spinner ring -->
        <div class="relative w-14 h-14">
          <svg class="w-14 h-14 animate-spin" viewBox="0 0 56 56" fill="none">
            <circle cx="28" cy="28" r="24" stroke="#252578" stroke-width="4" stroke-opacity="0.12" />
            <path d="M28 4a24 24 0 0 1 24 24" stroke="#252578" stroke-width="4"
              stroke-linecap="round" />
          </svg>
          <!-- Action icon in center -->
          <div class="absolute inset-0 flex items-center justify-center">
            <svg class="w-5 h-5 text-[#252578]" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path :d="icons[action] || icons.loading" />
            </svg>
          </div>
        </div>
        <!-- Label -->
        <p class="text-sm font-semibold text-[#252578] tracking-wide">{{ label }}</p>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.loader-enter-active,
.loader-leave-active { transition: opacity 0.18s ease; }
.loader-enter-from,
.loader-leave-to    { opacity: 0; }
</style>
