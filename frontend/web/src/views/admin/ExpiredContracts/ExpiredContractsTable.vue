<script setup lang="ts">
import { Search, Eye, AlertTriangle } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue
} from '@/components/ui/select'
import {
  Pagination, PaginationContent, PaginationEllipsis,
  PaginationItem, PaginationNext, PaginationPrevious,
} from '@/components/ui/pagination'
import { workflowStatusBadge, fmtDate, remainingDays } from '@/types/contract'
import type { Contract } from '@/types/contract'

const props = defineProps<{
  contracts:            Contract[]
  total:                number
  loading:              boolean
  searchQuery:          string
  categoryFilter:       string
  regionFilter:         string
  workflowStatusFilter: string
  currentPage:          number
  itemsPerPage:         number
}>()

const emit = defineEmits<{
  (e: 'openDetail', c: Contract): void
  (e: 'update:searchQuery', v: string): void
  (e: 'update:categoryFilter', v: string): void
  (e: 'update:regionFilter', v: string): void
  (e: 'update:workflowStatusFilter', v: string): void
  (e: 'update:currentPage', v: number): void
}>()

const categories = [
  'Service Agreement',
  'Supply Contract',
  'Equipment Maintenance',
  'Equipment Lease',
  'Partnership Agreement'
]

const palette = ['#252578', '#2E85D8', '#2F2F73']
function initials(name: string) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}
function avatarColor(name: string) {
  let h = 0
  for (const c of name) h = (h * 31 + c.charCodeAt(0)) & 0xffff
  return palette[h % palette.length]
}

