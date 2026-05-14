<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import {
  Plus,
  Upload,
  Search,
  MoreHorizontal,
  Pencil,
  Trash2,
  Eye,
} from 'lucide-vue-next'

import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

// ── Types ──────────────────────────────────────────────────────────
type Role   = 'Admin' | 'Manager' | 'Sales'
type Status = 'Active' | 'Inactive'

interface User {
  id:        string
  name:      string
  email:     string
  role:      Role
  status:    Status
  dateAdded: string
}

// ── Data ───────────────────────────────────────────────────────────
const users = ref<User[]>([
  { id: 'USR-001', name: 'Sarah Jenkins',    email: 'sarah.j@sbsi.com',      role: 'Admin',   status: 'Active',   dateAdded: 'June 23, 2026'  },
  { id: 'USR-002', name: 'Marcus Chen',      email: 'm.chen@sbsi.com',        role: 'Manager', status: 'Active',   dateAdded: 'June 23, 2026'  },
  { id: 'USR-003', name: 'Elena Rodriguez',  email: 'e.rodriguez@sbsi.com',   role: 'Sales',   status: 'Inactive', dateAdded: 'May 10, 2026'   },
  { id: 'USR-004', name: 'David Kim',        email: 'd.kim@sbsi.com',         role: 'Admin',   status: 'Active',   dateAdded: 'May 4, 2026'    },
  { id: 'USR-005', name: 'Jessica Williams', email: 'j.williams@sbsi.com',    role: 'Sales',   status: 'Active',   dateAdded: 'Apr 18, 2026'   },
  { id: 'USR-006', name: 'Michael Brown',    email: 'm.brown@sbsi.com',       role: 'Manager', status: 'Active',   dateAdded: 'Apr 1, 2026'    },
  { id: 'USR-007', name: 'Anna Reyes',       email: 'a.reyes@sbsi.com',       role: 'Sales',   status: 'Active',   dateAdded: 'Mar 15, 2026'   },
  { id: 'USR-008', name: 'James Torres',     email: 'j.torres@sbsi.com',      role: 'Manager', status: 'Active',   dateAdded: 'Mar 2, 2026'    },
  { id: 'USR-009', name: 'Patricia Lim',     email: 'p.lim@sbsi.com',         role: 'Sales',   status: 'Inactive', dateAdded: 'Feb 20, 2026'   },
  { id: 'USR-010', name: 'Robert Navarro',   email: 'r.navarro@sbsi.com',     role: 'Admin',   status: 'Active',   dateAdded: 'Jan 8, 2026'    },
  { id: 'USR-011', name: 'Lydia Santos',     email: 'l.santos@sbsi.com',      role: 'Sales',   status: 'Active',   dateAdded: 'Jan 3, 2026'    },
  { id: 'USR-012', name: 'Kevin Park',       email: 'k.park@sbsi.com',        role: 'Manager', status: 'Active',   dateAdded: 'Dec 12, 2025'   },
])

// ── Avatar helpers ─────────────────────────────────────────────────
const palette = [
  '#252578', '#2E85D8', '#2F2F73',
  '#7C3AED', '#0D9488', '#DB2777', '#D97706', '#059669',
]

function getInitials(name: string) {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}
function avatarColor(idx: number) {
  return palette[idx % palette.length]
}

// ── Role badge styles ──────────────────────────────────────────────
const roleBadge: Record<Role, string> = {
  Admin:   'bg-emerald-50  text-emerald-700 border-emerald-200',
  Manager: 'bg-[#2E85D8]/8 text-[#2E85D8]   border-[#2E85D8]/20',
  Sales:   'bg-[#252578]/6 text-[#252578]   border-[#252578]/20',
}

// ── Stat cards ─────────────────────────────────────────────────────
const statCards = computed(() => [
  { label: 'All users', value: users.value.length,                                   change: '+4.0%', positive: true  },
  { label: 'Admins',    value: users.value.filter(u => u.role === 'Admin').length,    change: '+2.1%', positive: true  },
  { label: 'Managers',  value: users.value.filter(u => u.role === 'Manager').length,  change: '-0.3%', positive: false },
  { label: 'Sales',     value: users.value.filter(u => u.role === 'Sales').length,    change: '+5.2%', positive: true  },
])

// ── Tab filter + search ────────────────────────────────────────────
type TabValue = 'all' | Role

