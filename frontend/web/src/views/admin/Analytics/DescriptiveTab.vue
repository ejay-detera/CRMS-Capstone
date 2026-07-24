<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { FileText, Users, Brain, Bell } from 'lucide-vue-next'
import type { AnalyticsSummary } from '@/types/analytics'
import { useApiCache } from '@/composables/useApiCache'
import { useAuth } from '@/composables/useAuth'
import { remainingDays } from '@/types/contract'

// Sub-components
import DescriptiveMetricsGrid from './DescriptiveMetricsGrid.vue'
import MetricsBreakdownCharts from './MetricsBreakdownCharts.vue'
import ContractTrendChart from '../Dashboard/ContractTrendChart.vue'
import ContractStatusChart from '../Dashboard/ContractStatusChart.vue'
import RenewalPipelineChart from '../Dashboard/RenewalPipelineChart.vue'
import ContractsByRegionChart from '../Dashboard/ContractsByRegionChart.vue'
import UserPartnerAnalyticsChart from '../Dashboard/UserPartnerAnalyticsChart.vue'
import VendorRiskChart from '../Dashboard/VendorRiskChart.vue'

const props = defineProps<{
  summary: AnalyticsSummary | null
}>()

const { state: authState, role } = useAuth()
const { state: cacheState, fetchDashboard } = useApiCache()

const contracts = computed(() => cacheState.contracts || [])
const withDays = computed(() => contracts.value.map(c => ({ ...c, days: remainingDays(c.endDate) })))

const users = ref<any[]>([])
const partners = ref<any[]>([])

async function fetchExtraData() {
  try {
    await fetchDashboard()
  } catch (e) {
    console.warn('Dashboard contract fetch warning', e)
  }

  const authApiUrl = import.meta.env.VITE_AUTH_API_URL || 'http://localhost:8000/api'
  const vendorApiUrl = import.meta.env.VITE_VENDOR_API_URL || 'http://localhost:8001/api'
  const token = authState.token
  const headers = { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }

  try {
    const fetchUsers = role.value === 'Admin'
      ? fetch(`${authApiUrl}/admin/users?per_page=100`, { headers })
      : Promise.resolve(null)

    const [usersRes, partnersRes, suppliersRes] = await Promise.all([
      fetchUsers,
      fetch(`${vendorApiUrl}/partners?per_page=100`, { headers }).catch(() => null),
      fetch(`${vendorApiUrl}/suppliers?per_page=100`, { headers }).catch(() => null)
    ])

    if (usersRes && usersRes.ok) {
      const uData = await usersRes.json()
      users.value = (uData.data || []).map((u: any) => ({
        id: u.id.toString(),
        name: `${u.profile?.first_name || ''} ${u.profile?.last_name || ''}`.trim() || u.email,
        email: u.email,
        role: u.profile?.role?.name || 'User',
        status: u.is_active ? 'Active' : 'Inactive',
      }))
    }

    const pList: any[] = []
    if (partnersRes && partnersRes.ok) {
      const pData = await partnersRes.json()
      ;(pData.data || []).forEach((item: any) => {
        pList.push({
          id: item.bp_code || `BP-${item.partner_id}`,
          name: item.partner_name || '',
          region: item.region || 'Luzon',
          status: item.status || 'Active',
          type: 'Partner'
        })
      })
    }
    if (suppliersRes && suppliersRes.ok) {
      const sData = await suppliersRes.json()
      ;(sData.data || []).forEach((item: any) => {
        pList.push({
          id: `SP-${item.supplier_id}`,
          name: item.supplier_name || '',
          region: item.region || 'Luzon',
          status: item.status || 'Active',
          type: 'Supplier'
        })
      })
    }
    partners.value = pList
  } catch (e) {
    console.error('Failed to fetch auxiliary analytics descriptive data', e)
  }
}

onMounted(fetchExtraData)
</script>

