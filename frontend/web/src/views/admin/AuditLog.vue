<script setup lang="ts">
import { ref, computed } from 'vue'
import { Search, Upload, ChevronDown } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table'
import {
  Pagination, PaginationContent, PaginationEllipsis,
  PaginationItem, PaginationNext, PaginationPrevious,
} from '@/components/ui/pagination'
import * as XLSX from 'xlsx'

// ── Types ────────────────────────────────────────────────────────────
type ActionType =
  | 'Contract Created'  | 'Contract Updated' | 'Contract Approved'
  | 'Contract Deleted'  | 'User Created'     | 'User Updated'
  | 'User Deleted'      | 'Partner Added'    | 'Partner Updated'
  | 'Role Updated'      | 'Settings Changed' | 'Login'

interface LogEntry {
  id:        string
  user:      string
  email:     string
  role:      string
  action:    ActionType
  target:    string
  timestamp: string
}

// ── Data ─────────────────────────────────────────────────────────────
const logs = ref<LogEntry[]>([
  { id: 'AL-001', user: 'John Doe',      email: 'john.doe@sbsi.com',      role: 'Admin',   action: 'Contract Approved',  target: 'CNT-2023-001 — Medical Supplies Co.',   timestamp: '2025-05-14  09:42 AM' },
  { id: 'AL-002', user: 'Alice Smith',   email: 'alice.smith@sbsi.com',   role: 'Manager', action: 'Contract Updated',   target: 'CNT-2023-042 — Bio-Tech Logistics',     timestamp: '2025-05-14  08:15 AM' },
  { id: 'AL-003', user: 'John Doe',      email: 'john.doe@sbsi.com',      role: 'Admin',   action: 'User Created',       target: 'Emma Wilson (Sales)',                   timestamp: '2025-05-13  04:30 PM' },
  { id: 'AL-004', user: 'Maria Santos',  email: 'maria.santos@sbsi.com',  role: 'Sales',   action: 'Login',              target: 'Session started from 192.168.1.12',     timestamp: '2025-05-13  09:00 AM' },
  { id: 'AL-005', user: 'Alice Smith',   email: 'alice.smith@sbsi.com',   role: 'Manager', action: 'Contract Created',   target: 'CNT-2023-134 — BioGenesis Research',    timestamp: '2025-05-12  03:18 PM' },
  { id: 'AL-006', user: 'John Doe',      email: 'john.doe@sbsi.com',      role: 'Admin',   action: 'Role Updated',       target: 'Manager — removed contracts.delete',    timestamp: '2025-05-12  11:05 AM' },
  { id: 'AL-007', user: 'John Doe',      email: 'john.doe@sbsi.com',      role: 'Admin',   action: 'User Deleted',       target: 'Bob Johnson (Manager)',                 timestamp: '2025-05-11  02:45 PM' },
  { id: 'AL-008', user: 'Alice Smith',   email: 'alice.smith@sbsi.com',   role: 'Manager', action: 'Partner Added',      target: 'SP-009 — PhilHealth Supplies',          timestamp: '2025-05-11  10:22 AM' },
  { id: 'AL-009', user: 'Emma Wilson',   email: 'emma.wilson@sbsi.com',   role: 'Sales',   action: 'Contract Updated',   target: 'CNT-2023-089 — Global Pharma Inc.',     timestamp: '2025-05-10  04:00 PM' },
  { id: 'AL-010', user: 'John Doe',      email: 'john.doe@sbsi.com',      role: 'Admin',   action: 'Settings Changed',   target: 'System config — session timeout updated', timestamp: '2025-05-10  09:30 AM' },
  { id: 'AL-011', user: 'Maria Santos',  email: 'maria.santos@sbsi.com',  role: 'Sales',   action: 'Login',              target: 'Session started from 192.168.1.08',     timestamp: '2025-05-09  08:55 AM' },
  { id: 'AL-012', user: 'Alice Smith',   email: 'alice.smith@sbsi.com',   role: 'Manager', action: 'Contract Approved',  target: 'CNT-2023-112 — Stellar Lab Equipment',  timestamp: '2025-05-08  03:10 PM' },
  { id: 'AL-013', user: 'John Doe',      email: 'john.doe@sbsi.com',      role: 'Admin',   action: 'Partner Updated',    target: 'BP-003 — Cebu Pacific Air',             timestamp: '2025-05-08  01:40 PM' },
  { id: 'AL-014', user: 'Emma Wilson',   email: 'emma.wilson@sbsi.com',   role: 'Sales',   action: 'Contract Created',   target: 'CNT-2023-155 — Wellcare Diagnostics',   timestamp: '2025-05-07  11:25 AM' },
  { id: 'AL-015', user: 'Alice Smith',   email: 'alice.smith@sbsi.com',   role: 'Manager', action: 'User Updated',       target: 'Maria Santos — role changed to Sales',  timestamp: '2025-05-07  09:50 AM' },
  { id: 'AL-016', user: 'John Doe',      email: 'john.doe@sbsi.com',      role: 'Admin',   action: 'Contract Deleted',   target: 'CNT-2022-088 — Expired draft removed',  timestamp: '2025-05-06  04:20 PM' },
  { id: 'AL-017', user: 'Maria Santos',  email: 'maria.santos@sbsi.com',  role: 'Sales',   action: 'Login',              target: 'Session started from 192.168.1.08',     timestamp: '2025-05-06  08:45 AM' },
  { id: 'AL-018', user: 'Alice Smith',   email: 'alice.smith@sbsi.com',   role: 'Manager', action: 'Partner Added',      target: 'BP-008 — Philippine Airlines',          timestamp: '2025-05-05  02:35 PM' },
  { id: 'AL-019', user: 'John Doe',      email: 'john.doe@sbsi.com',      role: 'Admin',   action: 'Role Updated',       target: 'Sales — added partners.view permission', timestamp: '2025-05-05  10:10 AM' },
  { id: 'AL-020', user: 'Emma Wilson',   email: 'emma.wilson@sbsi.com',   role: 'Sales',   action: 'Contract Updated',   target: 'CNT-2023-042 — Status → Draft Client',  timestamp: '2025-05-04  03:55 PM' },
])

