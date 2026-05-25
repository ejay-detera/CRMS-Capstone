<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ClipboardList, FileX, FileType2, ExternalLink, AlertCircle } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { useApiCache } from '@/composables/useApiCache'
import { remainingDays } from '@/types/contract'
import type { ContractRequest } from '@/types/contractRequest'
import { safeHref } from '@/utils/sanitize'
import RequestDetailHeader from './RequestDetailHeader.vue'
import RequestInfoSection  from './RequestInfoSection.vue'

const route  = useRoute()
const router = useRouter()
const { state: authState } = useAuth()
const { success, error } = useToast()
const { state: cacheState, fetchRequests, updateRequestStatusInCache, updateRequestInCache, updateContractInCache } = useApiCache()

const id = route.params.id as string
const request = ref<ContractRequest | null>(null)
const loading = ref(true)

const backPath = computed(() =>
  route.path.startsWith('/manager') ? '/manager/contract-requests' : '/sales/contract-requests'
)
const isManager = computed(() => route.path.startsWith('/manager'))

const days = computed(() =>
  request.value?.endDate ? remainingDays(request.value.endDate) : 0
)

// ── Follow-up (Sales) ────────────────────────────────────────────────────────

const followedUpIds = ref<string[]>(JSON.parse(localStorage.getItem('followed_up_requests') || '[]'))
const isFollowedUp  = computed(() => request.value ? followedUpIds.value.includes(request.value.id) : false)

function handleFollowUp() {
  if (!request.value || followedUpIds.value.includes(request.value.id)) return
  followedUpIds.value.push(request.value.id)
  localStorage.setItem('followed_up_requests', JSON.stringify(followedUpIds.value))
  success('Follow-up sent', `The manager has been notified about ${request.value.businessPartner}.`)
}

// ── Manager actions ──────────────────────────────────────────────────────────

const showRejectInput  = ref(false)
const rejectReason     = ref('')
const actionInProgress = ref(false)

const apiBase = import.meta.env.VITE_CONTRACT_API_URL as string

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

