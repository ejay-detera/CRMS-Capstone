<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import {
  Plus,
  Upload,
  Search,
  MoreHorizontal,
  Pencil,
  Trash2,
  Eye,
  EyeOff,
  UserPlus,
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
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

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

// ── Add User dialog ────────────────────────────────────────────────
const showAddUser = ref(false)

interface AddUserForm {
  firstName:       string
  lastName:        string
  middleName:      string
  email:           string
  password:        string
  confirmPassword: string
  role:            Role | ''
  department:      string
}

const form = reactive<AddUserForm>({
  firstName: '', lastName: '', middleName: '',
  email: '', password: '', confirmPassword: '',
  role: '', department: '',
})

const departments = ['Sales Department']

// track which fields the user has interacted with
const touched = reactive({
  firstName: false, lastName: false, middleName: false,
  email: false, password: false, confirmPassword: false,
})

// eye toggles
const showPassword        = ref(false)
const showConfirmPassword = ref(false)

// name fields: allow only letters, spaces, hyphens, apostrophes
function onNameInput(field: 'firstName' | 'lastName' | 'middleName', e: Event) {
  const el = e.target as HTMLInputElement
  const clean = el.value.replace(/[^a-zA-Z\s\-']/g, '')
  form[field] = clean
  el.value    = clean
  touched[field] = true
}

// email validation
const emailValid = computed(() =>
  !form.email || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)
)

// password rules
const pwRules = computed(() => ({
  minLength:   form.password.length >= 8,
  hasUpper:    /[A-Z]/.test(form.password),
  hasSpecial:  /[!@#$%^&*()\-_=+\[\]{};:'",.<>?/\\|`~]/.test(form.password),
}))
const passwordValid   = computed(() => Object.values(pwRules.value).every(Boolean))
const passwordMismatch = computed(() =>
  form.confirmPassword.length > 0 && form.password !== form.confirmPassword
)

function resetForm() {
  Object.assign(form, {
    firstName: '', lastName: '', middleName: '',
    email: '', password: '', confirmPassword: '',
    role: '', department: '',
  })
  Object.assign(touched, {
    firstName: false, lastName: false, middleName: false,
    email: false, password: false, confirmPassword: false,
  })
  showPassword.value        = false
  showConfirmPassword.value = false
}

watch(showAddUser, (open) => { if (!open) resetForm() })

function submitAddUser() {
  // mark all touched so errors show on submit attempt
  Object.keys(touched).forEach(k => ((touched as Record<string,boolean>)[k] = true))

  if (!form.firstName || !form.lastName || !form.email || !emailValid.value ||
      !passwordValid.value || passwordMismatch.value || !form.role || !form.department) return

  const pad = String(users.value.length + 1).padStart(3, '0')
  users.value.push({
    id:        `USR-${pad}`,
    name:      [form.firstName, form.middleName, form.lastName].filter(Boolean).join(' '),
    email:     form.email,
    role:      form.role as Role,
    status:    'Active',
    dateAdded: new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }),
  })
  showAddUser.value = false
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
        <Button
          @click="showAddUser = true"
          class="h-9 w-9 p-0 bg-[#252578] hover:bg-[#2F2F73] text-white rounded-lg shadow-sm"
        >
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

  <!-- ── Add User Dialog ──────────────────────────────────────────── -->
  <Dialog v-model:open="showAddUser">
    <DialogContent class="max-w-xl">

      <!-- Header -->
      <div class="px-6 pt-6 pb-5 border-b border-black/6">
        <div class="flex items-center gap-3 mb-1">
          <div class="w-9 h-9 rounded-lg bg-[#252578]/8 flex items-center justify-center shrink-0">
            <UserPlus class="w-4.5 h-4.5 text-[#252578]" />
          </div>
          <DialogHeader>
            <DialogTitle>Add new user</DialogTitle>
            <DialogDescription>Fill in the details below to create a new team member account.</DialogDescription>
          </DialogHeader>
        </div>
      </div>

      <!-- Form body -->
      <form @submit.prevent="submitAddUser" class="px-6 py-5 space-y-4">

        <!-- First + Last name -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
              First name <span class="text-red-500">*</span>
            </label>
            <input
              :value="form.firstName"
              @input="onNameInput('firstName', $event)"
              @blur="touched.firstName = true"
              type="text"
              placeholder="e.g. Sarah"
              :class="[
                'w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
                touched.firstName && !form.firstName
                  ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15'
                  : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'
              ]"
            />
            <p v-if="touched.firstName && !form.firstName" class="text-xs text-red-500">Required.</p>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
              Last name <span class="text-red-500">*</span>
            </label>
            <input
              :value="form.lastName"
              @input="onNameInput('lastName', $event)"
              @blur="touched.lastName = true"
              type="text"
              placeholder="e.g. Jenkins"
              :class="[
                'w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
                touched.lastName && !form.lastName
                  ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15'
                  : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'
              ]"
            />
            <p v-if="touched.lastName && !form.lastName" class="text-xs text-red-500">Required.</p>
          </div>
        </div>

        <!-- Middle name (optional) -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
            Middle name
            <span class="normal-case font-normal text-black/30 ml-1">(optional)</span>
          </label>
          <input
            :value="form.middleName"
            @input="onNameInput('middleName', $event)"
            type="text"
            placeholder="e.g. Anne"
            class="w-full h-9 rounded-md border border-black/12 bg-white px-3 text-sm placeholder:text-black/25 focus:border-[#2E85D8] focus:outline-none focus:ring-2 focus:ring-[#2E85D8]/15 transition"
          />
          <p class="text-[11px] text-black/30">Letters only — no numbers or special characters.</p>
        </div>

        <!-- Email -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
            Email address <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.email"
            @blur="touched.email = true"
            type="text"
            placeholder="e.g. sarah.j@sbsi.com"
            :class="[
              'w-full h-9 rounded-md border bg-white px-3 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
              touched.email && (!form.email || !emailValid)
                ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15'
                : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'
            ]"
          />
          <p v-if="touched.email && !form.email" class="text-xs text-red-500">Required.</p>
          <p v-else-if="touched.email && !emailValid" class="text-xs text-red-500">Enter a valid email address.</p>
        </div>

        <!-- Password -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
            Password <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input
              v-model="form.password"
              @blur="touched.password = true"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Min. 8 characters"
              :class="[
                'w-full h-9 rounded-md border bg-white pl-3 pr-10 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
                touched.password && !passwordValid
                  ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15'
                  : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'
              ]"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-black/30 hover:text-black/60 transition-colors"
              tabindex="-1"
            >
              <EyeOff v-if="showPassword" class="w-4 h-4" />
              <Eye v-else class="w-4 h-4" />
            </button>
          </div>
          <!-- Password rules checklist -->
          <div v-if="touched.password || form.password.length > 0" class="flex flex-wrap gap-x-4 gap-y-1 pt-1">
            <span class="flex items-center gap-1 text-[11px]" :class="pwRules.minLength ? 'text-emerald-600' : 'text-black/35'">
              <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="pwRules.minLength ? 'bg-emerald-500' : 'bg-black/20'" />
              8+ characters
            </span>
            <span class="flex items-center gap-1 text-[11px]" :class="pwRules.hasUpper ? 'text-emerald-600' : 'text-black/35'">
              <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="pwRules.hasUpper ? 'bg-emerald-500' : 'bg-black/20'" />
              One uppercase
            </span>
            <span class="flex items-center gap-1 text-[11px]" :class="pwRules.hasSpecial ? 'text-emerald-600' : 'text-black/35'">
              <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="pwRules.hasSpecial ? 'bg-emerald-500' : 'bg-black/20'" />
              One special character
            </span>
          </div>
        </div>

        <!-- Confirm password -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
            Confirm password <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input
              v-model="form.confirmPassword"
              @blur="touched.confirmPassword = true"
              :type="showConfirmPassword ? 'text' : 'password'"
              placeholder="Repeat password"
              :class="[
                'w-full h-9 rounded-md border bg-white pl-3 pr-10 text-sm placeholder:text-black/25 focus:outline-none focus:ring-2 transition',
                passwordMismatch
                  ? 'border-red-400 focus:border-red-400 focus:ring-red-400/15'
                  : 'border-black/12 focus:border-[#2E85D8] focus:ring-[#2E85D8]/15'
              ]"
            />
            <button
              type="button"
              @click="showConfirmPassword = !showConfirmPassword"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-black/30 hover:text-black/60 transition-colors"
              tabindex="-1"
            >
              <EyeOff v-if="showConfirmPassword" class="w-4 h-4" />
              <Eye v-else class="w-4 h-4" />
            </button>
          </div>
          <p v-if="passwordMismatch" class="text-xs text-red-500">Passwords do not match.</p>
        </div>

        <!-- Role + Department -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
              Role <span class="text-red-500">*</span>
            </label>
            <Select v-model="form.role">
              <SelectTrigger class="h-9 rounded-md border-black/12 text-sm focus:ring-[#2E85D8]/15 focus:border-[#2E85D8]">
                <SelectValue placeholder="Select role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Admin">Admin</SelectItem>
                <SelectItem value="Manager">Manager</SelectItem>
                <SelectItem value="Sales">Sales</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-black/55 uppercase tracking-wide">
              Department <span class="text-red-500">*</span>
            </label>
            <Select v-model="form.department">
              <SelectTrigger class="h-9 rounded-md border-black/12 text-sm focus:ring-[#2E85D8]/15 focus:border-[#2E85D8]">
                <SelectValue placeholder="Select department" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="dept in departments" :key="dept" :value="dept">
                  {{ dept }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <!-- Footer -->
        <div class="border-t border-black/6 pt-4">
          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              @click="showAddUser = false"
              class="h-9 px-4 text-sm border-black/15 text-black/60 hover:text-black"
            >
              Cancel
            </Button>
            <Button
              type="submit"
              class="h-9 px-5 text-sm bg-[#252578] hover:bg-[#2F2F73] text-white"
            >
              Add user
            </Button>
          </DialogFooter>
        </div>

      </form>
    </DialogContent>
  </Dialog>

</template>
