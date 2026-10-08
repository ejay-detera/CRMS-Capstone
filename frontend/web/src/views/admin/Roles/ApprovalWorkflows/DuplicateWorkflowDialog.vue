<script setup lang="ts">
import { reactive, ref, computed } from 'vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { useToast } from '@/composables/useToast'
import { useWorkflowBuilder } from '@/composables/useWorkflowBuilder'
import type { ContractTypeWithWorkflow } from '@/types/workflow'

const props = defineProps<{
  open: boolean
  sourceWorkflowId: number
  sourceName: string
  contractTypes: ContractTypeWithWorkflow[]
}>()

const emit = defineEmits<{
  close: []
  duplicated: []
}>()

const { error: toastError } = useToast()
const { duplicateWorkflow } = useWorkflowBuilder()

// One-time duplication (decision #10): the copy is a brand new draft with
// no ongoing link back to the source — editing either afterward never
// affects the other.
const form = reactive({
  targetContractTypeId: null as number | null,
  name: `${props.sourceName} (Copy)`,
})

const saving = ref(false)
const saveError = ref<string | null>(null)

const isValid = computed(() => !!form.targetContractTypeId && form.name.trim().length > 0)

async function doDuplicate() {
  if (!form.targetContractTypeId) return
  saveError.value = null
  saving.value = true
  try {
    await duplicateWorkflow(props.sourceWorkflowId, form.targetContractTypeId, form.name.trim())
    emit('duplicated')
  } catch (e: any) {
    saveError.value = e.message ?? 'Failed to duplicate workflow.'
    toastError('Could not duplicate workflow', saveError.value ?? undefined)
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Dialog :open="open" @update:open="(v: boolean) => !v && emit('close')">
    <DialogContent class="max-w-md p-6 gap-4">
      <DialogHeader>
        <DialogTitle class="text-sm font-bold text-black">Duplicate Workflow</DialogTitle>
        <DialogDescription class="text-xs text-black/40 mt-1">
          Creates a new draft copy of "{{ sourceName }}" for a different contract type. The copy has no
          ongoing link to the original.
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-3">
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-black/55">Target Contract Type</label>
          <select v-model="form.targetContractTypeId"
            class="h-9 rounded-lg border border-black/12 px-3 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-brand-blue/15 focus:border-brand-blue transition">
            <option :value="null" disabled>Select a contract type</option>
            <option v-for="ct in contractTypes" :key="ct.contract_type_id" :value="ct.contract_type_id">
              {{ ct.contract_type_name }}
            </option>
          </select>
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-semibold text-black/55">New Workflow Name</label>
          <input v-model="form.name" type="text"
            class="h-9 rounded-lg border border-black/12 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/15 focus:border-brand-blue transition" />
        </div>

        <p v-if="saveError" class="text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
          {{ saveError }}
        </p>
      </div>

      <DialogFooter class="flex items-center justify-end gap-3 mt-2">
        <Button variant="outline" @click="emit('close')" :disabled="saving"
          class="h-9 px-4 text-sm border-black/15 text-black/65 hover:text-black hover:bg-black/4">
          Cancel
        </Button>
        <Button @click="doDuplicate" :disabled="saving || !isValid"
          class="h-9 px-4 text-sm bg-brand-navy hover:bg-brand-dark text-white shadow-sm">
          {{ saving ? 'Duplicating...' : 'Duplicate' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
