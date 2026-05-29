<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ArrowRight } from 'lucide-vue-next'
import { useAuth } from '@/composables/useAuth'
import { useApiCache } from '@/composables/useApiCache'
import { useToast } from '@/composables/useToast'
import { remainingDays, fmtDate } from '@/types/contract'
import { Badge } from '@/components/ui/badge'
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table'
import { workflowStatusBadge } from '@/types/contract'

const router = useRouter()
const { state: authState } = useAuth()
const { state: cacheState, fetchDashboard } = useApiCache()
const { error } = useToast()

type Role   = 'Admin' | 'Manager' | 'Sales'
type Status = 'Active' | 'Inactive'

interface DashUser {
  id: string; name: string; email: string; role: Role; status: Status
}

// ── Live clock ─────────────────────────────────────────────────────
const now = ref(new Date())
let timer: ReturnType<typeof setInterval>
onMounted(() => { timer = setInterval(() => { now.value = new Date() }, 1000) })
onUnmounted(() => clearInterval(timer))

const greeting = computed(() => {
  const h = now.value.getHours()
  if (h < 12) return 'Good morning'
  if (h < 17) return 'Good afternoon'
  return 'Good evening'
})
const formattedDate = computed(() =>
  now.value.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })
)
const formattedTime = computed(() =>
  now.value.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
)

const userFirstName = computed(() => authState.user?.first_name || 'Admin')

// ── Real Data & Fetching ───────────────────────────────────────────
const loading = ref(true)
const users = ref<DashUser[]>([])
const rawAuditLogs = ref<any[]>([])
const timeFilter = ref<'1D' | '1W' | '1M' | 'All'>('All')
const userFilter = ref<'All' | Role>('All')

const contracts = computed(() => cacheState.contracts || [])
const withDays = computed(() => contracts.value.map(c => ({ ...c, days: remainingDays(c.endDate) })))

// KPI stats computed dynamically
const statCards = computed(() => {
  const total = withDays.value.length
  const expiring = withDays.value.filter(c => c.days >= 0 && c.days <= 30).length
  const expired = withDays.value.filter(c => c.days < 0).length
  const totalEmployees = users.value.length

  return [
    { label: 'Total Contracts',  value: String(total), change: '+2.1%', positive: true  },
    { label: 'Expiring Soon',    value: String(expiring), change: '+5.2%', positive: true  },
    { label: 'Expired',          value: String(expired), change: '-1.3%', positive: false },
    { label: 'Total Employees',  value: String(totalEmployees), change: '+4.0%', positive: true  },
  ]
})

// Recent contracts (filtered by time select, showing top 5)
const allContracts = computed(() => {
  let list = withDays.value

  if (timeFilter.value !== 'All') {
    const now = new Date()
    const cutoff = new Date()
    if (timeFilter.value === '1D') cutoff.setDate(now.getDate() - 1)
    else if (timeFilter.value === '1W') cutoff.setDate(now.getDate() - 7)
    else if (timeFilter.value === '1M') cutoff.setMonth(now.getMonth() - 1)

    list = list.filter(c => {
      const d = new Date(c.startDate)
      return d >= cutoff
    })
  }

  return list.slice(0, 5).map(c => ({
    id: c.id,
    partner: c.businessPartner,
    category: c.category,
    status: c.workflowStatus || 'SBSI Review',
    endDate: fmtDate(c.endDate)
  }))
})

// Dynamic audit log processing
type LogType = 'create' | 'update' | 'approve' | 'delete'
interface AuditLog {
  action: string; user: string; timestamp: string; type: LogType
}

const auditLogs = computed<AuditLog[]>(() => {
  return rawAuditLogs.value.slice(0, 5).map(log => {
    return {
      action: log.description || log.action,
      user: log.user_name || 'System',
      timestamp: formatTimestamp(log.performed_at),
      type: getLogType(log.action)
    }
  })
})

const logDot: Record<LogType, string> = {
  create:  'bg-[#2E85D8]',
  update:  'bg-[#2F2F73]',
  approve: 'bg-[#252578]',
  delete:  'bg-black/30',
}

const filteredUsers = computed(() =>
  userFilter.value === 'All'
    ? users.value
    : users.value.filter(u => u.role === userFilter.value)
)

