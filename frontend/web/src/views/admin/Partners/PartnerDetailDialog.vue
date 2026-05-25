<script setup lang="ts">
import { Building2, Truck, Phone, Mail, User, MapPin } from 'lucide-vue-next'
import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/components/ui/dialog'
import type { Partner, TabKey } from '@/types/partner'

defineProps<{ open: boolean; partner: Partner | null; activeTab: TabKey }>()
defineEmits<{ 'update:open': [v: boolean] }>()
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="max-w-sm p-0 overflow-hidden gap-0" @pointer-down-outside="$emit('update:open', false)">
      <template v-if="partner">

        <!-- ── Header ─────────────────────────────────────────────── -->
        <div class="px-5 pt-5 pb-4 border-b border-black/6">
          <div class="flex items-start gap-3.5">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white shrink-0 bg-[#252578]">
              <component :is="activeTab === 'partners' ? Building2 : Truck" class="w-5 h-5" />
            </div>
            <div class="flex-1 min-w-0 pr-6">
              <DialogTitle class="text-sm font-bold text-black leading-snug truncate">{{ partner.name }}</DialogTitle>
              <DialogDescription class="text-xs text-black/45 mt-0.5 truncate">{{ partner.industry }}</DialogDescription>
              <div class="flex items-center gap-2 mt-1.5">
                <span class="text-[10px] font-mono text-black/30 bg-black/4 px-1.5 py-0.5 rounded">{{ partner.id }}</span>
                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full border"
                  :class="partner.status === 'Active'
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                    : 'bg-black/4 text-black/40 border-black/8'">
                  {{ partner.status }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Stats row ──────────────────────────────────────────── -->
        <div class="grid grid-cols-3 divide-x divide-black/6 border-b border-black/6">
          <div class="px-4 py-3">
            <p class="text-[9px] font-bold text-black/30 uppercase tracking-widest mb-1">Contracts</p>
            <p class="text-sm font-bold tabular-nums" :class="partner.contracts > 0 ? 'text-[#2E85D8]' : 'text-black/25'">
              {{ partner.contracts }}
              <span class="text-xs font-medium ml-0.5">active</span>
            </p>
          </div>
          <div class="px-4 py-3">
            <p class="text-[9px] font-bold text-black/30 uppercase tracking-widest mb-1">Total Value</p>
            <p class="text-sm font-bold text-black tabular-nums">{{ partner.totalValue }}</p>
          </div>
          <div class="px-4 py-3">
            <p class="text-[9px] font-bold text-black/30 uppercase tracking-widest mb-1">Region</p>
            <p class="text-sm font-bold text-black">{{ partner.region }}</p>
          </div>
        </div>

        <!-- ── Contact info ───────────────────────────────────────── -->
        <div class="px-5 py-4 space-y-0 divide-y divide-black/4">
          <div class="flex items-center gap-3 py-2.5">
            <User class="w-3.5 h-3.5 text-black/25 shrink-0" />
            <div class="flex-1 min-w-0 flex items-baseline justify-between gap-4">
              <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider shrink-0">Contact</span>
              <span class="text-sm font-medium text-black truncate text-right">{{ partner.contactPerson }}</span>
            </div>
          </div>

          <div class="flex items-center gap-3 py-2.5">
            <Mail class="w-3.5 h-3.5 text-black/25 shrink-0" />
            <div class="flex-1 min-w-0 flex items-baseline justify-between gap-4">
              <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider shrink-0">Email</span>
              <span class="text-sm text-[#2E85D8] truncate text-right">{{ partner.email }}</span>
            </div>
          </div>

          <div class="flex items-center gap-3 py-2.5">
            <Phone class="w-3.5 h-3.5 text-black/25 shrink-0" />
            <div class="flex-1 min-w-0 flex items-baseline justify-between gap-4">
              <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider shrink-0">Phone</span>
              <span class="text-sm font-medium text-black tabular-nums">{{ partner.phone }}</span>
            </div>
          </div>

          <div class="flex items-start gap-3 py-2.5">
            <MapPin class="w-3.5 h-3.5 text-black/25 shrink-0 mt-0.5" />
            <div class="flex-1 min-w-0 flex items-start justify-between gap-4">
              <span class="text-[10px] font-semibold text-black/35 uppercase tracking-wider shrink-0 mt-0.5">Address</span>
              <span class="text-sm font-medium text-black text-right leading-snug">{{ partner.address }}</span>
            </div>
          </div>
        </div>

      </template>
    </DialogContent>
  </Dialog>
</template>
