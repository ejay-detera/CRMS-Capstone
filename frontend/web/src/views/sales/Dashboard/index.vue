<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { remainingDays } from '@/types/contract'
import RecentRequestsTable from './RecentRequestsTable.vue'
import ContractStatusPanel from './ContractStatusPanel.vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'

const { state: authState } = useAuth()
const { error } = useToast()

// ── Live clock ──────────────────────────────────────────────────
const now = ref(new Date())
let timer: ReturnType<typeof setInterval>
onMounted(() => { timer = setInterval(() => { now.value = new Date() }, 1000) })
onUnmounted(() => clearInterval(timer))

const greeting = computed(() => {
  const h = now.value.getHours()
  if (h < 12) return 'Good morning'
  if (h < 17) return 'Good afternoon'
  return 'Good evening'
})
const formattedDate = computed(() =>
  now.value.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })
)
const formattedTime = computed(() =>
  now.value.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
)

const userFirstName = computed(() => authState.user?.first_name || 'Shadrack')

import { useApiCache } from '@/composables/useApiCache'

const { state: cacheState, fetchDashboard } = useApiCache()

// ── Cached live data ─────────────────────────────────────────────
const contracts = computed(() => cacheState.contracts || [])
const recentRequests = computed(() => (cacheState.requests || []).slice(0, 6))
const loading = computed(() => cacheState.contractsLoading || cacheState.requestsLoading)

async function fetchDashboardData() {
  try {
    await fetchDashboard()
  } catch {
    error('Network error', 'Could not reach the server.')
  }
}

onMounted(() => {
  fetchDashboardData()
})

const withDays = computed(() => contracts.value.map(c => ({ ...c, days: remainingDays(c.endDate) })))

const statCards = computed(() => [
  { label: 'My Contracts',    value: withDays.value.length },
  { label: 'Pending',         value: recentRequests.value.filter(r => r.status === 'Pending' || r.status === 'Under Review').length },
  { label: 'Expiring Soon',   value: withDays.value.filter(c => c.days >= 0 && c.days <= 30).length },
  { label: 'Approved',        value: recentRequests.value.filter(r => r.status === 'Approved').length },
])
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Greeting header -->
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-black/35 uppercase tracking-widest mb-0.5">Sales Portal</p>
        <h1 class="text-xl font-semibold text-black">{{ greeting }}, {{ userFirstName }}.</h1>
        <p class="text-sm text-black/40 mt-0.5">Here's an overview of your contracts and requests.</p>
      </div>
      <div class="text-right hidden sm:block">
        <p class="text-sm font-semibold text-black tabular-nums">{{ formattedTime }}</p>
        <p class="text-xs text-black/40 mt-0.5">{{ formattedDate }}</p>
      </div>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
      <template v-if="loading">
        <div v-for="i in 4" :key="i"
          class="bg-white rounded-lg border border-black/8 px-6 py-5 shadow-sm">
          <div class="h-3.5 w-24 bg-black/5 animate-pulse rounded mb-4"></div>
          <div class="h-8 w-12 bg-black/5 animate-pulse rounded"></div>
        </div>
      </template>
      <template v-else>
        <div v-for="card in statCards" :key="card.label"
          class="bg-white rounded-lg border border-black/8 px-6 py-5 shadow-sm">
          <p class="text-xs font-medium text-black/40 uppercase tracking-wide mb-3">{{ card.label }}</p>
          <p class="text-3xl font-semibold tabular-nums text-black">{{ card.value }}</p>
        </div>
      </template>
    </div>

    <!-- Main content: requests table + status panel -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
      <div class="xl:col-span-2">
        <RecentRequestsTable :requests="recentRequests" :loading="loading" />
      </div>
      <div>
        <ContractStatusPanel :contracts="withDays" :loading="loading" />
      </div>
    </div>

  </div>
</template>
