<script setup lang="ts">
import { computed, ref } from 'vue'
import { ArrowRight } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table'

// ── Stat cards ─────────────────────────────────────────────────────
const statCards = [
  { label: 'Total Contracts',  value: '1,284', change: '+2.1%', positive: true  },
  { label: 'Expiring Soon',    value: '28',    change: '+5.2%', positive: true  },
  { label: 'Expired',          value: '14',    change: '-1.3%', positive: false },
  { label: 'Total Employees',  value: '452',   change: '+4.0%', positive: true  },
]

// ── Recent contracts ───────────────────────────────────────────────
type ContractStatus = 'Notarized' | 'Draft Client' | 'Draft SBSI'

interface Contract {
  id: string; partner: string; category: string
  status: ContractStatus; endDate: string
}

const timeFilter = ref<'1D' | '1W' | '1M' | 'All'>('All')

const allContracts: Contract[] = [
  { id: 'CNT-2023-001', partner: 'Medical Supplies Co.',   category: 'Supply',         status: 'Notarized',   endDate: 'Dec 12, 2024' },
  { id: 'CNT-2023-042', partner: 'Bio-Tech Logistics',     category: 'Logistics',      status: 'Draft Client', endDate: 'Dec 11, 2024' },
  { id: 'CNT-2023-089', partner: 'Global Pharma Inc.',     category: 'Pharmaceutical', status: 'Draft SBSI',  endDate: 'Dec 10, 2024' },
  { id: 'CNT-2023-112', partner: 'Stellar Lab Equipment',  category: 'Equipment',      status: 'Notarized',   endDate: 'Dec 09, 2024' },
  { id: 'CNT-2023-134', partner: 'BioGenesis Research',    category: 'Research',       status: 'Draft Client', endDate: 'Dec 08, 2024' },
]

const contractStatusBadge: Record<ContractStatus, string> = {
  'Notarized':   'bg-emerald-50 text-emerald-700 border-emerald-200',
  'Draft Client': 'bg-amber-50  text-amber-700   border-amber-200',
  'Draft SBSI':  'bg-[#2E85D8]/8 text-[#2E85D8] border-[#2E85D8]/20',
}

// ── Audit logs ─────────────────────────────────────────────────────
type LogType = 'create' | 'update' | 'approve' | 'delete'

interface AuditLog {
  action: string; user: string; timestamp: string; type: LogType
}

const auditLogs: AuditLog[] = [
  { action: 'Contract created',  user: 'Alex Rivera',   timestamp: 'June 21, 8:00 PM',  type: 'create'  },
  { action: 'Contract renewed',  user: 'Maria Santos',  timestamp: 'June 20, 3:00 PM',  type: 'update'  },
  { action: 'Contract approved', user: 'John Doe',      timestamp: 'June 19, 11:00 AM', type: 'approve' },
  { action: 'User deleted',      user: 'Admin',         timestamp: 'June 19, 9:00 AM',  type: 'delete'  },
  { action: 'Contract updated',  user: 'Sarah Jenkins', timestamp: 'June 18, 4:30 PM',  type: 'update'  },
]

const logDot: Record<LogType, string> = {
  create:  'bg-emerald-500',
  update:  'bg-[#2E85D8]',
  approve: 'bg-[#252578]',
  delete:  'bg-red-400',
}

// ── User list ──────────────────────────────────────────────────────
type Role   = 'Admin' | 'Manager' | 'Sales'
type Status = 'Active' | 'Inactive'

interface DashUser {
  id: string; name: string; email: string; role: Role; status: Status
}

const userFilter = ref<'All' | Role>('All')