const tabs: { label: string; value: TabValue }[] = [
  { label: 'View all', value: 'all'     },
  { label: 'Admin',    value: 'Admin'   },
  { label: 'Manager',  value: 'Manager' },
  { label: 'Sales',    value: 'Sales'   },
]

const activeTab   = ref<TabValue>('all')
const searchQuery = ref('')

const filteredUsers = computed(() =>
  users.value.filter(u => {
    const byTab    = activeTab.value === 'all' || u.role === activeTab.value
    const bySearch = !searchQuery.value ||
      u.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      u.email.toLowerCase().includes(searchQuery.value.toLowerCase())
    return byTab && bySearch
  })
)

// ── Pagination ─────────────────────────────────────────────────────
const currentPage  = ref(1)
const itemsPerPage = 8

watch([activeTab, searchQuery], () => { currentPage.value = 1 })

const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredUsers.value.slice(start, start + itemsPerPage)
})


// ── Row selection ──────────────────────────────────────────────────
const selectedIds = ref<string[]>([])

const allPageSelected = computed(() =>
  paginatedUsers.value.length > 0 &&
  paginatedUsers.value.every(u => selectedIds.value.includes(u.id))
)

function toggleSelectAll() {
  const ids = paginatedUsers.value.map(u => u.id)
  if (allPageSelected.value) {
    selectedIds.value = selectedIds.value.filter(id => !ids.includes(id))
  } else {
    ids.forEach(id => { if (!selectedIds.value.includes(id)) selectedIds.value.push(id) })
  }
}
function toggleRow(id: string) {
  const i = selectedIds.value.indexOf(id)
  i >= 0 ? selectedIds.value.splice(i, 1) : selectedIds.value.push(id)
}
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- ── Page Header ─────────────────────────────────────────── -->
    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-2xl font-bold text-black">User management</h1>
        <p class="text-sm text-black/45 mt-0.5">
          Manage your team members and their account permissions here.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <Button variant="outline" class="h-9 gap-2 text-sm font-medium border-black/15 text-black/65 hover:text-black">
          <Upload class="w-4 h-4" />
          Export CSV
        </Button>
        <Button class="h-9 w-9 p-0 bg-[#252578] hover:bg-[#2F2F73] text-white rounded-lg shadow-sm">
          <Plus class="w-5 h-5" />
        </Button>
      </div>
    </div>

    <!-- ── Stat Cards ───────────────────────────────────────────── -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="bg-white rounded-lg border border-black/8 px-6 py-5 shadow-sm"
      >
        <p class="text-sm font-medium text-black/45 mb-3">{{ card.label }}</p>
        <div class="flex items-end justify-between gap-2">
          <span class="text-4xl font-bold text-black tabular-nums">{{ card.value }}</span>
          <span
            class="text-xs font-semibold px-2 py-0.5 rounded-md mb-0.5 shrink-0"
            :class="card.positive ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'"
          >
            {{ card.change }}
          </span>
        </div>
      </div>
    </div>

    <!-- ── Table Section ────────────────────────────────────────── -->
    <div class="bg-white rounded-lg border border-black/8 shadow-sm overflow-hidden">

      <!-- Section heading -->
      <div class="px-6 pt-5 pb-4 border-b border-black/5">
        <h2 class="text-base font-bold text-black">
          All users
          <span class="text-black/30 font-semibold">({{ filteredUsers.length }})</span>
        </h2>
      </div>

      <!-- Tabs + Search -->
      <div class="flex items-center justify-between px-6 py-3 border-b border-black/5">
        <div class="flex items-center gap-0.5 bg-black/4 rounded-md p-1">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            @click="activeTab = tab.value"
            class="px-4 py-1.5 text-sm rounded transition-all font-medium"
            :class="activeTab === tab.value
              ? 'bg-white text-black shadow-sm'
              : 'text-black/40 hover:text-black/60'"
          >
            {{ tab.label }}
          </button>
        </div>

        <div class="relative w-52">
          <Search class="w-3.5 h-3.5 text-black/30 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search..."
            class="w-full h-9 rounded-lg border border-black/10 bg-white pl-8.5 pr-3 text-sm placeholder:text-black/25 focus:border-[#2E85D8] focus:outline-none focus:ring-2 focus:ring-[#2E85D8]/15 transition"
          />
        </div>
      </div>

      <!-- Table -->
      <Table>
        <TableHeader class="bg-black/[0.025]">
          <TableRow class="border-b border-black/6 hover:bg-transparent">
            <TableHead class="w-12 pl-6 py-3">
              <input
                type="checkbox"
                :checked="allPageSelected"
                @change="toggleSelectAll"
                class="w-4 h-4 rounded border-black/20 accent-[#252578] cursor-pointer"
              />
            </TableHead>
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">
              Name
            </TableHead>
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">
              Role
            </TableHead>
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">
              Status
            </TableHead>
            <TableHead class="text-[11px] font-semibold text-black/40 uppercase tracking-wider py-3">
              Date added
            </TableHead>
            <TableHead class="w-14 py-3" />
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableRow
            v-for="(user, index) in paginatedUsers"
            :key="user.id"
            class="border-b border-black/4 last:border-0 transition-colors"
            :class="selectedIds.includes(user.id)
              ? 'bg-[#252578]/2.5'
              : 'hover:bg-black/[0.012]'"
          >
            <!-- Checkbox -->
            <TableCell class="pl-6 py-4">
              <input
                type="checkbox"
                :checked="selectedIds.includes(user.id)"
                @change="toggleRow(user.id)"
                class="w-4 h-4 rounded border-black/20 accent-[#252578] cursor-pointer"
              />
            </TableCell>

            <!-- Name + avatar -->
            <TableCell class="py-4">
              <div class="flex items-center gap-3">
                <div
                  class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 select-none"
                  :style="{ backgroundColor: avatarColor(index) }"
                >
                  {{ getInitials(user.name) }}
                </div>
                <div>
                  <p class="text-sm font-semibold text-black leading-snug">{{ user.name }}</p>
                  <p class="text-xs text-black/38 mt-0.5">{{ user.email }}</p>
                </div>
              </div>
            </TableCell>

            <!-- Role -->
            <TableCell class="py-4">
              <Badge
                variant="outline"
                class="text-xs font-semibold rounded-full px-2.5 py-0.5"
                :class="roleBadge[user.role]"
              >
                {{ user.role }}
              </Badge>
            </TableCell>

            <!-- Status -->
            <TableCell class="py-4">
              <div class="flex items-center gap-2">
                <span
                  class="w-1.5 h-1.5 rounded-full shrink-0"
                  :class="user.status === 'Active' ? 'bg-emerald-500' : 'bg-black/15'"
                />
                <span
                  class="text-sm font-medium"
                  :class="user.status === 'Active' ? 'text-emerald-600' : 'text-black/30'"
                >
                  {{ user.status }}
                </span>
              </div>
            </TableCell>

            <!-- Date added -->
            <TableCell class="text-sm text-black/50 py-4 font-medium">
              {{ user.dateAdded }}
            </TableCell>

            <!-- Actions -->
            <TableCell class="py-4 pr-4">
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8 text-black/25 hover:text-black hover:bg-black/5 data-[state=open]:bg-black/5 data-[state=open]:text-black"
                  >
                    <MoreHorizontal class="w-4 h-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-44">
                  <DropdownMenuLabel class="text-xs font-semibold text-black/38 pb-1">
                    Actions
                  </DropdownMenuLabel>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem class="gap-2.5 text-sm cursor-pointer">
                    <Eye class="w-3.5 h-3.5 text-black/40" />
                    View profile
                  </DropdownMenuItem>
                  <DropdownMenuItem class="gap-2.5 text-sm cursor-pointer">
                    <Pencil class="w-3.5 h-3.5 text-black/40" />
                    Edit user
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem class="gap-2.5 text-sm cursor-pointer text-red-600 focus:text-red-600 focus:bg-red-50">
                    <Trash2 class="w-3.5 h-3.5" />
                    Delete user
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </TableCell>

          </TableRow>

          <!-- Empty state -->
          <TableRow v-if="paginatedUsers.length === 0">
            <TableCell colspan="6" class="text-center py-16">
              <p class="text-sm font-semibold text-black/28">No users found</p>
              <p class="text-xs text-black/20 mt-1">Try a different search or tab</p>
            </TableCell>
          </TableRow>

        </TableBody>
      </Table>

      <!-- Pagination footer -->
      <Pagination
        :total="filteredUsers.length"
        :sibling-count="1"
        :items-per-page="itemsPerPage"
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
                  :class="
                    item.value === currentPage
                      ? 'bg-[#252578] text-white hover:bg-[#2F2F73] hover:text-white border-transparent font-semibold'
                      : 'text-black/50 hover:bg-black/5'
                  "
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
