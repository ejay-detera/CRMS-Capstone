<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { FileX } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useContractStore } from '@/composables/useContractStore'
import { remainingDays } from '@/types/contract'
import type { ContractRegion, UploadedDoc } from '@/types/contract'
import ContractDetailHeader     from './ContractDetailHeader.vue'
import ContractInfoSection      from './ContractInfoSection.vue'
import ContractDocumentsSection from './ContractDocumentsSection.vue'

const route  = useRoute()
const router = useRouter()
const { store, get, update, updateDocs } = useContractStore()

const id = route.params.id as string

const contract = computed(() => {
  void store.size
  return get(id)
})

const days = computed(() => contract.value ? remainingDays(contract.value.endDate) : 0)

// ── Inline edit state ────────────────────────────────────────────────────────

const isEditing = ref(false)

const editForm = reactive({
  businessPartner: '',
  category:        '',
  itemCode:        '',
  description:     '',
  serialNo:        '',
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

function saveEdit() {
  Object.keys(touched).forEach(k => (touched[k] = true))
  if (!isFormValid.value) return
  update(id, { ...editForm, region: editForm.region as ContractRegion })
  updateDocs(id, contractDocs.value)
  isEditing.value = false
}
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Not-found state -->
    <div v-if="!contract"
      class="flex flex-col items-center gap-3 py-24 text-black/30">
      <FileX class="w-12 h-12" />
      <p class="text-base font-semibold">Contract not found</p>
      <p class="text-sm text-black/25">This contract may have been lost on page refresh.</p>
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