<template>
  <div class="space-y-12">

    <!-- ── CATEGORY 1: CONTRACTS ────────────────────────────────────────────── -->
    <section class="space-y-6">
      <div class="flex items-center gap-2 pb-2 border-b border-black/8">
        <div class="p-2 rounded-lg bg-[#252578]/10 text-[#252578]">
          <FileText class="w-5 h-5" />
        </div>
        <div>
          <h2 class="text-base font-bold text-black uppercase tracking-wider">Contracts Analytics</h2>
          <p class="text-xs text-black/40">Workflow, status, category, and regional distributions</p>
        </div>
      </div>

      <!-- Category Charts (TOP) -->
      <div class="space-y-6">
        <MetricsBreakdownCharts :summary="props.summary" service="contract-management" />

        <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
          <div class="xl:col-span-3">
            <ContractTrendChart :contracts="contracts" />
          </div>
          <div class="xl:col-span-2">
            <ContractStatusChart :contracts="contracts" />
          </div>
        </div>

        <RenewalPipelineChart :contracts="withDays" />

        <ContractsByRegionChart :contracts="contracts" />
      </div>

      <!-- Category KPI Cards (BELOW CHARTS) -->
      <DescriptiveMetricsGrid :summary="props.summary" service="contract-management" hide-header />
    </section>


    <!-- ── CATEGORY 2: VENDORS & PARTNERS ───────────────────────────────────── -->
    <section class="space-y-6">
      <div class="flex items-center gap-2 pb-2 border-b border-black/8">
        <div class="p-2 rounded-lg bg-[#2E85D8]/10 text-[#2E85D8]">
          <Users class="w-5 h-5" />
        </div>
        <div>
          <h2 class="text-base font-bold text-black uppercase tracking-wider">Vendors & Partners Analytics</h2>
          <p class="text-xs text-black/40">Supplier regional spread, vendor risk classification, and user role metrics</p>
        </div>
      </div>

      <!-- Category Charts (TOP) -->
      <div class="space-y-6">
        <UserPartnerAnalyticsChart :users="users" :partners="partners" />
        
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
          <VendorRiskChart :partners="partners" />
          <MetricsBreakdownCharts :summary="props.summary" service="vendor-management" />
        </div>
      </div>

      <!-- Category KPI Cards (BELOW CHARTS) -->
      <DescriptiveMetricsGrid :summary="props.summary" service="vendor-management" hide-header />
    </section>


    <!-- ── CATEGORY 3: AI SERVICES ─────────────────────────────────────────── -->
    <section class="space-y-6">
      <div class="flex items-center gap-2 pb-2 border-b border-black/8">
        <div class="p-2 rounded-lg bg-[#2F2F73]/10 text-[#2F2F73]">
          <Brain class="w-5 h-5" />
        </div>
        <div>
          <h2 class="text-base font-bold text-black uppercase tracking-wider">AI Services Analytics</h2>
          <p class="text-xs text-black/40">AI risk scanning metrics and clause deviation assessments</p>
        </div>
      </div>

      <!-- Category Charts (TOP) -->
      <div>
        <MetricsBreakdownCharts :summary="props.summary" service="ai-service" />
      </div>

      <!-- Category KPI Cards (BELOW CHARTS) -->
      <DescriptiveMetricsGrid :summary="props.summary" service="ai-service" hide-header />
    </section>


    <!-- ── CATEGORY 4: NOTIFICATIONS ────────────────────────────────────────── -->
    <section class="space-y-6">
      <div class="flex items-center gap-2 pb-2 border-b border-black/8">
        <div class="p-2 rounded-lg bg-[#5B7FD1]/10 text-[#5B7FD1]">
          <Bell class="w-5 h-5" />
        </div>
        <div>
          <h2 class="text-base font-bold text-black uppercase tracking-wider">Notifications Analytics</h2>
          <p class="text-xs text-black/40">System alert dispatch delivery rates and email success rates</p>
        </div>
      </div>

      <!-- Category Charts (TOP) -->
      <div>
        <MetricsBreakdownCharts :summary="props.summary" service="notification" />
      </div>

      <!-- Category KPI Cards (BELOW CHARTS) -->
      <DescriptiveMetricsGrid :summary="props.summary" service="notification" hide-header />
    </section>

  </div>
</template>