const allUsers: DashUser[] = [
  { id: 'USR-001', name: 'John Doe',      email: 'john.doe@sbsi.com',      role: 'Admin',   status: 'Active'   },
  { id: 'USR-002', name: 'Alice Smith',   email: 'alice.smith@sbsi.com',   role: 'Manager', status: 'Active'   },
  { id: 'USR-003', name: 'Maria Santos',  email: 'maria.santos@sbsi.com',  role: 'Sales',   status: 'Active'   },
  { id: 'USR-004', name: 'Bob Johnson',   email: 'bob.johnson@sbsi.com',   role: 'Manager', status: 'Inactive' },
  { id: 'USR-005', name: 'Emma Wilson',   email: 'emma.wilson@sbsi.com',   role: 'Sales',   status: 'Active'   },
]

const filteredUsers = computed(() =>
  userFilter.value === 'All'
    ? allUsers
    : allUsers.filter(u => u.role === userFilter.value)
)

const roleBadge: Record<Role, string> = {
  Admin:   'bg-emerald-50  text-emerald-700 border-emerald-200',
  Manager: 'bg-[#2E85D8]/8 text-[#2E85D8]  border-[#2E85D8]/20',
  Sales:   'bg-[#252578]/6 text-[#252578]  border-[#252578]/20',
}

