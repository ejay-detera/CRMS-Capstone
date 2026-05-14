<script setup lang="ts">
import { ref, computed } from 'vue'
import { Shield, Users, UserCheck, Save } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

const { success } = useToast()

// ── Types ──────────────────────────────────────────────────────────
type RoleKey = 'Admin' | 'Manager' | 'Sales'

interface Permission {
  key: string
  label: string
}

interface Category {
  key: string
  label: string
  permissions: Permission[]
}

interface RolePermissions {
  [categoryKey: string]: string[]
}

// ── Permission categories ───────────────────────────────────────────
const categories: Category[] = [
  {
    key: 'contracts',
    label: 'Contracts',
    permissions: [
      { key: 'contracts.view',   label: 'View'   },
      { key: 'contracts.create', label: 'Create' },
      { key: 'contracts.edit',   label: 'Edit'   },
      { key: 'contracts.delete', label: 'Delete' },
    ],
  },
  {
    key: 'user_management',
    label: 'User Management',
    permissions: [
      { key: 'user_management.view',   label: 'View'   },
      { key: 'user_management.create', label: 'Create' },
      { key: 'user_management.edit',   label: 'Edit'   },
      { key: 'user_management.delete', label: 'Delete' },
    ],
  },
  {
    key: 'partners',
    label: 'Business Partners & Suppliers',
    permissions: [
      { key: 'partners.view',   label: 'View'   },
      { key: 'partners.create', label: 'Create' },
      { key: 'partners.edit',   label: 'Edit'   },
      { key: 'partners.delete', label: 'Delete' },
    ],
  },
]

// ── Default permissions per role ────────────────────────────────────
const defaultPermissions: Record<RoleKey, RolePermissions> = {
  Admin: {
    contracts:       ['contracts.view', 'contracts.create', 'contracts.edit', 'contracts.delete'],
    user_management: ['user_management.view', 'user_management.create', 'user_management.edit', 'user_management.delete'],
    partners:        ['partners.view', 'partners.create', 'partners.edit', 'partners.delete'],
  },
  Manager: {
    contracts:       ['contracts.view', 'contracts.create', 'contracts.edit'],
    user_management: ['user_management.view'],
    partners:        ['partners.view', 'partners.create', 'partners.edit'],
  },
  Sales: {
    contracts:       ['contracts.view'],
    user_management: [],
    partners:        ['partners.view'],
  },
}

// Deep clone so edits don't mutate defaults
function clonePerms(src: Record<RoleKey, RolePermissions>): Record<RoleKey, RolePermissions> {
  return JSON.parse(JSON.stringify(src))
}

const permissions = ref<Record<RoleKey, RolePermissions>>(clonePerms(defaultPermissions))
const activeRole  = ref<RoleKey>('Admin')

// ── Role meta ───────────────────────────────────────────────────────
const roleMeta: Record<RoleKey, { icon: typeof Shield; description: string; locked: boolean }> = {
  Admin:   { icon: Shield,    description: 'Full system access. Can manage users, roles, and all data.', locked: true  },
  Manager: { icon: Users,     description: 'Team monitoring, contract oversight, and reporting. Cannot manage users.', locked: false },
  Sales:   { icon: UserCheck, description: 'Limited access. Can view assigned contracts and log activities only.', locked: false },
}

const roles: RoleKey[] = ['Admin', 'Manager', 'Sales']

// ── Helpers ─────────────────────────────────────────────────────────
function enabledCount(role: RoleKey): number {
  return Object.values(permissions.value[role]).flat().length
}

function hasPermission(role: RoleKey, permKey: string): boolean {
  return Object.values(permissions.value[role]).flat().includes(permKey)
}

function togglePermission(role: RoleKey, categoryKey: string, permKey: string) {
  if (roleMeta[role].locked) return
  const list = permissions.value[role][categoryKey]
  const idx  = list.indexOf(permKey)
  if (idx >= 0) list.splice(idx, 1)
  else list.push(permKey)
}

function isCategoryAllChecked(role: RoleKey, cat: Category): boolean {
  return cat.permissions.every(p => hasPermission(role, p.key))
}

function toggleCategory(role: RoleKey, cat: Category) {
  if (roleMeta[role].locked) return
  if (isCategoryAllChecked(role, cat)) {
    permissions.value[role][cat.key] = []
  } else {
    permissions.value[role][cat.key] = cat.permissions.map(p => p.key)
  }
}

const isLocked = computed(() => roleMeta[activeRole.value].locked)

