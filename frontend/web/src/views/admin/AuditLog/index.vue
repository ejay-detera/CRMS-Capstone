<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Upload } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import * as XLSX from 'xlsx'
import AuditLogFilters from './AuditLogFilters.vue'
import AuditLogTable   from './AuditLogTable.vue'
import type { LogEntry, ActionType } from '@/types/auditLog'

const logs = ref<LogEntry[]>([
  { id: 'AL-001', user: 'John Doe',     email: 'john.doe@sbsi.com',     role: 'Admin',   action: 'Contract Approved', target: 'CNT-2023-001 — Medical Supplies Co.',    timestamp: '2025-05-14 09:42 AM' },
  { id: 'AL-002', user: 'Alice Smith',  email: 'alice.smith@sbsi.com',  role: 'Manager', action: 'Contract Updated',  target: 'CNT-2023-042 — Bio-Tech Logistics',      timestamp: '2025-05-14 08:15 AM' },
  { id: 'AL-003', user: 'John Doe',     email: 'john.doe@sbsi.com',     role: 'Admin',   action: 'User Created',      target: 'Emma Wilson (Sales)',                    timestamp: '2025-05-13 04:30 PM' },
  { id: 'AL-004', user: 'Maria Santos', email: 'maria.santos@sbsi.com', role: 'Sales',   action: 'Login',             target: 'Session started from 192.168.1.12',      timestamp: '2025-05-13 09:00 AM' },
  { id: 'AL-005', user: 'Alice Smith',  email: 'alice.smith@sbsi.com',  role: 'Manager', action: 'Contract Created',  target: 'CNT-2023-134 — BioGenesis Research',     timestamp: '2025-05-12 03:18 PM' },
  { id: 'AL-006', user: 'John Doe',     email: 'john.doe@sbsi.com',     role: 'Admin',   action: 'Role Updated',      target: 'Manager — removed contracts.delete',     timestamp: '2025-05-12 11:05 AM' },
  { id: 'AL-007', user: 'John Doe',     email: 'john.doe@sbsi.com',     role: 'Admin',   action: 'User Deleted',      target: 'Bob Johnson (Manager)',                  timestamp: '2025-05-11 02:45 PM' },
  { id: 'AL-008', user: 'Alice Smith',  email: 'alice.smith@sbsi.com',  role: 'Manager', action: 'Partner Added',     target: 'SP-009 — PhilHealth Supplies',           timestamp: '2025-05-11 10:22 AM' },
  { id: 'AL-009', user: 'Emma Wilson',  email: 'emma.wilson@sbsi.com',  role: 'Sales',   action: 'Contract Updated',  target: 'CNT-2023-089 — Global Pharma Inc.',      timestamp: '2025-05-10 04:00 PM' },
  { id: 'AL-010', user: 'John Doe',     email: 'john.doe@sbsi.com',     role: 'Admin',   action: 'Settings Changed',  target: 'System config — session timeout updated',timestamp: '2025-05-10 09:30 AM' },
  { id: 'AL-011', user: 'Maria Santos', email: 'maria.santos@sbsi.com', role: 'Sales',   action: 'Login',             target: 'Session started from 192.168.1.08',      timestamp: '2025-05-09 08:55 AM' },
  { id: 'AL-012', user: 'Alice Smith',  email: 'alice.smith@sbsi.com',  role: 'Manager', action: 'Contract Approved', target: 'CNT-2023-112 — Stellar Lab Equipment',   timestamp: '2025-05-08 03:10 PM' },
  { id: 'AL-013', user: 'John Doe',     email: 'john.doe@sbsi.com',     role: 'Admin',   action: 'Partner Updated',   target: 'BP-003 — Cebu Pacific Air',              timestamp: '2025-05-08 01:40 PM' },
  { id: 'AL-014', user: 'Emma Wilson',  email: 'emma.wilson@sbsi.com',  role: 'Sales',   action: 'Contract Created',  target: 'CNT-2023-155 — Wellcare Diagnostics',    timestamp: '2025-05-07 11:25 AM' },
  { id: 'AL-015', user: 'Alice Smith',  email: 'alice.smith@sbsi.com',  role: 'Manager', action: 'User Updated',      target: 'Maria Santos — role changed to Sales',   timestamp: '2025-05-07 09:50 AM' },
  { id: 'AL-016', user: 'John Doe',     email: 'john.doe@sbsi.com',     role: 'Admin',   action: 'Contract Deleted',  target: 'CNT-2022-088 — Expired draft removed',   timestamp: '2025-05-06 04:20 PM' },
  { id: 'AL-017', user: 'Maria Santos', email: 'maria.santos@sbsi.com', role: 'Sales',   action: 'Login',             target: 'Session started from 192.168.1.08',      timestamp: '2025-05-06 08:45 AM' },
  { id: 'AL-018', user: 'Alice Smith',  email: 'alice.smith@sbsi.com',  role: 'Manager', action: 'Partner Added',     target: 'BP-008 — Philippine Airlines',           timestamp: '2025-05-05 02:35 PM' },
  { id: 'AL-019', user: 'John Doe',     email: 'john.doe@sbsi.com',     role: 'Admin',   action: 'Role Updated',      target: 'Sales — added partners.view permission', timestamp: '2025-05-05 10:10 AM' },
  { id: 'AL-020', user: 'Emma Wilson',  email: 'emma.wilson@sbsi.com',  role: 'Sales',   action: 'Contract Updated',  target: 'CNT-2023-042 — Status → Draft Client',   timestamp: '2025-05-04 03:55 PM' },
])

