<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Upload, Plus } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import * as XLSX from 'xlsx'
import { useToast } from '@/composables/useToast'
import ContractsTable      from '@/views/admin/Contracts/ContractsTable.vue'
import EditContractDialog   from '@/views/admin/Contracts/EditContractDialog.vue'
import { remainingDays } from '@/types/contract'
import type { Contract, FilterTab } from '@/types/contract'
import { useApiCache } from '@/composables/useApiCache'

const { success, error } = useToast()
const router = useRouter()

const { state: cacheState, fetchContracts: fetchContractsCached, updateContractInCache, deleteContractFromCache } = useApiCache()

const contracts = computed(() => cacheState.contracts || [])
const loading   = computed(() => cacheState.contractsLoading)

async function fetchContracts() {
  try {
    await fetchContractsCached()
  } catch {
    error('Network error', 'Could not reach the server.')
  }
}

onMounted(fetchContracts)

const activeFilter = ref<FilterTab>('all')
const searchQuery  = ref('')
const currentPage  = ref(1)
const itemsPerPage = 10

const withDays = computed(() =>
  contracts.value.map(c => ({ ...c, days: remainingDays(c.endDate) }))
)

const statCards = computed(() => ({
  total:    withDays.value.length,
  active:   withDays.value.filter(c => c.days > 30).length,
  expiring: withDays.value.filter(c => c.days >= 0 && c.days <= 30).length,
  expired:  withDays.value.filter(c => c.days < 0).length,
}))

const statCardList = computed(() => [
  { label: 'Total Contracts', value: statCards.value.total,    valueClass: 'text-black', change: '+2.1%', positive: true  },
  { label: 'Active',          value: statCards.value.active,   valueClass: 'text-black', change: '+4.0%', positive: true  },
  { label: 'Expiring Soon',   value: statCards.value.expiring, valueClass: 'text-black', change: '+5.2%', positive: true  },
  { label: 'Expired',         value: statCards.value.expired,  valueClass: 'text-black', change: '-1.3%', positive: false },
])

const filtered = computed(() => {
  const q = searchQuery.value.toLowerCase()
  return withDays.value.filter(c => {
    const bySearch = !q || c.id.toLowerCase().includes(q)
      || c.businessPartner.toLowerCase().includes(q)
      || c.category.toLowerCase().includes(q)
    const byFilter =
      activeFilter.value === 'all'      ? true :
      activeFilter.value === 'active'   ? c.days > 30 :
      activeFilter.value === 'expiring' ? c.days >= 0 && c.days <= 30 :
      c.days < 0
    return bySearch && byFilter
  })
})

watch([activeFilter, searchQuery], () => { currentPage.value = 1 })

const paginated = computed(() =>
  filtered.value.slice((currentPage.value - 1) * itemsPerPage, currentPage.value * itemsPerPage)
)

function openDetail(c: Contract & { days: number }) {
  router.push(`/manager/contracts/${c.id}`)
}

const showEdit   = ref(false)
const editTarget = ref<Contract | null>(null)
function openEdit(c: Contract & { days: number }) { editTarget.value = c; showEdit.value = true }

function handleEdit(data: Omit<Contract, 'id' | 'createdBy'>) {
  if (!editTarget.value) return
  updateContractInCache(editTarget.value.id, data)
  success('Contract updated', `${data.businessPartner}'s contract has been saved.`)
}

function handleDelete(id: string) {
  const name = contracts.value.find(c => c.id === id)?.businessPartner ?? id
  deleteContractFromCache(id)
  success('Contract removed', `${name}'s contract has been deleted.`)
}

function exportXLSX() {
  const rows = filtered.value.map(c => ({
    'Contract ID': c.id, 'Business Partner': c.businessPartner, 'Category': c.category,
    'Item Code': c.itemCode, 'Description': c.description, 'Serial No': c.serialNo,
    'Region': c.region, 'Start Date': c.startDate, 'End Date': c.endDate,
    'Remaining Days': c.days, 'Approval Status': c.approvalStatus, 'Workflow Status': c.workflowStatus ?? '',
  }))
  const ws = XLSX.utils.json_to_sheet(rows)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Contracts')
  XLSX.writeFile(wb, 'contracts.xlsx')
  success('Export complete', `${filtered.value.length} contracts exported.`)
}
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Header -->
    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-xl font-semibold text-black">All Contracts</h1>
        <p class="text-sm text-black/40 mt-0.5">View and manage all contract agreements.</p>
      </div>
      <div class="flex items-center gap-2">
        <Button @click="exportXLSX" variant="outline"
          class="h-9 gap-2 text-sm font-medium border-black/15 text-black/65 hover:text-black">
          <Upload class="w-4 h-4" /> Export XLSX
        </Button>
        <Button class="h-9 w-9 p-0 bg-[#252578] hover:bg-[#2F2F73] text-white rounded-lg shadow-sm">
          <Plus class="w-5 h-5" />
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
        <div v-for="card in statCardList" :key="card.label"
          class="bg-white rounded-lg border border-black/8 px-6 py-5 shadow-sm">
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
      :current-page="currentPage"
      :items-per-page="itemsPerPage"
      @open-detail="openDetail"
      @open-edit="openEdit"
      @delete="handleDelete"
      @update:active-filter="activeFilter = $event"
      @update:search-query="searchQuery = $event"
      @update:current-page="currentPage = $event"
    />

  </div>

  <EditContractDialog   v-model:open="showEdit"   :contract="editTarget"   @submit="handleEdit" />
</template>
