<script setup lang="ts">
import { computed } from 'vue'
import { TrendingUp } from 'lucide-vue-next'
import type { AnalyticsSummary } from '@/types/analytics'
import { serviceLabels, metricLabels } from '@/types/analytics'

const props = defineProps<{
  summary: AnalyticsSummary | null
}>()

// Metric types that read better as a percentage / ratio than a raw count.
const percentMetrics = new Set(['email_success_rate_pct'])
const decimalMetrics = new Set(['contracts_avg_approval_hours', 'avg_risk_score'])

function formatValue(metricType: string, value: number | null): string {
  if (value === null || value === undefined) return '—'
  if (percentMetrics.has(metricType)) return `${Number(value).toFixed(1)}%`
  if (decimalMetrics.has(metricType)) return Number(value).toFixed(2)
  return Math.round(value).toLocaleString('en-US')
}

const serviceOrder = ['contract-management', 'vendor-management', 'ai-service', 'notification']

const groups = computed(() => {
  if (!props.summary) return []
  return serviceOrder
    .filter(service => props.summary!.metrics[service]?.length)
    .map(service => ({
      service,
      label: serviceLabels[service] ?? service,
      metrics: props.summary!.metrics[service].filter(m => m.metricValue !== null),
    }))
})
</script>

<template>
  <div class="space-y-6">
    <div v-if="groups.length === 0" class="bg-white rounded-lg border border-black/8 shadow-sm p-10 text-center">
      <TrendingUp class="w-8 h-8 mx-auto text-black/20 mb-3" />
      <p class="text-sm text-black/40">No metrics available yet. Try refreshing analytics.</p>
    </div>

    <div v-for="group in groups" :key="group.service" class="space-y-3">
      <h3 class="text-xs font-semibold text-black/40 uppercase tracking-widest">{{ group.label }}</h3>
      <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <div
          v-for="m in group.metrics"
          :key="m.metricType"
          class="bg-white rounded-lg border border-black/8 px-5 py-4 shadow-sm"
        >
          <p class="text-xs font-medium text-black/40 mb-2 truncate" :title="metricLabels[m.metricType] ?? m.metricType">
            {{ metricLabels[m.metricType] ?? m.metricType }}
          </p>
          <span class="text-2xl font-semibold text-black tabular-nums">{{ formatValue(m.metricType, m.metricValue) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
