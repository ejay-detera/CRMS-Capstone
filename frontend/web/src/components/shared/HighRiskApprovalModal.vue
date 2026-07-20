<script setup lang="ts">
import { reactive, watch, computed } from 'vue'
import { AlertTriangle } from 'lucide-vue-next'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import type { RiskLevel } from '@/types/riskAssessment'
import { severityLabel } from '@/types/riskAssessment'

// US-023: mandatory approval gate for High/Critical-risk contracts. Requires
// a rationale before an "approved" or "rejected" decision can be recorded —
// once "approved" is recorded, the caller's normal Approve action can proceed.
const props = defineProps<{
  open:      boolean
  riskLevel: RiskLevel | null
  saving?:   boolean
}>()

const emit = defineEmits<{
  'update:open': [v: boolean]
  submit:        [decision: 'approved' | 'rejected', rationale: string]
}>()

const form = reactive({ rationale: '' })
const touched = reactive({ rationale: false })

watch(() => props.open, (open) => {
  if (open) {
    form.rationale = ''
    touched.rationale = false
  }
})

const isValid = computed(() => form.rationale.trim().length >= 5)

function submit(decision: 'approved' | 'rejected') {
  touched.rationale = true
  if (!isValid.value) return
  emit('submit', decision, form.rationale.trim())
}
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-lg p-0 overflow-hidden" @pointer-down-outside="saving ? undefined : $emit('update:open', false)">
      <div class="px-6 pt-6 pb-5 border-b border-black/6">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
            <AlertTriangle class="w-4.5 h-4.5 text-red-600" />
          </div>
          <DialogHeader>
            <DialogTitle class="text-base font-semibold text-black">
              High-Risk Approval Required
            </DialogTitle>
            <DialogDescription class="text-xs text-black/40 mt-0.5">
              This contract's AI Risk Assessment flagged it as
              <span class="font-semibold text-red-600">{{ riskLevel ? severityLabel[riskLevel] : 'High/Critical Risk' }}</span>.
              A recorded decision with rationale is required before it can move to Active.
            </DialogDescription>
          </DialogHeader>
        </div>
      </div>

      <div class="px-6 py-5 space-y-4">
        <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-xs text-amber-800 leading-relaxed">
          Remember: the AI Risk Assessment is a recommendation to inform your decision,
          not an automatic verdict. Review the flagged findings before deciding.
        </div>

        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
            Rationale <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="form.rationale"
            @blur="touched.rationale = true"
            rows="4"
            placeholder="Explain your decision (e.g. reviewed with legal, mitigations in place, risk is acceptable)..."
            class="w-full rounded-md border bg-white px-3 py-2 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition resize-none"
            :class="touched.rationale && !isValid
              ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15'
              : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'"
          />
          <p v-if="touched.rationale && !isValid" class="text-xs text-red-500">
            Rationale is required (minimum 5 characters).
          </p>
        </div>
      </div>

      <div class="border-t border-black/6 px-6 py-4">
        <DialogFooter class="flex items-center justify-end gap-3">
          <Button type="button" variant="outline" :disabled="saving" class="h-9 px-4 text-sm border-black/15 text-black/60 hover:text-black"
            @click="$emit('update:open', false)">
            Cancel
          </Button>
          <Button type="button" variant="outline" :disabled="saving" class="h-9 px-4 text-sm border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700"
            @click="submit('rejected')">
            Reject Contract
          </Button>
          <Button type="button" :disabled="saving" class="h-9 px-5 text-sm bg-[#252578] hover:bg-[#2F2F73] text-white"
            @click="submit('approved')">
            {{ saving ? 'Recording…' : 'Record Approval' }}
          </Button>
        </DialogFooter>
      </div>
    </DialogContent>
  </Dialog>
</template>
