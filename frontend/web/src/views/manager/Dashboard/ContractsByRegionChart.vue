<script setup lang="ts">
import { VisXYContainer, VisGroupedBar, VisAxis } from '@unovis/vue'

type RegionData = { region: string; luzon: number; visayas: number; mindanao: number }

const data: RegionData[] = [
  { region: 'Service Agreement',    luzon: 14, visayas: 8,  mindanao: 5  },
  { region: 'Supply Contract',      luzon: 10, visayas: 7,  mindanao: 4  },
  { region: 'Equip. Maintenance',  luzon: 8,  visayas: 5,  mindanao: 3  },
  { region: 'Equipment Lease',      luzon: 5,  visayas: 4,  mindanao: 4  },
  { region: 'Partnership',          luzon: 3,  visayas: 3,  mindanao: 3  },
]

const x = (_: RegionData, i: number) => i
const y = [
  (d: RegionData) => d.luzon,
  (d: RegionData) => d.visayas,
  (d: RegionData) => d.mindanao,
]
const colors = ['#252578', '#2E85D8', '#2F2F73']
const xTickFormat = (i: number) => data[Math.round(i)]?.region ?? ''

const legend = [
  { label: 'Luzon',    color: '#252578' },
  { label: 'Visayas',  color: '#2E85D8' },
  { label: 'Mindanao', color: '#2F2F73' },
]
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">
    <div class="px-6 pt-5 pb-4 border-b border-black/5 flex items-center justify-between">
      <div>
        <h3 class="text-sm font-semibold text-black">Contracts by Category &amp; Region</h3>
        <p class="text-xs text-black/40 mt-0.5">Breakdown across Luzon, Visayas, Mindanao</p>
      </div>
      <div class="flex items-center gap-4">
        <div v-for="l in legend" :key="l.label" class="flex items-center gap-1.5">
          <div class="w-2.5 h-2.5 rounded-sm shrink-0" :style="{ backgroundColor: l.color }" />
          <span class="text-xs text-black/40">{{ l.label }}</span>
        </div>
      </div>
    </div>
    <div class="px-6 py-5">
      <VisXYContainer
        :data="data"
        :height="220"
        :style="{
          '--vis-axis-tick-label-color': 'rgba(0,0,0,0.38)',
          '--vis-axis-domain-color': 'rgba(0,0,0,0.08)',
          '--vis-axis-tick-line-color': 'transparent',
          '--vis-axis-grid-color': 'rgba(0,0,0,0.04)',
          '--vis-font-family': 'inherit',
        }"
      >
        <VisGroupedBar :x="x" :y="y" :color="colors" :bar-padding="0.2" :group-padding="0.3" :rounded-corners="3" />
        <VisAxis type="x" :tick-format="xTickFormat" />
        <VisAxis type="y" />
      </VisXYContainer>
    </div>
  </div>
</template>
