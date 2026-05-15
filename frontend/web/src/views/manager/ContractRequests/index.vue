<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useToast } from '@/composables/useToast'
import RequestsTable      from './RequestsTable.vue'
import RequestDetailDialog from './RequestDetailDialog.vue'
import type { ContractRequest, RequestFilterTab } from '@/types/contractRequest'

const { success } = useToast()

const requests = ref<ContractRequest[]>([
  { id: 'REQ-001', businessPartner: 'Philippine National Bank', category: 'Service Agreement',     description: 'ATM maintenance unit for Luzon region branches.',               region: 'Luzon',    requestDate: '2026-01-15', startDate: '2026-03-01', endDate: '2027-02-28', priority: 'High',   status: 'Pending',      notes: 'Urgent — required before Q1 audit.', rejectionReason: '', contractLink: '#', createdBy: 'Maria Santos'  },
  { id: 'REQ-002', businessPartner: 'Globe Telecom',            category: 'Partnership Agreement', description: 'Network infrastructure expansion across Luzon corridors.',      region: 'Luzon',    requestDate: '2026-01-18', startDate: '2026-04-01', endDate: '2027-03-31', priority: 'High',   status: 'Under Review', notes: 'Awaiting technical review from IT.', rejectionReason: '', contractLink: '#', createdBy: 'Alex Rivera'   },
  { id: 'REQ-003', businessPartner: 'MedLine Philippines',      category: 'Supply Contract',       description: 'Surgical supply agreement for Luzon hospitals.',                region: 'Luzon',    requestDate: '2025-12-10', startDate: '2026-02-01', endDate: '2027-01-31', priority: 'Medium', status: 'Approved',     notes: '', rejectionReason: '', contractLink: '#', createdBy: 'John Doe'      },
  { id: 'REQ-004', businessPartner: 'BDO Unibank',              category: 'Equipment Lease',       description: 'Vault security system lease for Makati branch.',                region: 'Luzon',    requestDate: '2025-11-28', startDate: '2026-01-15', endDate: '2026-07-15', priority: 'Low',    status: 'Rejected',     notes: '', rejectionReason: 'Budget constraints for this fiscal year. Please resubmit next quarter.', contractLink: '#', createdBy: 'Sarah Jenkins' },
  { id: 'REQ-005', businessPartner: 'PLDT',                     category: 'Service Agreement',     description: 'Fiber optic maintenance contract for Mindanao operations.',     region: 'Mindanao', requestDate: '2026-02-01', startDate: '2026-05-01', endDate: '2027-04-30', priority: 'Medium', status: 'Pending',      notes: 'Renewal of previous contract.', rejectionReason: '', contractLink: '#', createdBy: 'Emma Wilson'   },
  { id: 'REQ-006', businessPartner: 'Bio-Tech Logistics',       category: 'Supply Contract',       description: 'Cold chain equipment supply for vaccine distribution.',          region: 'Luzon',    requestDate: '2026-01-22', startDate: '2026-03-15', endDate: '2026-09-15', priority: 'High',   status: 'Under Review', notes: 'Temperature-sensitive logistics required.', rejectionReason: '', contractLink: '#', createdBy: 'Maria Santos'  },
  { id: 'REQ-007', businessPartner: 'Cebu Pacific Air',         category: 'Service Agreement',     description: 'Cargo handling equipment services for Visayas routes.',          region: 'Visayas',  requestDate: '2025-12-20', startDate: '2026-02-15', endDate: '2026-08-15', priority: 'Low',    status: 'Approved',     notes: '', rejectionReason: '', contractLink: '#', createdBy: 'Alex Rivera'   },
  { id: 'REQ-008', businessPartner: 'SM Prime Holdings',        category: 'Equipment Lease',       description: 'HVAC system lease for SM Mall of Asia expansion units.',         region: 'Luzon',    requestDate: '2026-02-05', startDate: '2026-04-01', endDate: '2027-03-31', priority: 'Medium', status: 'Pending',      notes: 'Property management approved on their end.', rejectionReason: '', contractLink: '#', createdBy: 'John Doe'      },
  { id: 'REQ-009', businessPartner: 'Global Pharma Inc.',       category: 'Supply Contract',       description: 'Pharmaceutical dispenser units for Visayas distribution.',       region: 'Visayas',  requestDate: '2026-01-30', startDate: '2026-03-20', endDate: '2026-09-20', priority: 'High',   status: 'Under Review', notes: 'Compliance documents submitted.', rejectionReason: '', contractLink: '#', createdBy: 'Sarah Jenkins' },
  { id: 'REQ-010', businessPartner: 'BioGenesis Research',      category: 'Equipment Maintenance', description: 'PCR machine maintenance agreement for Mindanao labs.',           region: 'Mindanao', requestDate: '2025-11-15', startDate: '2026-01-01', endDate: '2026-12-31', priority: 'Medium', status: 'Approved',     notes: '', rejectionReason: '', contractLink: '#', createdBy: 'Emma Wilson'   },
  { id: 'REQ-011', businessPartner: 'Stellar Lab Equipment',    category: 'Equipment Lease',       description: 'Centrifuge model X200 lease for clinical laboratory.',            region: 'Luzon',    requestDate: '2025-10-30', startDate: '2026-01-01', endDate: '2026-06-30', priority: 'Low',    status: 'Rejected',     notes: '', rejectionReason: 'Vendor not on approved supplier list. Procurement review required.', contractLink: '#', createdBy: 'Maria Santos'  },
  { id: 'REQ-012', businessPartner: 'PharmaCare Dist.',         category: 'Supply Contract',       description: 'IV fluid supply for hospital network across Luzon.',             region: 'Luzon',    requestDate: '2026-02-10', startDate: '2026-04-15', endDate: '2027-04-14', priority: 'High',   status: 'Pending',      notes: 'Critical supply — stock running low.', rejectionReason: '', contractLink: '#', createdBy: 'Alex Rivera'   },
  { id: 'REQ-013', businessPartner: 'Metrobank',                category: 'Service Agreement',     description: 'Cash counting machine service agreement for Mindanao offices.',  region: 'Mindanao', requestDate: '2025-12-05', startDate: '2026-02-01', endDate: '2027-01-31', priority: 'Medium', status: 'Approved',     notes: '', rejectionReason: '', contractLink: '#', createdBy: 'John Doe'      },
  { id: 'REQ-014', businessPartner: 'LabTech Solutions',        category: 'Equipment Maintenance', description: 'Spectrophotometer SPX-5 calibration and maintenance.',           region: 'Visayas',  requestDate: '2026-02-12', startDate: '2026-05-01', endDate: '2027-04-30', priority: 'Medium', status: 'Under Review', notes: 'ISO certification review in progress.', rejectionReason: '', contractLink: '#', createdBy: 'Sarah Jenkins' },
  { id: 'REQ-015', businessPartner: 'Philippine Airlines',      category: 'Partnership Agreement', description: 'Ground support equipment partnership for Visayas airports.',      region: 'Visayas',  requestDate: '2026-01-25', startDate: '2026-04-01', endDate: '2026-10-01', priority: 'High',   status: 'Pending',      notes: 'Board approval pending.', rejectionReason: '', contractLink: '#', createdBy: 'Emma Wilson'   },
])

