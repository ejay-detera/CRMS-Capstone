<script setup lang="ts">
import { useRouter } from 'vue-router'
import { Clock, ArrowRight } from 'lucide-vue-next'

const router = useRouter()

const contracts = [
  { id: 'CNT-2024-089', partner: 'Bio-Tech Logistics',    category: 'Supply Contract',      daysLeft: 5  },
  { id: 'CNT-2024-112', partner: 'Stellar Lab Equipment', category: 'Equip. Maintenance',   daysLeft: 10 },
  { id: 'CNT-2024-134', partner: 'BioGenesis Research',   category: 'Partnership Agreement', daysLeft: 13 },
  { id: 'CNT-2024-201', partner: 'MedTech Solutions',     category: 'Service Agreement',    daysLeft: 18 },
  { id: 'CNT-2024-256', partner: 'Global Pharma Inc.',    category: 'Equipment Lease',      daysLeft: 26 },
]

function cls(days: number) {
  if (days <= 7)  return { dot: 'bg-red-500',   badge: 'bg-red-50 text-red-600 border-red-200',     bar: 'bg-red-500'   }
  if (days <= 14) return { dot: 'bg-amber-500', badge: 'bg-amber-50 text-amber-700 border-amber-200', bar: 'bg-amber-500' }
  return               { dot: 'bg-[#2E85D8]', badge: 'bg-[#2E85D8]/8 text-[#2E85D8] border-[#2E85D8]/20', bar: 'bg-[#2E85D8]' }
}

const urgentCount = contracts.filter(c => c.daysLeft <= 14).length
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden h-full">
    <div class="px-6 pt-5 pb-4 border-b border-black/5 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <h3 class="text-sm font-semibold text-black">Expiring Soon</h3>
        <span
          v-if="urgentCount > 0"
          class="px-1.5 py-0.5 rounded-full bg-red-50 text-red-600 text-[10px] font-bold border border-red-200"
        >{{ urgentCount }} urgent</span>
      </div>
      <button
        @click="router.push('/manager/contracts')"
        class="flex items-center gap-1 text-xs font-semibold text-[#2E85D8] hover:text-[#252578] transition-colors"
      >
        View all <ArrowRight class="w-3.5 h-3.5" />
      </button>
    </div>

    <div class="divide-y divide-black/[0.04]">
      <div
        v-for="c in contracts"
        :key="c.id"
        class="px-6 py-3.5 hover:bg-black/[0.012] transition-colors cursor-pointer"
        @click="router.push('/manager/contracts')"
      >
        <div class="flex items-start gap-3">
          <div class="w-2 h-2 rounded-full shrink-0 mt-1.5" :class="cls(c.daysLeft).dot" />
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <p class="text-sm font-medium text-black truncate">{{ c.partner }}</p>
                <p class="text-[11px] text-black/35 mt-0.5">{{ c.category }}</p>
              </div>
              <span
                class="flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full border shrink-0"
                :class="cls(c.daysLeft).badge"
              >
                <Clock class="w-3 h-3" />
                {{ c.daysLeft }}d
              </span>
            </div>
            <!-- Progress bar -->
            <div class="mt-2 h-1 rounded-full bg-black/6 overflow-hidden">
              <div
                class="h-full rounded-full transition-all"
                :class="cls(c.daysLeft).bar"
                :style="{ width: `${Math.min(100, Math.round((30 - c.daysLeft) / 30 * 100))}%` }"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
