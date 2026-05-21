<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/useAuth'
import RequestsTable      from './RequestsTable.vue'
import RequestDetailDialog from './RequestDetailDialog.vue'
import type { ContractRequest, RequestFilterTab, RequestStatus, RequestPriority } from '@/types/contractRequest'

const { success, error } = useToast()
const { state: authState } = useAuth()

const apiBase = import.meta.env.VITE_CONTRACT_API_URL as string

const requests = ref<ContractRequest[]>([])
const loading  = ref(true)

// Map contract API approval_status → request status label
const STATUS_MAP: Record<string, RequestStatus> = {
  'Pending':  'Pending',
  'Approved': 'Approved',
  'Rejected': 'Rejected',
}

function mapApiToRequest(d: any): ContractRequest {
  return {
    id:              `CTR-${String(d.contract_id).padStart(3, '0')}`,
    businessPartner: d.bp_name        ?? '',
    category:        d.category       ?? '',
    description:     d.description    ?? '',
    region:          (d.region        ?? 'Luzon') as ContractRequest['region'],
    requestDate:     d.created_at     ?? '',
    startDate:       d.start_date     ?? '',
    endDate:         d.end_date       ?? '',
    priority:        'Medium' as RequestPriority,   // priority not yet in DB; default
    status:          STATUS_MAP[d.approval_status ?? 'Pending'] ?? 'Pending',
    notes:           '',
    rejectionReason: '',
    contractLink:    '',
    createdBy:       d.created_by ? `User #${d.created_by}` : '—',
  }
}

async function fetchRequests() {
  loading.value = true
  try {
    const res = await fetch(`${apiBase}/contract-requests`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${authState.token}`,
      },
    })
    if (!res.ok) { error('Failed to load', 'Could not fetch contract requests.'); return }
    const json = await res.json()
    requests.value = (json.data ?? []).map(mapApiToRequest)
  } catch {
    error('Network error', 'Could not reach the server.')
  } finally {
    loading.value = false
  }
}

onMounted(fetchRequests)

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

// Optimistic local-state updates — PATCH endpoints can be wired later
function handleApprove(id: string) {
  const r = requests.value.find(x => x.id === id)
  if (!r) return
  r.status = 'Approved'
  success('Request approved', `${r.businessPartner}'s contract request has been approved.`)
}

function handleReject(id: string, reason: string = '') {
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
    <RequestsTable
      :loading="loading"
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