const searchQuery  = ref('')
const actionFilter = ref<ActionType | 'All'>('All')
const dateFilter   = ref('')
const currentPage  = ref(1)
const PAGE_SIZE    = 10

const filtered = computed(() => {
  let list = logs.value
  if (actionFilter.value !== 'All') list = list.filter(l => l.action === actionFilter.value)
  if (dateFilter.value)             list = list.filter(l => l.timestamp.startsWith(dateFilter.value))
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(l =>
      l.user.toLowerCase().includes(q) || l.email.toLowerCase().includes(q) ||
      l.target.toLowerCase().includes(q) || l.action.toLowerCase().includes(q)
    )
  }
  return list
})

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / PAGE_SIZE)))
const paginated  = computed(() => filtered.value.slice((currentPage.value - 1) * PAGE_SIZE, currentPage.value * PAGE_SIZE))

watch([actionFilter, dateFilter, searchQuery], () => { currentPage.value = 1 })

function exportXLSX() {
  const rows = filtered.value.map(l => ({
    'Log ID': l.id, 'User': l.user, 'Email': l.email, 'Role': l.role,
    'Action': l.action, 'Target': l.target, 'Timestamp': l.timestamp,
  }))
  const ws = XLSX.utils.json_to_sheet(rows)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Audit Log')
  XLSX.writeFile(wb, 'audit-log.xlsx')
}
</script>

<template>
  <div class="p-8 space-y-6">

    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-xl font-semibold text-black">Audit Log</h1>
        <p class="text-sm text-black/40 mt-0.5">Monitor all system activity and user actions across SBSI.</p>
      </div>
      <Button @click="exportXLSX" variant="outline"
        class="h-9 gap-2 text-sm font-medium border-black/15 text-black/65 hover:text-black">
        <Upload class="w-4 h-4" /> Export XLSX
      </Button>
    </div>

    <AuditLogFilters
      v-model:action-filter="actionFilter"
      v-model:date-filter="dateFilter"
      v-model:search-query="searchQuery"
    />

    <AuditLogTable
      :paginated="paginated"
      :filtered="filtered"
      :current-page="currentPage"
      :page-size="PAGE_SIZE"
      :total-pages="totalPages"
      @update:current-page="currentPage = $event"
    />

  </div>
</template>
