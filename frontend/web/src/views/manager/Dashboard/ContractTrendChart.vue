<script setup lang="ts">
import { VisXYContainer, VisStackedBar, VisAxis, VisTooltip, VisCrosshair } from '@unovis/vue'

type MonthData = { month: string; count: number }

const data: MonthData[] = [
  { month: 'Aug', count: 6  },
  { month: 'Sep', count: 9  },
  { month: 'Oct', count: 13 },
  { month: 'Nov', count: 8  },
  { month: 'Dec', count: 11 },
  { month: 'Jan', count: 15 },
  { month: 'Feb', count: 10 },
  { month: 'Mar', count: 18 },
  { month: 'Apr', count: 14 },
  { month: 'May', count: 16 },
]

const x = (_: MonthData, i: number) => i
const y = (d: MonthData) => d.count
const xTickFormat = (i: number) => data[Math.round(i)]?.month ?? ''
const tooltipTemplate = (d: MonthData) =>
  `<div class="bg-white border border-black/10 rounded-lg shadow-lg px-3 py-2 text-xs">
    <p class="font-semibold text-black">${d.month}</p>
    <p class="text-black/50 mt-0.5">${d.count} contracts</p>
  </div>`
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden h-full">
    <div class="px-6 pt-5 pb-4 border-b border-black/5 flex items-center justify-between">
      <div>
        <h3 class="text-sm font-semibold text-black">Contracts per Month</h3>
        <p class="text-xs text-black/40 mt-0.5">Last 10 months</p>
      </div>
      <div class="flex items-center gap-1.5">
        <div class="w-2.5 h-2.5 rounded-sm bg-[#2E85D8]"></div>
        <span class="text-xs text-black/40">Contracts</span>
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
        <VisStackedBar :x="x" :y="y" color="#2E85D8" :bar-padding="0.35" :rounded-corners="4" />
        <VisAxis type="x" :tick-format="xTickFormat" />
        <VisAxis type="y" />
        <VisTooltip :horizontal-shift="20" />
        <VisCrosshair :template="tooltipTemplate" color="#2E85D8" />
      </VisXYContainer>
    </div>
  </div>
</template>
