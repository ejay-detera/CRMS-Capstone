<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Plus, Upload } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import * as XLSX from 'xlsx'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/useAuth'
import { useApiCache } from '@/composables/useApiCache'
import ContractsTable       from './ContractsTable.vue'
import { remainingDays } from '@/types/contract'
import type { Contract, FilterTab, StatusFilter } from '@/types/contract'

const router = useRouter()
const { success, error } = useToast()
const { state: authState, hasPermission } = useAuth()
const { state: cacheState, fetchContracts: fetchContractsCached } = useApiCache()

const contracts = computed(() => cacheState.contracts || [])
const loading   = computed(() => cacheState.contractsLoading)

async function fetchContracts() {
  if (!authState.user) return
  try {
    await fetchContractsCached(undefined, true) // Force fetch from backend
  } catch (err) {
    console.error('Failed to fetch contracts:', err)
    error('Fetch failed', 'Could not load contracts from server.')
  }
}

onMounted(() => {
  fetchContracts()
})

const activeFilter   = ref<FilterTab>('all')
const searchQuery    = ref('')
const categoryFilter  = ref('')
const regionFilter    = ref('')
const statusFilter    = ref<StatusFilter>('')
const currentPage    = ref(1)
const itemsPerPage   = 10

const withDays = computed(() =>
  contracts.value.map(c => ({ ...c, days: remainingDays(c.endDate) }))
)

const statCards = computed(() => {
  const activeCount = withDays.value.filter(c => c.days > 30).length
  const expiringCount = withDays.value.filter(c => c.days >= 0 && c.days <= 30).length
  const expiredCount = withDays.value.filter(c => c.days < 0).length
  return {
    total:    activeCount + expiringCount + expiredCount,
    active:   activeCount,
    expiring: expiringCount,
    expired:  expiredCount,
  }
})

const statCardList = computed(() => [
  { label: 'Total Contracts', value: statCards.value.total,    valueClass: 'text-black', change: '+2.1%', positive: true, filter: 'all' as FilterTab },
  { label: 'Active',          value: statCards.value.active,   valueClass: 'text-black', change: '+4.0%', positive: true, filter: 'active' as FilterTab },
  { label: 'Expiring Soon',   value: statCards.value.expiring, valueClass: 'text-black', change: '+5.2%', positive: true, filter: 'expiring' as FilterTab },
  { label: 'Expired',         value: statCards.value.expired,  valueClass: 'text-black', change: '-1.3%', positive: false, filter: 'expired' as FilterTab },
])

const approvalStatuses = new Set(['Pending', 'Approved', 'Rejected'])

const filtered = computed(() => {
  const q = searchQuery.value.toLowerCase()
  const cat = categoryFilter.value
  const reg = regionFilter.value
  const sf = statusFilter.value
  return withDays.value.filter(c => {
    const bySearch = !q || c.id.toLowerCase().includes(q) || c.businessPartner.toLowerCase().includes(q) || c.category.toLowerCase().includes(q)
    const byFilter =
      activeFilter.value === 'all'      ? true :
      activeFilter.value === 'active'   ? c.days > 30 :
      activeFilter.value === 'expiring' ? c.days >= 0 && c.days <= 30 :
      c.days < 0
    const byCategory = !cat || c.category === cat
    const byRegion = !reg || c.region === reg
    const byStatus = !sf
      ? true
      : approvalStatuses.has(sf)
        ? c.approvalStatus === sf
        : c.workflowStatus === sf
    return bySearch && byFilter && byCategory && byRegion && byStatus
  })
})

watch([activeFilter, searchQuery, categoryFilter, regionFilter, statusFilter], () => { currentPage.value = 1 })

const paginated = computed(() =>
  filtered.value.slice((currentPage.value - 1) * itemsPerPage, currentPage.value * itemsPerPage)
)

function openDetail(c: Contract & { days: number }) {
  router.push(`/admin/contracts/${c.id}`)
}

function openEdit(c: Contract & { days: number }) {
  router.push(`/admin/contracts/${c.id}?edit=1`)
}

