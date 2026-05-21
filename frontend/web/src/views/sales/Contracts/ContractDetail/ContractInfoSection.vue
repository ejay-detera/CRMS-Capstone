<script setup lang="ts">
import { Hash, Cpu, Barcode, Tag, Building2, MapPin, CalendarDays, User, Clock, AlertTriangle } from 'lucide-vue-next'
import { fmtDate } from '@/types/contract'
import type { StoredContract } from '@/composables/useContractStore'

const props = defineProps<{
  contract: StoredContract
  days: number
}>()

const palette = ['#252578', '#2E85D8', '#2F2F73']

function initials(name: string) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function avatarColor(name: string) {
  let h = 0
  for (const c of name) h = (h * 31 + c.charCodeAt(0)) & 0xffff
  return palette[h % palette.length]
}

function durationMonths(start: string, end: string) {
  return Math.round(
    (new Date(end).getTime() - new Date(start).getTime()) / 86_400_000 / 30
  )
}

function daysChip(days: number) {
  if (days < 0)   return { cls: 'bg-red-50 text-red-600 border-red-200',     icon: AlertTriangle, text: 'Expired' }
  if (days <= 30) return { cls: 'bg-amber-50 text-amber-600 border-amber-200', icon: Clock,         text: `${days}d` }
  return                 { cls: 'bg-black/4 text-black/55 border-black/10',   icon: Clock,         text: `${days}d` }
}
</script>

<template>
  <div class="bg-white rounded-xl border border-black/8 shadow-sm overflow-hidden">

    <div class="px-6 py-4 border-b border-black/6">
      <h2 class="text-xs font-semibold text-black/40 uppercase tracking-widest">Contract Details</h2>
    </div>

    <!-- Stats strip -->
    <div class="grid grid-cols-3 divide-x divide-black/6 border-b border-black/6">
      <div class="px-5 py-4">
        <p class="text-[9px] font-bold text-black/30 uppercase tracking-widest mb-1">Region</p>
        <p class="text-sm font-bold text-black">{{ contract.region }}</p>
      </div>
      <div class="px-5 py-4">
        <p class="text-[9px] font-bold text-black/30 uppercase tracking-widest mb-1">Duration</p>
        <p class="text-sm font-bold text-black tabular-nums">
          {{ durationMonths(contract.startDate, contract.endDate) }}mo
        </p>
      </div>
      <div class="px-5 py-4">
        <p class="text-[9px] font-bold text-black/30 uppercase tracking-widest mb-1">Remaining</p>
        <span class="inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-full border"
          :class="daysChip(days).cls">
          <component :is="daysChip(days).icon" class="w-3 h-3" />
          {{ daysChip(days).text }}
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
          <span class="text-sm font-medium text-black text-right truncate max-w-xs">{{ contract.description }}</span>
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
        <Tag class="w-3.5 h-3.5 text-black/25 shrink-0" />
        <div class="flex-1 min-w-0 flex items-baseline justify-between gap-4">
          <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider shrink-0">Category</span>
          <span class="text-sm font-medium text-black">{{ contract.category }}</span>
        </div>
      </div>

      <div class="flex items-center gap-3 py-2.5">
        <Building2 class="w-3.5 h-3.5 text-black/25 shrink-0" />
        <div class="flex-1 min-w-0 flex items-baseline justify-between gap-4">
          <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider shrink-0">Business Partner</span>
          <span class="text-sm font-medium text-black">{{ contract.businessPartner }}</span>
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
        <User class="w-3.5 h-3.5 text-black/25 shrink-0" />
        <div class="flex-1 min-w-0 flex items-center justify-between gap-4">
          <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider shrink-0">Created By</span>
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
  </div>
</template>
