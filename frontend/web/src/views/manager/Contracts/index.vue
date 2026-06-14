<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Upload, Plus } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import * as XLSX from 'xlsx'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/useAuth'
import { useApiCache } from '@/composables/useApiCache'
import ManagerContractsTable from './ManagerContractsTable.vue'
import { remainingDays } from '@/types/contract'
import type { Contract, StatusFilter, FilterTab } from '@/types/contract'

const router = useRouter()
const { success, error } = useToast()
const { state: authState, hasPermission } = useAuth()
const { state: cacheState, fetchContracts: fetchContractsCached } = useApiCache()

const contracts = computed(() => cacheState.contracts || [])
const loading   = computed(() => cacheState.contractsLoading)

// ── Lookup options fetched from API ──────────────────────────────────────────

const statusOptions = ref<{ label: string; value: StatusFilter }[]>([
  { label: 'All Status', value: '' },
])

async function fetchStatusOptions() {
  const apiBase = import.meta.env.VITE_CONTRACT_API_URL as string
  const headers = {
    'Accept': 'application/json',
    'Authorization': `Bearer ${authState.token}`,
  }
  try {
    const [approvalRes, workflowRes] = await Promise.all([
      fetch(`${apiBase}/lookups/approval-statuses`, { headers }),
      fetch(`${apiBase}/lookups/workflow-statuses`, { headers }),
    ])

    const opts: { label: string; value: StatusFilter }[] = [{ label: 'All Status', value: '' }]

    if (approvalRes.ok) {
      const j = await approvalRes.json()
      for (const s of (j.data ?? []) as string[]) {
        opts.push({ label: s, value: s as StatusFilter })
      }
    }
    if (workflowRes.ok) {
      const j = await workflowRes.json()
      for (const s of (j.data ?? []) as { status_name: string }[]) {
        opts.push({ label: s.status_name, value: s.status_name as StatusFilter })
      }
    }

    statusOptions.value = opts
  } catch {
    // keep defaults on failure
  }
}

// ── Contracts fetch ──────────────────────────────────────────────────────────

const activeFilter   = ref<FilterTab>('all')
const statusFilter   = ref<StatusFilter>('')
const searchQuery    = ref('')
const categoryFilter  = ref('')
const regionFilter    = ref('')
const currentPage    = ref(1)
const itemsPerPage   = 15

const withDays = computed(() =>
  contracts.value.map(c => ({ ...c, days: remainingDays(c.endDate) }))
)

const statCards = computed(() => {
  return {
    total:    cacheState.contractsStats?.total ?? 0,
    active:   cacheState.contractsStats?.active ?? 0,
    expiring: cacheState.contractsStats?.expiring ?? 0,
    expired:  cacheState.contractsStats?.expired ?? 0,
  }
})

const statCardList = computed(() => [
  { label: 'All Contracts', value: statCards.value.total,    valueClass: 'text-black', change: '+2.1%', positive: true, filter: 'all' as FilterTab  },
  { label: 'Active',        value: statCards.value.active,   valueClass: 'text-black', change: '+4.0%', positive: true, filter: 'active' as FilterTab  },
  { label: 'Expiring Soon', value: statCards.value.expiring, valueClass: 'text-black', change: '+5.2%', positive: true, filter: 'expiring' as FilterTab  },
  { label: 'Expired',       value: statCards.value.expired,  valueClass: 'text-black', change: '-1.3%', positive: false, filter: 'expired' as FilterTab },
])

const filtered = computed(() => withDays.value)
const paginated = computed(() => withDays.value)
const totalItems = computed(() => cacheState.contractsPagination?.total ?? 0)

async function fetchContracts() {
  try {
    await fetchContractsCached(undefined, true, {
      paginate: true,
      page: currentPage.value,
      per_page: itemsPerPage,
      search: searchQuery.value,
      category: categoryFilter.value,
      region: regionFilter.value,
      status: statusFilter.value,
      lifecycle_status: activeFilter.value !== 'all' ? activeFilter.value : undefined,
    })
  } catch {
    error('Network error', 'Could not reach the server.')
  }
}

onMounted(() => {
  fetchContracts()
  fetchStatusOptions()
})

let debounceTimeout: any = null

async function handleFilterChange(resetPage = false) {
  if (resetPage) {
    currentPage.value = 1
  }
  await fetchContracts()
}

watch([activeFilter, statusFilter, categoryFilter, regionFilter], () => {
  handleFilterChange(true)
})

watch(currentPage, () => {
  handleFilterChange(false)
})

watch(searchQuery, () => {
  if (debounceTimeout) clearTimeout(debounceTimeout)
  debounceTimeout = setTimeout(() => {
    handleFilterChange(true)
  }, 350)
})

function openDetail(c: Contract & { days: number }) {
  router.push(`/manager/contracts/${c.id}`)
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
  XLSX.utils.book_append_sheet(wb, ws, 'All Contracts')
  XLSX.writeFile(wb, 'all-contracts.xlsx')
  success('Export complete', `${filtered.value.length} contracts exported.`)
}
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Header -->
    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-xl font-semibold text-black">All Contracts</h1>
        <p class="text-sm text-black/40 mt-0.5">View and manage all employee contracts.</p>
      </div>
      <div class="flex items-center gap-2">
        <Button @click="exportXLSX" variant="outline"
          class="h-9 gap-2 text-sm font-medium border-black/15 text-black/65 hover:text-black">
          <Upload class="w-4 h-4" /> Export XLSX
        </Button>
        <Button v-if="hasPermission('cms.contracts.create')" @click="router.push('/manager/contracts/create')" class="group h-9 w-9 p-0 bg-[#252578] hover:bg-[#2F2F73] text-white rounded-lg shadow-sm transition-all duration-200 hover:scale-105 active:scale-95">
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
    <ManagerContractsTable
      :loading="loading"
      :paginated="paginated"
      :filtered="filtered"
      :total-items="totalItems"
      :active-filter="activeFilter"
      :status-filter="statusFilter"
      :status-options="statusOptions"
      :category-filter="categoryFilter"
      :region-filter="regionFilter"
      :search-query="searchQuery"
      :current-page="currentPage"
      :items-per-page="itemsPerPage"
      @open-detail="openDetail"
      @update:active-filter="activeFilter = $event"
      @update:status-filter="statusFilter = $event"
      @update:category-filter="categoryFilter = $event"
      @update:region-filter="regionFilter = $event"
      @update:search-query="searchQuery = $event"
      @update:current-page="currentPage = $event"
    />

  </div>
</template>
