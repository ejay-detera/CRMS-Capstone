<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Search, Link2 } from 'lucide-vue-next'
import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/components/ui/dialog'
import { engagementBadge } from '@/types/partner'
import { useApiCache } from '@/composables/useApiCache'
import { remainingDays, deriveLifecycleStatus } from '@/types/contract'

interface ContractListItem {
  contractId: string
  description: string
  startDate: string
  endDate: string
  engagementStatus: 'active' | 'expiring' | 'expired'
}

const props = defineProps<{
  open: boolean
  alreadyLinked: string[]      // IDs already linked to THIS vendor
  globallyLinked?: string[]    // IDs linked to ANY vendor (globally taken)
}>()

const emit = defineEmits<{
  (e: 'update:open', open: boolean): void
  (e: 'submit', contractId: string): void
}>()

const { fetchContracts, state: cacheState } = useApiCache()

const search = ref('')
const selected = ref<string | null>(null)
const touched = ref(false)

watch(() => props.open, async (open) => {
  if (open) {
    try {
      await fetchContracts()
    } catch (err) {
      console.error('Failed to fetch contracts:', err)
    }
  }
})

const availableContracts = computed<ContractListItem[]>(() => {
  const list = cacheState.contracts || []
  return list.map(c => {
    const days = remainingDays(c.endDate)
    const status = deriveLifecycleStatus(days)
    return {
      contractId: c.id,
      description: c.description,
      startDate: c.startDate,
      endDate: c.endDate,
      engagementStatus: status
    }
  })
})

const filteredContracts = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return availableContracts.value
  return availableContracts.value.filter(c =>
    c.contractId.toLowerCase().includes(q) ||
    c.description.toLowerCase().includes(q)
  )
})

const isLinkedToThisVendor = (contractId: string) =>
  props.alreadyLinked.includes(contractId)

const isLinkedToOtherVendor = (contractId: string) =>
  !!(props.globallyLinked?.includes(contractId)) && !props.alreadyLinked.includes(contractId)

const isAlreadyLinked = (contractId: string) =>
  isLinkedToThisVendor(contractId) || isLinkedToOtherVendor(contractId)

const selectContract = (contractId: string) => {
  selected.value = contractId
}

const handleSubmit = () => {
  if (selected.value) {
    emit('submit', selected.value)
    selected.value = null
    search.value = ''
    touched.value = false
    emit('update:open', false)
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="max-w-md p-6 font-poppins gap-4" @pointer-down-outside="emit('update:open', false)">
      
      <!-- Header -->
      <div class="flex items-center justify-between pb-3 border-b border-black/[0.06]">
        <DialogTitle class="text-base font-bold text-[#2F2F73]">Link a Contract</DialogTitle>
      </div>

      <DialogDescription class="text-xs text-black/50 mt-1">
        Search for a contract from the contract registry to associate with this vendor.
      </DialogDescription>

      <!-- Search Input -->
      <div class="relative mt-2">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
          <Search class="w-4 h-4 text-black/30" />
        </span>
        <input
          v-model="search"
          type="text"
          placeholder="Search by Contract ID or description..."
          class="w-full pl-9 pr-4 py-2 text-sm border border-black/10 rounded-lg focus:outline-none focus:border-[#2E85D8] focus:ring-1 focus:ring-[#2E85D8]/20 transition-all font-poppins placeholder-black/30 text-black/80"
          @input="touched = true"
        />
      </div>

      <!-- Contracts List -->
      <div class="overflow-y-auto max-h-[240px] border border-black/[0.06] rounded-lg divide-y divide-black/[0.04] mt-2 modal-scrollbar">
        <div
          v-for="c in filteredContracts"
          :key="c.contractId"
          class="flex items-start justify-between p-3 transition-colors text-left"
          :class="[
            isAlreadyLinked(c.contractId)
              ? 'bg-black/[0.02] opacity-50 cursor-not-allowed'
              : selected === c.contractId
                ? 'bg-[#2E85D8]/5 border-[#2E85D8]/20'
                : 'hover:bg-black/[0.005] cursor-pointer'
          ]"
          @click="!isAlreadyLinked(c.contractId) && selectContract(c.contractId)"
        >
          <div class="flex-1 min-w-0 pr-4">
            <div class="flex items-center gap-2 mb-1">
              <span class="text-xs font-mono font-bold text-[#2F2F73]">
                {{ c.contractId }}
              </span>
              <span
                class="px-1.5 py-0.5 text-[9px] font-semibold border rounded-full uppercase tracking-wider scale-90 origin-left"
                :class="engagementBadge[c.engagementStatus]"
              >
                {{ c.engagementStatus }}
              </span>
              <span v-if="isLinkedToThisVendor(c.contractId)" class="text-[9px] text-black/35 font-medium italic">
                (Linked to This Vendor)
              </span>
              <span v-else-if="isLinkedToOtherVendor(c.contractId)" class="text-[9px] text-amber-500 font-medium italic">
                (Assigned to Another Vendor)
              </span>
            </div>
            <p class="text-xs text-black/70 font-semibold truncate">
              {{ c.description }}
            </p>
            <p class="text-[10px] text-black/40 mt-0.5">
              Period: {{ c.startDate }} to {{ c.endDate }}
            </p>
          </div>

          <div class="flex items-center self-center shrink-0">
            <input
              type="radio"
              :name="'contracts-selection'"
              :value="c.contractId"
              :checked="selected === c.contractId"
              :disabled="isAlreadyLinked(c.contractId)"
              class="w-4 h-4 text-[#2E85D8] border-black/10 focus:ring-[#2E85D8]"
            />
          </div>
        </div>

        <div v-if="filteredContracts.length === 0" class="p-8 text-center text-xs text-black/35">
          No contracts found matching your search.
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center justify-end gap-2 pt-3 border-t border-black/[0.06] mt-2">
        <button
          type="button"
          @click="emit('update:open', false)"
          class="px-4 py-2 text-xs font-medium text-black/60 hover:text-black border border-black/10 hover:bg-black/5 rounded transition-all"
        >
          Cancel
        </button>
        <button
          type="button"
          @click="handleSubmit"
          :disabled="!selected"
          class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-medium text-white bg-[#2E85D8] hover:bg-[#252578] rounded disabled:opacity-50 disabled:cursor-not-allowed transition-all"
        >
          <Link2 class="w-3.5 h-3.5" />
          Link Contract
        </button>
      </div>
    </DialogContent>
  </Dialog>
</template>