const roleBadge: Record<Role, string> = {
  Admin:   'bg-[#252578]/8 text-[#252578] border-[#252578]/20',
  Manager: 'bg-[#2F2F73]/8 text-[#2F2F73] border-[#2F2F73]/20',
  Sales:   'bg-[#2E85D8]/8 text-[#2E85D8] border-[#2E85D8]/20',
}

// ── Helpers ────────────────────────────────────────────────────────
function normalizeRole(roleName: string): Role {
  const name = roleName.toLowerCase()
  if (name.includes('admin')) return 'Admin'
  if (name.includes('manager')) return 'Manager'
  return 'Sales'
}

function getLogType(action: string): LogType {
  const act = action.toLowerCase()
  if (act.includes('created') || act.includes('added') || act.includes('upload')) return 'create'
  if (act.includes('deleted')) return 'delete'
  if (act.includes('approved') || act.includes('notarize')) return 'approve'
  return 'update'
}

function formatTimestamp(iso: string): string {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ', ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const palette = ['#252578', '#2E85D8', '#2F2F73']
function getInitials(name: string) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}
function avatarColor(idx: number) { return palette[idx % palette.length] }

// ── Fetch lifecycle ────────────────────────────────────────────────
async function fetchAdminDashboard() {
  loading.value = true
  const authApiUrl = import.meta.env.VITE_AUTH_API_URL || 'http://localhost:8000/api'
  const contractApiUrl = import.meta.env.VITE_CONTRACT_API_URL || 'http://localhost:8002/api'
  const token = authState.token

  try {
    // 1. Fetch contracts through API cache
    await fetchDashboard()

    // 2. Fetch users list
    const usersRes = await fetch(`${authApiUrl}/admin/users?per_page=100`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'X-Session-ID': localStorage.getItem('session_id') || '',
        'Accept': 'application/json'
      }
    })
    if (usersRes.ok) {
      const data = await usersRes.json()
      if (data.data) {
        users.value = data.data.map((u: any) => ({
          id: u.id.toString(),
          name: `${u.profile?.first_name || ''} ${u.profile?.last_name || ''}`.trim() || u.email,
          email: u.email,
          role: normalizeRole(u.profile?.role?.name || 'Unknown'),
          status: (u.is_active ? 'Active' : 'Inactive') as Status,
        }))
      }
    }

    // 3. Fetch audit logs (latest 5)
    const logsRes = await fetch(`${contractApiUrl}/audit-logs?per_page=5`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    })
    if (logsRes.ok) {
      const data = await logsRes.json()
      if (data.data) {
        rawAuditLogs.value = data.data
      }
    }
  } catch (err) {
    console.error('Failed to load admin dashboard:', err)
    error('Network error', 'Could not fetch admin dashboard data.')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchAdminDashboard()
})
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- ── Header ─────────────────────────────────────────────────── -->
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-black/35 uppercase tracking-widest mb-0.5">Admin Portal</p>
        <h1 class="text-xl font-semibold text-black">{{ greeting }}, {{ userFirstName }}.</h1>
        <p class="text-sm text-black/40 mt-0.5">Here's what's happening at SBSI today.</p>
      </div>
      <div class="text-right hidden sm:block">
        <p class="text-sm font-semibold text-black tabular-nums">{{ formattedTime }}</p>
        <p class="text-xs text-black/40 mt-0.5">{{ formattedDate }}</p>
      </div>
    </div>

    <!-- ── Stat Cards ─────────────────────────────────────────────── -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
      <template v-if="loading">
        <div v-for="i in 4" :key="i" class="bg-white rounded-lg border border-black/8 px-6 py-5 shadow-sm animate-pulse">
          <div class="h-3.5 w-24 bg-black/5 animate-pulse rounded mb-4"></div>
          <div class="h-8 w-12 bg-black/5 animate-pulse rounded"></div>
        </div>
      </template>
      <template v-else>
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
              :class="card.positive ? 'bg-black/5 text-black/50' : 'bg-black/5 text-black/40'"
            >
              {{ card.change }}
            </span>
          </div>
        </div>
      </template>
    </div>

    <!-- ── Recent Contracts + Audit Log ───────────────────────────── -->
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">

      <!-- Recent Contracts (3/5) -->
      <div class="xl:col-span-3 bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden flex flex-col justify-between">
        <div>
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

          <div v-if="loading" class="px-6 py-8 space-y-4 animate-pulse">
            <div v-for="i in 4" :key="i" class="h-10 bg-black/5 rounded w-full"></div>
          </div>
          <div v-else-if="allContracts.length === 0" class="p-8 text-center text-sm text-black/40">
            No recent contracts found.
          </div>
          <Table v-else>
            <TableHeader class="bg-black/[0.018]">
              <TableRow class="border-b border-black/4 hover:bg-transparent">
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
                class="border-b border-black/4 last:border-0 hover:bg-black/1.2 transition-colors cursor-pointer"
                @click="router.push('/admin/contracts/' + contract.id)"
              >
                <TableCell class="pl-6 py-3.5 text-xs font-medium text-[#252578]/70">{{ contract.id }}</TableCell>
                <TableCell class="py-3.5 text-sm text-black">{{ contract.partner }}</TableCell>
                <TableCell class="py-3.5 text-sm text-black/40">{{ contract.category }}</TableCell>
                <TableCell class="py-3.5">
                  <Badge variant="outline" class="text-xs font-medium rounded-full px-2.5 py-0.5"
                    :class="workflowStatusBadge[contract.status as keyof typeof workflowStatusBadge] || 'bg-black/5 text-black/50 border-black/10'">
                    {{ contract.status }}
                  </Badge>
                </TableCell>
                <TableCell class="py-3.5 text-sm text-black/40">{{ contract.endDate }}</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <div class="px-6 py-3 border-t border-black/5">
          <button @click="router.push('/admin/contracts')" class="flex items-center gap-1.5 text-xs font-semibold text-[#2E85D8] hover:text-[#252578] transition-colors">
            View all contracts <ArrowRight class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <!-- Audit Log (2/5) -->
      <div class="xl:col-span-2 bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden flex flex-col justify-between">
        <div>
          <div class="px-6 pt-5 pb-4 border-b border-black/5 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-black">Audit log</h2>
            <button @click="router.push('/admin/audit-log')" class="text-xs font-semibold text-[#2E85D8] hover:text-[#252578] transition-colors">
              See all
            </button>
          </div>

          <div v-if="loading" class="px-6 py-8 space-y-4 animate-pulse">
            <div v-for="i in 4" :key="i" class="h-10 bg-black/5 rounded w-full"></div>
          </div>
          <div v-else-if="auditLogs.length === 0" class="p-8 text-center text-sm text-black/40">
            No audit logs recorded.
          </div>
          <div v-else class="px-6 py-4 space-y-0 divide-y divide-black/4">
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

      <div v-if="loading" class="px-6 py-8 space-y-4 animate-pulse">
        <div v-for="i in 4" :key="i" class="h-12 bg-black/5 rounded w-full"></div>
      </div>
      <div v-else-if="filteredUsers.length === 0" class="p-8 text-center text-sm text-black/40">
        No users match this filter.
      </div>
      <Table v-else>
        <TableHeader class="bg-black/[0.018]">
          <TableRow class="border-b border-black/4 hover:bg-transparent">
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider pl-6 py-3">Name</TableHead>
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Role</TableHead>
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">Status</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow
            v-for="(user, index) in filteredUsers.slice(0, 5)"
            :key="user.id"
            class="border-b border-black/4 last:border-0 hover:bg-black/1.2 transition-colors"
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
              <span class="text-xs font-medium px-2.5 py-0.5 rounded-full border"
                :class="user.status === 'Active'
                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                  : 'bg-black/4 text-black/35 border-black/8'">
                {{ user.status }}
              </span>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>

      <div class="px-6 py-3 border-t border-black/5">
        <button @click="router.push('/admin/users')" class="flex items-center gap-1.5 text-xs font-semibold text-[#2E85D8] hover:text-[#252578] transition-colors">
          View all users <ArrowRight class="w-3.5 h-3.5" />
        </button>
      </div>

    </div>
  </div>
</template>