// ── Filters ──────────────────────────────────────────────────────────
const searchQuery   = ref('')
const actionFilter  = ref<ActionType | 'All'>('All')
const dateFilter    = ref('')

const actionOptions: Array<ActionType | 'All'> = [
  'All', 'Contract Created', 'Contract Updated', 'Contract Approved',
  'Contract Deleted', 'User Created', 'User Updated', 'User Deleted',
  'Partner Added', 'Partner Updated', 'Role Updated', 'Settings Changed', 'Login',
]

// ── Filtered + paginated ─────────────────────────────────────────────
const filtered = computed(() => {
  let list = logs.value
  if (actionFilter.value !== 'All')
    list = list.filter(l => l.action === actionFilter.value)
  if (dateFilter.value)
    list = list.filter(l => l.timestamp.startsWith(dateFilter.value))
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(l =>
      l.user.toLowerCase().includes(q) ||
      l.email.toLowerCase().includes(q) ||
      l.target.toLowerCase().includes(q) ||
      l.action.toLowerCase().includes(q)
    )
  }
  return list
})

const PAGE_SIZE    = 10
const currentPage  = ref(1)
const totalPages   = computed(() => Math.max(1, Math.ceil(filtered.value.length / PAGE_SIZE)))
const paginated    = computed(() => {
  const start = (currentPage.value - 1) * PAGE_SIZE
  return filtered.value.slice(start, start + PAGE_SIZE)
})

function resetPage() { currentPage.value = 1 }

