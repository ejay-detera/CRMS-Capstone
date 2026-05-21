<script setup lang="ts">
import { computed, ref } from 'vue'
import { Shield, Users, UserCheck, Save } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'
import RoleCards       from './RoleCards.vue'
import PermissionsPanel from './PermissionsPanel.vue'
import type { RoleKey, Category, RoleMeta, RolePermissions } from '@/types/role'

const { success } = useToast()

const categories: Category[] = [
  {
    key: 'contracts', label: 'Contracts',
    permissions: [
      { key: 'contracts.view', label: 'View' }, { key: 'contracts.create', label: 'Create' },
      { key: 'contracts.edit', label: 'Edit' }, { key: 'contracts.delete', label: 'Delete' },
    ],
  },
  {
    key: 'user_management', label: 'User Management',
    permissions: [
      { key: 'user_management.view', label: 'View' }, { key: 'user_management.create', label: 'Create' },
      { key: 'user_management.edit', label: 'Edit' }, { key: 'user_management.delete', label: 'Delete' },
    ],
  },
  {
    key: 'partners', label: 'Business Partners & Suppliers',
    permissions: [
      { key: 'partners.view', label: 'View' }, { key: 'partners.create', label: 'Create' },
      { key: 'partners.edit', label: 'Edit' }, { key: 'partners.delete', label: 'Delete' },
    ],
  },
]

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

const roleMeta: Record<RoleKey, RoleMeta> = {
  Admin:   { icon: Shield,    description: 'Full system access. Can manage users, roles, and all data.', locked: true  },
  Manager: { icon: Users,     description: 'Team monitoring, contract oversight, and reporting. Cannot manage users.', locked: false },
  Sales:   { icon: UserCheck, description: 'Limited access. Can view assigned contracts and log activities only.', locked: false },
}

const roles: RoleKey[] = ['Admin', 'Manager', 'Sales']
const permissions = ref<Record<RoleKey, RolePermissions>>(JSON.parse(JSON.stringify(defaultPermissions)))
const activeRole  = ref<RoleKey>('Admin')

const isLocked = computed(() => roleMeta[activeRole.value].locked)

const activePermissions = computed(() =>
  Object.values(permissions.value[activeRole.value]).flat()
)

const enabledCounts = computed(() => {
  const result = {} as Record<RoleKey, number>
  for (const role of roles) result[role] = Object.values(permissions.value[role]).flat().length
  return result
})

function togglePermission(categoryKey: string, permKey: string) {
  if (isLocked.value) return
  const list = permissions.value[activeRole.value][categoryKey]
  const idx  = list.indexOf(permKey)
  idx >= 0 ? list.splice(idx, 1) : list.push(permKey)
}

function toggleCategory(cat: Category) {
  if (isLocked.value) return
  const allChecked = cat.permissions.every(p => activePermissions.value.includes(p.key))
  permissions.value[activeRole.value][cat.key] = allChecked ? [] : cat.permissions.map(p => p.key)
}

function saveChanges() {
  success('Permissions saved', `${activeRole.value} role permissions have been updated.`)
}
</script>

<template>
  <div class="p-8 space-y-6">

    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-xl font-semibold text-black">Roles & Permissions</h1>
        <p class="text-sm text-black/40 mt-0.5">Configure access control for each role.</p>
      </div>
      <button @click="saveChanges"
        class="flex items-center gap-2 bg-[#252578] hover:bg-[#2F2F73] text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition-colors">
        <Save class="w-4 h-4" /> Save Changes
      </button>
    </div>

    <RoleCards
      :roles="roles"
      :role-meta="roleMeta"
      :enabled-counts="enabledCounts"
      v-model:active-role="activeRole"
    />

    <PermissionsPanel
      :active-role="activeRole"
      :is-locked="isLocked"
      :role-meta="roleMeta"
      :categories="categories"
      :active-permissions="activePermissions"
      @toggle-permission="togglePermission"
      @toggle-category="toggleCategory"
    />

  </div>
</template>