// ── Avatar helpers ─────────────────────────────────────────────────
const palette = ['#252578','#2E85D8','#2F2F73','#7C3AED','#0D9488','#DB2777','#D97706','#059669']
function getInitials(name: string) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}
function avatarColor(idx: number) { return palette[idx % palette.length] }
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- ── Header ─────────────────────────────────────────────────── -->
    <div>
      <h1 class="text-xl font-semibold text-black">Dashboard</h1>
      <p class="text-sm text-black/40 mt-0.5">Welcome back — here's what's happening at SBSI today.</p>
    </div>

    <!-- ── Stat Cards ─────────────────────────────────────────────── -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="bg-white rounded-lg border border-black/8 px-6 py-5 shadow-sm"
      >
        <p class="text-xs font-medium text-black/40 mb-3 uppercase tracking-wide">{{ card.label }}</p>
        <div class="flex items-end justify-between gap-2">
          <span class="text-3xl font-semibold text-black tabular-nums">{{ card.value }}</span>
          <span
            class="text-xs font-medium px-2 py-0.5 rounded-md mb-0.5 shrink-0"
            :class="card.positive ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'"
          >
            {{ card.change }}
          </span>
        </div>
      </div>
    </div>

    <!-- ── Recent Contracts + Audit Log ───────────────────────────── -->
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">

      <!-- Recent Contracts (3/5) -->
      <div class="xl:col-span-3 bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">
        <div class="px-6 pt-5 pb-4 border-b border-black/5 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-black">
            Recent contracts
            <span class="text-black/30 font-normal">({{ allContracts.length }})</span>
          </h2>
          <div class="flex items-center gap-0.5 bg-black/4 rounded-md p-1">
            <button
              v-for="f in ['1D','1W','1M','All']"
              :key="f"
              @click="timeFilter = f as any"
              class="px-3 py-1 text-xs rounded transition-all font-medium"
              :class="timeFilter === f ? 'bg-white text-black shadow-sm' : 'text-black/40 hover:text-black/60'"
            >
              {{ f }}
            </button>
          </div>
        </div>

        <Table>
          <TableHeader class="bg-black/2.5">
            <TableRow class="border-b border-black/6 hover:bg-transparent">
              <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider pl-6 py-3">Contract ID</TableHead>
              <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Partner</TableHead>
              <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Category</TableHead>
              <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Status</TableHead>
              <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">End Date</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="contract in allContracts"
              :key="contract.id"
              class="border-b border-black/4 last:border-0 hover:bg-black/[0.012] transition-colors"
            >
              <TableCell class="pl-6 py-3.5 text-xs font-medium text-[#252578]/70">{{ contract.id }}</TableCell>
              <TableCell class="py-3.5 text-sm text-black">{{ contract.partner }}</TableCell>
              <TableCell class="py-3.5 text-sm text-black/40">{{ contract.category }}</TableCell>
              <TableCell class="py-3.5">
                <Badge variant="outline" class="text-xs font-medium rounded-full px-2.5 py-0.5"
                  :class="contractStatusBadge[contract.status]">
                  {{ contract.status }}
                </Badge>
              </TableCell>
              <TableCell class="py-3.5 text-sm text-black/40">{{ contract.endDate }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <div class="px-6 py-3 border-t border-black/5">
          <button class="flex items-center gap-1.5 text-xs font-semibold text-[#2E85D8] hover:text-[#252578] transition-colors">
            View all contracts <ArrowRight class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <!-- Audit Log (2/5) -->
      <div class="xl:col-span-2 bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">
        <div class="px-6 pt-5 pb-4 border-b border-black/5 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-black">Audit log</h2>
          <button class="text-xs font-semibold text-[#2E85D8] hover:text-[#252578] transition-colors">
            See all
          </button>
        </div>

        <div class="px-6 py-4 space-y-0 divide-y divide-black/4">
          <div
            v-for="log in auditLogs"
            :key="log.action + log.timestamp"
            class="flex items-start gap-3 py-3.5 first:pt-0 last:pb-0"
          >
            <div class="mt-1.5 w-2 h-2 rounded-full shrink-0" :class="logDot[log.type]" />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-black leading-snug">{{ log.action }}</p>
              <p class="text-xs text-black/38 mt-0.5">by {{ log.user }}</p>
            </div>
            <p class="text-[11px] text-black/35 shrink-0 mt-0.5">{{ log.timestamp }}</p>
          </div>
        </div>
      </div>

    </div>

    <!-- ── User List ───────────────────────────────────────────────── -->
    <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">

      <div class="px-6 pt-5 pb-4 border-b border-black/5 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-black">
          User list
          <span class="text-black/30 font-normal">({{ filteredUsers.length }})</span>
        </h2>
        <div class="flex items-center gap-0.5 bg-black/4 rounded-md p-1">
          <button
            v-for="f in ['All','Admin','Manager','Sales']"
            :key="f"
            @click="userFilter = f as any"
            class="px-3 py-1 text-xs rounded transition-all font-medium"
            :class="userFilter === f ? 'bg-white text-black shadow-sm' : 'text-black/40 hover:text-black/60'"
          >
            {{ f }}
          </button>
        </div>
      </div>

      <Table>
        <TableHeader class="bg-black/2.5">
          <TableRow class="border-b border-black/6 hover:bg-transparent">
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider pl-6 py-3">Name</TableHead>
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Role</TableHead>
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Status</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow
            v-for="(user, index) in filteredUsers.slice(0, 5)"
            :key="user.id"
            class="border-b border-black/4 last:border-0 hover:bg-black/[0.012] transition-colors"
          >
            <TableCell class="pl-6 py-3.5">
              <div class="flex items-center gap-3">
                <div
                  class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 select-none"
                  :style="{ backgroundColor: avatarColor(index) }"
                >
                  {{ getInitials(user.name) }}
                </div>
                <div>
                  <p class="text-sm font-medium text-black leading-snug">{{ user.name }}</p>
                  <p class="text-xs text-black/35 mt-0.5">{{ user.email }}</p>
                </div>
              </div>
            </TableCell>
            <TableCell class="py-3.5">
              <Badge variant="outline" class="text-xs font-medium rounded-full px-2.5 py-0.5"
                :class="roleBadge[user.role]">
                {{ user.role }}
              </Badge>
            </TableCell>
            <TableCell class="py-3.5">
              <div class="flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full shrink-0"
                  :class="user.status === 'Active' ? 'bg-emerald-500' : 'bg-black/15'" />
                <span class="text-sm"
                  :class="user.status === 'Active' ? 'text-emerald-600' : 'text-black/30'">
                  {{ user.status }}
                </span>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>

      <div class="px-6 py-3 border-t border-black/5">
        <button class="flex items-center gap-1.5 text-xs font-semibold text-[#2E85D8] hover:text-[#252578] transition-colors">
          View all users <ArrowRight class="w-3.5 h-3.5" />
        </button>
      </div>

    </div>
  </div>
</template>
