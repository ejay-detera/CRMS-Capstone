<script setup lang="ts">
import { computed, ref } from 'vue'
import { VisXYContainer, VisStackedBar, VisAxis, VisTooltip, VisCrosshair } from '@unovis/vue'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue
} from '@/components/ui/select'
import type { Contract } from '@/types/contract'

const props = defineProps<{
  contracts: Contract[]
}>()

type Range = '3M' | '6M' | '12M'

const range = ref<Range>('12M')
const selectedYear = ref<number | null>(null)

const availableYears = computed(() => {
  const years = new Set<number>()
  props.contracts.forEach(c => {
    if (!c.startDate) return
    const yr = new Date(c.startDate).getFullYear()
    years.add(yr)
  })
  return Array.from(years).sort((a, b) => b - a)
})

function handleYearSelect(v: any) {
  if (!v || v === '__none__') {
    selectedYear.value = null
  } else {
    selectedYear.value = Number(v)
  }
}

function handleRangeClick(r: Range) {
  selectedYear.value = null
  range.value = r
}

const trendData = computed(() => {
  const months: { month: string; count: number; yearMonth: string; monthIndex: number }[] = []
  
  // Find reference date (latest start date among all contracts, or today, whichever is later)
  let latestDate = new Date()
  
  props.contracts.forEach(c => {
    if (!c.startDate) return
    const d = new Date(c.startDate)
    if (!isNaN(d.getTime()) && d > latestDate) {
      latestDate = d
    }
  })

  if (selectedYear.value !== null) {
    const yr = selectedYear.value
    // Generate all 12 months for selected calendar year
    for (let m = 0; m < 12; m++) {
      const d = new Date(yr, m, 1)
      const label = d.toLocaleString('en-US', { month: 'short' })
      const yearMonth = `${yr}-${String(m + 1).padStart(2, '0')}`
      months.push({ month: label, count: 0, yearMonth, monthIndex: m })
    }
  } else {
    // Generate relative months based on the reference date
    let monthsToGenerate = 12
    if (range.value === '3M') monthsToGenerate = 3
    else if (range.value === '6M') monthsToGenerate = 6
    else if (range.value === '12M') monthsToGenerate = 12

    for (let i = monthsToGenerate - 1; i >= 0; i--) {
      const d = new Date(latestDate.getFullYear(), latestDate.getMonth() - i, 1)
      const label = d.toLocaleString('en-US', { month: 'short' })
      const yearMonth = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
      months.push({ month: label, count: 0, yearMonth, monthIndex: d.getMonth() })
    }
  }

  // Populate counts
  props.contracts.forEach(c => {
    if (!c.startDate) return
    const cDate = new Date(c.startDate)
    if (isNaN(cDate.getTime())) return
    const ym = `${cDate.getFullYear()}-${String(cDate.getMonth() + 1).padStart(2, '0')}`
    const found = months.find(m => m.yearMonth === ym)
    if (found) {
      found.count++
    }
  })

  // Ensure chronological calendar month sorting ascending (June to December)
  months.sort((a, b) => a.monthIndex - b.monthIndex)

  return months
})

const chartSubtitle = computed(() => {
  if (selectedYear.value !== null) {
    return `Monthly trend for year ${selectedYear.value}`
  }
  return `Last ${range.value === '3M' ? 3 : (range.value === '6M' ? 6 : 12)} months`
})

const yTickValues = computed(() => {
  const maxVal = Math.max(...trendData.value.map(d => d.count), 0)
  if (maxVal === 0) return [0]
  if (maxVal <= 5) {
    return Array.from({ length: maxVal + 1 }, (_, i) => i)
  }
  const step = Math.ceil(maxVal / 5)
  const ticks = []
  for (let val = 0; val <= maxVal; val += step) {
    ticks.push(val)
  }
  if (ticks[ticks.length - 1] < maxVal) {
    ticks.push(maxVal)
  }
  return ticks
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
        <h3 class="text-sm font-semibold text-black">My Contracts per Month</h3>
        <p class="text-xs text-black/40 mt-0.5">{{ chartSubtitle }}</p>
      </div>
      <div class="flex items-center gap-3">
        <!-- Relative range buttons -->
        <div class="flex items-center gap-0.5 bg-black/4 rounded-md p-1">
          <button v-for="r in (['3M', '6M', '12M'] as Range[])" :key="r"
            @click="handleRangeClick(r)"
            class="px-3 py-1 text-xs rounded transition-all font-medium"
            :class="range === r && selectedYear === null
              ? 'bg-white text-black shadow-sm'
              : 'text-black/40 hover:text-black/60'">
            {{ r }}
          </button>
        </div>
        
        <!-- Year Select dropdown -->
        <Select :model-value="selectedYear !== null ? String(selectedYear) : '__none__'" @update:model-value="handleYearSelect">
          <SelectTrigger class="w-24 h-8 rounded-md border-black/10 bg-white text-xs text-black/70 focus:ring-[#2E85D8]/15">
            <SelectValue placeholder="Year" />
          </SelectTrigger>
          <SelectContent class="bg-white border border-black/10 shadow-lg">
            <SelectItem value="__none__">Year</SelectItem>
            <SelectItem v-for="yr in availableYears" :key="yr" :value="String(yr)">{{ yr }}</SelectItem>
          </SelectContent>
        </Select>

        <div class="flex items-center gap-1.5 ml-2">
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
        <VisAxis type="y" :tickValues="yTickValues" :tickFormat="(v: number) => String(Math.round(v))" />
        <VisTooltip :horizontal-shift="20" />
        <VisCrosshair :template="tooltipTemplate" color="#2E85D8" />
      </VisXYContainer>
    </div>
  </div>
</template>
