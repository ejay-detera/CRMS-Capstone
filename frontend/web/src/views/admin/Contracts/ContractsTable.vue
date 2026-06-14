<script setup lang="ts">
import { Search, MoreHorizontal, Eye, Pencil, Trash2 } from 'lucide-vue-next'
import { useAuth } from '@/composables/useAuth'
import { Button } from '@/components/ui/button'
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table'
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuItem,
  DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue
} from '@/components/ui/select'
import TablePagination from '@/components/shared/TablePagination.vue'
import { approvalStatusBadge, workflowStatusBadge, fmtDate, deriveLifecycleStatus, formatRemainingTime } from '@/types/contract'
import type { Contract, FilterTab, StatusFilter } from '@/types/contract'
import ContractLifecycleBadge from '@/components/shared/ContractLifecycleBadge.vue'

type ContractWithDays = Contract & { days: number }

const props = defineProps<{
  paginated:      ContractWithDays[]
  filtered:       ContractWithDays[]
  activeFilter:   FilterTab
  searchQuery:    string
  categoryFilter: string
  regionFilter:   string
  statusFilter:   StatusFilter
  currentPage:    number
  itemsPerPage:   number
  loading?:       boolean
  totalItems?:    number
}>()

const emit = defineEmits<{
  openDetail:              [c: ContractWithDays]
  openEdit:                [c: ContractWithDays]
  delete:                  [id: string]
  'update:activeFilter':   [v: FilterTab]
  'update:searchQuery':    [v: string]
  'update:categoryFilter': [v: string]
  'update:regionFilter':   [v: string]
  'update:statusFilter':   [v: StatusFilter]
  'update:currentPage':    [v: number]
}>()

const filterTabs: { label: string; value: FilterTab }[] = [
  { label: 'All',           value: 'all'      },
  { label: 'Active',        value: 'active'   },
  { label: 'Expiring Soon', value: 'expiring' },
  { label: 'Expired',       value: 'expired'  },
]

const categories = [
  'Service Agreement',
  'Supply Contract',
  'Equipment Maintenance',
  'Equipment Lease',
  'Partnership Agreement'
]

const statusOptions: { label: string; value: StatusFilter }[] = [
  { label: 'Pending',       value: 'Pending'      },
  { label: 'Approved',      value: 'Approved'     },
  { label: 'Rejected',      value: 'Rejected'     },
  { label: 'Notarized PDF', value: 'Notarized PDF'},
  { label: 'SBSI Review',   value: 'SBSI Review'  },
  { label: 'Client Review', value: 'Client Review'},
]


const { hasPermission } = useAuth()

const palette = ['#252578', '#2E85D8', '#2F2F73']
function initials(name: string) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}
function avatarColor(name: string) {
  let h = 0
  for (const c of name) h = (h * 31 + c.charCodeAt(0)) & 0xffff
  return palette[h % palette.length]
}
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">

    <div class="px-6 pt-5 pb-4 border-b border-black/5">
      <h2 class="text-sm font-semibold text-black">
        All Contracts <span class="text-black/30 font-normal">({{ loading ? 0 : (totalItems !== undefined ? totalItems : filtered.length) }})</span>
      </h2>
    </div>

    <!-- Filters + search -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 px-6 py-4 border-b border-black/5 bg-black/[0.005]">
      <div class="flex flex-wrap items-center gap-3">
        <!-- Tabs -->
        <div class="flex items-center gap-0.5 bg-black/4 rounded-md p-1">
          <button v-for="tab in filterTabs" :key="tab.value"
            @click="emit('update:activeFilter', tab.value)"
            class="flex items-center gap-1.5 px-4 py-1.5 text-sm rounded transition-all font-medium"
            :class="activeFilter === tab.value
              ? 'bg-white text-black shadow-sm'
              : 'text-black/40 hover:text-black/60'">
            {{ tab.label }}
          </button>
        </div>

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
              <SelectItem v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</SelectItem>
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
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Remaining Time</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Contract State</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Status</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Sales Rep</TableHead>
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

            <!-- Remaining Time -->
            <TableCell class="py-4">
              <div class="h-4 w-24 bg-black/5 animate-pulse rounded"></div>
            </TableCell>

            <!-- Contract State -->
            <TableCell class="py-4">
              <div class="h-5 w-24 bg-black/5 animate-pulse rounded-full"></div>
            </TableCell>

            <!-- Status -->
            <TableCell class="py-4">
              <div class="flex flex-col gap-1">
                <div class="h-5 w-20 bg-black/5 animate-pulse rounded-full"></div>
                <div class="h-5 w-20 bg-black/5 animate-pulse rounded-full"></div>
              </div>
            </TableCell>

            <!-- Sales Rep -->
            <TableCell class="py-4">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-black/5 animate-pulse shrink-0"></div>
                <div class="h-3 w-24 bg-black/5 animate-pulse rounded"></div>
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

            <!-- Remaining Time -->
            <TableCell class="py-4 text-sm text-black/60">{{ formatRemainingTime(c.endDate) }}</TableCell>

            <!-- Contract State -->
            <TableCell class="py-4">
              <ContractLifecycleBadge :status="deriveLifecycleStatus(c.days)" />
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
                  <DropdownMenuItem @click="emit('openEdit', c)" class="gap-2.5 text-sm cursor-pointer">
                    <Pencil class="w-3.5 h-3.5 text-black/40" /> Edit contract
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem v-if="hasPermission('cms.contracts.delete')" @click="emit('delete', c.id)"
                    class="gap-2.5 text-sm cursor-pointer text-red-600 focus:text-red-600 focus:bg-red-50">
                    <Trash2 class="w-3.5 h-3.5" /> Delete
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </TableCell>
          </TableRow>

          <TableRow v-if="paginated.length === 0">
            <TableCell colspan="9" class="text-center py-16">
              <p class="text-sm font-semibold text-black/28">No contracts found</p>
              <p class="text-xs text-black/20 mt-1">Try a different filter or search term</p>
            </TableCell>
          </TableRow>
        </template>
      </TableBody>
    </Table>

    <!-- Pagination -->
    <div class="flex justify-center px-6 py-4 border-t border-black/5">
      <TablePagination :current-page="currentPage" :total-items="loading ? itemsPerPage : filtered.length"
        :items-per-page="itemsPerPage" @update:current-page="emit('update:currentPage', $event)" />
    </div>

  </div>
</template>
