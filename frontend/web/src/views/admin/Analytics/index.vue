<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { RefreshCw, BarChart3, TrendingUp, Sparkles, Activity } from 'lucide-vue-next'
import { useAnalytics } from '@/composables/useAnalytics'
import { useToast } from '@/composables/useToast'

// Sub-components for Tabs
import DescriptiveTab from './DescriptiveTab.vue'
import DiagnosticInsightsPanel from './DiagnosticInsightsPanel.vue'
import PredictiveTab from './PredictiveTab.vue'

const {
  summary,
  insights,
  predictions,
  loadingSummary,
  loadingDiagnostics,
  loadingPredictive,
  refreshing,
  error,
  fetchSummary,
  fetchDiagnostics,
  fetchPredictive,
  refresh
} = useAnalytics()

const { success, error: toastError } = useToast()

type Tab = 'descriptive' | 'diagnostic' | 'predictive'
const activeTab = ref<Tab>('descriptive')

const loading = computed(() => loadingSummary.value || loadingDiagnostics.value || loadingPredictive.value)

const asOfLabel = computed(() => {
  if (!summary.value?.asOf) return null
  const d = new Date(summary.value.asOf)
  return isNaN(d.getTime()) ? summary.value.asOf : d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
})

async function loadAll() {
  await Promise.all([
    fetchSummary(),
    fetchDiagnostics(),
    fetchPredictive(),
  ])
}

async function handleRefresh() {
  const result = await refresh()
  if (result) {
    success('Analytics refreshed', 'Latest metrics, diagnostic insights, and AI forecasts have been recomputed.')
    await loadAll()
  } else {
    toastError('Refresh failed', error.value ?? 'Could not refresh analytics right now.')
  }
}

onMounted(loadAll)
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <p class="text-xs font-semibold text-black/35 uppercase tracking-widest mb-0.5">Analytics & Intelligence</p>
        <h1 class="text-xl font-semibold text-black">System Analytics</h1>
        <p class="text-sm text-black/40 mt-0.5">
          <template v-if="asOfLabel">Descriptive, diagnostic, and predictive insights as of {{ asOfLabel }}.</template>
          <template v-else>Descriptive, diagnostic, and predictive insights across the system.</template>
        </p>
      </div>
      <button
        @click="handleRefresh"
        :disabled="refreshing"
        class="inline-flex items-center gap-2 text-sm font-medium px-4 py-2 rounded-lg bg-[#252578] text-white hover:bg-[#2F2F73] transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-xs"
      >
        <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': refreshing }" />
        {{ refreshing ? 'Refreshing AI & Data…' : 'Refresh Analytics' }}
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex items-center gap-1 bg-black/4 rounded-xl p-1 w-fit border border-black/5">
      <button
        @click="activeTab = 'descriptive'"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all"
        :class="activeTab === 'descriptive' ? 'bg-white text-black shadow-xs' : 'text-black/50 hover:text-black'"
      >
        <Activity class="w-4 h-4 text-[#252578]" />
        Descriptive
      </button>

      <button
        @click="activeTab = 'diagnostic'"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all"
        :class="activeTab === 'diagnostic' ? 'bg-white text-black shadow-xs' : 'text-black/50 hover:text-black'"
      >
        <Sparkles class="w-4 h-4 text-[#2E85D8]" />
        Diagnostic Health Report
      </button>

      <button
        @click="activeTab = 'predictive'"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all"
        :class="activeTab === 'predictive' ? 'bg-white text-black shadow-xs' : 'text-black/50 hover:text-black'"
      >
        <TrendingUp class="w-4 h-4 text-[#252578]" />
        Predictive 30-Day Forecast
      </button>
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading && !summary && insights.length === 0 && predictions.length === 0" class="space-y-6 animate-pulse">
      <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <div v-for="i in 8" :key="i" class="bg-white rounded-lg border border-black/8 px-6 py-5 shadow-sm">
          <div class="h-3.5 w-24 bg-black/5 rounded mb-4"></div>
          <div class="h-8 w-12 bg-black/5 rounded"></div>
        </div>
      </div>
    </div>

    <!-- Error state -->
    <div v-else-if="error && !summary && insights.length === 0 && predictions.length === 0" class="bg-white rounded-lg border border-black/8 shadow-sm p-10 text-center">
      <BarChart3 class="w-8 h-8 mx-auto text-black/20 mb-3" />
      <p class="text-sm text-black/50">{{ error }}</p>
    </div>

    <!-- Descriptive Tab -->
    <div v-else-if="activeTab === 'descriptive'">
      <DescriptiveTab :summary="summary" />
    </div>

    <!-- Diagnostic Tab -->
    <div v-else-if="activeTab === 'diagnostic'">
      <DiagnosticInsightsPanel :insights="insights" />
    </div>

    <!-- Predictive Tab -->
    <div v-else-if="activeTab === 'predictive'">
      <PredictiveTab :predictions="predictions" />
    </div>

  </div>
</template>
