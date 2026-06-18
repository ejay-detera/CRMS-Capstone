<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { FileX } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { useApiCache } from '@/composables/useApiCache'
import { remainingDays } from '@/types/contract'
import type { ContractApprovalStatus, ContractWorkflowStatus, ContractRegion, UploadedDoc } from '@/types/contract'
import type { StoredContract } from '@/composables/useContractStore'
import ContractDetailHeader     from './ContractDetailHeader.vue'
import ContractInfoSection      from './ContractInfoSection.vue'
import ContractDocumentsSection from './ContractDocumentsSection.vue'
import ConfirmationDialog       from '@/components/shared/ConfirmationDialog.vue'
import RejectionReasonModal     from './RejectionReasonModal.vue'
import { AlertCircle } from 'lucide-vue-next'

const route  = useRoute()
const router = useRouter()
const { state: authState, role } = useAuth()
const isManager = computed(() => role.value === 'Manager' || role.value === 'Admin')
const isOwner   = computed(() => !!contract.value && !contract.value.createdBy.startsWith('User #'))
const { success, error } = useToast()
const { state: cacheState, fetchContracts, updateContractInCache } = useApiCache()

const id = route.params.id as string

const contract        = ref<StoredContract | null>(null)
const loadingContract = ref(true)
const savingEdit      = ref(false)

const days = computed(() => contract.value ? remainingDays(contract.value.endDate) : 0)

const backPath = computed(() => {
  if (route.path.startsWith('/admin')) return '/admin/contracts'
  if (route.path.startsWith('/manager')) return '/manager/contracts'
  return '/sales/contracts'
})

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

function mapApiToContract(data: any): StoredContract {
  const user = authState.user
  const firstName = (user as any)?.profile?.first_name || user?.first_name
  const lastName = (user as any)?.profile?.last_name || user?.last_name
  const createdBy = (user && data.created_by === user.id)
    ? `${firstName || ''} ${lastName || ''}`.trim() || 'Me'
    : `User #${data.created_by}`

  return {
    id:              String(data.contract_id),
    businessPartner: data.bp_name ?? '',
    category:        data.category ?? '',
    itemCode:        data.item_code ?? '',
    description:     data.description ?? '',
    serialNo:        data.serial_number ?? '',
    sbuNumber:       data.sbu_number ?? '',
    region:          (data.region ?? '') as ContractRegion,
    startDate:       data.start_date ?? '',
    endDate:         data.end_date ?? '',
    approvalStatus:  (data.approval_status ?? 'Pending') as ContractApprovalStatus,
    workflowStatus:  (data.workflow_status ?? null) as ContractWorkflowStatus | null,
    rejectionReason: data.rejection_reason ?? undefined,
    contractLink:    '',
    createdBy,
    docs: (data.documents ?? []).map((d: any): UploadedDoc => ({
      id: d.document_id || d._id,
      name: d.file_name,
      type: d.file_type as 'pdf' | 'docx',
      size: d.file_size ?? 0,
      previewUrl: normalizeDocumentUrl(d.document_url),
      uploadStatus: 'success',
    })),
  }
}

function loadLocalContract() {
  const c = (cacheState.contracts || []).find(item => item.id === id)
  if (c) {
    contract.value = c as StoredContract
  }
}

async function loadContract() {
  loadLocalContract()
  if (contract.value) {
    loadingContract.value = false
  } else {
    loadingContract.value = true
  }

  try {
    const isAdmin = route.path.startsWith('/admin')
    const isManager = route.path.startsWith('/manager')
    const userId = (isAdmin || isManager) ? undefined : authState.user?.id
    await fetchContracts(userId)
    loadLocalContract()
  } catch {
    if (!contract.value) {
      contract.value = null
    }
  } finally {
    loadingContract.value = false
  }
}

const showRejectionModal = ref(false)

