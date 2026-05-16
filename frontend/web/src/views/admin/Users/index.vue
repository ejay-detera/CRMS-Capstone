<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Plus, Upload } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import * as XLSX from 'xlsx'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/useAuth'
import UsersTable       from './UsersTable.vue'
import ViewProfileDialog from './ViewProfileDialog.vue'
import AddUserDialog    from './AddUserDialog.vue'
import EditUserDialog   from './EditUserDialog.vue'
import DeleteUserDialog from './DeleteUserDialog.vue'
import type { User, Role, Status } from '@/types/user'

const { success, error } = useToast()

const users = ref<User[]>([
  { id: 'USR-001', name: 'Sarah Jenkins',    email: 'sarah.j@sbsi.com',    role: 'Admin',   status: 'Active',   dateAdded: 'June 23, 2026'  },
  { id: 'USR-002', name: 'Marcus Chen',      email: 'm.chen@sbsi.com',      role: 'Manager', status: 'Active',   dateAdded: 'June 23, 2026'  },
  { id: 'USR-003', name: 'Elena Rodriguez',  email: 'e.rodriguez@sbsi.com', role: 'Sales',   status: 'Inactive', dateAdded: 'May 10, 2026'   },
  { id: 'USR-004', name: 'David Kim',        email: 'd.kim@sbsi.com',       role: 'Admin',   status: 'Active',   dateAdded: 'May 4, 2026'    },
  { id: 'USR-005', name: 'Jessica Williams', email: 'j.williams@sbsi.com',  role: 'Sales',   status: 'Active',   dateAdded: 'Apr 18, 2026'   },
  { id: 'USR-006', name: 'Michael Brown',    email: 'm.brown@sbsi.com',     role: 'Manager', status: 'Active',   dateAdded: 'Apr 1, 2026'    },
  { id: 'USR-007', name: 'Anna Reyes',       email: 'a.reyes@sbsi.com',     role: 'Sales',   status: 'Active',   dateAdded: 'Mar 15, 2026'   },
  { id: 'USR-008', name: 'James Torres',     email: 'j.torres@sbsi.com',    role: 'Manager', status: 'Active',   dateAdded: 'Mar 2, 2026'    },
  { id: 'USR-009', name: 'Patricia Lim',     email: 'p.lim@sbsi.com',       role: 'Sales',   status: 'Inactive', dateAdded: 'Feb 20, 2026'   },
  { id: 'USR-010', name: 'Robert Navarro',   email: 'r.navarro@sbsi.com',   role: 'Admin',   status: 'Active',   dateAdded: 'Jan 8, 2026'    },
  { id: 'USR-011', name: 'Lydia Santos',     email: 'l.santos@sbsi.com',    role: 'Sales',   status: 'Active',   dateAdded: 'Jan 3, 2026'    },
  { id: 'USR-012', name: 'Kevin Park',       email: 'k.park@sbsi.com',      role: 'Manager', status: 'Active',   dateAdded: 'Dec 12, 2025'   },
])

const statCards = computed(() => [
  { label: 'All users', value: users.value.length,                                   change: '+4.0%', positive: true  },
  { label: 'Admins',    value: users.value.filter(u => u.role === 'Admin').length,    change: '+2.1%', positive: true  },
  { label: 'Managers',  value: users.value.filter(u => u.role === 'Manager').length,  change: '-0.3%', positive: false },
  { label: 'Sales',     value: users.value.filter(u => u.role === 'Sales').length,    change: '+5.2%', positive: true  },
])

type TabValue = 'all' | Role
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

const currentPage  = ref(1)
const itemsPerPage = 8
watch([activeTab, searchQuery], () => { currentPage.value = 1 })

const paginatedUsers = computed(() =>
  filteredUsers.value.slice((currentPage.value - 1) * itemsPerPage, currentPage.value * itemsPerPage)
)

const selectedIds = ref<string[]>([])
const allPageSelected = computed(() =>
  paginatedUsers.value.length > 0 &&
  paginatedUsers.value.every(u => selectedIds.value.includes(u.id))
)
function toggleSelectAll() {
  const ids = paginatedUsers.value.map(u => u.id)
  if (allPageSelected.value) selectedIds.value = selectedIds.value.filter(id => !ids.includes(id))
  else ids.forEach(id => { if (!selectedIds.value.includes(id)) selectedIds.value.push(id) })
}
function toggleRow(id: string) {
  const i = selectedIds.value.indexOf(id)
  i >= 0 ? selectedIds.value.splice(i, 1) : selectedIds.value.push(id)
}

const showViewProfile = ref(false)
const viewTarget      = ref<User | null>(null)
function openViewProfile(user: User) { viewTarget.value = user; showViewProfile.value = true }

const showEditUser = ref(false)
const editTarget   = ref<User | null>(null)
function openEditUser(user: User) { editTarget.value = user; showEditUser.value = true }

const showDeleteConfirm = ref(false)
const deleteTarget      = ref<User | null>(null)
function openDeleteConfirm(user: User) { deleteTarget.value = user; showDeleteConfirm.value = true }

const showAddUser = ref(false)

function avatarIndex(id: string) {
  return users.value.findIndex(u => u.id === id)
}

