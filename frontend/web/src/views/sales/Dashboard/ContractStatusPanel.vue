<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { Plus, FileText, ClipboardList, AlertTriangle } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import type { Contract } from '@/types/contract'

const props = defineProps<{
  contracts: (Contract & { days: number })[]
}>()

const router = useRouter()

const expiringSoon = computed(() =>
  props.contracts.filter(c => c.days >= 0 && c.days <= 30).slice(0, 4)
)
</script>

<template>
  <div class="space-y-4">

    <!-- Expiring Soon (only shown when relevant) -->
    <div v-if="expiringSoon.length > 0" class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">
      <div class="px-5 pt-4 pb-3 border-b border-black/5 flex items-center gap-2">
        <AlertTriangle class="w-3.5 h-3.5 text-black/40 shrink-0" />
        <h2 class="text-sm font-semibold text-black">Expiring Soon</h2>
      </div>
      <div class="divide-y divide-black/4">
        <div v-for="c in expiringSoon" :key="c.id" class="px-5 py-3 flex items-center justify-between gap-3">
          <div class="min-w-0">
            <p class="text-xs font-medium text-black truncate">{{ c.businessPartner }}</p>
            <p class="text-[10px] text-black/35 mt-0.5">{{ c.id }}</p>
          </div>
          <span class="text-xs font-semibold text-black/50 shrink-0 tabular-nums">{{ c.days }}d left</span>
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
