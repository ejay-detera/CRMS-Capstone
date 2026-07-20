<script setup lang="ts">
import { computed } from 'vue'
import { Sparkles, TrendingUp, TrendingDown, Minus, AlertCircle } from 'lucide-vue-next'
import type { DiagnosticInsight } from '@/types/analytics'
import { diagnosticLabels } from '@/types/analytics'

const props = defineProps<{
  insights: DiagnosticInsight[]
}>()

function trendMeta(trend: string) {
  switch (trend) {
    case 'worsening':
      return { icon: TrendingUp,   bgColor: 'bg-red-50',      textColor: 'text-red-600',     barColor: '#EF4444', label: 'Worsening', desc: 'This metric is getting worse compared to the prior period.' }
    case 'slower':
      return { icon: TrendingUp,   bgColor: 'bg-red-50',      textColor: 'text-red-600',     barColor: '#EF4444', label: 'Slower',    desc: 'Approvals are taking longer than the prior period.' }
    case 'improving':
      return { icon: TrendingDown, bgColor: 'bg-emerald-50',  textColor: 'text-emerald-600', barColor: '#10B981', label: 'Improving', desc: 'This metric is trending in the right direction.' }
    case 'faster':
      return { icon: TrendingDown, bgColor: 'bg-emerald-50',  textColor: 'text-emerald-600', barColor: '#10B981', label: 'Faster',    desc: 'Approvals are being processed faster than before.' }
    case 'stable':
      return { icon: Minus,        bgColor: 'bg-black/5',     textColor: 'text-black/50',    barColor: '#6B7280', label: 'Stable',    desc: 'No significant change since the prior period.' }
    default:
      return { icon: AlertCircle,  bgColor: 'bg-black/5',     textColor: 'text-black/40',    barColor: '#9CA3AF', label: 'Unknown',   desc: 'Not enough data to determine the trend.' }
  }
}

// Build a friendly plain-English description of what each diagnostic metric means
const metricDescriptions: Record<string, string> = {
  risk_flag_rate:          'Measures the proportion of contracts that were flagged as high-risk by the AI scanning service. A rising rate means more contracts are being identified as risky.',
  approval_sla_bottleneck: 'Tracks how long it takes, on average, for contracts to move through the approval workflow. A longer average means the process is getting slower.',
}

// Returns a human-readable unit label for the values
const metricUnits: Record<string, string> = {
  risk_flag_rate:          'risk rate',
  approval_sla_bottleneck: 'hours avg.',
}

type ComparionBar = { label: string; value: number; pct: number; color: string }

function buildComparison(insight: DiagnosticInsight, trend: ReturnType<typeof trendMeta>): { current: ComparionBar; prior: ComparionBar } | null {
  const s = insight.findingSummary ?? {}
  // Support both naming patterns
  const current = s.current_period_avg ?? s.current_period_avg_hours ?? null
  const prior   = s.prior_period_avg   ?? s.prior_period_avg_hours   ?? null
  if (current === null || prior === null) return null

  const max = Math.max(current, prior, 0.001)
  return {
    current: { label: 'This Period',  value: current, pct: Math.round((current / max) * 100), color: trend.barColor },
    prior:   { label: 'Prior Period', value: prior,   pct: Math.round((prior   / max) * 100), color: '#CBD5E1' },
  }
}

function formatNum(v: number, metricType: string): string {
  if (metricType === 'approval_sla_bottleneck') return `${Number(v).toFixed(1)} hrs`
  if (metricType === 'risk_flag_rate')          return `${Number(v).toFixed(2)}`
  return Number(v).toFixed(2)
}

function deltaText(insight: DiagnosticInsight): string | null {
  const s = insight.findingSummary ?? {}
  const delta = s.delta ?? s.delta_hours ?? null
  if (delta === null) return null
  const sign = delta > 0 ? '+' : ''
  const unit = metricUnits[insight.metricType] ?? ''
  return `${sign}${Number(delta).toFixed(2)} ${unit} vs. prior period`
}

const cards = computed(() => props.insights.map(insight => {
  const trend = trendMeta(insight.findingSummary?.trend ?? 'unknown')
  const comparison = buildComparison(insight, trend)
  const delta = deltaText(insight)
  return { insight, trend, comparison, delta }
}))
</script>

<template>
  <div class="space-y-6">
    <div v-if="cards.length === 0" class="bg-white rounded-xl border border-black/8 shadow-sm p-10 text-center">
      <Sparkles class="w-8 h-8 mx-auto text-black/20 mb-3" />
      <p class="text-sm text-black/40">No diagnostic insights yet. Try refreshing analytics.</p>
    </div>

    <div v-for="card in cards" :key="card.insight.metricType" class="bg-white rounded-xl border border-black/8 shadow-sm overflow-hidden">

      <!-- Header -->
      <div class="px-6 pt-5 pb-4 border-b border-black/5 flex items-start justify-between gap-4">
        <div class="min-w-0">
          <h3 class="text-sm font-semibold text-black">
            {{ diagnosticLabels[card.insight.metricType] ?? card.insight.metricType }}
          </h3>
          <p class="text-xs text-black/40 mt-0.5">
            {{ card.insight.periodStart }} — {{ card.insight.periodEnd }}
          </p>
        </div>
        <!-- Trend badge -->
        <span
          class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full shrink-0"
          :class="[card.trend.bgColor, card.trend.textColor]"
        >
          <component :is="card.trend.icon" class="w-3.5 h-3.5" />
          {{ card.trend.label }}
        </span>
      </div>

      <div class="px-6 py-5 space-y-5">

        <!-- What does this metric mean? -->
        <div class="text-sm text-black/60 leading-relaxed">
          {{ metricDescriptions[card.insight.metricType] ?? card.trend.desc }}
        </div>

        <!-- Current vs. Prior comparison bars -->
        <div v-if="card.comparison" class="space-y-3">
          <p class="text-xs font-semibold text-black/40 uppercase tracking-widest">Period Comparison</p>
          <div v-for="bar in [card.comparison.current, card.comparison.prior]" :key="bar.label" class="space-y-1">
            <div class="flex items-center justify-between">
              <span class="text-xs font-medium text-black/60">{{ bar.label }}</span>
              <span class="text-xs font-bold text-black tabular-nums">
                {{ formatNum(bar.value, card.insight.metricType) }}
              </span>
            </div>
            <div class="w-full h-3 rounded-full bg-black/5 overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-700"
                :style="{ width: bar.pct + '%', backgroundColor: bar.color }"
              />
            </div>
          </div>
          <!-- Delta callout -->
          <div
            v-if="card.delta"
            class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium"
            :class="[card.trend.bgColor, card.trend.textColor]"
          >
            <component :is="card.trend.icon" class="w-3.5 h-3.5 shrink-0" />
            {{ card.delta }}
          </div>
        </div>

        <!-- AI Narrative (plain-English summary) -->
        <div
          v-if="card.insight.aiNarrative"
          class="flex items-start gap-3 bg-[#2E85D8]/[0.05] border border-[#2E85D8]/10 rounded-lg px-4 py-3"
        >
          <Sparkles class="w-4 h-4 text-[#2E85D8] shrink-0 mt-0.5" />
          <p class="text-sm text-black/70 leading-relaxed">{{ card.insight.aiNarrative }}</p>
        </div>

      </div>
    </div>
  </div>
</template>