// ── Action badge styles ──────────────────────────────────────────────
const actionBadge: Record<ActionType, string> = {
  'Contract Created':  'bg-emerald-50 text-emerald-700 border-emerald-200',
  'Contract Updated':  'bg-[#2E85D8]/8 text-[#2E85D8] border-[#2E85D8]/20',
  'Contract Approved': 'bg-[#252578]/8 text-[#252578] border-[#252578]/20',
  'Contract Deleted':  'bg-red-50 text-red-600 border-red-200',
  'User Created':      'bg-emerald-50 text-emerald-700 border-emerald-200',
  'User Updated':      'bg-[#2E85D8]/8 text-[#2E85D8] border-[#2E85D8]/20',
  'User Deleted':      'bg-red-50 text-red-600 border-red-200',
  'Partner Added':     'bg-emerald-50 text-emerald-700 border-emerald-200',
  'Partner Updated':   'bg-[#2E85D8]/8 text-[#2E85D8] border-[#2E85D8]/20',
  'Role Updated':      'bg-amber-50 text-amber-700 border-amber-200',
  'Settings Changed':  'bg-amber-50 text-amber-700 border-amber-200',
  'Login':             'bg-black/4 text-black/45 border-black/10',
}

// ── Avatar ───────────────────────────────────────────────────────────
const palette = ['#252578', '#2E85D8', '#2F2F73']
function getInitials(name: string) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}
function avatarColor(name: string) {
  const idx = name.charCodeAt(0) % palette.length
  return palette[idx]
}

