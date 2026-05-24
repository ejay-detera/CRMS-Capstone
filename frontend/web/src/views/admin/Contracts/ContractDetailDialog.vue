<script setup lang="ts">
import { FileText, ExternalLink, MapPin, Hash, Cpu, Barcode, CalendarDays, Clock, AlertTriangle, User } from 'lucide-vue-next'
import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { approvalStatusBadge, workflowStatusBadge, fmtDate } from '@/types/contract'
import type { Contract } from '@/types/contract'
import { safeHref } from '@/utils/sanitize'

defineProps<{ open: boolean; contract: (Contract & { days: number }) | null }>()
defineEmits<{ 'update:open': [v: boolean] }>()

function daysDisplay(days: number) {
  if (days < 0)   return { text: `Expired ${Math.abs(days)}d ago`, cls: 'bg-red-50 text-red-600 border-red-200', icon: AlertTriangle }
  if (days <= 30) return { text: `${days} days left`,              cls: 'bg-amber-50 text-amber-600 border-amber-200', icon: Clock }
  return                 { text: `${days} days left`,              cls: 'bg-black/4 text-black/55 border-black/10', icon: Clock }
}

const palette = ['#252578', '#2E85D8', '#2F2F73']
function initials(name: string) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}
function avatarColor(name: string) {
  let h = 0
  for (const c of name) h = (h * 31 + c.charCodeAt(0)) & 0xffff
  return palette[h % palette.length]
}
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-md p-0 overflow-hidden gap-0" @pointer-down-outside="$emit('update:open', false)">
      <template v-if="contract">

        <!-- Header -->
        <div class="px-5 pt-5 pb-4 border-b border-black/6">
          <div class="flex items-start gap-3.5">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white shrink-0 bg-[#252578]">
              <FileText class="w-5 h-5" />
            </div>
            <div class="flex-1 min-w-0 pr-6">
              <DialogTitle class="text-sm font-bold text-black leading-snug truncate">{{ contract.businessPartner }}</DialogTitle>
              <DialogDescription class="text-xs text-black/45 mt-0.5">{{ contract.category }}</DialogDescription>
              <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                <span class="text-[10px] font-mono text-black/30 bg-black/4 px-1.5 py-0.5 rounded">{{ contract.id }}</span>
                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full border" :class="approvalStatusBadge[contract.approvalStatus]">
                  {{ contract.approvalStatus }}
                </span>
                <span v-if="contract.workflowStatus"
                  class="text-[11px] font-semibold px-2 py-0.5 rounded-full border" :class="workflowStatusBadge[contract.workflowStatus]">
                  {{ contract.workflowStatus }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Stats strip -->
        <div class="grid grid-cols-3 divide-x divide-black/6 border-b border-black/6">
          <div class="px-4 py-3">
            <p class="text-[9px] font-bold text-black/30 uppercase tracking-widest mb-1">Region</p>
            <p class="text-sm font-bold text-black">{{ contract.region }}</p>
          </div>
          <div class="px-4 py-3">
            <p class="text-[9px] font-bold text-black/30 uppercase tracking-widest mb-1">Duration</p>
            <p class="text-sm font-bold text-black tabular-nums">
              {{ Math.round((new Date(contract.endDate).getTime() - new Date(contract.startDate).getTime()) / 86_400_000 / 30) }}mo
            </p>
          </div>
          <div class="px-4 py-3">
            <p class="text-[9px] font-bold text-black/30 uppercase tracking-widest mb-1">Remaining</p>
            <span class="inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-full border"
              :class="daysDisplay(contract.days).cls">
              <component :is="daysDisplay(contract.days).icon" class="w-3 h-3" />
              {{ contract.days < 0 ? 'Expired' : `${contract.days}d` }}
            </span>
          </div>
        </div>

        <!-- Detail rows -->
        <div class="px-5 py-1 divide-y divide-black/4">

          <div class="flex items-center gap-3 py-2.5">
            <Hash class="w-3.5 h-3.5 text-black/25 shrink-0" />
            <div class="flex-1 min-w-0 flex items-baseline justify-between gap-4">
              <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider shrink-0">Item Code</span>
              <span class="text-sm font-mono font-medium text-black">{{ contract.itemCode }}</span>
            </div>
          </div>

          <div class="flex items-center gap-3 py-2.5">
            <Cpu class="w-3.5 h-3.5 text-black/25 shrink-0" />
            <div class="flex-1 min-w-0 flex items-baseline justify-between gap-4">
              <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider shrink-0">Description</span>
              <span class="text-sm font-medium text-black text-right truncate max-w-50">{{ contract.description }}</span>
            </div>
          </div>

          <div class="flex items-center gap-3 py-2.5">
            <Barcode class="w-3.5 h-3.5 text-black/25 shrink-0" />
            <div class="flex-1 min-w-0 flex items-baseline justify-between gap-4">
              <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider shrink-0">Serial No</span>
              <span class="text-sm font-mono font-medium text-black">{{ contract.serialNo }}</span>
            </div>
          </div>

          <div class="flex items-center gap-3 py-2.5">
            <CalendarDays class="w-3.5 h-3.5 text-black/25 shrink-0" />
            <div class="flex-1 min-w-0 flex items-baseline justify-between gap-4">
              <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider shrink-0">Start Date</span>
              <span class="text-sm font-medium text-black tabular-nums">{{ fmtDate(contract.startDate) }}</span>
            </div>
          </div>

          <div class="flex items-center gap-3 py-2.5">
            <CalendarDays class="w-3.5 h-3.5 text-black/25 shrink-0" />
            <div class="flex-1 min-w-0 flex items-baseline justify-between gap-4">
              <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider shrink-0">End Date</span>
              <span class="text-sm font-medium text-black tabular-nums">{{ fmtDate(contract.endDate) }}</span>
            </div>
          </div>

          <div class="flex items-center gap-3 py-2.5">
            <MapPin class="w-3.5 h-3.5 text-black/25 shrink-0" />
            <div class="flex-1 min-w-0 flex items-baseline justify-between gap-4">
              <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider shrink-0">Region</span>
              <span class="text-sm font-medium text-black">{{ contract.region }}</span>
            </div>
          </div>

          <div class="flex items-center gap-3 py-2.5">
            <User class="w-3.5 h-3.5 text-black/25 shrink-0" />
            <div class="flex-1 min-w-0 flex items-center justify-between gap-4">
              <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider shrink-0">Sales Rep</span>
              <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-[9px] font-bold shrink-0 select-none"
                  :style="{ backgroundColor: avatarColor(contract.createdBy) }">
                  {{ initials(contract.createdBy) }}
                </div>
                <span class="text-sm font-medium text-black">{{ contract.createdBy }}</span>
              </div>
            </div>
          </div>

        </div>

        <!-- Contract link + footer -->
        <div class="px-5 py-4 border-t border-black/6 flex items-center justify-between gap-3">
          <a :href="safeHref(contract.contractLink)" target="_blank" rel="noopener noreferrer"
            class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#2E85D8] hover:underline">
            <ExternalLink class="w-3.5 h-3.5" /> View Contract PDF
          </a>
          <Button variant="outline" @click="$emit('update:open', false)"
            class="h-8 px-4 text-sm border-black/15 text-black/60 hover:text-black">
            Close
          </Button>
        </div>

      </template>
    </DialogContent>
  </Dialog>
</template>