function saveChanges() {
  success('Permissions saved', `${activeRole.value} role permissions have been updated.`)
}
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- ── Header ─────────────────────────────────────────────────── -->
    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-xl font-semibold text-black">Roles & Permissions</h1>
        <p class="text-sm text-black/40 mt-0.5">Configure access control for each role.</p>
      </div>
      <button
        @click="saveChanges"
        class="flex items-center gap-2 bg-[#252578] hover:bg-[#2F2F73] text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition-colors"
      >
        <Save class="w-4 h-4" />
        Save Changes
      </button>
    </div>

    <!-- ── Role Cards ──────────────────────────────────────────────── -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <button
        v-for="role in roles"
        :key="role"
        @click="activeRole = role"
        class="text-left p-5 rounded-xl border transition-all duration-200"
        :class="activeRole === role
          ? 'bg-[#252578]/5 border-[#252578]/30 ring-1 ring-[#252578]/20'
          : 'bg-white border-black/8 hover:border-black/15'"
      >
        <div class="flex items-center gap-3 mb-3">
          <div
            class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
            :class="activeRole === role ? 'bg-[#252578]/10' : 'bg-black/5'"
          >
            <component :is="roleMeta[role].icon" class="w-4.5 h-4.5"
              :class="activeRole === role ? 'text-[#252578]' : 'text-black/40'" />
          </div>
          <span
            class="text-xs font-semibold px-2.5 py-1 rounded-full border"
            :class="activeRole === role
              ? 'bg-[#252578]/8 text-[#252578] border-[#252578]/20'
              : 'bg-black/4 text-black/50 border-black/8'"
          >
            {{ role.toUpperCase() }}
          </span>
        </div>
        <p class="text-xs text-black/50 leading-relaxed mb-3">{{ roleMeta[role].description }}</p>
        <p class="text-xs font-semibold" :class="activeRole === role ? 'text-[#252578]' : 'text-black/40'">
          {{ enabledCount(role) }} permissions enabled
        </p>
      </button>
    </div>

    <!-- ── Permissions Panel ───────────────────────────────────────── -->
    <div class="bg-white rounded-xl border border-black/8 shadow-sm overflow-hidden">

      <!-- Panel header -->
      <div class="px-6 py-4 border-b border-black/5 flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-[#252578]/8">
          <component :is="roleMeta[activeRole].icon" class="w-4 h-4 text-[#252578]" />
        </div>
        <div>
          <h2 class="text-sm font-semibold text-black">{{ activeRole }} Role Permissions</h2>
          <p class="text-xs text-black/40 mt-0.5">
            {{ isLocked ? `${activeRole} has all permissions (cannot be restricted)` : `Customize what ${activeRole} users can access` }}
          </p>
        </div>
        <span v-if="isLocked" class="ml-auto text-xs font-medium text-amber-600 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-full">
          Locked
        </span>
      </div>

      <!-- Categories -->
      <div class="divide-y divide-black/5">
        <div v-for="cat in categories" :key="cat.key" class="px-6 py-5">

          <!-- Category header -->
          <div class="flex items-center justify-between mb-4">
            <span class="text-[11px] font-semibold text-black/40 uppercase tracking-wider">{{ cat.label }}</span>
            <button
              v-if="!isLocked"
              @click="toggleCategory(activeRole, cat)"
              class="text-xs font-medium transition-colors"
              :class="isCategoryAllChecked(activeRole, cat)
                ? 'text-[#2E85D8] hover:text-[#252578]'
                : 'text-black/35 hover:text-black/60'"
            >
              {{ isCategoryAllChecked(activeRole, cat) ? 'Deselect all' : 'Select all' }}
            </button>
          </div>

          <!-- Permission checkboxes -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <button
              v-for="perm in cat.permissions"
              :key="perm.key"
              @click="togglePermission(activeRole, cat.key, perm.key)"
              :disabled="isLocked"
              class="flex items-center justify-between gap-2 px-4 py-3 rounded-lg border text-left transition-all duration-150"
              :class="hasPermission(activeRole, perm.key)
                ? 'bg-[#252578]/6 border-[#252578]/20 text-[#252578]'
                : 'bg-black/2 border-black/8 text-black/40 hover:border-black/15'"
            >
              <div class="flex items-center gap-2.5">
                <!-- Checkbox visual -->
                <div
                  class="w-4 h-4 rounded flex items-center justify-center shrink-0 border transition-colors"
                  :class="hasPermission(activeRole, perm.key)
                    ? 'bg-[#252578] border-[#252578]'
                    : 'bg-white border-black/20'"
                >
                  <svg v-if="hasPermission(activeRole, perm.key)" viewBox="0 0 10 8" class="w-2.5 h-2.5 fill-none stroke-white stroke-[1.8]">
                    <polyline points="1,4 3.5,6.5 9,1" />
                  </svg>
                </div>
                <span class="text-sm font-medium">{{ perm.label }}</span>
              </div>
              <!-- Lock icon for admin -->
              <svg v-if="isLocked" viewBox="0 0 24 24" class="w-3.5 h-3.5 shrink-0 fill-none stroke-current opacity-40 stroke-[1.8]">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
              </svg>
            </button>
          </div>

        </div>
      </div>

    </div>
  </div>
</template>
