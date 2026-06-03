<script setup lang="ts">
import { Search, MoreHorizontal, Eye, Pencil, AlertTriangle, Clock } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table'
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuItem,
  DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Pagination, PaginationContent, PaginationEllipsis,
  PaginationItem, PaginationNext, PaginationPrevious,
} from '@/components/ui/pagination'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue
} from '@/components/ui/select'
import { approvalStatusBadge, workflowStatusBadge, fmtDate } from '@/types/contract'
import type { Contract, StatusFilter } from '@/types/contract'

type ContractWithDays = Contract & { days: number }

const router = useRouter()

const props = defineProps<{
  paginated:    ContractWithDays[]
  filtered:     ContractWithDays[]
  statusFilter: StatusFilter
  categoryFilter: string
  regionFilter:   string
  searchQuery:  string
  currentPage:  number
  itemsPerPage: number
  loading?:     boolean
}>()

const emit = defineEmits<{
  openDetail:              [c: ContractWithDays]
  'update:statusFilter':   [v: StatusFilter]
  'update:categoryFilter': [v: string]
  'update:regionFilter':   [v: string]
  'update:searchQuery':    [v: string]
  'update:currentPage':    [v: number]
}>()

const statusOptions: { label: string; value: StatusFilter }[] = [
  { label: 'Pending',       value: 'Pending'      },
  { label: 'Rejected',      value: 'Rejected'     },
  { label: 'Notarized',     value: 'Notarized PDF'},
  { label: 'SBSI Review',   value: 'SBSI Review'  },
  { label: 'Client Review', value: 'Client Review'},
]

const categories = [
  'Service Agreement',
  'Supply Contract',
  'Equipment Maintenance',
  'Equipment Lease',
  'Partnership Agreement'
]

