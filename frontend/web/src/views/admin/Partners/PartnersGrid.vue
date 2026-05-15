<script setup lang="ts">
import { Building2, Truck } from 'lucide-vue-next'
import type { Partner, TabKey } from '@/types/partner'

defineProps<{ partners: Partner[]; activeTab: TabKey }>()
const emit = defineEmits<{ openDetail: [p: Partner] }>()
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
    <button
      v-for="partner in partners"
      :key="partner.id"
      @click="emit('openDetail', partner)"
      class="text-left bg-white rounded-xl border border-black/8 shadow-sm p-5 hover:border-[#2E85D8]/40 hover:shadow-md transition-all duration-200 group"
    >
      <div class="flex items-start justify-between mb-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 bg-[#252578]/8 text-[#252578]">
          <component :is="activeTab === 'partners' ? Building2 : Truck" class="w-5 h-5" />
        </div>
        <span class="text-xs font-medium px-2.5 py-0.5 rounded-full border"
          :class="partner.status === 'Active'
            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
            : 'bg-black/4 text-black/35 border-black/8'">
          {{ partner.status }}
        </span>
      </div>
      <p class="text-sm font-semibold text-black leading-snug group-hover:text-[#252578] transition-colors">{{ partner.name }}</p>
      <p class="text-xs text-black/40 mt-0.5 mb-4">{{ partner.industry }}</p>
      <div class="space-y-1.5 border-t border-black/5 pt-3">
        <div class="flex items-center justify-between text-xs">
          <span class="text-black/40">Region</span>
          <span class="font-medium text-black">{{ partner.region }}</span>
        </div>
        <div class="flex items-center justify-between text-xs">
          <span class="text-black/40">Contracts</span>
          <span class="font-semibold" :class="partner.contracts > 0 ? 'text-[#2E85D8]' : 'text-black/35'">
            {{ partner.contracts }} active
          </span>
        </div>
        <div class="flex items-center justify-between text-xs">
          <span class="text-black/40">Total Value</span>
          <span class="font-medium text-black">{{ partner.totalValue }}</span>
        </div>
      </div>
    </button>

    <div v-if="partners.length === 0" class="col-span-full py-16 text-center">
      <p class="text-sm font-medium text-black/40">No results found.</p>
      <p class="text-xs text-black/25 mt-1">Try adjusting your search or region filter.</p>
    </div>
  </div>
</template>
