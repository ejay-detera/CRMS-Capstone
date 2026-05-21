<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue'
import { Plus, Upload, Loader2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import * as XLSX from 'xlsx'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/useAuth'
import ContractsTable       from './ContractsTable.vue'
import ContractDetailDialog  from './ContractDetailDialog.vue'
import EditContractDialog    from './EditContractDialog.vue'
import { remainingDays } from '@/types/contract'
import type { Contract, FilterTab, ContractApprovalStatus, ContractWorkflowStatus, ContractRegion } from '@/types/contract'

const { success, error } = useToast()
const { state: authState } = useAuth()

const apiBase = import.meta.env.VITE_CONTRACT_API_URL as string

const contracts  = ref<Contract[]>([])
const loading    = ref(true)

function mapApiContract(d: any): Contract {
  return {
    id:              String(d.contract_id),
    businessPartner: d.bp_name         ?? '',
    category:        d.category        ?? '',
    itemCode:        d.item_code       ?? '',
    description:     d.description     ?? '',
    serialNo:        d.serial_number   ?? '',
    sbuNumber:       d.sbu_number      ?? '',
    region:          (d.region         ?? 'Luzon') as ContractRegion,
    startDate:       d.start_date      ?? '',
    endDate:         d.end_date        ?? '',
    approvalStatus:  (d.approval_status ?? 'Pending') as ContractApprovalStatus,
    workflowStatus:  (d.workflow_status ?? null)       as ContractWorkflowStatus | null,
    contractLink:    '',
    createdBy:       d.created_by ? `User #${d.created_by}` : '—',
  }
}

async function fetchContracts() {
  loading.value = true
  try {
    const res = await fetch(`${apiBase}/contracts`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${authState.token}`,
      },
    })
    if (!res.ok) { error('Failed to load', 'Could not fetch contracts.'); return }
    const json = await res.json()
    contracts.value = (json.data ?? []).map(mapApiContract)
  } catch {
    error('Network error', 'Could not reach the server.')
  } finally {
    loading.value = false
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
    const bySearch = !q || c.id.toLowerCase().includes(q) || c.businessPartner.toLowerCase().includes(q) || c.category.toLowerCase().includes(q)
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

const showDetail   = ref(false)
const detailTarget = ref<(Contract & { days: number }) | null>(null)
function openDetail(c: Contract & { days: number }) { detailTarget.value = c; showDetail.value = true }

const showEdit   = ref(false)
const editTarget = ref<Contract | null>(null)
function openEdit(c: Contract & { days: number }) { editTarget.value = c; showEdit.value = true }

function handleEdit(data: Omit<Contract, 'id' | 'createdBy'>) {
  if (!editTarget.value) return
  const idx = contracts.value.findIndex(c => c.id === editTarget.value!.id)
  if (idx < 0) return
  contracts.value[idx] = { ...contracts.value[idx], ...data }
  success('Contract updated', `${data.businessPartner}'s contract has been saved.`)
}

function handleDelete(id: string) {
  const name = contracts.value.find(c => c.id === id)?.businessPartner ?? id
  contracts.value = contracts.value.filter(c => c.id !== id)
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
        <Button class="h-9 w-9 p-0 bg-[#252578] hover:bg-[#2F2F73] text-white rounded-lg shadow-sm">
          <Plus class="w-5 h-5" />
        </Button>
      </div>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
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
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-24 text-black/30">
      <Loader2 class="w-8 h-8 animate-spin" />
    </div>

    <!-- Table -->
    <ContractsTable v-else
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

  <ContractDetailDialog v-model:open="showDetail" :contract="detailTarget" />
  <EditContractDialog   v-model:open="showEdit"   :contract="editTarget"   @submit="handleEdit" />
</template>
