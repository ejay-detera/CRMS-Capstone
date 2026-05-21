<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue'
import { Plus } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/useAuth'
import SalesRequestsTable      from './SalesRequestsTable.vue'
import SalesRequestDetailDialog from './SalesRequestDetailDialog.vue'
import type { ContractRequest, RequestFilterTab, RequestStatus, RequestPriority } from '@/types/contractRequest'

const { success, error } = useToast()
const { state: authState } = useAuth()

const apiBase = import.meta.env.VITE_CONTRACT_API_URL as string

const requests = ref<ContractRequest[]>([])
const loading  = ref(true)

const STATUS_MAP: Record<string, RequestStatus> = {
  'Pending':      'Pending',
  'Under Review': 'Under Review',
  'Approved':     'Approved',
  'Rejected':     'Rejected',
}

function mapApiToRequest(d: any): ContractRequest {
  const user = authState.user
  const createdBy = (user && d.created_by === user.id)
    ? `${user.first_name} ${user.last_name}`.trim()
    : d.created_by ? `User #${d.created_by}` : '—'
  return {
    id:              `REQ-${String(d.contract_id).padStart(3, '0')}`,
    businessPartner: d.bp_name        ?? '',
    category:        d.category       ?? '',
    description:     d.description    ?? '',
    region:          (d.region        ?? 'Luzon') as ContractRequest['region'],
    requestDate:     d.created_at     ?? '',
    startDate:       d.start_date     ?? '',
    endDate:         d.end_date       ?? '',
    priority:        'Medium' as RequestPriority, // priority not yet in DB; default
    status:          d.workflow_status ? 'Under Review' : (STATUS_MAP[d.approval_status ?? 'Pending'] ?? 'Pending'),
    notes:           '',
    rejectionReason: '',
    contractLink:    '',
    createdBy,
  }
}

async function fetchRequests() {
  loading.value = true
  try {
    const userId = authState.user?.id
    const url = userId
      ? `${apiBase}/contract-requests?created_by=${userId}`
      : `${apiBase}/contract-requests`
    const res = await fetch(url, {
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
    <SalesRequestsTable
      :loading="loading"
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
