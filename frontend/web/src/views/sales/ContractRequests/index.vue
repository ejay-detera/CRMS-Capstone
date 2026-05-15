<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Plus } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useToast } from '@/composables/useToast'
import SalesRequestsTable      from './SalesRequestsTable.vue'
import SalesRequestDetailDialog from './SalesRequestDetailDialog.vue'
import type { ContractRequest, RequestFilterTab } from '@/types/contractRequest'

const { success } = useToast()

const requests = ref<ContractRequest[]>([
  { id: 'REQ-001', businessPartner: 'ABS-CBN Corporation',    category: 'Service Agreement',     description: 'Broadcast equipment maintenance for Luzon region studios.',        region: 'Luzon',    requestDate: '2026-01-15', startDate: '2026-03-01', endDate: '2027-02-28', priority: 'High',   status: 'Pending',      notes: 'Urgent — required before Q1 audit.', rejectionReason: '', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'REQ-002', businessPartner: 'Jollibee Foods Corp.',   category: 'Supply Contract',       description: 'Refrigeration unit supply agreement for Luzon commissaries.',       region: 'Luzon',    requestDate: '2026-01-20', startDate: '2026-04-01', endDate: '2027-03-31', priority: 'Medium', status: 'Pending',      notes: 'Renewal of previous supply agreement.', rejectionReason: '', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'REQ-003', businessPartner: 'San Miguel Brewery',     category: 'Supply Contract',       description: 'Industrial cooling system supply for Luzon brewing facilities.',    region: 'Luzon',    requestDate: '2025-12-10', startDate: '2026-02-01', endDate: '2027-01-31', priority: 'High',   status: 'Approved',     notes: '', rejectionReason: '', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'REQ-004', businessPartner: 'Ayala Land Inc.',        category: 'Equipment Lease',       description: 'HVAC facility system lease for Makati corporate tower.',             region: 'Luzon',    requestDate: '2025-11-28', startDate: '2026-01-15', endDate: '2026-07-15', priority: 'Low',    status: 'Rejected',     notes: '', rejectionReason: 'Budget constraints for Q2. Please resubmit next quarter with revised scope.', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'REQ-005', businessPartner: 'Meralco',                category: 'Service Agreement',     description: 'Power monitoring equipment service for Luzon distribution points.', region: 'Luzon',    requestDate: '2026-02-01', startDate: '2026-05-01', endDate: '2027-04-30', priority: 'Medium', status: 'Under Review', notes: 'Awaiting technical review from engineering.', rejectionReason: '', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'REQ-006', businessPartner: 'Globe Telecom',          category: 'Partnership Agreement', description: 'Network infrastructure partnership for Luzon data corridors.',       region: 'Luzon',    requestDate: '2026-01-22', startDate: '2026-03-15', endDate: '2026-09-15', priority: 'High',   status: 'Approved',     notes: '', rejectionReason: '', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'REQ-007', businessPartner: 'SM Prime Holdings',      category: 'Equipment Lease',       description: 'Escalator maintenance unit lease for SM MOA expansion.',            region: 'Luzon',    requestDate: '2026-02-05', startDate: '2026-04-01', endDate: '2027-03-31', priority: 'Medium', status: 'Under Review', notes: 'Property management approved on their end.', rejectionReason: '', contractLink: '#', createdBy: 'Shadrack Castro' },
  { id: 'REQ-008', businessPartner: 'PharmaCare Dist.',       category: 'Supply Contract',       description: 'IV fluid supply for hospital network across Luzon.',                 region: 'Luzon',    requestDate: '2026-02-10', startDate: '2026-04-15', endDate: '2027-04-14', priority: 'High',   status: 'Pending',      notes: 'Critical supply — stock running low.', rejectionReason: '', contractLink: '#', createdBy: 'Shadrack Castro' },
])

const followedUpIds = ref<string[]>([])

const activeFilter = ref<RequestFilterTab>('all')
const searchQuery  = ref('')
const currentPage  = ref(1)
const itemsPerPage = 10

const statCards = computed(() => ({
  total:     requests.value.length,
  pending:   requests.value.filter(r => r.status === 'Pending').length,
  reviewing: requests.value.filter(r => r.status === 'Under Review').length,
  approved:  requests.value.filter(r => r.status === 'Approved').length,
}))

const statCardList = computed(() => [
  { label: 'Total Requests', value: statCards.value.total,     valueClass: 'text-black',       change: '+3.2%', positive: true },
  { label: 'Pending',        value: statCards.value.pending,   valueClass: 'text-amber-500',   change: '+1.8%', positive: true },
  { label: 'Under Review',   value: statCards.value.reviewing, valueClass: 'text-[#2E85D8]',   change: '+2.5%', positive: true },
  { label: 'Approved',       value: statCards.value.approved,  valueClass: 'text-emerald-600', change: '+4.1%', positive: true },
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

const showDetail   = ref(false)
const detailTarget = ref<ContractRequest | null>(null)
function openDetail(r: ContractRequest) { detailTarget.value = r; showDetail.value = true }

const isDetailFollowedUp = computed(() =>
  detailTarget.value ? followedUpIds.value.includes(detailTarget.value.id) : false
)

function handleFollowUp(id: string) {
  if (followedUpIds.value.includes(id)) return
  followedUpIds.value = [...followedUpIds.value, id]
  const r = requests.value.find(x => x.id === id)
  success('Follow-up sent', `The manager has been notified about ${r?.businessPartner ?? id}.`)
}
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Header -->
    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-xl font-semibold text-black">My Contract Requests</h1>
        <p class="text-sm text-black/40 mt-0.5">Track and manage your contract submissions.</p>
      </div>
      <Button class="h-9 w-9 p-0 bg-[#252578] hover:bg-[#2F2F73] text-white rounded-lg shadow-sm">
        <Plus class="w-5 h-5" />
      </Button>
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
    <SalesRequestsTable
      :paginated="paginated"
      :filtered="filtered"
      :active-filter="activeFilter"
      :search-query="searchQuery"
      :current-page="currentPage"
      :items-per-page="itemsPerPage"
      :followed-up-ids="followedUpIds"
      @open-detail="openDetail"
      @follow-up="handleFollowUp"
      @update:active-filter="activeFilter = $event"
      @update:search-query="searchQuery = $event"
      @update:current-page="currentPage = $event"
    />

  </div>

  <SalesRequestDetailDialog
    v-model:open="showDetail"
    :request="detailTarget"
    :is-followed-up="isDetailFollowedUp"
    @follow-up="handleFollowUp"
  />
</template>
