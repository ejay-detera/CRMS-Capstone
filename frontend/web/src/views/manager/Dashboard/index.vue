<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useApiCache } from '@/composables/useApiCache'
import { useToast } from '@/composables/useToast'
import { remainingDays } from '@/types/contract'
import DashboardStats          from './DashboardStats.vue'
import ContractTrendChart      from './ContractTrendChart.vue'
import ContractStatusChart     from './ContractStatusChart.vue'
import ContractsByRegionChart  from './ContractsByRegionChart.vue'
import RecentContractsTable    from './RecentContractsTable.vue'
import ExpiringContractsList   from './ExpiringContractsList.vue'

const { state: authState, hasPermission } = useAuth()
const { state: cacheState, fetchDashboard } = useApiCache()
const { error } = useToast()

const canViewContracts = computed(() => hasPermission('crms.contracts.view'))

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

const userFirstName = computed(() => authState.user?.profile?.first_name || authState.user?.first_name || 'Manager')

const contracts = computed(() => cacheState.contracts || [])
const loading = computed(() => cacheState.contractsLoading)

const withDays = computed(() =>
  contracts.value.map(c => ({ ...c, days: remainingDays(c.endDate) }))
)

async function fetchDashboardData() {
  try {
    await fetchDashboard()
  } catch {
    error('Network error', 'Could not reach the server.')
  }
}

watch(canViewContracts, (canView) => {
  if (canView) {
    fetchDashboardData()
  }
}, { immediate: true })
</script>

<template>
  <div class="p-8 space-y-6">

    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-black/35 uppercase tracking-widest mb-0.5">Manager Portal</p>
        <h1 class="text-xl font-semibold text-black">{{ greeting }}, {{ userFirstName }}.</h1>
        <p class="text-sm text-black/40 mt-0.5">Here's your contract overview for today.</p>
      </div>
      <div class="text-right hidden sm:block">
        <p class="text-sm font-semibold text-black tabular-nums">{{ formattedTime }}</p>
        <p class="text-xs text-black/40 mt-0.5">{{ formattedDate }}</p>
      </div>
    </div>

    <!-- KPI Cards (Skeletal loading or Real cards) -->
    <template v-if="loading">
      <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <div v-for="i in 4" :key="i" class="bg-white rounded-lg border border-black/8 px-5 py-5 shadow-sm animate-pulse">
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-black/5 shrink-0"></div>
            <div class="flex-1 space-y-2">
              <div class="h-3 w-16 bg-black/5 rounded"></div>
              <div class="h-6 w-12 bg-black/5 rounded"></div>
            </div>
          </div>
        </div>
      </div>
    </template>
    <DashboardStats v-else-if="canViewContracts" :contracts="withDays" />

    <!-- Trend chart + Status donut -->
    <template v-if="loading">
      <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
        <div class="xl:col-span-3 bg-white rounded-lg border border-black/8 p-6 h-[280px] animate-pulse flex flex-col justify-between">
          <div class="h-4 w-32 bg-black/5 rounded"></div>
          <div class="h-40 w-full bg-black/5 rounded"></div>
        </div>
        <div class="xl:col-span-2 bg-white rounded-lg border border-black/8 p-6 h-[280px] animate-pulse flex flex-col justify-between">
          <div class="h-4 w-24 bg-black/5 rounded"></div>
          <div class="h-40 w-full bg-black/5 rounded"></div>
        </div>
      </div>
    </template>
    <div v-else-if="canViewContracts" class="grid grid-cols-1 xl:grid-cols-5 gap-6">
      <div class="xl:col-span-3"><ContractTrendChart :contracts="contracts" /></div>
      <div class="xl:col-span-2"><ContractStatusChart :contracts="contracts" /></div>
    </div>

    <!-- Recent contracts table + Expiring soon list -->
    <template v-if="loading">
      <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
        <div class="xl:col-span-3 bg-white rounded-lg border border-black/8 p-6 h-[320px] animate-pulse flex flex-col justify-between">
          <div class="h-4 w-36 bg-black/5 rounded"></div>
          <div class="h-48 w-full bg-black/5 rounded"></div>
        </div>
        <div class="xl:col-span-2 bg-white rounded-lg border border-black/8 p-6 h-[320px] animate-pulse flex flex-col justify-between">
          <div class="h-4 w-28 bg-black/5 rounded"></div>
          <div class="h-48 w-full bg-black/5 rounded"></div>
        </div>
      </div>
    </template>
    <div v-else-if="canViewContracts" class="grid grid-cols-1 xl:grid-cols-5 gap-6">
      <div class="xl:col-span-3"><RecentContractsTable :contracts="contracts" /></div>
      <div class="xl:col-span-2"><ExpiringContractsList :contracts="withDays" /></div>
    </div>

    <!-- Grouped bar: category × region -->
    <template v-if="loading">
      <div class="bg-white rounded-lg border border-black/8 p-6 h-[280px] animate-pulse flex flex-col justify-between">
        <div class="h-4 w-48 bg-black/5 rounded"></div>
        <div class="h-40 w-full bg-black/5 rounded"></div>
      </div>
    </template>
    <ContractsByRegionChart v-else-if="canViewContracts" :contracts="contracts" />

    <div v-if="!canViewContracts" class="bg-white p-8 rounded-xl border border-black/10 text-center">
      <p class="text-black/40">You do not have permission to view contract data.</p>
    </div>

  </div>
</template>
