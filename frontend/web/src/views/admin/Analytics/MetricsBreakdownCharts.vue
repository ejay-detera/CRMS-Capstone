<script setup lang="ts">
import { computed } from 'vue'
import { VisSingleContainer, VisDonut } from '@unovis/vue'
import type { AnalyticsSummary } from '@/types/analytics'

const props = defineProps<{
  summary: AnalyticsSummary | null
}>()

type SliceItem = { label: string; value: number; color: string }

// Brand-color rotation for segment breakdowns pulled from metric metadata.
const PALETTE = ['#252578', '#2E85D8', '#2F2F73', '#5B7FD1', '#8FA8E0']

function toSlices(record: Record<string, number> | null | undefined): SliceItem[] {
  if (!record) return []
  return Object.entries(record)
    .filter(([, v]) => typeof v === 'number' && v > 0)
    .map(([label, value], i) => ({ label, value, color: PALETTE[i % PALETTE.length] }))
}

function findMetadata(service: string, metricType: string): Record<string, any> | null {
  const entry = props.summary?.metrics[service]?.find(m => m.metricType === metricType)
  return entry?.metadata ?? null
}

const contractsByStatus = computed(() => toSlices(findMetadata('contract-management', 'high_risk_approvals_escalated')?.by_status))
const contractsByCategory = computed(() => toSlices(findMetadata('contract-management', 'high_risk_approvals_escalated')?.by_category))
const suppliersByRegion = computed(() => toSlices(findMetadata('vendor-management', 'partners_total')?.suppliers_by_region))
const aiByRiskLevel = computed(() => toSlices(findMetadata('ai-service', 'avg_risk_score')?.by_risk_level))

const charts = computed(() => [
  { title: 'Contracts by Status',    subtitle: 'Current workflow distribution', data: contractsByStatus.value },
  { title: 'Contracts by Category',  subtitle: 'Distribution by contract category', data: contractsByCategory.value },
  { title: 'Suppliers by Region',    subtitle: 'Distribution by region', data: suppliersByRegion.value },
  { title: 'Risk Assessments by Level', subtitle: 'Contracts scanned by AI Risk Assessment', data: aiByRiskLevel.value },
].filter(c => c.data.length > 0))

const value = (d: SliceItem) => d.value
const color = (d: SliceItem) => d.color
</script>

<template>
  <div v-if="charts.length > 0" class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div
      v-for="chart in charts"
      :key="chart.title"
      class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden"
    >
      <div class="px-6 pt-5 pb-4 border-b border-black/5">
        <h3 class="text-sm font-semibold text-black">{{ chart.title }}</h3>
        <p class="text-xs text-black/40 mt-0.5">{{ chart.subtitle }}</p>
      </div>
      <div class="px-6 py-6 flex flex-col items-center gap-5">
        <div class="relative w-40 h-40 shrink-0">
          <VisSingleContainer :data="chart.data" :height="160" :width="160">
            <VisDonut :value="value" :color="color" :arc-width="34" />
          </VisSingleContainer>
          <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
            <p class="text-xl font-bold text-black tabular-nums leading-none">
              {{ chart.data.reduce((s, d) => s + d.value, 0) }}
            </p>
            <p class="text-[10px] text-black/35 mt-0.5">total</p>
          </div>
        </div>
        <div class="w-full space-y-2">
          <div v-for="item in chart.data" :key="item.label" class="flex items-center justify-between">
            <div class="flex items-center gap-2 min-w-0">
              <div class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: item.color }" />
              <span class="text-xs text-black/60 truncate">{{ item.label }}</span>
            </div>
            <span class="text-xs font-semibold text-black shrink-0">{{ item.value }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
