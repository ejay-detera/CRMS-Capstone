<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Plus, Upload } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import * as XLSX from 'xlsx'
import { useToast } from '@/composables/useToast'
import ContractsTable       from './ContractsTable.vue'
import ContractDetailDialog  from './ContractDetailDialog.vue'
import EditContractDialog    from './EditContractDialog.vue'
import { remainingDays } from '@/types/contract'
import type { Contract, FilterTab } from '@/types/contract'

const { success } = useToast()

const contracts = ref<Contract[]>([
  { id: 'CTR-001', businessPartner: 'Philippine National Bank', category: 'Service Agreement',     itemCode: 'ITM-0041', description: 'ATM Maintenance Unit',       serialNo: 'SN-2024-0041', region: 'Luzon',    startDate: '2026-01-01', endDate: '2026-12-31', approvalStatus: 'Approved',  workflowStatus: 'Notarized PDF', contractLink: '#', createdBy: 'Maria Santos'   },
  { id: 'CTR-002', businessPartner: 'Globe Telecom',            category: 'Partnership Agreement', itemCode: 'ITM-0082', description: 'Network Infrastructure',    serialNo: 'SN-2024-0082', region: 'Luzon',    startDate: '2025-10-01', endDate: '2026-09-30', approvalStatus: 'Approved',  workflowStatus: 'Client Review', contractLink: '#', createdBy: 'Alex Rivera'    },
  { id: 'CTR-003', businessPartner: 'MedLine Philippines',      category: 'Supply Contract',       itemCode: 'ITM-0113', description: 'Surgical Supply Agreement',  serialNo: 'SN-2025-0113', region: 'Luzon',    startDate: '2025-08-15', endDate: '2026-08-15', approvalStatus: 'Approved',  workflowStatus: 'Notarized PDF', contractLink: '#', createdBy: 'John Doe'       },
  { id: 'CTR-004', businessPartner: 'BDO Unibank',              category: 'Equipment Lease',       itemCode: 'ITM-0054', description: 'Vault Security System',      serialNo: 'SN-2025-0054', region: 'Luzon',    startDate: '2026-01-01', endDate: '2026-07-01', approvalStatus: 'Pending',   workflowStatus: null,            contractLink: '#', createdBy: 'Sarah Jenkins'  },
  { id: 'CTR-005', businessPartner: 'PLDT',                     category: 'Service Agreement',     itemCode: 'ITM-0095', description: 'Fiber Optic Maintenance',    serialNo: 'SN-2024-0095', region: 'Mindanao', startDate: '2026-01-01', endDate: '2026-06-30', approvalStatus: 'Approved',  workflowStatus: 'Client Review', contractLink: '#', createdBy: 'Emma Wilson'    },
  { id: 'CTR-006', businessPartner: 'Bio-Tech Logistics',       category: 'Supply Contract',       itemCode: 'ITM-0076', description: 'Cold Chain Equipment',       serialNo: 'SN-2025-0076', region: 'Luzon',    startDate: '2025-12-15', endDate: '2026-06-15', approvalStatus: 'Approved',  workflowStatus: 'Notarized PDF', contractLink: '#', createdBy: 'Maria Santos'   },
  { id: 'CTR-007', businessPartner: 'Cebu Pacific Air',         category: 'Service Agreement',     itemCode: 'ITM-0037', description: 'Cargo Handling Equipment',   serialNo: 'SN-2025-0037', region: 'Visayas',  startDate: '2025-11-20', endDate: '2026-05-20', approvalStatus: 'Rejected',  workflowStatus: 'SBSI Review',   contractLink: '#', createdBy: 'Alex Rivera'    },
  { id: 'CTR-008', businessPartner: 'SM Prime Holdings',        category: 'Equipment Lease',       itemCode: 'ITM-0068', description: 'HVAC System Unit B',         serialNo: 'SN-2025-0068', region: 'Luzon',    startDate: '2025-11-25', endDate: '2026-05-25', approvalStatus: 'Approved',  workflowStatus: 'Client Review', contractLink: '#', createdBy: 'John Doe'       },
  { id: 'CTR-009', businessPartner: 'Global Pharma Inc.',       category: 'Supply Contract',       itemCode: 'ITM-0019', description: 'Pharmaceutical Dispenser',   serialNo: 'SN-2025-0019', region: 'Visayas',  startDate: '2025-11-28', endDate: '2026-05-28', approvalStatus: 'Approved',  workflowStatus: 'Notarized PDF', contractLink: '#', createdBy: 'Sarah Jenkins'  },
  { id: 'CTR-010', businessPartner: 'BioGenesis Research',      category: 'Equipment Maintenance', itemCode: 'ITM-0150', description: 'PCR Machine Unit 3',         serialNo: 'SN-2024-0150', region: 'Mindanao', startDate: '2025-10-30', endDate: '2026-04-30', approvalStatus: 'Pending',   workflowStatus: null,            contractLink: '#', createdBy: 'Emma Wilson'    },
  { id: 'CTR-011', businessPartner: 'Stellar Lab Equipment',    category: 'Equipment Lease',       itemCode: 'ITM-0121', description: 'Centrifuge Model X200',       serialNo: 'SN-2024-0121', region: 'Luzon',    startDate: '2025-09-15', endDate: '2026-03-15', approvalStatus: 'Pending',   workflowStatus: null,            contractLink: '#', createdBy: 'Maria Santos'   },
  { id: 'CTR-012', businessPartner: 'PharmaCare Dist.',         category: 'Supply Contract',       itemCode: 'ITM-0033', description: 'IV Fluid Supply Agreement',   serialNo: 'SN-2023-0033', region: 'Luzon',    startDate: '2025-07-01', endDate: '2026-01-01', approvalStatus: 'Approved',  workflowStatus: 'Client Review', contractLink: '#', createdBy: 'Alex Rivera'    },
  { id: 'CTR-013', businessPartner: 'Metrobank',                category: 'Service Agreement',     itemCode: 'ITM-0087', description: 'Cash Counting Machine',       serialNo: 'SN-2024-0087', region: 'Mindanao', startDate: '2026-02-01', endDate: '2027-01-31', approvalStatus: 'Approved',  workflowStatus: 'Notarized PDF', contractLink: '#', createdBy: 'John Doe'       },
  { id: 'CTR-014', businessPartner: 'LabTech Solutions',        category: 'Equipment Maintenance', itemCode: 'ITM-0102', description: 'Spectrophotometer SPX-5',     serialNo: 'SN-2025-0102', region: 'Visayas',  startDate: '2026-03-01', endDate: '2027-02-28', approvalStatus: 'Approved',  workflowStatus: 'Client Review', contractLink: '#', createdBy: 'Sarah Jenkins'  },
  { id: 'CTR-015', businessPartner: 'Philippine Airlines',      category: 'Partnership Agreement', itemCode: 'ITM-0059', description: 'Ground Support Equipment',    serialNo: 'SN-2024-0059', region: 'Visayas',  startDate: '2026-01-15', endDate: '2026-07-15', approvalStatus: 'Rejected',  workflowStatus: 'SBSI Review',   contractLink: '#', createdBy: 'Emma Wilson'    },
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

    <!-- Table -->
    <ContractsTable
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