// ── Export ───────────────────────────────────────────────────────────
function exportXLSX() {
  const rows = filtered.value.map(l => ({
    'Log ID':    l.id,
    'User':      l.user,
    'Email':     l.email,
    'Role':      l.role,
    'Action':    l.action,
    'Target':    l.target,
    'Timestamp': l.timestamp,
  }))
  const ws = XLSX.utils.json_to_sheet(rows)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Audit Log')
  XLSX.writeFile(wb, 'audit-log.xlsx')
}
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- ── Header ───────────────────────────────────────────────────── -->
    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-xl font-semibold text-black">Audit Log</h1>
        <p class="text-sm text-black/40 mt-0.5">Monitor all system activity and user actions across SBSI.</p>
      </div>
      <Button @click="exportXLSX" variant="outline"
        class="h-9 gap-2 text-sm font-medium border-black/15 text-black/65 hover:text-black">
        <Upload class="w-4 h-4" />
        Export XLSX
      </Button>
    </div>

    <!-- ── Filters ──────────────────────────────────────────────────── -->
    <div class="bg-white rounded-lg border border-black/8 shadow-sm px-6 py-4">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <!-- Action filter -->
        <div class="space-y-1.5">
          <label class="text-[11px] font-semibold text-black/40 uppercase tracking-wider">Action</label>
          <div class="relative">
            <select
              v-model="actionFilter"
              @change="resetPage"
              class="w-full appearance-none rounded-lg border border-black/10 bg-white px-3 py-2 pr-8 text-sm text-black focus:border-[#2E85D8] focus:outline-none transition-colors cursor-pointer"
            >
              <option v-for="opt in actionOptions" :key="opt" :value="opt">
                {{ opt === 'All' ? 'All Actions' : opt }}
              </option>
            </select>
            <ChevronDown class="w-3.5 h-3.5 text-black/35 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>

        <!-- Date filter -->
        <div class="space-y-1.5">
          <label class="text-[11px] font-semibold text-black/40 uppercase tracking-wider">Date</label>
          <input
            v-model="dateFilter"
            @change="resetPage"
            type="date"
            class="w-full rounded-lg border border-black/10 bg-white px-3 py-2 text-sm text-black focus:border-[#2E85D8] focus:outline-none transition-colors cursor-pointer"
          />
        </div>

        <!-- Search -->
        <div class="space-y-1.5">
          <label class="text-[11px] font-semibold text-black/40 uppercase tracking-wider">Search</label>
          <div class="relative">
            <Search class="w-3.5 h-3.5 text-black/35 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchQuery"
              @input="resetPage"
              type="text"
              placeholder="Search logs..."
              class="w-full rounded-lg border border-black/10 bg-white py-2 pl-8 pr-3 text-sm text-black placeholder:text-black/35 focus:border-[#2E85D8] focus:outline-none transition-colors"
            />
          </div>
        </div>

      </div>
    </div>

    <!-- ── Table ─────────────────────────────────────────────────────── -->
    <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">

      <!-- Section heading -->
      <div class="px-6 pt-5 pb-4 border-b border-black/5 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-black">
          All logs
          <span class="text-black/30 font-normal">({{ filtered.length }})</span>
        </h2>
      </div>

      <Table>
        <TableHeader class="bg-black/[0.018]">
          <TableRow class="border-b border-black/[0.04] hover:bg-transparent">
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider pl-6 py-3 w-56">User</TableHead>
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Email</TableHead>
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Action</TableHead>
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Target</TableHead>
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3 pr-6 text-right">Timestamp</TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <!-- Empty state -->
          <TableRow v-if="paginated.length === 0">
            <TableCell colspan="5" class="text-center py-16">
              <p class="text-sm font-medium text-black/30">No log entries found</p>
              <p class="text-xs text-black/20 mt-1">Try adjusting your filters</p>
            </TableCell>
          </TableRow>

          <TableRow
            v-for="log in paginated"
            :key="log.id"
            class="border-b border-black/[0.04] last:border-0 hover:bg-black/[0.012] transition-colors"
          >
            <!-- User -->
            <TableCell class="pl-6 py-4">
              <div class="flex items-center gap-3">
                <div
                  class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 select-none"
                  :style="{ backgroundColor: avatarColor(log.user) }"
                >
                  {{ getInitials(log.user) }}
                </div>
                <div>
                  <p class="text-sm font-medium text-black leading-snug">{{ log.user }}</p>
                  <p class="text-[11px] text-black/35 mt-0.5">{{ log.role }}</p>
                </div>
              </div>
            </TableCell>

            <!-- Email -->
            <TableCell class="py-4 text-sm text-black/55">{{ log.email }}</TableCell>

            <!-- Action -->
            <TableCell class="py-4">
              <span
                class="text-xs font-medium px-2.5 py-0.5 rounded-full border whitespace-nowrap"
                :class="actionBadge[log.action]"
              >
                {{ log.action }}
              </span>
            </TableCell>

            <!-- Target -->
            <TableCell class="py-4 text-sm text-black/55 max-w-xs truncate">{{ log.target }}</TableCell>

            <!-- Timestamp -->
            <TableCell class="py-4 pr-6 text-sm text-black/40 text-right whitespace-nowrap tabular-nums">
              {{ log.timestamp }}
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>

      <!-- ── Pagination ────────────────────────────────────────────── -->
      <Pagination
        v-if="totalPages > 1"
        :total="filtered.length"
        :sibling-count="1"
        :items-per-page="PAGE_SIZE"
        v-model:page="currentPage"
      >
        <div class="grid grid-cols-3 items-center px-6 py-4 border-t border-black/5">
          <div class="flex justify-start">
            <PaginationPrevious />
          </div>
          <div class="flex justify-center">
            <PaginationContent v-slot="{ items }" class="flex items-center gap-1">
              <template v-for="(item, index) in items">
                <PaginationItem
                  v-if="item.type === 'page'"
                  :key="index"
                  :value="item.value"
                  :is-active="item.value === currentPage"
                  :class="item.value === currentPage
                    ? 'bg-[#252578] text-white hover:bg-[#2F2F73] hover:text-white border-transparent font-semibold'
                    : 'text-black/50 hover:bg-black/5'"
                >
                  {{ item.value }}
                </PaginationItem>
                <PaginationEllipsis v-else :key="item.type" :index="index" />
              </template>
            </PaginationContent>
          </div>
          <div class="flex justify-end">
            <PaginationNext />
          </div>
        </div>
      </Pagination>

    </div>
  </div>
</template>
