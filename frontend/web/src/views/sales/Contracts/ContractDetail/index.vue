<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { FileX, Loader2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { remainingDays } from '@/types/contract'
import type { ContractApprovalStatus, ContractWorkflowStatus, ContractRegion, UploadedDoc } from '@/types/contract'
import type { StoredContract } from '@/composables/useContractStore'
import ContractDetailHeader     from './ContractDetailHeader.vue'
import ContractInfoSection      from './ContractInfoSection.vue'
import ContractDocumentsSection from './ContractDocumentsSection.vue'

const route  = useRoute()
const router = useRouter()
const { state: authState } = useAuth()
const { success, error } = useToast()

const id = route.params.id as string

const contract        = ref<StoredContract | null>(null)
const loadingContract = ref(true)
const savingEdit      = ref(false)

const days = computed(() => contract.value ? remainingDays(contract.value.endDate) : 0)

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

async function loadContract() {
  loadingContract.value = true
  try {
    const res = await fetch(`${apiBase}/contracts/${id}`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${authState.token}`,
      },
    })
    if (!res.ok) {
      contract.value = null
      return
    }
    const json = await res.json()
    contract.value = mapApiToContract(json.data)
  } catch {
    contract.value = null
  } finally {
    loadingContract.value = false
  }
}

onMounted(loadContract)

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
    <div v-if="loadingContract" class="flex items-center justify-center py-24 text-black/30">
      <Loader2 class="w-8 h-8 animate-spin" />
    </div>

    <!-- Not-found state -->
    <div v-else-if="!contract"
      class="flex flex-col items-center gap-3 py-24 text-black/30">
      <FileX class="w-12 h-12" />
      <p class="text-base font-semibold">Contract not found</p>
      <p class="text-sm text-black/25">The contract may have been deleted or the ID is invalid.</p>
      <Button variant="outline" @click="router.push('/sales/contracts')"
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
        @back="router.push('/sales/contracts')"
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
      />
      <ContractDocumentsSection
        :docs="isEditing ? contractDocs : contract.docs"
        :is-editing="isEditing"
        @update:docs="contractDocs = $event"
      />
    </template>

  </div>
</template>
