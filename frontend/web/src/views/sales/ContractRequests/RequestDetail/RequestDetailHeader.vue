<script setup lang="ts">
import { AlertTriangle, Clock, ArrowLeft, ClipboardList, FilePenLine, Loader2, CheckCircle, XCircle, RefreshCw, Bell } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { requestStatusBadge } from '@/types/contractRequest'
import type { ContractRequest } from '@/types/contractRequest'

const props = defineProps<{
  request:           ContractRequest
  days:              number
  isEditing:         boolean
  saving?:           boolean
  isManager:         boolean
  isFollowedUp:      boolean
  showRejectInput:   boolean
  rejectReasonValid: boolean
}>()

defineEmits<{
  back:          []
  edit:          []
  save:          []
  cancel:        []
  followUp:      []
  approve:       []
  setReviewing:  []
  toggleReject:  []
  confirmReject: []
}>()

function daysDisplay(days: number) {
  if (days < 0)   return { text: `Expired ${Math.abs(days)}d ago`, cls: 'bg-red-50 text-red-600 border-red-200',     icon: AlertTriangle }
  if (days <= 30) return { text: `${days} days left`,              cls: 'bg-amber-50 text-amber-600 border-amber-200', icon: Clock }
  return                 { text: `${days} days left`,              cls: 'bg-black/4 text-black/55 border-black/10',   icon: Clock }
}
</script>

<template>
  <div class="flex items-start gap-4">

    <!-- Back button -->
    <button @click="$emit('back')"
      class="flex items-center justify-center w-9 h-9 rounded-lg border border-black/10 bg-white hover:bg-black/4 text-black/50 hover:text-black transition shrink-0 mt-0.5">
      <ArrowLeft class="w-4 h-4" />
    </button>

    <!-- Icon + title block -->
    <div class="flex items-start gap-3.5 flex-1 min-w-0">
      <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white shrink-0 bg-[#252578]">
        <ClipboardList class="w-5 h-5" />
      </div>
      <div class="flex-1 min-w-0">
        <h1 class="text-xl font-semibold text-black leading-snug truncate">{{ request.businessPartner }}</h1>
        <p class="text-sm text-black/45 mt-0.5">{{ request.category }}</p>
        <div class="flex items-center gap-2 mt-2 flex-wrap">
          <span class="text-[10px] font-mono text-black/30 bg-black/4 px-1.5 py-0.5 rounded">{{ request.id }}</span>
          <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full border" :class="requestStatusBadge[request.status]">
            {{ request.status }}
          </span>
          <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full border"
            :class="daysDisplay(days).cls">
            <component :is="daysDisplay(days).icon" class="w-3 h-3" />
            {{ daysDisplay(days).text }}
          </span>
        </div>
      </div>
    </div>

    <!-- Action buttons -->
    <div class="flex items-center gap-2 shrink-0">
      <template v-if="isEditing">
        <Button @click="$emit('cancel')" variant="outline" :disabled="saving"
          class="h-9 px-4 text-sm border-black/15 text-black/60 hover:text-black">
          Cancel
        </Button>
        <Button @click="$emit('save')" :disabled="saving"
          class="h-9 px-5 text-sm bg-[#252578] hover:bg-[#2F2F73] text-white shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
          <Loader2 v-if="saving" class="w-3.5 h-3.5 animate-spin mr-1.5" />
          {{ saving ? 'Saving…' : 'Save Changes' }}
        </Button>
      </template>

      <template v-else>
        <!-- Edit Request (Pending / Under Review, not while reject input is open) -->
        <Button
          v-if="(request.status === 'Pending' || request.status === 'Under Review') && !showRejectInput"
          @click="$emit('edit')" variant="outline"
          class="h-9 gap-2 text-sm font-medium border-[#252578]/25 text-[#252578] hover:bg-[#252578]/5 hover:border-[#252578]/40">
          <FilePenLine class="w-4 h-4" />
          Edit Request
        </Button>

        <!-- Sales: Follow Up -->
        <template v-if="!isManager && request.status === 'Pending'">
          <Button v-if="!isFollowedUp" @click="$emit('followUp')"
            class="h-9 bg-amber-500 hover:bg-amber-600 text-white gap-2 font-semibold shadow-sm">
            <Bell class="w-4 h-4" /> Follow Up Manager
          </Button>
          <div v-else class="h-9 px-4 flex items-center justify-center gap-1.5 text-sm font-semibold text-emerald-600 bg-emerald-50 rounded-lg border border-emerald-100">
            <CheckCircle class="w-4 h-4" /> Follow-up sent
          </div>
        </template>

        <!-- Manager: Approve / Set Reviewing / Reject -->
        <template v-if="isManager && (request.status === 'Pending' || request.status === 'Under Review')">
          <template v-if="!showRejectInput">
            <Button v-if="request.status === 'Pending'" @click="$emit('setReviewing')" variant="outline"
              class="h-9 border-[#2E85D8]/20 text-[#2E85D8] hover:bg-[#2E85D8]/5 gap-2 font-semibold">
              <RefreshCw class="w-4 h-4" /> Set Reviewing
            </Button>
            <Button @click="$emit('approve')"
              class="h-9 bg-emerald-600 hover:bg-emerald-700 text-white gap-2 font-semibold shadow-sm">
              <CheckCircle class="w-4 h-4" /> Approve Request
            </Button>
            <Button @click="$emit('toggleReject')" variant="outline"
              class="h-9 border-red-200 text-red-600 hover:bg-red-50 gap-2 font-semibold">
              <XCircle class="w-4 h-4" /> Reject Request
            </Button>
          </template>
          <template v-else>
            <Button variant="outline" @click="$emit('toggleReject')"
              class="h-9 border-black/10 text-black/60 hover:text-black">
              Cancel
            </Button>
            <Button @click="$emit('confirmReject')" :disabled="!rejectReasonValid"
              class="h-9 bg-red-600 hover:bg-red-700 text-white gap-2 font-semibold disabled:opacity-40 shadow-sm">
              <XCircle class="w-4 h-4" /> Confirm Reject
            </Button>
          </template>
        </template>
      </template>
    </div>

  </div>
</template>
