<script setup lang="ts">
import { computed } from 'vue'
import { VisSingleContainer, VisDonut } from '@unovis/vue'

interface PartnerItem {
  id: string
  name: string
  region: string
  status: string
  type: 'Partner' | 'Supplier'
}

const props = defineProps<{
  partners: PartnerItem[]
}>()

type RiskItem = { label: string; value: number; color: string }

const data = computed<RiskItem[]>(() => {
  let low = 0
  let medium = 0
  let high = 0

  props.partners.forEach(p => {
    if (p.status === 'Inactive') {
      medium++
    } else if (p.status === 'Suspended') {
      high++
    } else {
      // split active ones: suppliers in Mindanao or some region could map to medium risk, others low risk
      if (p.type === 'Supplier' && p.region === 'Mindanao') {
        medium++
      } else {
        low++
      }
    }
  })

  // fallback if no data
  if (low === 0 && medium === 0 && high === 0) {
    low = 1
  }

  return [
    { label: 'Low Risk',    value: low,    color: '#2E85D8' },
    { label: 'Medium Risk', value: medium, color: '#2F2F73' },
    { label: 'High Risk',   value: high,   color: '#252578' }
  ]
})

const total = computed(() => data.value.reduce((s, d) => s + d.value, 0))
const value = (d: RiskItem) => d.value
const color = (d: RiskItem) => d.color
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden h-full">
    <div class="px-6 pt-5 pb-4 border-b border-black/5">
      <h3 class="text-sm font-semibold text-black">Vendor Risk Classification</h3>
      <p class="text-xs text-black/40 mt-0.5">Vendors divided by internal safety tiers</p>
    </div>
    <div class="px-6 py-6 flex flex-col items-center gap-5">

      <!-- Donut with total overlaid in the hole -->
      <div class="relative w-45 h-45 shrink-0">
        <VisSingleContainer :data="data" :height="180" :width="180">
          <VisDonut :value="value" :color="color" :arc-width="38" />
        </VisSingleContainer>
        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
          <p class="text-2xl font-bold text-black tabular-nums leading-none">{{ total }}</p>
          <p class="text-[11px] text-black/35 mt-0.5">vendors</p>
        </div>
      </div>

      <!-- Legend -->
      <div class="w-full space-y-2.5">
        <div v-for="item in data" :key="item.label" class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: item.color }" />
            <span class="text-xs text-black/60">{{ item.label }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-black">{{ item.value }}</span>
            <span class="text-[10px] text-black/30 w-6 text-right">
              {{ total > 0 ? Math.round(item.value / total * 100) : 0 }}%
            </span>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
