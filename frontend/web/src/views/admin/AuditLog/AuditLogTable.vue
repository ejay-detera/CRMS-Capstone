<script setup lang="ts">
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table'
import {
  Pagination, PaginationContent, PaginationEllipsis,
  PaginationItem, PaginationNext, PaginationPrevious,
} from '@/components/ui/pagination'
import { actionBadge } from '@/types/auditLog'
import { getInitials, avatarColor } from '@/types/user'
import type { LogEntry } from '@/types/auditLog'

const props = defineProps<{
  paginated:   LogEntry[]
  filtered:    LogEntry[]
  currentPage: number
  pageSize:    number
  totalPages:  number
  isLoading?:  boolean
  totalLogs?:  number
}>()

const emit = defineEmits<{ 'update:currentPage': [page: number] }>()

function formatTimestamp(isoStr: string) {
  if (!isoStr) return ''
  const d = new Date(isoStr)
  return d.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  })
}
</script>

<template>
  <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">

    <div class="px-6 pt-5 pb-4 border-b border-black/5">
      <h2 class="text-sm font-semibold text-black">
        All logs <span class="text-black/30 font-normal">({{ totalLogs ?? filtered.length }})</span>
      </h2>
    </div>

    <Table>
      <TableHeader class="bg-black/1.8">
        <TableRow class="border-b border-black/4 hover:bg-transparent">
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider pl-6 py-3 w-52">User</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Email</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Action</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Description</TableHead>
          <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3 pr-6 text-right w-44">Timestamp</TableHead>
        </TableRow>
      </TableHeader>

      <TableBody>
        <!-- Loading Skeletons -->
        <template v-if="isLoading">
          <TableRow v-for="i in pageSize" :key="'skeleton-' + i" class="border-b border-black/4 last:border-0 hover:bg-transparent">
            <TableCell class="pl-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-black/5 shrink-0 animate-pulse"></div>
                <div class="space-y-1.5">
                  <div class="h-3 w-24 bg-black/5 rounded animate-pulse"></div>
                  <div class="h-2 w-12 bg-black/5 rounded animate-pulse"></div>
                </div>
              </div>
            </TableCell>
            <TableCell class="py-4">
              <div class="h-3.5 w-32 bg-black/5 rounded animate-pulse"></div>
            </TableCell>
            <TableCell class="py-4">
              <div class="h-5.5 w-24 bg-black/5 rounded-full animate-pulse"></div>
            </TableCell>
            <TableCell class="py-4">
              <div class="h-3.5 w-48 bg-black/5 rounded animate-pulse"></div>
            </TableCell>
            <TableCell class="py-4 pr-6 text-right">
              <div class="h-3.5 w-24 bg-black/5 rounded ml-auto animate-pulse"></div>
            </TableCell>
          </TableRow>
        </template>

        <!-- No data state -->
        <template v-else-if="paginated.length === 0">
          <TableRow>
            <TableCell colspan="5" class="text-center py-16">
              <p class="text-sm font-medium text-black/30">No log entries found</p>
              <p class="text-xs text-black/20 mt-1">Try adjusting your filters</p>
            </TableCell>
          </TableRow>
        </template>

        <!-- Real Data Rows -->
        <template v-else>
          <TableRow v-for="log in paginated" :key="log.id"
            class="border-b border-black/4 last:border-0 hover:bg-black/1.2 transition-colors">

            <TableCell class="pl-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 select-none"
                  :style="{ backgroundColor: avatarColor((log.user_name || '').charCodeAt(0) % 3) }">
                  {{ getInitials(log.user_name || '') }}
                </div>
                <div>
                  <p class="text-sm font-medium text-black leading-snug">{{ log.user_name }}</p>
                  <p class="text-[11px] text-black/35 mt-0.5">{{ log.role || 'Finance' }}</p>
                </div>
              </div>
            </TableCell>

            <TableCell class="py-4 text-sm text-black/55">{{ log.user_email || 'N/A' }}</TableCell>

            <TableCell class="py-4">
              <span class="text-xs font-medium px-2.5 py-0.5 rounded-full border whitespace-nowrap"
                :class="actionBadge[log.action] || 'bg-black/4 text-black/45 border-black/10'">
                {{ log.action }}
              </span>
            </TableCell>

            <TableCell class="py-4 text-sm text-black/55 max-w-xs truncate" :title="log.description">
              {{ log.description }}
            </TableCell>

            <TableCell class="py-4 pr-6 text-sm text-black/40 text-right whitespace-nowrap tabular-nums">
              {{ formatTimestamp(log.performed_at) }}
            </TableCell>
          </TableRow>
        </template>
      </TableBody>
    </Table>

    <Pagination :total="totalLogs ?? filtered.length" :sibling-count="1"
      :items-per-page="pageSize" :page="currentPage" @update:page="emit('update:currentPage', $event)">
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

