<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import DashboardStats          from './DashboardStats.vue'
import ContractTrendChart      from './ContractTrendChart.vue'
import ContractStatusChart     from './ContractStatusChart.vue'
import ContractsByRegionChart  from './ContractsByRegionChart.vue'
import RecentContractsTable    from './RecentContractsTable.vue'
import ExpiringContractsList   from './ExpiringContractsList.vue'

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
</script>

<template>
  <div class="p-8 space-y-6">

    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-black/35 uppercase tracking-widest mb-0.5">Manager Portal</p>
        <h1 class="text-xl font-semibold text-black">{{ greeting }}, Shadrack.</h1>
        <p class="text-sm text-black/40 mt-0.5">Here's your contract overview for today.</p>
      </div>
      <div class="text-right hidden sm:block">
        <p class="text-sm font-semibold text-black tabular-nums">{{ formattedTime }}</p>
        <p class="text-xs text-black/40 mt-0.5">{{ formattedDate }}</p>
      </div>
    </div>

    <!-- KPI Cards -->
    <DashboardStats />

    <!-- Trend chart + Status donut -->
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
      <div class="xl:col-span-3"><ContractTrendChart /></div>
      <div class="xl:col-span-2"><ContractStatusChart /></div>
    </div>

    <!-- Recent contracts table + Expiring soon list -->
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
      <div class="xl:col-span-3"><RecentContractsTable /></div>
      <div class="xl:col-span-2"><ExpiringContractsList /></div>
    </div>

    <!-- Grouped bar: category × region -->
    <ContractsByRegionChart />

  </div>
</template>