const activeFilter = ref<RequestFilterTab>('all')
const searchQuery  = ref('')
const currentPage  = ref(1)
const itemsPerPage = 10

const statCards = computed(() => ({
  total:     requests.value.length,
  pending:   requests.value.filter(r => r.status === 'Pending').length,
  reviewing: requests.value.filter(r => r.status === 'Under Review').length,
  approved:  requests.value.filter(r => r.status === 'Approved').length,
  rejected:  requests.value.filter(r => r.status === 'Rejected').length,
}))

const statCardList = computed(() => [
  { label: 'Total Requests', value: statCards.value.total,     valueClass: 'text-black', change: '+3.2%', positive: true },
  { label: 'Pending',        value: statCards.value.pending,   valueClass: 'text-black', change: '+1.8%', positive: true },
  { label: 'Under Review',   value: statCards.value.reviewing, valueClass: 'text-black', change: '+2.5%', positive: true },
  { label: 'Approved',       value: statCards.value.approved,  valueClass: 'text-black', change: '+4.1%', positive: true },
])

const filtered = computed(() => {
  const q = searchQuery.value.toLowerCase()
  return requests.value.filter(r => {
    const bySearch = !q
      || r.id.toLowerCase().includes(q)
      || r.businessPartner.toLowerCase().includes(q)
      || r.category.toLowerCase().includes(q)
    const byFilter =
      activeFilter.value === 'all'       ? true :
      activeFilter.value === 'pending'   ? r.status === 'Pending' :
      activeFilter.value === 'reviewing' ? r.status === 'Under Review' :
      activeFilter.value === 'approved'  ? r.status === 'Approved' :
      r.status === 'Rejected'
    return bySearch && byFilter
  })
})

watch([activeFilter, searchQuery], () => { currentPage.value = 1 })

const paginated = computed(() =>
  filtered.value.slice((currentPage.value - 1) * itemsPerPage, currentPage.value * itemsPerPage)
)

const showDetail    = ref(false)
const detailTarget  = ref<ContractRequest | null>(null)
function openDetail(r: ContractRequest) { detailTarget.value = r; showDetail.value = true }

function handleApprove(id: string) {
  const r = requests.value.find(x => x.id === id)
  if (!r) return
  r.status = 'Approved'
  success('Request approved', `${r.businessPartner}'s contract request has been approved.`)
}

function handleReject(id: string, reason: string) {
  const r = requests.value.find(x => x.id === id)
  if (!r) return
  r.status = 'Rejected'
  r.rejectionReason = reason
  success('Request rejected', `${r.businessPartner}'s contract request has been rejected.`)
}

function handleSetReviewing(id: string) {
  const r = requests.value.find(x => x.id === id)
  if (!r) return
  r.status = 'Under Review'
  success('Status updated', `${r.businessPartner}'s request is now under review.`)
}
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Header -->
    <div>
      <h1 class="text-xl font-semibold text-black">Contract Requests</h1>
      <p class="text-sm text-black/40 mt-0.5">Review and action incoming contract requests.</p>
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
    <RequestsTable
      :paginated="paginated"
      :filtered="filtered"
      :active-filter="activeFilter"
      :search-query="searchQuery"
      :current-page="currentPage"
      :items-per-page="itemsPerPage"
      @open-detail="openDetail"
      @approve="handleApprove"
      @reject="handleReject"
      @set-reviewing="handleSetReviewing"
      @update:active-filter="activeFilter = $event"
      @update:search-query="searchQuery = $event"
      @update:current-page="currentPage = $event"
    />

  </div>

  <RequestDetailDialog
    v-model:open="showDetail"
    :request="detailTarget"
    @approve="handleApprove"
    @reject="handleReject"
    @set-reviewing="handleSetReviewing"
  />
</template>
