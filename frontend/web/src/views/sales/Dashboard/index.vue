<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Loader2 } from 'lucide-vue-next'
import { remainingDays } from '@/types/contract'
import type { Contract, ContractApprovalStatus, ContractWorkflowStatus, ContractRegion } from '@/types/contract'
import type { ContractRequest, RequestStatus, RequestPriority } from '@/types/contractRequest'
import RecentRequestsTable from './RecentRequestsTable.vue'
import ContractStatusPanel from './ContractStatusPanel.vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'

const { state: authState } = useAuth()
const { error } = useToast()
const apiBase = import.meta.env.VITE_CONTRACT_API_URL as string

// ── Live clock ──────────────────────────────────────────────────
const now = ref(new Date())
let timer: ReturnType<typeof setInterval>
onMounted(() => { timer = setInterval(() => { now.value = new Date() }, 1000) })
onUnmounted(() => clearInterval(timer))

const greeting = computed(() => {
  const h = now.value.getHours()
  if (h < 12) return 'Good morning'
  if (h < 17) return 'Good afternoon'
  return 'Good evening'
})
const formattedDate = computed(() =>
  now.value.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })
)
const formattedTime = computed(() =>
  now.value.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
)

const userFirstName = computed(() => authState.user?.first_name || 'Shadrack')

// ── Live data ───────────────────────────────────────────────────
const contracts = ref<Contract[]>([])
const recentRequests = ref<ContractRequest[]>([])
const loading = ref(true)

function mapApiContract(d: any): Contract {
  const user = authState.user
  const createdBy = (user && d.created_by === user.id)
    ? `${user.first_name} ${user.last_name}`.trim()
    : d.created_by ? `User #${d.created_by}` : '—'
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
    createdBy,
  }
}

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
    priority:        'Medium' as RequestPriority, // default priority since not in DB
    status:          d.workflow_status ? 'Under Review' : (STATUS_MAP[d.approval_status ?? 'Pending'] ?? 'Pending'),
    notes:           '',
    rejectionReason: '',
    contractLink:    '',
    createdBy,
  }
}

async function fetchDashboardData() {
  loading.value = true
  try {
    const userId = authState.user?.id
    const headers = {
      'Accept': 'application/json',
      'Authorization': `Bearer ${authState.token}`,
    }

    // Fetch contracts
    const contractsUrl = userId
      ? `${apiBase}/contracts?created_by=${userId}`
      : `${apiBase}/contracts`
    const contractsRes = await fetch(contractsUrl, { headers })
    if (contractsRes.ok) {
      const contractsJson = await contractsRes.json()
      contracts.value = (contractsJson.data ?? []).map(mapApiContract)
    }

    // Fetch requests (contracts in any state belonging to the user)
    const requestsUrl = userId
      ? `${apiBase}/contract-requests?created_by=${userId}`
      : `${apiBase}/contract-requests`
    const requestsRes = await fetch(requestsUrl, { headers })
    if (requestsRes.ok) {
      const requestsJson = await requestsRes.json()
      // Limit to 6 items on the dashboard
      recentRequests.value = (requestsJson.data ?? []).map(mapApiToRequest).slice(0, 6)
    }
  } catch {
    error('Network error', 'Could not reach the server.')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDashboardData()
})

const withDays = computed(() => contracts.value.map(c => ({ ...c, days: remainingDays(c.endDate) })))

const statCards = computed(() => [
  { label: 'My Contracts',    value: withDays.value.length },
  { label: 'Pending',         value: recentRequests.value.filter(r => r.status === 'Pending' || r.status === 'Under Review').length },
  { label: 'Expiring Soon',   value: withDays.value.filter(c => c.days >= 0 && c.days <= 30).length },
  { label: 'Approved',        value: recentRequests.value.filter(r => r.status === 'Approved').length },
])
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Greeting header -->
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-black/35 uppercase tracking-widest mb-0.5">Sales Portal</p>
        <h1 class="text-xl font-semibold text-black">{{ greeting }}, {{ userFirstName }}.</h1>
        <p class="text-sm text-black/40 mt-0.5">Here's an overview of your contracts and requests.</p>
      </div>
      <div class="text-right hidden sm:block">
        <p class="text-sm font-semibold text-black tabular-nums">{{ formattedTime }}</p>
        <p class="text-xs text-black/40 mt-0.5">{{ formattedDate }}</p>
      </div>
    </div>

    <!-- Loading spinner -->
    <div v-if="loading" class="flex items-center justify-center py-24 text-black/30">
      <Loader2 class="w-8 h-8 animate-spin" />
    </div>

    <template v-else>
      <!-- Stat cards -->
      <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <div v-for="card in statCards" :key="card.label"
          class="bg-white rounded-lg border border-black/8 px-6 py-5 shadow-sm">
          <p class="text-xs font-medium text-black/40 uppercase tracking-wide mb-3">{{ card.label }}</p>
          <p class="text-3xl font-semibold tabular-nums text-black">{{ card.value }}</p>
        </div>
      </div>

      <!-- Main content: requests table + status panel -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2">
          <RecentRequestsTable :requests="recentRequests" />
        </div>
        <div>
          <ContractStatusPanel :contracts="withDays" />
        </div>
      </div>
    </template>

  </div>
</template>
