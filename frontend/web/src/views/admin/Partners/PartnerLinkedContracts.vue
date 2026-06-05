<script setup lang="ts">
import { type LinkedContract, engagementBadge } from '@/types/partner'

defineProps<{
  contracts: LinkedContract[]
  vendorType: 'partners' | 'suppliers'
}>()

const emit = defineEmits<{
  (e: 'open-associate'): void
  (e: 'detach', associationId: string): void
}>()
</script>

<template>
  <div class="mt-8 border-t border-black/[0.06] pt-6 font-poppins">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <h3 class="text-base font-semibold text-[#2F2F73]">Linked Contracts</h3>
        <span class="px-2 py-0.5 text-xs font-medium bg-[#2E85D8]/10 text-[#2E85D8] rounded-full">
          {{ contracts.length }}
        </span>
      </div>
      <button
        type="button"
        @click="emit('open-associate')"
        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-[#2E85D8] hover:bg-[#252578] rounded transition-colors"
      >
        Link Contract
      </button>
    </div>

    <!-- Empty State -->
    <div
      v-if="contracts.length === 0"
      class="flex flex-col items-center justify-center p-6 border border-dashed border-black/[0.08] rounded-lg bg-black/[0.005]"
    >
      <p class="text-sm text-black/40 mb-3">No contracts linked to this {{ vendorType === 'partners' ? 'business partner' : 'supplier' }} yet.</p>
      <button
        type="button"
        @click="emit('open-associate')"
        class="text-xs font-medium text-[#2E85D8] hover:text-[#252578] hover:underline"
      >
        Associate a contract now
      </button>
    </div>

    <!-- Table -->
    <div v-else class="overflow-x-auto border border-black/[0.06] rounded-lg modal-scrollbar">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-black/[0.018] border-b border-black/[0.04]">
            <th class="px-4 py-2 text-xs font-semibold text-black/50 w-[120px]">Contract ID</th>
            <th class="px-4 py-2 text-xs font-semibold text-black/50">Description</th>
            <th class="px-4 py-2 text-xs font-semibold text-black/50 w-[180px]">Period</th>
            <th class="px-4 py-2 text-xs font-semibold text-black/50 w-[100px]">Status</th>
            <th class="px-4 py-2 text-xs font-semibold text-black/50 w-[80px] text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-black/[0.04]">
          <tr v-for="c in contracts" :key="c.associationId" class="hover:bg-black/[0.005] transition-colors">
            <td class="px-4 py-3 text-xs font-mono font-medium text-[#2F2F73]">
              {{ c.contractId }}
            </td>
            <td class="px-4 py-3 text-xs text-black/70 font-medium">
              {{ c.description }}
            </td>
            <td class="px-4 py-3 text-xs text-black/50">
              {{ c.startDate }} to {{ c.endDate }}
            </td>
            <td class="px-4 py-3 text-xs">
              <span
                class="px-2 py-0.5 text-[10px] font-semibold border rounded-full uppercase tracking-wider"
                :class="engagementBadge[c.engagementStatus]"
              >
                {{ c.engagementStatus }}
              </span>
            </td>
            <td class="px-4 py-3 text-xs text-right">
              <button
                type="button"
                @click="emit('detach', c.associationId)"
                class="p-1 text-black/30 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                title="Detach contract"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
