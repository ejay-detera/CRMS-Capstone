<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { RefreshCw, Upload, AlertTriangle } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import * as XLSX from 'xlsx'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/useAuth'
import ExpiredContractsTable from './ExpiredContractsTable.vue'
import ContractDetailDialog from '../Contracts/ContractDetailDialog.vue'
import { remainingDays } from '@/types/contract'
import type { Contract, ContractRegion, ContractApprovalStatus, ContractWorkflowStatus } from '@/types/contract'

const { success, error } = useToast()
const { state: authState } = useAuth()

const apiBase = import.meta.env.VITE_CONTRACT_API_URL as string

const contracts = ref<Contract[]>([])
const total = ref(0)
const loading = ref(false)
const errorMsg = ref<string | null>(null)
const lastRefreshed = ref<Date | null>(null)

const searchQuery = ref('')
const categoryFilter = ref('')
const regionFilter = ref('')
const workflowStatusFilter = ref('')
const currentPage = ref(1)
const itemsPerPage = 10

let refreshIntervalId: any = null

function normalizeDocumentUrl(url?: string): string {
  if (!url) return ''
  if (url.startsWith('blob:')) return url
  const baseDomain = apiBase.replace(/\/api$/, '')
  if (url.startsWith('/storage')) {
    return `${baseDomain}${url}`
  }
  if (url.startsWith('http://localhost/storage')) {
    return url.replace('http://localhost', baseDomain)
  }
  return url
}

function mapApiContract(d: any, currentUserId: number | null, firstName?: string, lastName?: string): Contract {
  const isCreatedByCurrentUser = currentUserId !== null && d.created_by === currentUserId
  const createdBy = isCreatedByCurrentUser
    ? `${firstName || ''} ${lastName || ''}`.trim() || 'Me'
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
    docs: (d.documents ?? []).map((doc: any) => ({
      id: doc.document_id || doc._id,
      name: doc.file_name,
      type: doc.file_type as 'pdf' | 'docx',
      size: doc.file_size ?? 0,
      previewUrl: normalizeDocumentUrl(doc.document_url),
      uploadStatus: 'success',
    })),
  }
}

async function fetchExpired(isManual = false) {
  if (!authState.user) return
  loading.value = true
  errorMsg.value = null

  try {
    const params = new URLSearchParams()
    if (searchQuery.value) params.append('search', searchQuery.value)
    if (categoryFilter.value) params.append('category', categoryFilter.value)
    if (regionFilter.value) params.append('region', regionFilter.value)
    if (workflowStatusFilter.value) params.append('workflow_status', workflowStatusFilter.value)
    params.append('page', currentPage.value.toString())
    params.append('per_page', itemsPerPage.toString())

    const res = await fetch(`${apiBase}/contracts/expired?${params.toString()}`, {
      headers: {
        'Authorization': `Bearer ${authState.token || ''}`,
        'Accept': 'application/json'
      }
    })
    const json = await res.json()
    if (res.ok) {
      const user = authState.user
      contracts.value = (json.data ?? []).map((d: any) =>
        mapApiContract(d, user?.id || null, user?.first_name, user?.last_name)
      )
      total.value = json.meta?.total ?? 0
      lastRefreshed.value = new Date()
      if (isManual) {
        success('Refreshed', 'Expired contracts list updated successfully.')
      }
    } else {
      errorMsg.value = json.message || 'Could not load expired contracts.'
      error('Fetch failed', errorMsg.value || '')
    }
  } catch (err) {
    console.error('Failed to fetch expired contracts:', err)
    errorMsg.value = 'Connection to contract service failed.'
    error('Fetch failed', errorMsg.value)
  } finally {
    loading.value = false
  }
}

