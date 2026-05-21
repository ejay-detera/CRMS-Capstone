<script setup lang="ts">
import { Building2, Truck, MoreHorizontal, Eye, Trash2 } from 'lucide-vue-next'
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
import type { Partner, TabKey } from '@/types/partner'

const props = defineProps<{
  paginated:       Partner[]
  filtered:        Partner[]
  activeTab:       TabKey
  selectedIds:     string[]
  allPageSelected: boolean
  currentPage:     number
  itemsPerPage:    number
}>()

const emit = defineEmits<{
  openDetail:      [p: Partner]
  openDelete:      [p: Partner]
  toggleRow:       [id: string]
  toggleSelectAll: []
  'update:currentPage': [page: number]
}>()
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">

    <div class="px-6 pt-5 pb-4 border-b border-black/5">
      <h2 class="text-sm font-semibold text-black">
        {{ activeTab === 'partners' ? 'Business Partners' : 'Suppliers' }}
        <span class="text-black/30 font-normal">({{ filtered.length }})</span>
      </h2>
    </div>

    <Table>
      <TableHeader class="bg-black/1.8">
        <TableRow class="border-b border-black/4 hover:bg-transparent">
          <TableHead class="w-12 pl-6 py-3">
            <input type="checkbox" :checked="allPageSelected" @change="emit('toggleSelectAll')"
              class="w-4 h-4 rounded border-black/20 accent-[#252578] cursor-pointer" />
          </TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Name</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Industry</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Region</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Contracts</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Total Value</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Status</TableHead>
          <TableHead class="w-14 py-3" />
        </TableRow>
      </TableHeader>

      <TableBody>
        <TableRow
          v-for="partner in paginated"
          :key="partner.id"
          class="border-b border-black/4 last:border-0 transition-colors"
          :class="selectedIds.includes(partner.id) ? 'bg-[#252578]/2.5' : 'hover:bg-black/1.2'"
        >
          <TableCell class="pl-6 py-4">
            <input type="checkbox" :checked="selectedIds.includes(partner.id)"
              @change="emit('toggleRow', partner.id)"
              class="w-4 h-4 rounded border-black/20 accent-[#252578] cursor-pointer" />
          </TableCell>
          <TableCell class="py-4">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 bg-[#252578]/8 text-[#252578]">
                <component :is="activeTab === 'partners' ? Building2 : Truck" class="w-4 h-4" />
              </div>
              <div>
                <p class="text-sm font-medium text-black leading-snug">{{ partner.name }}</p>
                <p class="text-xs text-black/35 mt-0.5">{{ partner.id }}</p>
              </div>
            </div>
          </TableCell>
          <TableCell class="py-4 text-sm text-black/60">{{ partner.industry }}</TableCell>
          <TableCell class="py-4 text-sm text-black/60">{{ partner.region }}</TableCell>
          <TableCell class="py-4">
            <span class="text-sm font-semibold" :class="partner.contracts > 0 ? 'text-[#2E85D8]' : 'text-black/30'">
              {{ partner.contracts }} active
            </span>
          </TableCell>
          <TableCell class="py-4 text-sm font-medium text-black">{{ partner.totalValue }}</TableCell>
          <TableCell class="py-4">
            <span class="text-xs font-medium px-2.5 py-0.5 rounded-full border"
              :class="partner.status === 'Active'
                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                : 'bg-black/4 text-black/35 border-black/8'">
              {{ partner.status }}
            </span>
          </TableCell>
          <TableCell class="py-4 pr-4">
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
                <DropdownMenuItem @click="emit('openDetail', partner)" class="gap-2.5 text-sm cursor-pointer">
                  <Eye class="w-3.5 h-3.5 text-black/40" /> View details
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem @click="emit('openDelete', partner)"
                  class="gap-2.5 text-sm cursor-pointer text-red-600 focus:text-red-600 focus:bg-red-50">
                  <Trash2 class="w-3.5 h-3.5" /> Delete
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </TableCell>
        </TableRow>

        <TableRow v-if="paginated.length === 0">
          <TableCell colspan="8" class="text-center py-16">
            <p class="text-sm font-semibold text-black/28">No records found</p>
            <p class="text-xs text-black/20 mt-1">Try a different search or region filter</p>
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>

    <Pagination :total="filtered.length" :sibling-count="1" :items-per-page="itemsPerPage"
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
