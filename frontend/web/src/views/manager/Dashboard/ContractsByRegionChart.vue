<script setup lang="ts">
import { computed, ref } from 'vue'
import { VisXYContainer, VisGroupedBar, VisAxis } from '@unovis/vue'
import type { Contract } from '@/types/contract'

const props = defineProps<{
  contracts: Contract[]
}>()

type RegionData    = { region: string; luzon: number; visayas: number; mindanao: number }
type RegionFilter  = 'All' | 'Luzon' | 'Visayas' | 'Mindanao'

const allData = computed<RegionData[]>(() => {
  // Get unique categories (limit to top 5 categories)
  const categories = Array.from(new Set(props.contracts.map(c => c.category || 'Other'))).slice(0, 5)
  
  if (categories.length === 0) {
    return [
      { region: 'Service Agreement', luzon: 0, visayas: 0, mindanao: 0 },
      { region: 'Supply Contract',     luzon: 0, visayas: 0, mindanao: 0 },
    ]
  }

  return categories.map(cat => {
    const catContracts = props.contracts.filter(c => (c.category || 'Other') === cat)
    const luzon = catContracts.filter(c => c.region === 'Luzon').length
    const visayas = catContracts.filter(c => c.region === 'Visayas').length
    const mindanao = catContracts.filter(c => c.region === 'Mindanao').length
    
    return {
      region: cat,
      luzon,
      visayas,
      mindanao
    }
  })
})

const regionFilter = ref<RegionFilter>('All')

const yAll     = [(d: RegionData) => d.luzon, (d: RegionData) => d.visayas, (d: RegionData) => d.mindanao]
const colorsAll = ['#252578', '#2E85D8', '#2F2F73']

const activeY = computed(() => {
  if (regionFilter.value === 'Luzon')    return [(d: RegionData) => d.luzon]
  if (regionFilter.value === 'Visayas')  return [(d: RegionData) => d.visayas]
  if (regionFilter.value === 'Mindanao') return [(d: RegionData) => d.mindanao]
  return yAll
})

const activeColors = computed(() => {
  if (regionFilter.value === 'Luzon')    return ['#252578']
  if (regionFilter.value === 'Visayas')  return ['#2E85D8']
  if (regionFilter.value === 'Mindanao') return ['#2F2F73']
  return colorsAll
})

const activeLegend = computed(() => {
  if (regionFilter.value !== 'All') return [{ label: regionFilter.value, color: activeColors.value[0] }]
  return [
    { label: 'Luzon',    color: '#252578' },
    { label: 'Visayas',  color: '#2E85D8' },
    { label: 'Mindanao', color: '#2F2F73' },
  ]
})

const subtitle = computed(() =>
  regionFilter.value === 'All'
    ? 'Breakdown across Luzon, Visayas, Mindanao'
    : `Showing ${regionFilter.value} only`
)

const x = (_: RegionData, i: number) => i
const xTickFormat = (i: number) => allData.value[Math.round(i)]?.region ?? ''
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">
    <div class="px-6 pt-5 pb-4 border-b border-black/5 flex items-center justify-between gap-4">
      <div>
        <h3 class="text-sm font-semibold text-black">Contracts by Category &amp; Region</h3>
        <p class="text-xs text-black/40 mt-0.5">{{ subtitle }}</p>
      </div>
      <div class="flex items-center gap-4">
        <!-- Region filter -->
        <div class="flex items-center gap-0.5 bg-black/4 rounded-md p-1">
          <button v-for="f in (['All', 'Luzon', 'Visayas', 'Mindanao'] as RegionFilter[])" :key="f"
            @click="regionFilter = f"
            class="px-3 py-1 text-xs rounded transition-all font-medium"
            :class="regionFilter === f ? 'bg-white text-black shadow-sm' : 'text-black/40 hover:text-black/60'">
            {{ f }}
          </button>
        </div>
        <!-- Legend -->
        <div class="flex items-center gap-3">
          <div v-for="l in activeLegend" :key="l.label" class="flex items-center gap-1.5">
            <div class="w-2.5 h-2.5 rounded-sm shrink-0" :style="{ backgroundColor: l.color }" />
            <span class="text-xs text-black/40">{{ l.label }}</span>
          </div>
        </div>
      </div>
    </div>
    <div class="px-6 py-5">
      <VisXYContainer
        :data="allData"
        :height="220"
        :style="{
          '--vis-axis-tick-label-color': 'rgba(0,0,0,0.38)',
          '--vis-axis-domain-color': 'rgba(0,0,0,0.08)',
          '--vis-axis-tick-line-color': 'transparent',
          '--vis-axis-grid-color': 'rgba(0,0,0,0.04)',
          '--vis-font-family': 'inherit',
        }"
      >
        <VisGroupedBar :x="x" :y="activeY" :color="activeColors" :bar-padding="0.2" :group-padding="0.3" :rounded-corners="3" />
        <VisAxis type="x" :tick-format="xTickFormat" />
        <VisAxis type="y" />
      </VisXYContainer>
    </div>
  </div>
</template>
