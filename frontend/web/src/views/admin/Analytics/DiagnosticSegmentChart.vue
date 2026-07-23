<script setup lang="ts">
import { computed } from 'vue'
import { VisXYContainer, VisStackedBar, VisAxis, VisTooltip, VisCrosshair } from '@unovis/vue'
import type { DiagnosticInsight } from '@/types/analytics'

const props = defineProps<{
  insight: DiagnosticInsight
}>()

// The segment breakdown is expected to be an array of: { category_name: string, region_name: string, count: number }
const segmentData = computed(() => {
  const breakdown = props.insight.findingSummary?.segment_breakdown ?? []
  if (!Array.isArray(breakdown) || breakdown.length === 0) return null

  // Map and sort by count descending, taking top 5
  return breakdown
    .map((item: any) => ({
      label: `${item.category_name} - ${item.region_name}`,
      count: Number(item.count || 0)
    }))
    .sort((a, b) => b.count - a.count)
    .slice(0, 5)
})

const x = (_: any, i: number) => i
const y = (d: any) => d.count
const color = () => '#2E85D8' // Brand blue

const xTickFormat = (i: number) => {
  const label = segmentData.value?.[Math.round(i)]?.label ?? ''
  // Truncate long labels
  return label.length > 20 ? label.substring(0, 18) + '...' : label
}

const yTickValues = computed(() => {
  if (!segmentData.value) return [0]
  const maxVal = Math.max(...segmentData.value.map(d => d.count), 0)
  if (maxVal === 0) return [0]
  if (maxVal <= 5) return Array.from({ length: maxVal + 1 }, (_, i) => i)
  
  const step = Math.ceil(maxVal / 5)
  const ticks = []
  for (let val = 0; val <= maxVal; val += step) ticks.push(val)
  if (ticks[ticks.length - 1] < maxVal) ticks.push(maxVal)
  return ticks
})

const tooltipTemplate = (d: any) =>
  `<div class="bg-white border border-black/10 rounded-lg shadow-lg px-3 py-2 text-xs">
    <p class="font-semibold text-black">${d.label}</p>
    <p class="text-black/50 mt-0.5">${d.count} contracts</p>
  </div>`
</script>

<template>
  <div v-if="segmentData" class="mt-4 pt-4 border-t border-black/5">
    <h4 class="text-xs font-semibold text-black/40 uppercase tracking-widest mb-3">Top Segments</h4>
    <div class="overflow-x-auto">
      <VisXYContainer
        :data="segmentData"
        :height="160"
        :style="{
          '--vis-axis-tick-label-color': 'rgba(0,0,0,0.38)',
          '--vis-axis-domain-color': 'transparent',
          '--vis-axis-tick-line-color': 'transparent',
          '--vis-axis-grid-color': 'rgba(0,0,0,0.04)',
          '--vis-font-family': 'inherit',
        }"
      >
        <VisStackedBar :x="x" :y="y" :color="color" :bar-padding="0.5" :rounded-corners="4" />
        <VisAxis type="x" :tick-format="xTickFormat" :tickValues="segmentData.map((_, i) => i)" />
        <VisAxis type="y" :tickValues="yTickValues" :tickFormat="(v: number) => String(Math.round(v))" />
        <VisTooltip :horizontal-shift="20" />
        <VisCrosshair :template="tooltipTemplate" color="#252578" />
      </VisXYContainer>
    </div>
  </div>
</template>
