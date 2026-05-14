<script setup lang="ts">
import DashboardStats          from './DashboardStats.vue'
import ContractTrendChart      from './ContractTrendChart.vue'
import ContractStatusChart     from './ContractStatusChart.vue'
import ContractsByRegionChart  from './ContractsByRegionChart.vue'
import RecentContractsTable    from './RecentContractsTable.vue'
import ExpiringContractsList   from './ExpiringContractsList.vue'
</script>

<template>
  <div class="p-8 space-y-6">

    <div>
      <h1 class="text-xl font-semibold text-black">Dashboard</h1>
      <p class="text-sm text-black/40 mt-0.5">Welcome back — here's your contract overview for today.</p>
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
