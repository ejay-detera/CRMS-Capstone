<script setup lang="ts">
import { computed } from 'vue'
import { Sparkles, TrendingUp, TrendingDown, Minus, HelpCircle } from 'lucide-vue-next'
import type { DiagnosticInsight } from '@/types/analytics'
import { diagnosticLabels, findingFieldLabels } from '@/types/analytics'

const props = defineProps<{
  insights: DiagnosticInsight[]
}>()

function trendOf(insight: DiagnosticInsight): string {
  return insight.findingSummary?.trend ?? 'unknown'
}

function trendMeta(trend: string): { icon: any; color: string; label: string } {
  switch (trend) {
    case 'worsening':
    case 'slower':
      return { icon: TrendingUp, color: 'text-red-600 bg-red-50', label: trend === 'worsening' ? 'Worsening' : 'Slower' }
    case 'improving':
    case 'faster':
      return { icon: TrendingDown, color: 'text-emerald-600 bg-emerald-50', label: trend === 'improving' ? 'Improving' : 'Faster' }
    case 'stable':
      return { icon: Minus, color: 'text-black/50 bg-black/5', label: 'Stable' }
    default:
      return { icon: HelpCircle, color: 'text-black/40 bg-black/5', label: 'Unknown' }
  }
}

function segmentEntries(insight: DiagnosticInsight): { key: string; value: any }[] {
  const summary = insight.findingSummary ?? {}
  return Object.entries(summary)
    .filter(([key]) => key !== 'trend' && key !== 'segment_breakdown')
    .map(([key, value]) => ({ key, value }))
}

const cards = computed(() => props.insights.map(insight => ({
  insight,
  trend: trendMeta(trendOf(insight)),
  fields: segmentEntries(insight),
})))
</script>

<template>
  <div class="space-y-6">
    <div v-if="cards.length === 0" class="bg-white rounded-lg border border-black/8 shadow-sm p-10 text-center">
      <Sparkles class="w-8 h-8 mx-auto text-black/20 mb-3" />
      <p class="text-sm text-black/40">No diagnostic insights yet. Try refreshing analytics.</p>
    </div>

    <div v-for="card in cards" :key="card.insight.metricType" class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">
      <div class="px-6 pt-5 pb-4 border-b border-black/5 flex items-start justify-between gap-4">
        <div>
          <h3 class="text-sm font-semibold text-black">
            {{ diagnosticLabels[card.insight.metricType] ?? card.insight.metricType }}
          </h3>
          <p class="text-xs text-black/40 mt-0.5">
            {{ card.insight.periodStart }} — {{ card.insight.periodEnd }}
          </p>
        </div>
        <span
          class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full shrink-0"
          :class="card.trend.color"
        >
          <component :is="card.trend.icon" class="w-3.5 h-3.5" />
          {{ card.trend.label }}
        </span>
      </div>

      <div class="px-6 py-5 space-y-5">
        <!-- Structured, explainable root-cause fields — no black box -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
          <div v-for="f in card.fields" :key="f.key" class="min-w-0">
            <p class="text-[11px] text-black/40 uppercase tracking-wide truncate">
              {{ findingFieldLabels[f.key] ?? f.key }}
            </p>
            <p class="text-sm font-semibold text-black mt-0.5 truncate">
              {{ f.value === null || f.value === undefined ? '—' : f.value }}
            </p>
          </div>
        </div>

        <!-- AI narrative summarizing the already-computed structured result -->
        <div v-if="card.insight.aiNarrative" class="flex items-start gap-2.5 bg-[#2E85D8]/[0.05] border border-[#2E85D8]/10 rounded-lg px-4 py-3">
          <Sparkles class="w-4 h-4 text-[#2E85D8] shrink-0 mt-0.5" />
          <p class="text-sm text-black/70 leading-relaxed">{{ card.insight.aiNarrative }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
