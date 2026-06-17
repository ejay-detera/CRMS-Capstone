<script setup lang="ts">
import { computed, ref } from 'vue'
import { VisXYContainer, VisStackedBar, VisAxis, VisTooltip, VisCrosshair } from '@unovis/vue'
import type { Contract } from '@/types/contract'

const props = defineProps<{
  contracts: Contract[]
}>()

type Range = '3M' | '6M' | '12M' | 'ALL'

const range = ref<Range>('12M')

const trendData = computed(() => {
  const months: { month: string; count: number; yearMonth: string }[] = []
  const today = new Date()
  
  let earliestDate = new Date(today.getFullYear(), today.getMonth() - 11, 1) // default to 12 months
  let latestDate = today
  
  if (range.value === 'ALL' && props.contracts.length > 0) {
    let minDate = new Date()
    let maxDate = new Date(0)
    let foundValid = false

    props.contracts.forEach(c => {
      if (!c.startDate) return
      const d = new Date(c.startDate)
      if (d < minDate) {
        minDate = d
        foundValid = true
      }
      if (d > maxDate) {
        maxDate = d
        foundValid = true
      }
    })

    if (foundValid) {
      earliestDate = minDate
      latestDate = maxDate
    }
  }

  // Calculate months between earliestDate and latestDate
  const diffYear = latestDate.getFullYear() - earliestDate.getFullYear()
  const diffMonth = latestDate.getMonth() - earliestDate.getMonth()
  const totalMonths = diffYear * 12 + diffMonth + 1
  
  // limit 'ALL' range to maximum of 36 months to prevent squeezing labels
  let monthsToGenerate = 12
  if (range.value === '3M') monthsToGenerate = 3
  else if (range.value === '6M') monthsToGenerate = 6
  else if (range.value === '12M') monthsToGenerate = 12
  else if (range.value === 'ALL') monthsToGenerate = Math.min(totalMonths, 36)

  // In ALL timeline, generate backwards from latestDate, else generate backwards from today
  const referenceDate = range.value === 'ALL' ? latestDate : today

  for (let i = monthsToGenerate - 1; i >= 0; i--) {
    const d = new Date(referenceDate.getFullYear(), referenceDate.getMonth() - i, 1)
    const label = range.value === 'ALL'
      ? `${d.toLocaleString('en-US', { month: 'short' })} ${d.getFullYear()}`
      : d.toLocaleString('en-US', { month: 'short' })
    const yearMonth = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
    months.push({ month: label, count: 0, yearMonth })
  }

  // Populate counts
  props.contracts.forEach(c => {
    if (!c.startDate) return
    const cDate = new Date(c.startDate)
    const ym = `${cDate.getFullYear()}-${String(cDate.getMonth() + 1).padStart(2, '0')}`
    const found = months.find(m => m.yearMonth === ym)
    if (found) {
      found.count++
    }
  })

  return months
})

const x = (_: any, i: number) => i
const y = (d: any) => d.count
const xTickFormat = (i: number) => trendData.value[Math.round(i)]?.month ?? ''
const tooltipTemplate = (d: any) =>
  `<div class="bg-white border border-black/10 rounded-lg shadow-lg px-3 py-2 text-xs">
    <p class="font-semibold text-black">${d.month}</p>
    <p class="text-black/50 mt-0.5">${d.count} contracts</p>
  </div>`
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden h-full">
    <div class="px-6 pt-5 pb-4 border-b border-black/5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h3 class="text-sm font-semibold text-black">Contracts per Month (Bar)</h3>
        <p class="text-xs text-black/40 mt-0.5">
          <span v-if="range === 'ALL'">All historical months</span>
          <span v-else>Last {{ range === '3M' ? 3 : (range === '6M' ? 6 : 12) }} months</span>
        </p>
      </div>
      <div class="flex items-center gap-3">
        <div class="flex items-center gap-0.5 bg-black/4 rounded-md p-1">
          <button v-for="r in (['3M', '6M', '12M', 'ALL'] as Range[])" :key="r"
            @click="range = r"
            class="px-3 py-1 text-xs rounded transition-all font-medium"
            :class="range === r ? 'bg-white text-black shadow-sm' : 'text-black/40 hover:text-black/60'">
            {{ r }}
          </button>
        </div>
        <div class="flex items-center gap-1.5">
          <div class="w-2.5 h-2.5 rounded-sm bg-[#2E85D8]"></div>
          <span class="text-xs text-black/40">Contracts</span>
        </div>
      </div>
    </div>
    <div class="px-6 py-5 overflow-x-auto">
      <VisXYContainer
        :data="trendData"
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
        <VisAxis type="x" :tick-format="xTickFormat" :tickValues="trendData.map((_, i) => i)" />
        <VisAxis type="y" />
        <VisTooltip :horizontal-shift="20" />
        <VisCrosshair :template="tooltipTemplate" color="#2E85D8" />
      </VisXYContainer>
    </div>
  </div>
</template>
