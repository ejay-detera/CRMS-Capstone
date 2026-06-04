<script setup lang="ts">
import { computed } from 'vue'
import { FileText, CheckCircle2, Clock, XCircle } from 'lucide-vue-next'
import type { Contract } from '@/types/contract'

const props = defineProps<{
  contracts: (Contract & { days: number })[]
}>()

const stats = computed(() => {
  const approvedContracts = props.contracts.filter(c => c.approvalStatus === 'Approved')
  const total = approvedContracts.length
  const active = approvedContracts.filter(c => c.days > 30).length
  const expiring = approvedContracts.filter(c => c.days >= 0 && c.days <= 30).length
  const expired = approvedContracts.filter(c => c.days < 0).length

  return [
    {
      label: 'Total Contracts', value: String(total), change: '+12.5%', positive: true,
      icon: FileText,    iconBg: 'bg-[#252578]/8',  iconColor: 'text-[#252578]',
      sub: 'All time',
    },
    {
      label: 'Active', value: String(active), change: '+8.3%', positive: true,
      icon: CheckCircle2, iconBg: 'bg-emerald-50', iconColor: 'text-emerald-600',
      sub: 'Currently running',
    },
    {
      label: 'Expiring Soon', value: String(expiring), change: '+22.2%', positive: false,
      icon: Clock,     iconBg: 'bg-amber-50',     iconColor: 'text-amber-600',
      sub: 'Within 30 days',
    },
    {
      label: 'Expired', value: String(expired), change: '-5.0%', positive: true,
      icon: XCircle,   iconBg: 'bg-red-50',       iconColor: 'text-red-500',
      sub: 'Past end date',
    },
  ]
})
</script>

<template>
  <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
    <div
      v-for="s in stats"
      :key="s.label"
      class="bg-white rounded-lg border border-black/8 px-5 py-5 shadow-sm flex items-start gap-4"
    >
      <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" :class="s.iconBg">
        <component :is="s.icon" class="w-5 h-5" :class="s.iconColor" />
      </div>
      <div class="min-w-0">
        <p class="text-xs font-medium text-black/40 mb-1 truncate">{{ s.label }}</p>
        <div class="flex items-end gap-2">
          <span class="text-2xl font-semibold text-black tabular-nums">{{ s.value }}</span>
          <span
            class="text-[11px] font-medium px-1.5 py-0.5 rounded mb-0.5 shrink-0"
            :class="s.positive ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'"
          >{{ s.change }}</span>
        </div>
        <p class="text-[11px] text-black/30 mt-0.5">{{ s.sub }}</p>
      </div>
    </div>
  </div>
</template>