async function handleApprove() {
  if (!request.value || actionInProgress.value) return
  const numericId  = parseInt(id.replace('REQ-', ''), 10)
  const contractId = String(numericId)

  actionInProgress.value = true
  try {
    const res = await fetch(`${apiBase}/contracts/${numericId}/status`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${authState.token}`,
      },
      body: JSON.stringify({ approval_status: 'Approved', workflow_status: 'SBSI Review' }),
    })

    const data = await res.json()
    if (!res.ok) { error('Failed to approve', data.message ?? 'Something went wrong.'); return }

    updateRequestStatusInCache(id, 'Approved')
    updateContractInCache(contractId, { approvalStatus: 'Approved', workflowStatus: 'SBSI Review' })
    success('Request approved', `${request.value.businessPartner}'s contract request has been approved.`)
    loadLocalRequest()
  } catch {
    error('Network error', 'Could not reach the server. Please try again.')
  } finally {
    actionInProgress.value = false
  }
}

function handleToggleReject() {
  showRejectInput.value = !showRejectInput.value
  if (!showRejectInput.value) rejectReason.value = ''
}

async function confirmReject() {
  if (!request.value || !rejectReason.value.trim() || actionInProgress.value) return
  const numericId  = parseInt(id.replace('REQ-', ''), 10)
  const contractId = String(numericId)

  actionInProgress.value = true
  try {
    const res = await fetch(`${apiBase}/contracts/${numericId}/status`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${authState.token}`,
      },
      body: JSON.stringify({ approval_status: 'Rejected' }),
    })

    const data = await res.json()
    if (!res.ok) { error('Failed to reject', data.message ?? 'Something went wrong.'); return }

    updateRequestStatusInCache(id, 'Rejected', { rejectionReason: rejectReason.value.trim() })
    updateContractInCache(contractId, { approvalStatus: 'Rejected', workflowStatus: null })
    success('Request rejected', `${request.value.businessPartner}'s contract request has been rejected.`)
    showRejectInput.value = false
    rejectReason.value = ''
    loadLocalRequest()
  } catch {
    error('Network error', 'Could not reach the server. Please try again.')
  } finally {
    actionInProgress.value = false
  }
}

// ── Data loading ─────────────────────────────────────────────────────────────

function loadLocalRequest() {
  const req = (cacheState.requests || []).find(r => r.id === id)
  if (req) request.value = req
}

async function loadRequest() {
  loadLocalRequest()
  if (request.value) {
    loading.value = false
  } else {
    loading.value = true
  }
  try {
    const userId = isManager.value ? undefined : authState.user?.id
    await fetchRequests(userId)
    loadLocalRequest()
  } catch {
    error('Error loading requests', 'Could not load contract requests.')
  } finally {
    loading.value = false
  }
}

onMounted(loadRequest)

// ── Inline edit ──────────────────────────────────────────────────────────────

const isEditing  = ref(false)
const savingEdit = ref(false)

const editForm = reactive({
  businessPartner: '',
  category:        '',
  itemCode:        '',
  description:     '',
  serialNo:        '',
  sbuNumber:       '',
  region:          '' as ContractRequest['region'] | '',
  startDate:       '',
  endDate:         '',
})

const touched = reactive<Record<string, boolean>>({
  businessPartner: false,
  category:        false,
  itemCode:        false,
  description:     false,
  serialNo:        false,
  sbuNumber:       false,
  region:          false,
  startDate:       false,
  endDate:         false,
})

const dateError = computed(() =>
  touched.startDate && touched.endDate && editForm.startDate && editForm.endDate
    ? editForm.endDate <= editForm.startDate ? 'End date must be after start date.' : ''
    : ''
)

const isFormValid = computed(() =>
  !!editForm.businessPartner && !!editForm.category && !!editForm.itemCode &&
  !!editForm.description && !!editForm.serialNo && !!editForm.sbuNumber &&
  !!editForm.region && !!editForm.startDate && !!editForm.endDate && !dateError.value
)

function startEdit() {
  if (!request.value) return
  Object.assign(editForm, {
    businessPartner: request.value.businessPartner,
    category:        request.value.category,
    itemCode:        request.value.itemCode ?? '',
    description:     request.value.description,
    serialNo:        request.value.serialNo ?? '',
    sbuNumber:       request.value.sbuNumber ?? '',
    region:          request.value.region,
    startDate:       request.value.startDate ? request.value.startDate.split('T')[0] : '',
    endDate:         request.value.endDate   ? request.value.endDate.split('T')[0]   : '',
  })
  Object.keys(touched).forEach(k => (touched[k] = false))
  isEditing.value = true
}

function cancelEdit() {
  isEditing.value = false
}

async function saveEdit() {
  Object.keys(touched).forEach(k => (touched[k] = true))
  if (!isFormValid.value) return

  savingEdit.value = true
  try {
    const payload = {
      bp_name:       editForm.businessPartner,
      category:      editForm.category,
      item_code:     editForm.itemCode,
      description:   editForm.description,
      serial_number: editForm.serialNo,
      sbu_number:    editForm.sbuNumber,
      region:        editForm.region,
      start_date:    editForm.startDate,
      end_date:      editForm.endDate,
    }

    const dbId = parseInt(id.replace('REQ-', ''), 10)
    const res  = await fetch(`${apiBase}/contracts/${dbId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept':        'application/json',
        'Authorization': `Bearer ${authState.token}`,
      },
      body: JSON.stringify(payload),
    })

    const data = await res.json()
    if (!res.ok) { error('Failed to save', data.message ?? 'Something went wrong.'); return }

    const user = authState.user
    const createdBy = (user && data.data.created_by === user.id)
      ? `${user.first_name || ''} ${user.last_name || ''}`.trim() || 'Me'
      : data.data.created_by ? `User #${data.data.created_by}` : '—'

    const updated: ContractRequest = {
      id:              `REQ-${String(data.data.contract_id).padStart(3, '0')}`,
      businessPartner: data.data.bp_name        ?? '',
      category:        data.data.category       ?? '',
      description:     data.data.description    ?? '',
      region:          (data.data.region        ?? 'Luzon') as ContractRequest['region'],
      requestDate:     data.data.created_at     ?? '',
      startDate:       data.data.start_date     ?? '',
      endDate:         data.data.end_date       ?? '',
      status: (
        data.data.approval_status === 'Approved' ? 'Approved' :
        data.data.approval_status === 'Rejected' ? 'Rejected' :
        data.data.workflow_status                ? 'Under Review' :
        'Pending'
      ) as ContractRequest['status'],
      notes:           '',
      rejectionReason: '',
      contractLink:    '',
      createdBy,
      docs: (data.data.documents ?? []).map((doc: any) => ({
        id: doc.document_id || doc._id,
        name: doc.file_name,
        type: doc.file_type as 'pdf' | 'docx',
        size: doc.file_size ?? 0,
        previewUrl: normalizeDocumentUrl(doc.document_url),
        uploadStatus: 'success',
      })),
      itemCode:  data.data.item_code     ?? '',
      serialNo:  data.data.serial_number ?? '',
      sbuNumber: data.data.sbu_number    ?? '',
    }

    request.value = updated
    updateRequestInCache(id, updated)
    isEditing.value = false
    success('Request updated', 'Changes have been saved.')
  } catch {
    error('Network error', 'Could not reach the server. Please try again.')
  } finally {
    savingEdit.value = false
  }
}

// ── Documents ────────────────────────────────────────────────────────────────

const allDocs = computed(() => {
  const list = [...(request.value?.docs || [])]
  if (request.value?.contractLink && list.length === 0) {
    list.push({ name: 'Proposed Contract.pdf', type: 'pdf', size: 0, previewUrl: request.value.contractLink })
  }
  return list
})

function fmtSize(bytes: number) {
  return (bytes / 1_048_576).toFixed(2) + ' MB'
}
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-6">
      <div class="flex items-center justify-between border-b border-black/5 pb-5">
        <div class="space-y-2.5">
          <div class="h-3.5 w-16 bg-black/5 animate-pulse rounded"></div>
          <div class="h-6 w-64 bg-black/5 animate-pulse rounded"></div>
          <div class="h-4.5 w-40 bg-black/5 animate-pulse rounded"></div>
        </div>
        <div class="flex gap-2">
          <div class="h-9 w-20 bg-black/5 animate-pulse rounded-lg"></div>
          <div class="h-9 w-24 bg-black/5 animate-pulse rounded-lg"></div>
        </div>
      </div>
      <div class="bg-white rounded-lg border border-black/8 shadow-sm p-6 space-y-5">
        <div class="h-4.5 w-32 bg-black/5 animate-pulse rounded"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="i in 9" :key="i" class="space-y-2">
            <div class="h-3 w-20 bg-black/5 animate-pulse rounded"></div>
            <div class="h-4 w-40 bg-black/5 animate-pulse rounded"></div>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg border border-black/8 shadow-sm p-6 space-y-4">
        <div class="h-4.5 w-32 bg-black/5 animate-pulse rounded"></div>
        <div class="h-12 w-full bg-black/5 animate-pulse rounded-lg"></div>
      </div>
    </div>

    <!-- Not found -->
    <div v-else-if="!request" class="flex flex-col items-center gap-3 py-24 text-black/30">
      <ClipboardList class="w-12 h-12" />
      <p class="text-base font-semibold">Request not found</p>
      <p class="text-sm text-black/25">The contract request may have been deleted or the ID is invalid.</p>
      <Button variant="outline" @click="router.push(backPath)"
        class="mt-2 h-9 px-5 text-sm border-black/15 text-black/60 hover:text-black">
        Back to Requests
      </Button>
    </div>

    <!-- Content -->
    <template v-else>

      <RequestDetailHeader
        :request="request"
        :days="days"
        :is-editing="isEditing"
        :saving="savingEdit"
        :action-in-progress="actionInProgress"
        :is-manager="isManager"
        :is-followed-up="isFollowedUp"
        :show-reject-input="showRejectInput"
        :reject-reason-valid="rejectReason.trim().length > 0"
        @back="router.push(backPath)"
        @edit="startEdit"
        @save="saveEdit"
        @cancel="cancelEdit"
        @follow-up="handleFollowUp"
        @approve="handleApprove"
        @toggle-reject="handleToggleReject"
        @confirm-reject="confirmReject"
      />

      <RequestInfoSection
        :request="request"
        :is-editing="isEditing"
        :edit-form="editForm"
        :touched="touched"
        :date-error="dateError"
      />

      <!-- Rejection input (manager rejecting) -->
      <div v-if="showRejectInput" class="bg-white rounded-lg border border-black/8 shadow-sm p-6">
        <h2 class="text-xs font-semibold text-black/40 uppercase tracking-widest mb-4">Rejection Reason</h2>
        <label class="text-xs font-semibold text-red-600/80 block mb-1.5">
          Reason <span class="text-red-500">*</span>
        </label>
        <textarea v-model="rejectReason" rows="3" placeholder="Provide a reason for rejection..."
          class="w-full rounded-lg border border-red-200 bg-white px-3 py-2 text-sm placeholder:text-black/25 focus:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-200/15 transition resize-none" />
      </div>

      <!-- Rejection reason display (already rejected) -->
      <div v-else-if="request.status === 'Rejected' && request.rejectionReason"
        class="bg-white rounded-lg border border-red-100 shadow-sm p-6 flex items-start gap-3">
        <AlertCircle class="w-4 h-4 text-red-500 shrink-0 mt-0.5" />
        <div>
          <span class="text-[10px] font-semibold text-red-500/70 uppercase tracking-wider block mb-1">Rejection Reason</span>
          <span class="text-sm text-red-600 font-medium leading-relaxed">{{ request.rejectionReason }}</span>
        </div>
      </div>

      <!-- Documents -->
      <div class="bg-white rounded-xl border border-black/8 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-black/6 flex items-center gap-2">
          <h2 class="text-xs font-semibold text-black/40 uppercase tracking-widest">Documents</h2>
          <span class="text-[10px] font-bold text-black/35 bg-black/5 px-1.5 py-0.5 rounded-full tabular-nums">
            {{ allDocs.length }}
          </span>
        </div>
        <div class="p-5">
          <div v-if="allDocs.length === 0" class="flex flex-col items-center gap-2 py-10 text-black/25">
            <FileX class="w-8 h-8" />
            <p class="text-sm font-medium">No documents attached</p>
          </div>
          <div v-else class="flex flex-wrap gap-4">
            <a v-for="doc in allDocs" :key="doc.name"
              :href="safeHref(doc.previewUrl)" target="_blank" rel="noopener noreferrer"
              class="relative w-36 rounded-lg border border-black/8 overflow-hidden shadow-sm bg-white flex flex-col group hover:border-[#2E85D8]/50 transition-colors">
              <template v-if="doc.type === 'pdf'">
                <div class="w-full h-44 bg-red-50 flex flex-col items-center justify-center gap-2 relative group-hover:bg-red-100 transition-all border-b border-black/5">
                  <FileType2 class="w-10 h-10 text-red-400" />
                  <span class="text-[10px] font-bold text-red-500 uppercase tracking-wider">PDF</span>
                  <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors flex items-center justify-center">
                    <ExternalLink class="w-5 h-5 text-white opacity-0 group-hover:opacity-100 transition-opacity drop-shadow" />
                  </div>
                </div>
              </template>
              <template v-else>
                <div class="w-full h-44 bg-blue-50 flex flex-col items-center justify-center gap-2">
                  <FileType2 class="w-10 h-10 text-blue-400" />
                  <span class="text-[10px] font-bold text-blue-400 uppercase tracking-wider">DOCX</span>
                </div>
              </template>
              <div class="px-2 py-1.5 border-t border-black/6 bg-white">
                <p class="text-[10px] font-medium text-black/60 truncate" :title="doc.name">{{ doc.name }}</p>
                <p class="text-[9px] text-black/30 tabular-nums">
                  {{ doc.size > 0 ? fmtSize(doc.size) : 'Proposed Link' }}
                </p>
              </div>
            </a>
          </div>
        </div>
      </div>

    </template>
  </div>
</template>
