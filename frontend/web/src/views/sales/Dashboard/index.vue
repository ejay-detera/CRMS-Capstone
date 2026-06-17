<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { remainingDays } from '@/types/contract'
import RecentRequestsTable from './RecentRequestsTable.vue'
import ContractStatusPanel from './ContractStatusPanel.vue'
import SalesTrendChart from './SalesTrendChart.vue'
import SalesStatusChart from './SalesStatusChart.vue'
import SalesCategoryChart from './SalesCategoryChart.vue'
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

const userFirstName = computed(() => (authState.user as any)?.profile?.first_name || authState.user?.first_name || 'Sales Rep')

import { useApiCache } from '@/composables/useApiCache'

const { state: cacheState, fetchDashboard } = useApiCache()

// ── Cached live data ─────────────────────────────────────────────
const contracts = computed(() => cacheState.contracts || [])
const requests = computed(() => cacheState.requests || [])
const recentRequests = computed(() => requests.value.slice(0, 6))
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
  { label: 'My Contracts',    value: withDays.value.filter(c => c.approvalStatus === 'Approved').length },
  { label: 'Pending',         value: requests.value.filter(r => r.status === 'Pending' || r.status === 'Under Review').length },
  { label: 'Expiring Soon',   value: withDays.value.filter(c => c.approvalStatus === 'Approved' && c.days >= 0 && c.days <= 30).length },
  { label: 'Approved',        value: requests.value.filter(r => r.status === 'Approved').length },
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

    <!-- Skeletal loader during fetch -->
    <div v-if="loading" class="space-y-6">
      <!-- 1. KPI Cards (Stats summary) -->
      <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 animate-pulse">
        <div v-for="i in 4" :key="i" class="bg-white rounded-lg border border-black/8 px-6 py-5 shadow-sm">
          <div class="h-3.5 w-24 bg-black/5 rounded mb-4"></div>
          <div class="h-8 w-12 bg-black/5 rounded"></div>
        </div>
      </div>
      
      <!-- 2. Row 1: Trend Chart + Status Donut -->
      <div class="grid grid-cols-1 xl:grid-cols-5 gap-6 animate-pulse">
        <div class="xl:col-span-3 bg-white rounded-lg border border-black/8 p-6 h-[280px] flex flex-col justify-between">
          <div class="h-4 w-32 bg-black/5 rounded"></div>
          <div class="h-40 w-full bg-black/5 rounded"></div>
        </div>
        <div class="xl:col-span-2 bg-white rounded-lg border border-black/8 p-6 h-[280px] flex flex-col justify-between">
          <div class="h-4 w-24 bg-black/5 rounded"></div>
          <div class="h-40 w-full bg-black/5 rounded"></div>
        </div>
      </div>

      <!-- 3. Row 2: Category Chart + Status Panel -->
      <div class="grid grid-cols-1 xl:grid-cols-5 gap-6 animate-pulse">
        <div class="xl:col-span-3 bg-white rounded-lg border border-black/8 p-6 h-[280px] flex flex-col justify-between">
          <div class="h-4 w-36 bg-black/5 rounded"></div>
          <div class="h-40 w-full bg-black/5 rounded"></div>
        </div>
        <!-- Status Panel Skeleton (covers Expiring Soon & Quick Actions) -->
        <div class="xl:col-span-2 space-y-4">
          <div class="bg-white rounded-lg border border-black/8 p-5 h-[160px] flex flex-col justify-between">
            <div class="h-3.5 w-24 bg-black/5 rounded"></div>
            <div class="h-20 w-full bg-black/5 rounded"></div>
          </div>
          <div class="bg-white rounded-lg border border-black/8 p-5 h-[106px] flex flex-col justify-between">
            <div class="h-3.5 w-24 bg-black/5 rounded"></div>
            <div class="h-10 w-full bg-black/5 rounded"></div>
          </div>
        </div>
      </div>

      <!-- 4. Row 3: Recent Requests Table (Full Width) -->
      <div class="bg-white rounded-lg border border-black/8 p-6 h-[320px] animate-pulse flex flex-col justify-between">
        <div class="h-4 w-28 bg-black/5 rounded"></div>
        <div class="h-48 w-full bg-black/5 rounded"></div>
      </div>
    </div>

    <!-- Active Analytics Dashboard -->
    <div v-else class="space-y-6">
      <!-- Stat cards -->
      <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <div v-for="card in statCards" :key="card.label"
          class="bg-white rounded-lg border border-black/8 px-6 py-5 shadow-sm">
          <p class="text-xs font-medium text-black/40 uppercase tracking-wide mb-3">{{ card.label }}</p>
          <p class="text-3xl font-semibold tabular-nums text-black">{{ card.value }}</p>
        </div>
      </div>

      <!-- Row 1: Trend Chart + Status Donut -->
      <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
        <div class="xl:col-span-3">
          <SalesTrendChart :contracts="contracts" />
        </div>
        <div class="xl:col-span-2">
          <SalesStatusChart :requests="requests" />
        </div>
      </div>

      <!-- Row 2: Category Chart + Status Panel -->
      <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
        <div class="xl:col-span-3">
          <SalesCategoryChart :contracts="contracts" />
        </div>
        <div class="xl:col-span-2">
          <ContractStatusPanel :contracts="withDays" />
        </div>
      </div>

      <!-- Row 3: Recent Requests Table (Full Width) -->
      <RecentRequestsTable :requests="recentRequests" />
    </div>

  </div>
</template>
