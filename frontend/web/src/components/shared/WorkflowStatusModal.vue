<script setup lang="ts">
import { ref, watch } from 'vue'
import { CheckCircle2, Loader2, GitCommitHorizontal } from 'lucide-vue-next'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import type { ContractWorkflowStatus } from '@/types/contract'

const props = defineProps<{
  open: boolean
  title?: string
  currentStatus: ContractWorkflowStatus | null
  loading?: boolean
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  'submit': [status: ContractWorkflowStatus | null]
}>()

const statusOptions: ContractWorkflowStatus[] = ['Notarized PDF', 'Client Review', 'SBSI Review']

const selectedStatus = ref<ContractWorkflowStatus | null>(props.currentStatus)

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    selectedStatus.value = props.currentStatus
  }
})

function handleSubmit() {
  emit('submit', selectedStatus.value)
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-[425px] p-0 overflow-hidden font-poppins border-black/5 shadow-xl">
      <div class="px-6 pt-6 pb-4">
        <DialogHeader>
          <div class="w-10 h-10 rounded-full bg-[#252578]/10 flex items-center justify-center mb-3">
            <GitCommitHorizontal class="w-5 h-5 text-[#252578]" />
          </div>
          <DialogTitle class="text-xl font-bold text-black">{{ title || 'Change Workflow Status' }}</DialogTitle>
          <DialogDescription class="text-sm text-black/50 mt-1.5 leading-relaxed">
            Update the workflow progress status for this contract.
          </DialogDescription>
        </DialogHeader>

        <div class="mt-6 space-y-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/60 uppercase tracking-wide">Workflow Status</label>
            <Select v-model="selectedStatus">
              <SelectTrigger class="w-full h-10 rounded-lg border-black/15 bg-white text-sm text-black focus:ring-[#2E85D8]/20 focus:border-[#2E85D8] transition-all">
                <SelectValue placeholder="Select status..." />
              </SelectTrigger>
              <SelectContent class="bg-white border border-black/10 shadow-lg rounded-lg">
                <SelectItem v-for="opt in statusOptions" :key="opt" :value="opt" class="cursor-pointer hover:bg-black/5">
                  {{ opt }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
      </div>

      <DialogFooter class="px-6 py-4 bg-black/[0.015] border-t border-black/5 flex items-center gap-2 sm:justify-end">
        <Button variant="outline" @click="emit('update:open', false)" :disabled="loading"
          class="flex-1 sm:flex-none h-10 border-black/15 text-black/60 hover:text-black hover:bg-black/5 font-medium transition-colors">
          Cancel
        </Button>
        <Button @click="handleSubmit" :disabled="loading || selectedStatus === currentStatus"
          class="flex-1 sm:flex-none h-10 bg-[#252578] hover:bg-[#2F2F73] text-white font-semibold transition-all flex items-center justify-center gap-2">
          <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
          <CheckCircle2 v-else class="w-4 h-4" />
          Save Changes
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
