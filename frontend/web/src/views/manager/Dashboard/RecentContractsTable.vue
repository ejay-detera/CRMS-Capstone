<script setup lang="ts">
import { useRouter } from 'vue-router'
import { ArrowRight } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'

const router = useRouter()

type Status = 'Notarized PDF' | 'Client Review' | 'SBSI Review'

const contracts = [
  { id: 'CNT-2025-001', partner: 'Philippine National Bank', category: 'Service Agreement',    status: 'Notarized PDF' as Status, endDate: 'Dec 15, 2025' },
  { id: 'CNT-2025-002', partner: 'Meralco',                  category: 'Equip. Maintenance',   status: 'Client Review' as Status, endDate: 'Nov 30, 2025' },
  { id: 'CNT-2025-003', partner: 'SM Prime Holdings',        category: 'Equipment Lease',      status: 'SBSI Review'   as Status, endDate: 'Jan 10, 2026' },
  { id: 'CNT-2025-004', partner: 'Jollibee Foods Corp.',     category: 'Supply Contract',      status: 'Notarized PDF' as Status, endDate: 'Mar 22, 2026' },
  { id: 'CNT-2025-005', partner: 'Ayala Corporation',        category: 'Partnership Agreement', status: 'Client Review' as Status, endDate: 'Feb 14, 2026' },
]

const statusBadge: Record<Status, string> = {
  'Notarized PDF': 'bg-black/5 text-black/55 border-black/10',
  'Client Review': 'bg-emerald-50 text-emerald-700 border-emerald-200',
  'SBSI Review':   'bg-red-50 text-red-600 border-red-200',
}
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden h-full">
    <div class="px-6 pt-5 pb-4 border-b border-black/5 flex items-center justify-between">
      <h3 class="text-sm font-semibold text-black">
        Recent Contracts
        <span class="text-black/30 font-normal">({{ contracts.length }})</span>
      </h3>
      <button
        @click="router.push('/manager/contracts')"
        class="flex items-center gap-1 text-xs font-semibold text-[#2E85D8] hover:text-[#252578] transition-colors"
      >
        View all <ArrowRight class="w-3.5 h-3.5" />
      </button>
    </div>

    <Table>
      <TableHeader class="bg-black/1.8">
        <TableRow class="border-b border-black/4 hover:bg-transparent">
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider pl-6 py-3">Contract</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Category</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Status</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">End Date</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        <TableRow
          v-for="c in contracts"
          :key="c.id"
          class="border-b border-black/4 last:border-0 hover:bg-black/1.2 transition-colors cursor-pointer"
          @click="router.push('/manager/contracts')"
        >
          <TableCell class="pl-6 py-3.5">
            <p class="text-sm font-medium text-black leading-snug">{{ c.partner }}</p>
            <p class="text-[11px] font-mono text-black/35 mt-0.5">{{ c.id }}</p>
          </TableCell>
          <TableCell class="py-3.5 text-sm text-black/50">{{ c.category }}</TableCell>
          <TableCell class="py-3.5">
            <Badge variant="outline" class="text-xs font-medium rounded-full px-2.5 py-0.5" :class="statusBadge[c.status]">
              {{ c.status }}
            </Badge>
          </TableCell>
          <TableCell class="py-3.5 text-sm text-black/50">{{ c.endDate }}</TableCell>
        </TableRow>
      </TableBody>
    </Table>
  </div>
</template>