async function exportXLSX() {
  if (!authState.user) return
  try {
    const params = new URLSearchParams()
    if (searchQuery.value) params.append('search', searchQuery.value)
    if (categoryFilter.value) params.append('category', categoryFilter.value)
    if (regionFilter.value) params.append('region', regionFilter.value)
    if (workflowStatusFilter.value) params.append('workflow_status', workflowStatusFilter.value)
    params.append('per_page', '10000') // fetch all matching records for export

    const res = await fetch(`${apiBase}/contracts/expired?${params.toString()}`, {
      headers: {
        'Authorization': `Bearer ${authState.token || ''}`,
        'Accept': 'application/json'
      }
    })
    const json = await res.json()
    if (res.ok) {
      const user = authState.user
      const allContracts = (json.data ?? []).map((d: any) =>
        mapApiContract(d, user?.id || null, user?.first_name, user?.last_name)
      )
      const rows = allContracts.map((c: Contract) => ({
        'Contract ID': c.id,
        'Business Partner': c.businessPartner,
        'Category': c.category,
        'Item Code': c.itemCode,
        'Description': c.description,
        'Serial No': c.serialNo,
        'Region': c.region,
        'Start Date': c.startDate,
        'End Date': c.endDate,
        'Days Overdue': Math.abs(remainingDays(c.endDate)),
        'Approval Status': c.approvalStatus,
        'Workflow Status': c.workflowStatus ?? '',
        'Sales Rep': c.createdBy,
      }))
      const ws = XLSX.utils.json_to_sheet(rows)
      const wb = XLSX.utils.book_new()
      XLSX.utils.book_append_sheet(wb, ws, 'Expired Contracts')
      XLSX.writeFile(wb, 'sbsi-expired-contracts.xlsx')
      success('Export complete', `${allContracts.length} expired contracts exported.`)
    } else {
      error('Export failed', 'Could not fetch records for export.')
    }
  } catch (err) {
    console.error('Failed to export contracts:', err)
    error('Export failed', 'Connection to contract service failed.')
  }
}

// Watch filters to reset page to 1 and re-fetch
watch([searchQuery, categoryFilter, regionFilter, workflowStatusFilter], () => {
  currentPage.value = 1
  fetchExpired()
})

// Watch page change to re-fetch
watch(currentPage, () => {
  fetchExpired()
})

onMounted(() => {
  fetchExpired()
  refreshIntervalId = setInterval(() => fetchExpired(false), 5 * 60 * 1000) // Auto-refresh every 5 mins
})

onUnmounted(() => {
  if (refreshIntervalId) {
    clearInterval(refreshIntervalId)
  }
})

// Detail dialog state
const showDetail = ref(false)
const detailTarget = ref<(Contract & { days: number }) | null>(null)
function openDetail(c: Contract) {
  detailTarget.value = {
    ...c,
    days: remainingDays(c.endDate)
  }
  showDetail.value = true
}

const formattedRefreshedTime = computed(() => {
  if (!lastRefreshed.value) return ''
  return lastRefreshed.value.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
})
</script>

<template>
  <div class="p-8 space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2">
          <h1 class="text-xl font-semibold text-black">Expired Contracts</h1>
          <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-50 text-red-500 border border-red-100">
            Compliance Alert
          </span>
        </div>
        <p class="text-sm text-black/40 mt-0.5">Isolate and action contracts that are past their end date.</p>
      </div>

      <div class="flex items-center gap-2 shrink-0">
        <!-- Last refreshed label -->
        <span v-if="formattedRefreshedTime" class="text-xs text-black/35 font-medium mr-2">
          Last refreshed: {{ formattedRefreshedTime }}
        </span>

        <Button @click="fetchExpired(true)" variant="outline" class="h-9 gap-2 text-sm font-medium border-black/15 text-black/65 hover:text-black">
          <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" />
          Refresh
        </Button>

        <Button @click="exportXLSX" variant="outline" class="h-9 gap-2 text-sm font-medium border-black/15 text-black/65 hover:text-black">
          <Upload class="w-4 h-4" />
          Export XLSX
        </Button>
      </div>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white rounded-lg border border-black/8 px-6 py-5 shadow-sm flex items-center justify-between">
        <div>
          <p class="text-xs font-medium text-black/40 uppercase tracking-wide mb-1">Total Overdue Contracts</p>
          <span class="text-3xl font-semibold tabular-nums text-red-500">{{ total }}</span>
        </div>
        <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center shrink-0">
          <AlertTriangle class="w-6 h-6 text-red-500" />
        </div>
      </div>
    </div>

    <!-- Alert Message in case of connection errors -->
    <div v-if="errorMsg" class="p-4 rounded-lg bg-red-50 border border-red-200 flex items-start gap-3">
      <AlertTriangle class="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
      <div>
        <h4 class="text-sm font-semibold text-red-800">Connection Error</h4>
        <p class="text-xs text-red-600 mt-0.5">{{ errorMsg }}</p>
      </div>
    </div>

    <!-- Expired Contracts Table -->
    <ExpiredContractsTable
      :contracts="contracts"
      :total="total"
      :loading="loading"
      v-model:searchQuery="searchQuery"
      v-model:categoryFilter="categoryFilter"
      v-model:regionFilter="regionFilter"
      v-model:workflowStatusFilter="workflowStatusFilter"
      v-model:currentPage="currentPage"
      :itemsPerPage="itemsPerPage"
      @openDetail="openDetail"
    />

    <!-- Detail Dialog -->
    <ContractDetailDialog
      v-model:open="showDetail"
      :contract="detailTarget"
    />
  </div>
</template>