async function handleAdd(data: any) {
  const { state } = useAuth()

  try {
    const response = await fetch('http://localhost:8001/api/admin/users', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${state.token}`,
        'Accept': 'application/json'
      },
      body: JSON.stringify(data)
    })

    const result = await response.json()

    if (response.ok) {
      const newUser = {
        id: result.user.id || `USR-${Math.random().toString(36).substr(2, 3).toUpperCase()}`,
        name: `${data.first_name} ${data.last_name}`,
        email: data.email,
        role: data.role_name as Role,
        status: 'Active',
        dateAdded: new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }),
      }
      users.value.push(newUser)
      success('User created', `${newUser.name} has been added successfully.`)
    } else {
      error('Creation failed', result.message || 'Failed to create user.')
    }
  } catch (err) {
    console.error('Network error creating user:', err)
    error('Network Error', 'Could not connect to the server.')
  }
}

function handleEdit(data: { id: string; name: string; email: string; role: Role; status: Status }) {
  const idx = users.value.findIndex(u => u.id === data.id)
  if (idx < 0) return
  users.value[idx] = { ...users.value[idx], name: data.name, email: data.email, role: data.role, status: data.status }
  success('User updated', `${data.name}'s details have been saved.`)
}

function confirmDelete() {
  if (!deleteTarget.value) return
  const { id, name } = deleteTarget.value
  users.value       = users.value.filter(u => u.id !== id)
  selectedIds.value = selectedIds.value.filter(s => s !== id)
  showDeleteConfirm.value = false
  deleteTarget.value      = null
  success('User deleted', `${name} has been removed.`)
}

function exportXLSX() {
  function splitName(full: string) {
    const parts = full.trim().split(/\s+/)
    return { firstName: parts[0] ?? '', lastName: parts.length > 1 ? parts[parts.length - 1] : '', middleName: parts.length > 2 ? parts.slice(1, -1).join(' ') : '' }
  }
  const rows = users.value.map(u => {
    const { firstName, middleName, lastName } = splitName(u.name)
    return { 'First Name': firstName, 'Last Name': lastName, 'Middle Name': middleName, 'Email': u.email, 'Role': u.role, 'Department': 'Sales Department', 'Status': u.status }
  })
  const ws = XLSX.utils.json_to_sheet(rows)
  ws['!cols'] = [{ wch: 18 }, { wch: 18 }, { wch: 18 }, { wch: 30 }, { wch: 12 }, { wch: 20 }, { wch: 10 }]
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Users')
  XLSX.writeFile(wb, 'sbsi-users.xlsx')
  success('Export complete', `${users.value.length} users exported to sbsi-users.xlsx`)
}
</script>

<template>
  <div class="p-8 space-y-6">

    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-xl font-semibold text-black">User management</h1>
        <p class="text-sm text-black/40 mt-0.5">Manage all users in the system.</p>
      </div>
      <div class="flex items-center gap-2">
        <Button @click="exportXLSX" variant="outline" class="h-9 gap-2 text-sm font-medium border-black/15 text-black/65 hover:text-black">
          <Upload class="w-4 h-4" /> Export XLSX
        </Button>
        <Button @click="showAddUser = true" class="h-9 w-9 p-0 bg-[#252578] hover:bg-[#2F2F73] text-white rounded-lg shadow-sm">
          <Plus class="w-5 h-5" />
        </Button>
      </div>
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
      <div v-for="card in statCards" :key="card.label" class="bg-white rounded-lg border border-black/8 px-6 py-5 shadow-sm">
        <p class="text-xs font-medium text-black/40 mb-3 uppercase tracking-wide">{{ card.label }}</p>
        <div class="flex items-end justify-between gap-2">
          <span class="text-3xl font-semibold text-black tabular-nums">{{ card.value }}</span>
          <span class="text-xs font-medium px-2 py-0.5 rounded-md mb-0.5 shrink-0"
            :class="card.positive ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'">
            {{ card.change }}
          </span>
        </div>
      </div>
    </div>

    <UsersTable
      :paginated-users="paginatedUsers"
      :filtered-users="filteredUsers"
      :selected-ids="selectedIds"
      :all-page-selected="allPageSelected"
      :current-page="currentPage"
      :items-per-page="itemsPerPage"
      :active-tab="activeTab"
      :search-query="searchQuery"
      @open-view-profile="openViewProfile"
      @open-edit-user="openEditUser"
      @open-delete-confirm="openDeleteConfirm"
      @toggle-row="toggleRow"
      @toggle-select-all="toggleSelectAll"
      @update:current-page="currentPage = $event"
      @update:active-tab="activeTab = $event"
      @update:search-query="searchQuery = $event"
    />

  </div>

  <ViewProfileDialog
    v-model:open="showViewProfile"
    :user="viewTarget"
    :avatar-index="viewTarget ? avatarIndex(viewTarget.id) : 0"
    @edit="u => { showViewProfile = false; openEditUser(u) }"
  />
  <AddUserDialog    v-model:open="showAddUser"         @submit="handleAdd" />
  <EditUserDialog   v-model:open="showEditUser"  :user="editTarget"   @submit="handleEdit" />
  <DeleteUserDialog v-model:open="showDeleteConfirm" :user="deleteTarget"
    :avatar-index="deleteTarget ? avatarIndex(deleteTarget.id) : 0"
    @confirm="confirmDelete" />
</template>
