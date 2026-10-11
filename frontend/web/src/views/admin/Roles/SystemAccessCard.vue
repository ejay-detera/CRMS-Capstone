<script setup lang="ts">
import { Monitor, ShoppingCart, Users, DollarSign } from 'lucide-vue-next'
import type { SystemDefinition } from '@/types/role'

defineProps<{
  systems:          SystemDefinition[]
  activeSystemKeys: string[]
  isLocked:         boolean
}>()

const emit = defineEmits<{
  'toggle-system': [systemKey: string]
}>()

const SYSTEM_ICONS: Record<string, any> = {
  cms: Monitor,
  prs: ShoppingCart,
  hrm: Users,
  fin: DollarSign,
}
</script>

<template>
  <div class="bg-white rounded-xl border border-black/8 shadow-sm overflow-hidden">

    <!-- Card header -->
    <div class="px-6 py-4 border-b border-black/5 flex items-center gap-3">
      <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-brand-blue/8">
        <Monitor class="w-4 h-4 text-brand-blue" />
      </div>
      <div>
        <h2 class="text-sm font-semibold text-black">System Access</h2>
        <p class="text-xs text-black/40 mt-0.5">
          {{ isLocked ? 'This role has access to all systems.' : 'Choose which systems this role can log into.' }}
        </p>
      </div>
    </div>

    <!-- System toggles -->
    <div class="px-6 py-5 flex flex-wrap gap-3">
      <template v-for="sys in systems" :key="sys.key">

        <!-- Available system toggle -->
        <button
          v-if="sys.available"
          :disabled="isLocked"
          @click="emit('toggle-system', sys.key)"
          class="group flex items-center gap-3 px-4 py-3 rounded-xl border text-left transition-all duration-200 min-w-[175px]"
          :class="activeSystemKeys.includes(sys.key) || isLocked
            ? 'bg-brand-navy/5 border-brand-navy/25 ring-1 ring-brand-navy/15'
            : 'bg-black/[0.02] border-black/8 hover:border-black/15 hover:bg-black/[0.04]'"
        >
          <div
            class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 transition-all duration-200"
            :class="activeSystemKeys.includes(sys.key) || isLocked ? 'bg-brand-navy/10' : 'bg-black/5'"
          >
            <component
              :is="SYSTEM_ICONS[sys.key]"
              class="w-4 h-4 transition-colors duration-200"
              :class="activeSystemKeys.includes(sys.key) || isLocked ? 'text-brand-navy' : 'text-black/40'"
            />
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <span
                class="text-sm font-semibold transition-colors duration-200"
                :class="activeSystemKeys.includes(sys.key) || isLocked ? 'text-brand-navy' : 'text-black/60'"
              >{{ sys.label }}</span>
              <span
                v-if="activeSystemKeys.includes(sys.key) || isLocked"
                class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-brand-navy/10 text-brand-navy"
              >Active</span>
            </div>
            <p class="text-xs text-black/35 mt-0.5 truncate">{{ sys.description }}</p>
          </div>
          <!-- Toggle switch -->
          <div
            class="w-10 h-5 rounded-full flex items-center px-0.5 shrink-0 transition-all duration-300"
            :class="activeSystemKeys.includes(sys.key) || isLocked
              ? 'bg-brand-navy justify-end'
              : 'bg-black/15 justify-start'"
          >
            <div class="w-4 h-4 rounded-full bg-white shadow-sm" />
          </div>
        </button>

        <!-- Placeholder / coming soon -->
        <div
          v-else
          class="flex items-center gap-3 px-4 py-3 rounded-xl border border-black/6 bg-black/[0.015] min-w-[175px] opacity-55 cursor-not-allowed select-none"
        >
          <div class="w-9 h-9 rounded-lg bg-black/4 flex items-center justify-center shrink-0">
            <component :is="SYSTEM_ICONS[sys.key]" class="w-4 h-4 text-black/25" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <span class="text-sm font-semibold text-black/40">{{ sys.label }}</span>
              <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-black/6 text-black/35 border border-black/8">Soon</span>
            </div>
            <p class="text-xs text-black/30 mt-0.5 truncate">{{ sys.description }}</p>
          </div>
          <div class="w-10 h-5 rounded-full bg-black/10 flex items-center px-0.5 justify-start shrink-0">
            <div class="w-4 h-4 rounded-full bg-white/70 shadow-sm" />
          </div>
        </div>

      </template>
    </div>
  </div>
</template>