function overdueDaysLabel(endDate: string) {
  const days = remainingDays(endDate)
  const absDays = Math.abs(days)
  return `${absDays} ${absDays === 1 ? 'day' : 'days'} overdue`
}
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">
    <!-- Section heading -->
    <div class="px-6 pt-5 pb-4 border-b border-black/5">
      <h2 class="text-sm font-semibold text-black">
        Overdue Contracts <span class="text-black/30 font-normal">({{ loading ? 0 : total }})</span>
      </h2>
    </div>

    <!-- Filters + Search -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 px-6 py-4 border-b border-black/5 bg-black/[0.005]">
      <div class="flex flex-wrap items-center gap-3">
        <!-- Category Filter -->
        <div class="w-44">
          <Select
            :model-value="categoryFilter || '__all__'"
            @update:model-value="(v) => emit('update:categoryFilter', (v as string) === '__all__' ? '' : (v as string))"
          >
            <SelectTrigger class="h-9 rounded-md text-sm border-black/10 bg-white text-black/70 focus:ring-[#2E85D8]/15">
              <SelectValue placeholder="All Categories" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="__all__">All Categories</SelectItem>
              <SelectItem v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <!-- Region Filter -->
        <div class="w-40">
          <Select
            :model-value="regionFilter || '__all__'"
            @update:model-value="(v) => emit('update:regionFilter', (v as string) === '__all__' ? '' : (v as string))"
          >
            <SelectTrigger class="h-9 rounded-md text-sm border-black/10 bg-white text-black/70 focus:ring-[#2E85D8]/15">
              <SelectValue placeholder="All Regions" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="__all__">All Regions</SelectItem>
              <SelectItem value="Luzon">Luzon</SelectItem>
              <SelectItem value="Visayas">Visayas</SelectItem>
              <SelectItem value="Mindanao">Mindanao</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <!-- Workflow Status Filter -->
        <div class="w-44">
          <Select
            :model-value="workflowStatusFilter || '__all__'"
            @update:model-value="(v) => emit('update:workflowStatusFilter', (v as string) === '__all__' ? '' : (v as string))"
          >
            <SelectTrigger class="h-9 rounded-md text-sm border-black/10 bg-white text-black/70 focus:ring-[#2E85D8]/15">
              <SelectValue placeholder="All Status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="__all__">All Status</SelectItem>
              <SelectItem value="Notarized PDF">Notarized PDF</SelectItem>
              <SelectItem value="Client Review">Client Review</SelectItem>
              <SelectItem value="SBSI Review">SBSI Review</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <!-- Search query input -->
      <div class="relative w-full lg:w-64">
        <Search class="w-3.5 h-3.5 text-black/30 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
        <input
          :value="searchQuery"
          @input="emit('update:searchQuery', ($event.target as HTMLInputElement).value.trim())"
          type="text"
          placeholder="Search partner, code..."
          class="w-full h-9 rounded-lg border border-black/10 bg-white pl-8.5 pr-3 text-sm placeholder:text-black/25 focus:border-[#2E85D8] focus:outline-none focus:ring-2 focus:ring-[#2E85D8]/15 transition"
        />
      </div>
    </div>

    <!-- Table -->
    <Table>
      <TableHeader class="bg-black/[0.018]">
        <TableRow class="border-b border-black/[0.04] hover:bg-transparent">
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3 pl-6 w-56">Contract</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Category</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Region</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">End Date</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Days Overdue</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Status</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Sales Rep</TableHead>
          <TableHead class="w-12 py-3" />
        </TableRow>
      </TableHeader>

      <TableBody>
        <template v-if="loading">
          <TableRow v-for="i in itemsPerPage" :key="i" class="border-b border-black/4 last:border-0">
            <TableCell class="py-4 pl-6">
              <div class="h-4 w-32 bg-black/5 animate-pulse rounded mb-1.5"></div>
              <div class="h-3 w-16 bg-black/5 animate-pulse rounded"></div>
            </TableCell>
            <TableCell class="py-4">
              <div class="h-4 w-24 bg-black/5 animate-pulse rounded"></div>
            </TableCell>
            <TableCell class="py-4">
              <div class="h-4 w-16 bg-black/5 animate-pulse rounded"></div>
            </TableCell>
            <TableCell class="py-4">
              <div class="h-4 w-20 bg-black/5 animate-pulse rounded mb-1.5"></div>
              <div class="h-3 w-28 bg-black/5 animate-pulse rounded"></div>
            </TableCell>
            <TableCell class="py-4">
              <div class="h-4 w-16 bg-black/5 animate-pulse rounded"></div>
            </TableCell>
            <TableCell class="py-4">
              <div class="h-5 w-20 bg-black/5 animate-pulse rounded-full"></div>
            </TableCell>
            <TableCell class="py-4">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-black/5 animate-pulse shrink-0"></div>
                <div class="h-3 w-24 bg-black/5 animate-pulse rounded"></div>
              </div>
            </TableCell>
            <TableCell class="py-4 pr-4">
              <div class="h-8 w-8 bg-black/5 animate-pulse rounded ml-auto"></div>
            </TableCell>
          </TableRow>
        </template>
        <template v-else>
          <TableRow
            v-for="c in contracts"
            :key="c.id"
            class="border-b border-black/4 last:border-0 transition-colors hover:bg-black/[0.012] cursor-pointer"
            @click="emit('openDetail', c)"
          >
            <!-- Contract ID + Partner -->
            <TableCell class="py-4 pl-6">
              <p class="text-sm font-medium text-black leading-snug">{{ c.businessPartner }}</p>
              <span class="text-[10px] font-mono text-black/35 bg-black/4 px-1.5 py-0.5 rounded mt-0.5 inline-block">{{ c.id }}</span>
            </TableCell>

            <!-- Category -->
            <TableCell class="py-4 text-sm text-black/60">{{ c.category }}</TableCell>

            <!-- Region -->
            <TableCell class="py-4 text-sm text-black/60">{{ c.region }}</TableCell>

            <!-- End Date -->
            <TableCell class="py-4">
              <p class="text-sm font-medium text-black">{{ fmtDate(c.endDate) }}</p>
              <p class="text-xs text-black/35 mt-0.5">from {{ fmtDate(c.startDate) }}</p>
            </TableCell>

            <!-- Days Overdue -->
            <TableCell class="py-4">
              <span class="inline-flex items-center gap-1 text-sm font-medium text-red-500">
                <AlertTriangle class="w-3.5 h-3.5 shrink-0" />
                {{ overdueDaysLabel(c.endDate) }}
              </span>
            </TableCell>

            <!-- Status -->
            <TableCell class="py-4" @click.stop>
              <span class="text-xs font-medium px-2.5 py-0.5 rounded-full border whitespace-nowrap w-fit block"
                :class="c.workflowStatus ? workflowStatusBadge[c.workflowStatus] : 'bg-black/5 text-black/50 border-black/10'">
                {{ c.workflowStatus || '—' }}
              </span>
            </TableCell>

            <!-- Sales Rep -->
            <TableCell class="py-4" @click.stop>
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-[10px] font-bold shrink-0 select-none"
                  :style="{ backgroundColor: avatarColor(c.createdBy) }">
                  {{ initials(c.createdBy) }}
                </div>
                <span class="text-xs font-medium text-black/70 leading-snug">{{ c.createdBy }}</span>
              </div>
            </TableCell>

            <!-- Actions (View details) -->
            <TableCell class="py-4 pr-4 text-right" @click.stop>
              <Button
                variant="ghost"
                size="icon"
                @click="emit('openDetail', c)"
                class="h-8 w-8 text-black/25 hover:text-black hover:bg-black/5"
              >
                <Eye class="w-4 h-4" />
              </Button>
            </TableCell>
          </TableRow>

          <TableRow v-if="contracts.length === 0">
            <TableCell colspan="8" class="text-center py-16">
              <p class="text-sm font-semibold text-black/28">No expired contracts found</p>
              <p class="text-xs text-black/20 mt-1">Try relaxing your filters or search term</p>
            </TableCell>
          </TableRow>
        </template>
      </TableBody>
    </Table>

    <!-- Pagination -->
    <Pagination
      :total="loading ? itemsPerPage : total"
      :sibling-count="1"
      :items-per-page="itemsPerPage"
      :page="currentPage"
      @update:page="emit('update:currentPage', $event)"
    >
      <div class="grid grid-cols-3 items-center px-6 py-4 border-t border-black/5">
        <div class="flex justify-start"><PaginationPrevious /></div>
        <div class="flex justify-center">
          <PaginationContent v-slot="{ items }" class="flex items-center gap-1">
            <template v-for="(item, index) in items">
              <PaginationItem v-if="item.type === 'page'" :key="index" :value="item.value"
                :is-active="item.value === currentPage"
                :class="item.value === currentPage
                  ? 'bg-[#252578] text-white hover:bg-[#2F2F73] hover:text-white border-transparent font-semibold'
                  : 'text-black/50 hover:bg-black/5'">
                {{ item.value }}
              </PaginationItem>
              <PaginationEllipsis v-else :key="'ellipsis-' + index" :index="index" />
            </template>
          </PaginationContent>
        </div>
        <div class="flex justify-end"><PaginationNext /></div>
      </div>
    </Pagination>
  </div>
</template>
