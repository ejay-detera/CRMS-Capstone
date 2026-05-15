<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Upload, Plus } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import * as XLSX from 'xlsx'
import { useToast } from '@/composables/useToast'
import SalesContractsTable  from './SalesContractsTable.vue'
import ContractDetailDialog from '@/views/admin/Contracts/ContractDetailDialog.vue'
import { remainingDays } from '@/types/contract'
import type { Contract, FilterTab } from '@/types/contract'

const { success } = useToast()

const contracts = ref<Contract[]>([
  { id: 'CTR-001', businessPartner: 'ABS-CBN Corporation',    category: 'Service Agreement',     itemCode: 'ITM-0041', description: 'Broadcast Equipment Unit',       serialNo: 'SN-2024-0041', region: 'Luzon',    startDate: '2026-01-01', endDate: '2026-12-31', status: 'Notarized PDF', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-002', businessPartner: 'Globe Telecom',           category: 'Partnership Agreement', itemCode: 'ITM-0082', description: 'Network Infrastructure',         serialNo: 'SN-2024-0082', region: 'Luzon',    startDate: '2025-10-01', endDate: '2026-09-30', status: 'Client Review', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-003', businessPartner: 'San Miguel Brewery',      category: 'Supply Contract',       itemCode: 'ITM-0113', description: 'Industrial Cooling System',       serialNo: 'SN-2025-0113', region: 'Luzon',    startDate: '2025-08-15', endDate: '2026-08-15', status: 'Notarized PDF', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-004', businessPartner: 'Ayala Land Inc.',         category: 'Equipment Lease',       itemCode: 'ITM-0054', description: 'HVAC Facility System',            serialNo: 'SN-2025-0054', region: 'Luzon',    startDate: '2026-01-01', endDate: '2026-07-01', status: 'SBSI Review',   contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-005', businessPartner: 'Meralco',                 category: 'Service Agreement',     itemCode: 'ITM-0095', description: 'Power Monitoring Equipment',      serialNo: 'SN-2024-0095', region: 'Luzon',    startDate: '2026-01-01', endDate: '2026-06-30', status: 'Client Review', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-006', businessPartner: 'Jollibee Foods Corp.',    category: 'Supply Contract',       itemCode: 'ITM-0076', description: 'Refrigeration Unit Agreement',    serialNo: 'SN-2025-0076', region: 'Luzon',    startDate: '2025-12-15', endDate: '2026-06-15', status: 'Notarized PDF', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-007', businessPartner: 'Cebu Pacific Air',        category: 'Service Agreement',     itemCode: 'ITM-0037', description: 'Cargo Handling Equipment',        serialNo: 'SN-2025-0037', region: 'Visayas',  startDate: '2025-11-20', endDate: '2026-05-20', status: 'SBSI Review',   contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-008', businessPartner: 'SM Prime Holdings',       category: 'Equipment Lease',       itemCode: 'ITM-0068', description: 'Escalator Maintenance Unit',      serialNo: 'SN-2025-0068', region: 'Luzon',    startDate: '2025-11-25', endDate: '2026-05-25', status: 'Client Review', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-009', businessPartner: 'Global Pharma Inc.',      category: 'Supply Contract',       itemCode: 'ITM-0019', description: 'Pharmaceutical Dispenser',        serialNo: 'SN-2025-0019', region: 'Visayas',  startDate: '2025-11-28', endDate: '2026-05-28', status: 'Notarized PDF', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-010', businessPartner: 'BioGenesis Research',     category: 'Equipment Maintenance', itemCode: 'ITM-0150', description: 'PCR Machine Unit 3',              serialNo: 'SN-2024-0150', region: 'Mindanao', startDate: '2025-10-30', endDate: '2026-04-30', status: 'Notarized PDF', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-011', businessPartner: 'Stellar Lab Equipment',   category: 'Equipment Lease',       itemCode: 'ITM-0121', description: 'Centrifuge Model X200',           serialNo: 'SN-2024-0121', region: 'Luzon',    startDate: '2025-09-15', endDate: '2026-03-15', status: 'SBSI Review',   contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'CTR-012', businessPartner: 'PharmaCare Dist.',        category: 'Supply Contract',       itemCode: 'ITM-0033', description: 'IV Fluid Supply Agreement',       serialNo: 'SN-2023-0033', region: 'Luzon',    startDate: '2025-07-01', endDate: '2026-01-01', status: 'Client Review', contractLink: '#', createdBy: 'Shadrack Castro' },
])

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
  { label: 'My Contracts',  value: statCards.value.total,    valueClass: 'text-black',     change: '+2.1%', positive: true  },
  { label: 'Active',        value: statCards.value.active,   valueClass: 'text-[#2E85D8]', change: '+4.0%', positive: true  },
  { label: 'Expiring Soon', value: statCards.value.expiring, valueClass: 'text-amber-500', change: '+5.2%', positive: true  },
  { label: 'Expired',       value: statCards.value.expired,  valueClass: 'text-red-500',   change: '-1.3%', positive: false },
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

const showDetail   = ref(false)
const detailTarget = ref<(Contract & { days: number }) | null>(null)
function openDetail(c: Contract & { days: number }) { detailTarget.value = c; showDetail.value = true }

const showEdit   = ref(false)
const editTarget = ref<Contract | null>(null)
function openEdit(c: Contract & { days: number }) { editTarget.value = c; showEdit.value = true }

function exportXLSX() {
  const rows = filtered.value.map(c => ({
    'Contract ID': c.id, 'Business Partner': c.businessPartner, 'Category': c.category,
    'Item Code': c.itemCode, 'Description': c.description, 'Serial No': c.serialNo,
    'Region': c.region, 'Start Date': c.startDate, 'End Date': c.endDate,
    'Remaining Days': c.days, 'Status': c.status,
  }))
  const ws = XLSX.utils.json_to_sheet(rows)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'My Contracts')
  XLSX.writeFile(wb, 'my-contracts.xlsx')
  success('Export complete', `${filtered.value.length} contracts exported.`)
}
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Header -->
    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-xl font-semibold text-black">My Contracts</h1>
        <p class="text-sm text-black/40 mt-0.5">View all contracts assigned to you.</p>
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

    <!-- Table -->
    <SalesContractsTable
      :paginated="paginated"
      :filtered="filtered"
      :active-filter="activeFilter"
      :search-query="searchQuery"
      :current-page="currentPage"
      :items-per-page="itemsPerPage"
      @open-detail="openDetail"
      @open-edit="openEdit"
      @update:active-filter="activeFilter = $event"
      @update:search-query="searchQuery = $event"
      @update:current-page="currentPage = $event"
    />

  </div>

  <ContractDetailDialog v-model:open="showDetail" :contract="detailTarget" />
</template>
