<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Shield, Users, UserCheck, Save, Loader2, AlertCircle } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'
import { useRolePermissions, UI_CATEGORIES } from '@/composables/useRolePermissions'
import RoleCards       from './RoleCards.vue'
import PermissionsPanel from './PermissionsPanel.vue'
import type { ApiRole, RoleMeta } from '@/types/role'

const { success, error: toastError } = useToast()

const {
  roles,
  allPermissions,
  rolePermissionIds,
  isLoading,
  error,
  init,
  togglePermission,
  toggleCategory,
  saveRolePermissions,
  getActivePermIds,
} = useRolePermissions()

// ── Role meta (icon + description + locked flag) keyed by role name ──
const ROLE_META: Record<string, RoleMeta> = {
  'Admin':        { icon: Shield,    description: 'Full system access. Can manage users, roles, and all data.', locked: true  },
  'Manager':      { icon: Users,     description: 'Team monitoring, contract oversight, and reporting. Cannot manage users.', locked: false },
  'Sales':        { icon: UserCheck, description: 'Limited access. Can view assigned contracts and log activities only.', locked: false },
  'Super Admin':  { icon: Shield,    description: 'System-wide access across all modules.', locked: true  },
  'IT Admin':     { icon: Shield,    description: 'IT infrastructure and user management.', locked: false },
  'Finance':      { icon: Users,     description: 'Finance staff access.',  locked: false },
  'Employee':     { icon: UserCheck, description: 'Regular staff access.',  locked: false },
}

function roleMeta(role: ApiRole): RoleMeta {
  return ROLE_META[role.name] ?? { icon: UserCheck, description: role.description ?? '', locked: false }
}

// ── Active role ───────────────────────────────────────────────────────
const activeRole = ref<ApiRole | null>(null)

const activeRoleMeta = computed(() =>
  activeRole.value ? roleMeta(activeRole.value) : null
)

const isLocked = computed(() => activeRoleMeta.value?.locked ?? false)

// ── Enabled permission counts per role (for the role cards) ───────────
const enabledCounts = computed(() => {
  const result: Record<number, number> = {}
  for (const role of roles.value) {
    result[role.id] = getActivePermIds(role.id).length
  }
  return result
})

// ── Save ──────────────────────────────────────────────────────────────
const isSaving = ref(false)

async function saveChanges() {
  if (!activeRole.value || isLocked.value) return
  isSaving.value = true
  try {
    await saveRolePermissions(activeRole.value.id)
    success('Permissions saved', `${activeRole.value.name} role permissions have been updated.`)
  } catch (e: any) {
    toastError?.('Save failed', e.message ?? 'Could not update permissions.')
  } finally {
    isSaving.value = false
  }
}

// ── Toggle handlers (passed into PermissionsPanel) ────────────────────
function handleTogglePermission(_categoryKey: string, permKey: string) {
  if (!activeRole.value || isLocked.value) return
  const perm = allPermissions.value.find(p => p.slug === permKey)
  if (perm) togglePermission(activeRole.value.id, perm.id)
}

function handleToggleCategory(cat: typeof UI_CATEGORIES[number]) {
  if (!activeRole.value || isLocked.value) return
  const roleId = activeRole.value.id
  const set = rolePermissionIds.value[roleId]
  const catPerms = cat.permissions
    .map(p => allPermissions.value.find(ap => ap.slug === p.key))
    .filter(Boolean) as typeof allPermissions.value
  const allOn = catPerms.every(p => set?.has(p.id))
  toggleCategory(roleId, cat.key, allOn)
}

// ── Active permissions as slugs — reads directly from rolePermissionIds ──
// Uses the Set reference inside the record so Vue tracks it reactively.
const activePermSlugs = computed(() => {
  if (!activeRole.value) return []
  // Access the Set directly so Vue tracks mutations on the record
  const set = rolePermissionIds.value[activeRole.value.id]
  if (!set || set.size === 0) return []
  return allPermissions.value
    .filter(p => set.has(p.id))
    .map(p => p.slug)
})

// ── Initialise ────────────────────────────────────────────────────────
onMounted(async () => {
  await init()
  // Default to first role
  if (roles.value.length > 0) activeRole.value = roles.value[0]
})
</script>

<template>
  <div class="p-8 space-y-6">

    <!-- Header -->
    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-xl font-semibold text-black">Roles &amp; Permissions</h1>
        <p class="text-sm text-black/40 mt-0.5">Configure access control for each role.</p>
      </div>
      <button
        @click="saveChanges"
        :disabled="isSaving || isLocked || isLoading"
        class="flex items-center gap-2 bg-[#252578] hover:bg-[#2F2F73] disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition-colors"
      >
        <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
        <Save v-else class="w-4 h-4" />
        {{ isSaving ? 'Saving…' : 'Save Changes' }}
      </button>
    </div>

    <!-- Loading skeleton -->
    <template v-if="isLoading">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div v-for="i in 3" :key="i"
          class="h-28 rounded-xl border border-black/8 bg-black/3 animate-pulse" />
      </div>
      <div class="h-64 rounded-xl border border-black/8 bg-black/3 animate-pulse" />
    </template>

    <!-- Error state -->
    <div v-else-if="error"
      class="flex items-center gap-3 p-4 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm">
      <AlertCircle class="w-4 h-4 shrink-0" />
      <span>{{ error }}</span>
      <button @click="init" class="ml-auto font-semibold underline text-xs">Retry</button>
    </div>

    <!-- Loaded state -->
    <template v-else-if="roles.length">
      <RoleCards
        :roles="roles"
        :role-meta-fn="roleMeta"
        :enabled-counts="enabledCounts"
        :active-role="activeRole"
        @update:active-role="activeRole = $event"
      />

      <PermissionsPanel
        v-if="activeRole && activeRoleMeta"
        :active-role-name="activeRole.name"
        :is-locked="isLocked"
        :active-role-meta="activeRoleMeta"
        :categories="UI_CATEGORIES"
        :active-permissions="activePermSlugs"
        @toggle-permission="handleTogglePermission"
        @toggle-category="handleToggleCategory"
      />
    </template>

  </div>
</template>
