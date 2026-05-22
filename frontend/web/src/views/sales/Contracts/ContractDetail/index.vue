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

const route  = useRoute()
const router = useRouter()
const { state: authState, role } = useAuth()
const isManager = computed(() => role.value === 'Manager' || role.value === 'Admin')
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

function mapApiToContract(data: any): StoredContract {
  const user = authState.user
  const createdBy = (user && data.created_by === user.id)
    ? `${user.first_name} ${user.last_name}`.trim()
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
    contractLink:    '',
    createdBy,
    docs: (data.documents ?? []).map((d: any): UploadedDoc => ({
      name: d.file_name,
      type: d.file_type as 'pdf' | 'docx',
      size: d.file_size ?? 0,
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

onMounted(async () => {
  await loadContract()
  if (route.query.edit === '1' && contract.value) {
    startEdit()
  }
})

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

async function saveEdit() {
  Object.keys(touched).forEach(k => (touched[k] = true))
  if (!isFormValid.value) return

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
    }
    if (isManager.value) {
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
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Loading state -->
    <div v-if="loadingContract" class="space-y-6">
      <!-- Header Skeleton -->
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
      <!-- Info Section Skeleton -->
      <div class="bg-white rounded-lg border border-black/8 shadow-sm p-6 space-y-5">
        <div class="h-4.5 w-32 bg-black/5 animate-pulse rounded"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="i in 9" :key="i" class="space-y-2">
            <div class="h-3 w-20 bg-black/5 animate-pulse rounded"></div>
            <div class="h-4 w-40 bg-black/5 animate-pulse rounded"></div>
          </div>
        </div>
      </div>
      <!-- Documents Section Skeleton -->
      <div class="bg-white rounded-lg border border-black/8 shadow-sm p-6 space-y-4">
        <div class="h-4.5 w-32 bg-black/5 animate-pulse rounded"></div>
        <div class="h-12 w-full bg-black/5 animate-pulse rounded-lg"></div>
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
        @back="router.push(backPath)"
        @edit="startEdit"
        @save="saveEdit"
        @cancel="cancelEdit"
      />
      <ContractInfoSection
        :contract="contract"
        :is-editing="isEditing"
        :edit-form="editForm"
        :touched="touched"
        :date-error="dateError"
        :is-manager="isManager"
        :is-approved="contract.approvalStatus === 'Approved'"
      />
      <ContractDocumentsSection
        :docs="isEditing ? contractDocs : contract.docs"
        :is-editing="isEditing"
        @update:docs="contractDocs = $event"
      />
    </template>

  </div>
</template>
