<script setup lang="ts">
import { ArrowLeft, FileText, FilePenLine, Clock, AlertTriangle, Loader2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { approvalStatusBadge, workflowStatusBadge } from '@/types/contract'
import type { StoredContract } from '@/composables/useContractStore'

const props = defineProps<{
  contract:  StoredContract
  days:      number
  isEditing: boolean
  saving?:   boolean
  disabled?: boolean
}>()

defineEmits<{ back: []; edit: []; save: []; cancel: [] }>()

function daysDisplay(days: number) {
  if (days < 0)   return { text: `Expired ${Math.abs(days)}d ago`, cls: 'bg-red-50 text-red-600 border-red-200',     icon: AlertTriangle }
  if (days <= 30) return { text: `${days} days left`,              cls: 'bg-amber-50 text-amber-600 border-amber-200', icon: Clock }
  return                 { text: `${days} days left`,              cls: 'bg-black/4 text-black/55 border-black/10',   icon: Clock }
}
</script>

<template>
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
    
    <div class="flex items-start gap-4">
      <!-- Back button -->
      <button @click="$emit('back')"
        class="w-10 h-10 bg-white rounded-lg border border-black/10 flex items-center justify-center hover:bg-black/5 transition-colors shadow-sm shrink-0">
        <ArrowLeft class="w-5 h-5 text-[#252578]" />
      </button>

      <div>
        <div class="flex items-center gap-3 mb-1">
          <!-- Icon box -->
          <div class="p-2 bg-[#252578]/10 rounded-lg text-[#252578] flex items-center justify-center">
            <FileText class="w-5 h-5" />
          </div>
          <!-- Title -->
          <h2 class="text-2xl font-bold text-black">{{ contract.businessPartner }}</h2>
        </div>
        
        <!-- Subtitle -->
        <p class="text-black/60 text-sm flex items-center gap-2">
          {{ contract.category }} • <span class="text-[11px] font-bold tracking-wide text-black/40 uppercase">ID: {{ contract.id }}</span>
        </p>

        <!-- Badges -->
        <div class="flex flex-wrap gap-2 mt-3">
          <span class="px-3 py-1 text-xs font-semibold rounded-full border" :class="approvalStatusBadge[contract.approvalStatus]">
            {{ contract.approvalStatus }}
          </span>
          <span v-if="contract.workflowStatus" class="px-3 py-1 text-xs font-semibold rounded-full border" :class="workflowStatusBadge[contract.workflowStatus]">
            {{ contract.workflowStatus }}
          </span>
          <span class="px-3 py-1 text-xs font-medium rounded-full border flex items-center gap-1.5"
            :class="daysDisplay(days).cls">
            <component :is="daysDisplay(days).icon" class="w-3.5 h-3.5" />
            {{ daysDisplay(days).text }}
          </span>
        </div>
      </div>
    </div>

    <!-- Action buttons -->
    <div class="flex items-center gap-3 shrink-0">
      <template v-if="isEditing">
        <button @click="$emit('cancel')"
          class="px-6 py-2.5 bg-white border border-black/15 text-black/60 rounded-lg text-sm font-medium hover:text-black hover:bg-black/5 transition-colors">
          Cancel
        </button>
        <button @click="$emit('save')" :disabled="saving || disabled"
          class="px-6 py-2.5 bg-[#252578] text-white rounded-lg text-sm font-medium flex items-center gap-2 hover:bg-[#2F2F73] transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
          <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
          {{ saving ? 'Saving…' : 'Save Changes' }}
        </button>
      </template>
      <template v-else>
        <button @click="$emit('edit')"
          class="px-6 py-2.5 bg-[#252578] text-white rounded-lg text-sm font-medium flex items-center gap-2 hover:opacity-90 transition-opacity shadow-sm">
          <FilePenLine class="w-4 h-4" />
          Edit Contract
        </button>
      </template>
    </div>

  </div>
</template>
