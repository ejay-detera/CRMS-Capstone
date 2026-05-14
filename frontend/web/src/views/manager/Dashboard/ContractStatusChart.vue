<script setup lang="ts">
import { VisSingleContainer, VisDonut } from '@unovis/vue'
import { computed } from 'vue'

type StatusItem = { label: string; value: number; color: string }

const data: StatusItem[] = [
  { label: 'Notarized PDF', value: 19, color: '#252578' },
  { label: 'Client Review', value: 15, color: '#2E85D8' },
  { label: 'SBSI Review',   value: 8,  color: '#ef4444' },
  { label: 'Active',        value: 42, color: '#10b981' },
]

const total = computed(() => data.reduce((s, d) => s + d.value, 0))
const value = (d: StatusItem) => d.value
const color = (d: StatusItem) => d.color
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden h-full">
    <div class="px-6 pt-5 pb-4 border-b border-black/5">
      <h3 class="text-sm font-semibold text-black">Contract Status</h3>
      <p class="text-xs text-black/40 mt-0.5">Distribution by current status</p>
    </div>
    <div class="px-6 py-5">
      <div class="flex items-center justify-center">
        <VisSingleContainer :data="data" :height="180" :width="180">
          <VisDonut :value="value" :color="color" :arc-width="36" />
        </VisSingleContainer>
      </div>
      <!-- Centre total -->
      <p class="text-center text-xs text-black/35 -mt-2 mb-4">{{ total }} total</p>
      <!-- Legend -->
      <div class="space-y-2.5">
        <div v-for="item in data" :key="item.label" class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: item.color }" />
            <span class="text-xs text-black/60">{{ item.label }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-black">{{ item.value }}</span>
            <span class="text-[10px] text-black/30 w-6 text-right">{{ Math.round(item.value / total * 100) }}%</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
