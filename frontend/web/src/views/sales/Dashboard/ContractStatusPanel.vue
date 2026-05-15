<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { Plus, FileText, ClipboardList, AlertTriangle } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import type { Contract } from '@/types/contract'
import type { ContractRequest } from '@/types/contractRequest'

const props = defineProps<{
  contracts: (Contract & { days: number })[]
  requests:  ContractRequest[]
}>()

const router = useRouter()

type StatusKey = 'Notarized PDF' | 'Client Review' | 'SBSI Review'

const statusConfig: Record<StatusKey, { label: string; bar: string; text: string; bg: string }> = {
  'Notarized PDF': { label: 'Notarized PDF', bar: 'bg-[#252578]',   text: 'text-[#252578]',   bg: 'bg-[#252578]/8'  },
  'Client Review': { label: 'Client Review', bar: 'bg-[#2E85D8]',   text: 'text-[#2E85D8]',   bg: 'bg-[#2E85D8]/8'  },
  'SBSI Review':   { label: 'SBSI Review',   bar: 'bg-[#2F2F73]',   text: 'text-[#2F2F73]',   bg: 'bg-[#2F2F73]/8'  },
}

const breakdown = computed(() => {
  const total = props.contracts.length || 1
  return (Object.keys(statusConfig) as StatusKey[]).map(key => {
    const count = props.contracts.filter(c => c.status === key).length
    return {
      ...statusConfig[key],
      count,
      pct: Math.round((count / total) * 100),
    }
  })
})

const expiringSoon = computed(() =>
  props.contracts.filter(c => c.days >= 0 && c.days <= 30).slice(0, 3)
)

const requestSummary = computed(() => [
  { label: 'Pending',      value: props.requests.filter(r => r.status === 'Pending').length,      dot: 'bg-amber-400' },
  { label: 'Under Review', value: props.requests.filter(r => r.status === 'Under Review').length,  dot: 'bg-[#2E85D8]' },
  { label: 'Approved',     value: props.requests.filter(r => r.status === 'Approved').length,      dot: 'bg-emerald-500' },
  { label: 'Rejected',     value: props.requests.filter(r => r.status === 'Rejected').length,      dot: 'bg-red-400' },
])
</script>

<template>
  <div class="space-y-4">

    <!-- Contract Status Breakdown -->
    <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">
      <div class="px-5 pt-5 pb-4 border-b border-black/5">
        <h2 class="text-sm font-semibold text-black">Contract Status</h2>
        <p class="text-xs text-black/40 mt-0.5">Breakdown by review stage</p>
      </div>
      <div class="px-5 py-4 space-y-3.5">
        <div v-for="row in breakdown" :key="row.label" class="space-y-1.5">
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-black/60">{{ row.label }}</span>
            <div class="flex items-center gap-2">
              <span class="text-xs font-semibold" :class="row.text">{{ row.count }}</span>
              <span class="text-[10px] text-black/30 tabular-nums w-7 text-right">{{ row.pct }}%</span>
            </div>
          </div>
          <div class="h-1.5 bg-black/5 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500" :class="row.bar" :style="{ width: `${row.pct}%` }" />
          </div>
        </div>
      </div>
    </div>

    <!-- Request Summary -->
    <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">
      <div class="px-5 pt-5 pb-4 border-b border-black/5">
        <h2 class="text-sm font-semibold text-black">Request Summary</h2>
        <p class="text-xs text-black/40 mt-0.5">All-time request status</p>
      </div>
      <div class="px-5 py-4 grid grid-cols-2 gap-3">
        <div v-for="item in requestSummary" :key="item.label"
          class="flex items-center gap-2.5 bg-black/2 rounded-lg px-3 py-2.5">
          <span class="w-2 h-2 rounded-full shrink-0" :class="item.dot" />
          <div>
            <p class="text-xs font-medium text-black/50 leading-none">{{ item.label }}</p>
            <p class="text-base font-semibold text-black tabular-nums mt-0.5 leading-none">{{ item.value }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Expiring Soon -->
    <div v-if="expiringSoon.length > 0" class="bg-white rounded-lg border border-amber-100 shadow-sm overflow-hidden">
      <div class="px-5 pt-4 pb-3 border-b border-amber-100 flex items-center gap-2">
        <AlertTriangle class="w-3.5 h-3.5 text-amber-500 shrink-0" />
        <h2 class="text-sm font-semibold text-amber-700">Expiring Soon</h2>
      </div>
      <div class="divide-y divide-amber-50">
        <div v-for="c in expiringSoon" :key="c.id" class="px-5 py-3 flex items-center justify-between gap-3">
          <div class="min-w-0">
            <p class="text-xs font-medium text-black truncate">{{ c.businessPartner }}</p>
            <p class="text-[10px] text-black/35 mt-0.5">{{ c.id }}</p>
          </div>
          <span class="text-xs font-semibold text-amber-600 shrink-0 tabular-nums">{{ c.days }}d left</span>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">
      <div class="px-5 pt-5 pb-4 border-b border-black/5">
        <h2 class="text-sm font-semibold text-black">Quick Actions</h2>
      </div>
      <div class="px-5 py-4 space-y-2.5">
        <Button @click="router.push('/sales/contract-requests')"
          class="w-full h-9 justify-start gap-2.5 text-sm bg-[#252578] hover:bg-[#2F2F73] text-white">
          <Plus class="w-4 h-4" /> New Contract Request
        </Button>
        <Button @click="router.push('/sales/contracts')" variant="outline"
          class="w-full h-9 justify-start gap-2.5 text-sm border-black/12 text-black/65 hover:text-black">
          <FileText class="w-4 h-4" /> View My Contracts
        </Button>
        <Button @click="router.push('/sales/contract-requests')" variant="outline"
          class="w-full h-9 justify-start gap-2.5 text-sm border-black/12 text-black/65 hover:text-black">
          <ClipboardList class="w-4 h-4" /> My Requests
        </Button>
      </div>
    </div>

  </div>
</template>