async function handleDelete(id: string) {
  if (!authState.user) return
  const contract = contracts.value.find(c => c.id === id)
  if (!contract) return

  const apiBase = import.meta.env.VITE_CONTRACT_API_URL as string

  try {
    const res = await fetch(`${apiBase}/contracts/${id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${authState.token || ''}`,
        'Accept': 'application/json'
      }
    })
    const json = await res.json()
    if (res.ok) {
      await fetchContracts()
      success('Contract removed', `${contract.businessPartner}'s contract has been deleted.`)
    } else {
      error('Delete failed', json.message || 'Could not delete contract.')
    }
  } catch (err) {
    console.error('Failed to delete contract:', err)
    error('Delete failed', 'Connection to contract service failed.')
  }
}

function exportXLSX() {
  const rows = filtered.value.map(c => ({
    'Contract ID': c.id, 'Business Partner': c.businessPartner, 'Category': c.category,
    'Item Code': c.itemCode, 'Description': c.description, 'Serial No': c.serialNo,
    'Region': c.region, 'Start Date': c.startDate, 'End Date': c.endDate,
    'Remaining Days': c.days, 'Approval Status': c.approvalStatus,
    'Workflow Status': c.workflowStatus ?? '', 'Sales Rep': c.createdBy,
  }))
  const ws = XLSX.utils.json_to_sheet(rows)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Contracts')
  XLSX.writeFile(wb, 'sbsi-contracts.xlsx')
  success('Export complete', `${filtered.value.length} contracts exported.`)
}
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Header -->
    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-xl font-semibold text-black">Contracts</h1>
        <p class="text-sm text-black/40 mt-0.5">Manage and monitor all contract agreements.</p>
      </div>
      <div class="flex items-center gap-2">
        <Button @click="exportXLSX" variant="outline" class="h-9 gap-2 text-sm font-medium border-black/15 text-black/65 hover:text-black">
          <Upload class="w-4 h-4" /> Export XLSX
        </Button>
        <Button v-if="hasPermission('crms.contracts.create')" class="group h-9 w-9 p-0 bg-[#252578] hover:bg-[#2F2F73] text-white rounded-lg shadow-sm transition-all duration-200 hover:scale-105 active:scale-95">
          <Plus class="w-5 h-5 transition-transform duration-300 group-hover:rotate-90" />
        </Button>
      </div>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
      <template v-if="loading">
        <div v-for="i in 4" :key="i"
          class="bg-white rounded-lg border border-black/8 px-6 py-5 shadow-sm">
          <div class="h-3.5 w-24 bg-black/5 animate-pulse rounded mb-4"></div>
          <div class="flex items-end justify-between gap-2">
            <div class="h-8 w-12 bg-black/5 animate-pulse rounded"></div>
            <div class="h-5 w-10 bg-black/5 animate-pulse rounded mb-0.5"></div>
          </div>
        </div>
      </template>
      <template v-else>
        <div
          v-for="card in statCardList"
          :key="card.label"
          @click="activeFilter = card.filter"
          class="bg-white rounded-lg border px-6 py-5 shadow-sm block hover:shadow-md cursor-pointer transition-all duration-200"
          :class="activeFilter === card.filter ? 'border-[#2E85D8]' : 'border-black/8'"
        >
          <p class="text-xs font-medium text-black/40 uppercase tracking-wide mb-3">{{ card.label }}</p>
          <div class="flex items-end justify-between gap-2">
            <span class="text-3xl font-semibold tabular-nums" :class="card.valueClass">{{ card.value }}</span>
            <span class="text-xs font-medium px-2 py-0.5 rounded-md mb-0.5 shrink-0"
              :class="card.positive ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'">
              {{ card.change }}
            </span>
          </div>
        </div>
      </template>
    </div>

    <!-- Table -->
    <ContractsTable
      :loading="loading"
      :paginated="paginated"
      :filtered="filtered"
      :active-filter="activeFilter"
      :search-query="searchQuery"
      :category-filter="categoryFilter"
      :region-filter="regionFilter"
      :status-filter="statusFilter"
      :current-page="currentPage"
      :items-per-page="itemsPerPage"
      @open-detail="openDetail"
      @open-edit="openEdit"
      @delete="handleDelete"
      @update:active-filter="activeFilter = $event"
      @update:search-query="searchQuery = $event"
      @update:category-filter="categoryFilter = $event"
      @update:region-filter="regionFilter = $event"
      @update:status-filter="statusFilter = $event"
      @update:current-page="currentPage = $event"
    />

  </div>
</template>