function daysLabel(days: number) {
  if (days < 0)   return { text: 'Expired',       cls: 'text-red-500' }
  if (days <= 30) return { text: `${days}d left`, cls: 'text-amber-500' }
  return                 { text: `${days}d left`, cls: 'text-black/45' }
}
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">

    <!-- Section heading -->
    <div class="px-6 pt-5 pb-4 border-b border-black/5">
      <h2 class="text-sm font-semibold text-black">
        My Contracts <span class="text-black/30 font-normal">({{ loading ? 0 : filtered.length }})</span>
      </h2>
    </div>

    <!-- Filters + search -->
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

        <!-- Status Filter -->
        <div class="w-44">
          <Select
            :model-value="statusFilter || '__all__'"
            @update:model-value="(v) => emit('update:statusFilter', (v as string) === '__all__' ? '' : (v as StatusFilter))"
          >
            <SelectTrigger class="h-9 rounded-md text-sm border-black/10 bg-white text-black/70 focus:ring-[#2E85D8]/15">
              <SelectValue placeholder="All Status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="__all__">All Status</SelectItem>
              <SelectItem v-for="opt in statusOptions.filter(o => o.value !== '')" :key="opt.value" :value="opt.value">{{ opt.label }}</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <!-- Search query input -->
      <div class="relative w-full lg:w-64">
        <Search class="w-3.5 h-3.5 text-black/30 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
        <input :value="searchQuery"
          @input="emit('update:searchQuery', ($event.target as HTMLInputElement).value.trim())"
          type="text" placeholder="Search contracts..."
          maxlength="100"
          class="w-full h-9 rounded-lg border border-black/10 bg-white pl-8.5 pr-3 text-sm placeholder:text-black/25 focus:border-[#2E85D8] focus:outline-none focus:ring-2 focus:ring-[#2E85D8]/15 transition" />
      </div>
    </div>

    <!-- Table -->
    <Table>
      <TableHeader class="bg-black/1.8">
        <TableRow class="border-b border-black/4 hover:bg-transparent">
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3 pl-6 w-56">Contract</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Category</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Region</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">End Date</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Remaining</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Status</TableHead>
          <TableHead class="w-12 py-3" />
        </TableRow>
      </TableHeader>

      <TableBody>
        <template v-if="loading">
          <TableRow v-for="i in itemsPerPage" :key="i" class="border-b border-black/4 last:border-0">
            <!-- Contract ID + Partner -->
            <TableCell class="py-4 pl-6">
              <div class="h-4 w-32 bg-black/5 animate-pulse rounded mb-1.5"></div>
              <div class="h-3 w-16 bg-black/5 animate-pulse rounded"></div>
            </TableCell>

            <!-- Category -->
            <TableCell class="py-4">
              <div class="h-4 w-24 bg-black/5 animate-pulse rounded"></div>
            </TableCell>

            <!-- Region -->
            <TableCell class="py-4">
              <div class="h-4 w-16 bg-black/5 animate-pulse rounded"></div>
            </TableCell>

            <!-- End Date -->
            <TableCell class="py-4">
              <div class="h-4 w-20 bg-black/5 animate-pulse rounded mb-1.5"></div>
              <div class="h-3 w-28 bg-black/5 animate-pulse rounded"></div>
            </TableCell>

            <!-- Remaining Days -->
            <TableCell class="py-4">
              <div class="h-4 w-16 bg-black/5 animate-pulse rounded"></div>
            </TableCell>

            <!-- Status -->
            <TableCell class="py-4">
              <div class="flex flex-col gap-1">
                <div class="h-5 w-20 bg-black/5 animate-pulse rounded-full"></div>
                <div class="h-5 w-20 bg-black/5 animate-pulse rounded-full"></div>
              </div>
            </TableCell>

            <!-- Actions -->
            <TableCell class="py-4 pr-4">
              <div class="h-8 w-8 bg-black/5 animate-pulse rounded ml-auto"></div>
            </TableCell>
          </TableRow>
        </template>
        <template v-else>
          <TableRow v-for="c in paginated" :key="c.id"
            class="border-b border-black/4 last:border-0 transition-colors hover:bg-black/1.2 cursor-pointer"
            @click="emit('openDetail', c)">

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

            <!-- Remaining Days -->
            <TableCell class="py-4">
              <span class="inline-flex items-center gap-1 text-sm font-medium" :class="daysLabel(c.days).cls">
                <AlertTriangle v-if="c.days < 0"        class="w-3.5 h-3.5 shrink-0" />
                <Clock         v-else-if="c.days <= 15" class="w-3.5 h-3.5 shrink-0" />
                {{ daysLabel(c.days).text }}
              </span>
            </TableCell>

            <!-- Status -->
            <TableCell class="py-4" @click.stop>
              <div class="flex flex-col gap-1">
                <span class="text-xs font-medium px-2.5 py-0.5 rounded-full border whitespace-nowrap w-fit"
                  :class="approvalStatusBadge[c.approvalStatus]">
                  {{ c.approvalStatus }}
                </span>
                <span v-if="c.workflowStatus"
                  class="text-xs font-medium px-2.5 py-0.5 rounded-full border whitespace-nowrap w-fit"
                  :class="workflowStatusBadge[c.workflowStatus]">
                  {{ c.workflowStatus }}
                </span>
              </div>
            </TableCell>

            <!-- Actions -->
            <TableCell class="py-4 pr-4" @click.stop>
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="ghost" size="icon"
                    class="h-8 w-8 text-black/25 hover:text-black hover:bg-black/5 data-[state=open]:bg-black/5 data-[state=open]:text-black">
                    <MoreHorizontal class="w-4 h-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-44">
                  <DropdownMenuLabel class="text-xs font-semibold text-black/38 pb-1">Actions</DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem @click="emit('openDetail', c)" class="gap-2.5 text-sm cursor-pointer">
                    <Eye class="w-3.5 h-3.5 text-black/40" /> View details
                  </DropdownMenuItem>
                  <DropdownMenuItem @click="router.push(`/sales/contracts/${c.id}?edit=1`)" class="gap-2.5 text-sm cursor-pointer">
                    <Pencil class="w-3.5 h-3.5 text-black/40" /> Edit contract
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </TableCell>
          </TableRow>

          <TableRow v-if="paginated.length === 0">
            <TableCell colspan="7" class="text-center py-16">
              <p class="text-sm font-semibold text-black/28">No contracts found</p>
              <p class="text-xs text-black/20 mt-1">Try a different filter or search term</p>
            </TableCell>
          </TableRow>
        </template>
      </TableBody>
    </Table>

    <!-- Pagination -->
    <Pagination :total="loading ? itemsPerPage : filtered.length" :sibling-count="1" :items-per-page="itemsPerPage"
      :page="currentPage" @update:page="emit('update:currentPage', $event)">
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
              <PaginationEllipsis v-else :key="item.type" :index="index" />
            </template>
          </PaginationContent>
        </div>
        <div class="flex justify-end"><PaginationNext /></div>
      </div>
    </Pagination>

  </div>
</template>