onMounted(async () => {
  await loadContract()
  if (contract.value && contract.value.approvalStatus === 'Rejected') {
    showRejectionModal.value = true
  }
  if (route.query.edit === '1' && contract.value) {
    startEdit()
  }
})

// ── Manager Actions ──────────────────────────────────────────────────────────

const showRejectInput  = ref(false)
const rejectReason     = ref('')
const actionInProgress = ref(false)

const showConfirm = ref(false)
const confirmTitle = ref('')
const confirmDesc = ref('')
const confirmAction = ref<(() => void) | null>(null)

function triggerApprove() {
  confirmTitle.value = 'Approve Contract'
  confirmDesc.value = 'Are you sure you want to approve this contract? This will change the status to Approved.'
  confirmAction.value = handleApprove
  showConfirm.value = true
}

function triggerReject() {
  if (!rejectReason.value.trim()) return
  confirmTitle.value = 'Reject Contract'
  confirmDesc.value = 'Are you sure you want to reject this contract? This will mark it as Rejected.'
  confirmAction.value = confirmReject
  showConfirm.value = true
}

async function handleApprove() {
  if (!contract.value || actionInProgress.value) return
  showConfirm.value = false
  actionInProgress.value = true
  try {
    const res = await fetch(`${apiBase}/contracts/${id}/status`, {
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

    updateContractInCache(id, { approvalStatus: 'Approved', workflowStatus: 'SBSI Review' })
    success('Contract approved', `${contract.value.businessPartner}'s contract has been approved.`)
    loadLocalContract()
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
  if (!contract.value || !rejectReason.value.trim() || actionInProgress.value) return
  showConfirm.value = false
  actionInProgress.value = true
  try {
    const res = await fetch(`${apiBase}/contracts/${id}/status`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${authState.token}`,
      },
      body: JSON.stringify({ approval_status: 'Rejected', rejection_reason: rejectReason.value.trim() }),
    })
    const data = await res.json()
    if (!res.ok) { error('Failed to reject', data.message ?? 'Something went wrong.'); return }

    updateContractInCache(id, { approvalStatus: 'Rejected', workflowStatus: null, rejectionReason: rejectReason.value.trim() })
    success('Contract rejected', `${contract.value.businessPartner}'s contract has been rejected.`)
    showRejectInput.value = false
    rejectReason.value = ''
    loadLocalContract()
  } catch {
    error('Network error', 'Could not reach the server. Please try again.')
  } finally {
    actionInProgress.value = false
  }
}

// ── Inline edit state ────────────────────────────────────────────────────────

const isEditing = ref(false)

const editForm = reactive({
  businessPartner: '',
  category:        '',
  itemCode:        '',
  description:     '',
  serialNo:        '',
  sbuNumber:       '',
  region:          '' as ContractRegion | '',
  startDate:       '',
  endDate:         '',
  workflowStatus:  '' as ContractWorkflowStatus | '',
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
  !!editForm.businessPartner &&
  !!editForm.category &&
  !!editForm.itemCode &&
  !!editForm.description &&
  !!editForm.serialNo &&
  !!editForm.sbuNumber &&
  !!editForm.region &&
  !!editForm.startDate &&
  !!editForm.endDate &&
  !dateError.value
)

const contractDocs = ref<UploadedDoc[]>([])
const isUploadingOrScanFailed = computed(() => {
  return contractDocs.value.some(d => d.uploadStatus === 'uploading' || d.uploadStatus === 'scanning' || d.uploadStatus === 'error')
})

function startEdit() {
  if (!contract.value) return
  Object.assign(editForm, {
    businessPartner: contract.value.businessPartner,
    category:        contract.value.category,
    itemCode:        contract.value.itemCode,
    description:     contract.value.description,
    serialNo:        contract.value.serialNo,
    sbuNumber:       contract.value.sbuNumber ?? '',
    region:          contract.value.region,
    startDate:       contract.value.startDate,
    endDate:         contract.value.endDate,
    workflowStatus:  contract.value.workflowStatus ?? '',
  })
  contractDocs.value = [...contract.value.docs]
  Object.keys(touched).forEach(k => (touched[k] = false))
  isEditing.value = true
}

function cancelEdit() {
  isEditing.value = false
}

function triggerSaveEdit() {
  Object.keys(touched).forEach(k => (touched[k] = true))
  if (!isFormValid.value) return
  confirmTitle.value = 'Save Contract Changes'
  confirmDesc.value = 'Are you sure you want to apply these changes to the contract? This action will update the contract details in the system.'
  confirmAction.value = confirmSaveEdit
  showConfirm.value = true
}

async function confirmSaveEdit() {
  showConfirm.value = false
  savingEdit.value = true
  try {
    const payload: Record<string, unknown> = {
      bp_name:       editForm.businessPartner,
      category:      editForm.category,
      item_code:     editForm.itemCode,
      description:   editForm.description,
      serial_number: editForm.serialNo,
      sbu_number:    editForm.sbuNumber,
      region:        editForm.region,
      start_date:    editForm.startDate,
      end_date:      editForm.endDate,
      document_ids:  contractDocs.value.filter(d => d.id).map(d => d.id),
    }
    if (isManager.value || isOwner.value) {
      payload.workflow_status = editForm.workflowStatus || null
    }

    const res = await fetch(`${apiBase}/contracts/${id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${authState.token}`,
      },
      body: JSON.stringify(payload),
    })

    const data = await res.json()

    if (!res.ok) {
      error('Failed to save', data.message ?? 'Something went wrong.')
      return
    }

    contract.value = mapApiToContract(data.data)
    updateContractInCache(id, contract.value)
    isEditing.value = false
    success('Contract updated', 'Changes have been saved.')
  } catch {
    error('Network error', 'Could not reach the server. Please try again.')
  } finally {
    savingEdit.value = false
  }
}

async function handleNotifyManager() {
  try {
    const res = await fetch(`${apiBase}/contracts/${id}/notify-manager`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${authState.token}`,
      },
    })
    const data = await res.json()
    if (!res.ok) {
      if (res.status === 429) {
        error('Action limited', 'The manager is already notified, please wait.')
      } else {
        error('Failed to notify', data.message ?? 'Something went wrong.')
      }
      return
    }
    success('Success', data.message ?? 'Manager has been notified.')
  } catch {
    error('Network error', 'Could not reach the server.')
  }
}
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Loading state -->
    <div v-if="loadingContract" class="space-y-6">
      <!-- Header Skeleton -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="flex items-start gap-4">
          <div class="w-10 h-10 bg-black/5 animate-pulse rounded-lg shrink-0"></div>
          <div>
            <div class="flex items-center gap-3 mb-1">
              <div class="w-9 h-9 bg-black/5 animate-pulse rounded-lg"></div>
              <div class="h-6 w-64 bg-black/5 animate-pulse rounded"></div>
            </div>
            <div class="h-4 w-40 bg-black/5 animate-pulse rounded mt-2"></div>
            <div class="flex gap-2 mt-3">
              <div class="h-6 w-16 bg-black/5 animate-pulse rounded-full"></div>
              <div class="h-6 w-24 bg-black/5 animate-pulse rounded-full"></div>
            </div>
          </div>
        </div>
        <div class="h-10 w-32 bg-black/5 animate-pulse rounded-lg"></div>
      </div>
      
      <!-- Contract Info Skeleton -->
      <div class="bg-white rounded-xl border border-black/[0.08] shadow-sm p-8 space-y-5">
        <div class="h-3 w-32 bg-black/5 animate-pulse rounded"></div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div v-for="i in 3" :key="i" class="space-y-2">
            <div class="h-3 w-20 bg-black/5 animate-pulse rounded"></div>
            <div class="h-4 w-40 bg-black/5 animate-pulse rounded"></div>
          </div>
        </div>
      </div>
      
      <!-- Item Details Skeleton -->
      <div class="bg-white rounded-xl border border-black/[0.08] shadow-sm p-8 space-y-5">
        <div class="h-3 w-32 bg-black/5 animate-pulse rounded"></div>
        <div class="h-10 w-full bg-black/5 animate-pulse rounded"></div>
      </div>

      <!-- Schedule & Location Skeleton -->
      <div class="bg-white rounded-xl border border-black/[0.08] shadow-sm p-8 space-y-5">
        <div class="h-3 w-32 bg-black/5 animate-pulse rounded"></div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div v-for="i in 3" :key="i" class="space-y-2">
            <div class="h-3 w-20 bg-black/5 animate-pulse rounded"></div>
            <div class="h-4 w-40 bg-black/5 animate-pulse rounded"></div>
          </div>
        </div>
      </div>
      
      <!-- Documents Section Skeleton -->
      <div class="bg-white rounded-xl border border-black/[0.08] shadow-sm p-8 space-y-5">
        <div class="h-3 w-32 bg-black/5 animate-pulse rounded"></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="i in 2" :key="i" class="h-20 w-full bg-black/5 animate-pulse rounded-lg"></div>
        </div>
      </div>
    </div>

    <!-- Not-found state -->
    <div v-else-if="!contract"
      class="flex flex-col items-center gap-3 py-24 text-black/30">
      <FileX class="w-12 h-12" />
      <p class="text-base font-semibold">Contract not found</p>
      <p class="text-sm text-black/25">The contract may have been deleted or the ID is invalid.</p>
      <Button variant="outline" @click="router.push(backPath)"
        class="mt-2 h-9 px-5 text-sm border-black/15 text-black/60 hover:text-black">
        Back to Contracts
      </Button>
    </div>

    <template v-else>
      <ContractDetailHeader
        :contract="contract"
        :days="days"
        :is-editing="isEditing"
        :saving="savingEdit"
        :action-in-progress="actionInProgress"
        :is-manager="isManager"
        :show-reject-input="showRejectInput"
        :reject-reason-valid="rejectReason.trim().length > 0"
        :disabled="isUploadingOrScanFailed"
        @back="router.push(backPath)"
        @edit="startEdit"
        @save="triggerSaveEdit"
        @cancel="cancelEdit"
        @notify-manager="handleNotifyManager"
        @approve="triggerApprove"
        @toggle-reject="handleToggleReject"
        @confirm-reject="triggerReject"
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
      <div v-else-if="contract.approvalStatus === 'Rejected'"
        class="bg-white rounded-lg border border-red-100 shadow-sm p-6 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <AlertCircle class="w-5 h-5 text-red-500 shrink-0" />
          <div>
            <span class="text-sm font-semibold text-red-600 block">Contract Rejected</span>
            <span class="text-xs text-red-500/80 mt-0.5 block">This contract was rejected by the manager.</span>
          </div>
        </div>
        <Button variant="outline" @click="showRejectionModal = true" class="h-9 text-xs border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700">
          View Rejection Reason
        </Button>
      </div>

      <ContractInfoSection
        :contract="contract"
        :is-editing="isEditing"
        :edit-form="editForm"
        :touched="touched"
        :date-error="dateError"
        :is-manager="isManager"
        :is-owner="isOwner"
        :is-approved="contract.approvalStatus === 'Approved'"
      />
      <ContractDocumentsSection
        :docs="isEditing ? contractDocs : contract.docs"
        :is-editing="isEditing"
        @update:docs="contractDocs = $event"
      />
    </template>

    <ConfirmationDialog
      v-model:open="showConfirm"
      :title="confirmTitle"
      :description="confirmDesc"
      confirm-label="Confirm"
      variant="default"
      :loading="actionInProgress || savingEdit"
      @confirm="confirmAction ? confirmAction() : undefined"
    />

    <RejectionReasonModal
      v-if="contract"
      v-model:open="showRejectionModal"
      :reason="contract.rejectionReason || ''"
    />
  </div>
</template>
