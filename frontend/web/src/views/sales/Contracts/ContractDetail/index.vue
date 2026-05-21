<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { FileX } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useContractStore } from '@/composables/useContractStore'
import { remainingDays } from '@/types/contract'
import type { Contract } from '@/types/contract'
import ContractDetailHeader    from './ContractDetailHeader.vue'
import ContractInfoSection     from './ContractInfoSection.vue'
import ContractDocumentsSection from './ContractDocumentsSection.vue'
import EditContractDialog      from './EditContractDialog.vue'

const route  = useRoute()
const router = useRouter()
const { store, get, update } = useContractStore()

const id = route.params.id as string

const contract = computed(() => {
  void store.size
  return get(id)
})

const days = computed(() => contract.value ? remainingDays(contract.value.endDate) : 0)

const showEdit = ref(false)

function handleSave(patch: Omit<Contract, 'id' | 'createdBy'>) {
  update(id, patch)
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
        @back="router.push('/sales/contracts')"
        @edit="showEdit = true"
      />
      <ContractInfoSection
        :contract="contract"
        :days="days"
      />
      <ContractDocumentsSection
        :docs="contract.docs"
      />
    </template>

    <EditContractDialog
      v-model:open="showEdit"
      :contract="contract ?? null"
      @save="handleSave"
    />

  </div>
</template>
