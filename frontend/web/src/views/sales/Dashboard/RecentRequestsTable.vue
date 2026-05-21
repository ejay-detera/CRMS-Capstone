<script setup lang="ts">
import { ArrowRight } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import { requestStatusBadge, fmtReqDate } from '@/types/contractRequest'
import type { ContractRequest } from '@/types/contractRequest'

defineProps<{ requests: ContractRequest[] }>()

const router = useRouter()
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">

    <!-- Header -->
    <div class="px-6 pt-5 pb-4 border-b border-black/5 flex items-center justify-between">
      <div>
        <h2 class="text-sm font-semibold text-black">Recent Contract Requests</h2>
        <p class="text-xs text-black/40 mt-0.5">Your latest {{ requests.length }} submissions</p>
      </div>
      <button @click="router.push('/sales/contract-requests')"
        class="inline-flex items-center gap-1 text-xs font-semibold text-[#2E85D8] hover:underline">
        View all <ArrowRight class="w-3 h-3" />
      </button>
    </div>

    <!-- List -->
    <div class="divide-y divide-black/4">
      <div v-for="r in requests" :key="r.id"
        class="px-6 py-3.5 flex items-center justify-between gap-4 hover:bg-black/1.5 transition-colors cursor-default">

        <!-- Partner + meta -->
        <div class="min-w-0 flex-1">
          <p class="text-sm font-medium text-black truncate leading-snug">{{ r.businessPartner }}</p>
          <div class="flex items-center gap-2 mt-0.5">
            <span class="text-[10px] font-mono text-black/30 bg-black/4 px-1.5 py-0.5 rounded">{{ r.id }}</span>
            <span class="text-[11px] text-black/40">{{ r.category }}</span>
          </div>
        </div>

        <!-- Status + date -->
        <div class="shrink-0 text-right">
          <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full border whitespace-nowrap"
            :class="requestStatusBadge[r.status]">
            {{ r.status }}
          </span>
          <p class="text-[10px] text-black/35 mt-1 tabular-nums">{{ fmtReqDate(r.requestDate) }}</p>
        </div>

      </div>

      <div v-if="requests.length === 0" class="px-6 py-12 text-center">
        <p class="text-sm text-black/30">No requests submitted yet.</p>
      </div>
    </div>

  </div>
</template>
