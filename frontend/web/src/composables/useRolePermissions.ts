import { ref } from 'vue'
import type { ApiRole, ApiPermission, Category } from '@/types/role'

const AUTH_API = import.meta.env.VITE_AUTH_API_URL as string

// ── Slug → UI translation map ──────────────────────────────────────────
// Maps each DB permission slug to the frontend category key and pill label.
// Add new slugs here when new CRMS permissions are seeded.
const SLUG_UI_MAP: Record<string, { category: string; label: string }> = {
  // Contracts
  'crms.contracts.view':   { category: 'contracts', label: 'View' },
  'crms.contracts.create': { category: 'contracts', label: 'Create' },
  'crms.contracts.edit':   { category: 'contracts', label: 'Edit' },
  'crms.contracts.delete': { category: 'contracts', label: 'Delete' },

  // Business Partners & Suppliers
  'crms.partners.view':   { category: 'partners', label: 'View' },
  'crms.partners.create': { category: 'partners', label: 'Create' },
  'crms.partners.edit':   { category: 'partners', label: 'Edit' },
  'crms.partners.delete': { category: 'partners', label: 'Delete' },
}

// The fixed UI category structure (labels stay exactly as designed)
export const UI_CATEGORIES: Category[] = [
  {
    key: 'contracts',
    label: 'Contracts',
    permissions: [
      { key: 'crms.contracts.view',   label: 'View' },
      { key: 'crms.contracts.create', label: 'Create' },
      { key: 'crms.contracts.edit',   label: 'Edit' },
      { key: 'crms.contracts.delete', label: 'Delete' },
    ],
  },
  {
    key: 'partners',
    label: 'Business Partners & Suppliers',
    permissions: [
      { key: 'crms.partners.view',   label: 'View' },
      { key: 'crms.partners.create', label: 'Create' },
      { key: 'crms.partners.edit',   label: 'Edit' },
      { key: 'crms.partners.delete', label: 'Delete' },
    ],
  },
]

// ── Helpers ───────────────────────────────────────────────────────────
function authHeaders(): HeadersInit {
  const token = localStorage.getItem('access_token')
  const sessionId = localStorage.getItem('session_id')
  return {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    ...(sessionId ? { 'X-Session-ID': sessionId } : {}),
  }
}

async function apiFetch<T>(path: string, options?: RequestInit): Promise<T> {
  const res = await fetch(`${AUTH_API}${path}`, {
    ...options,
    headers: { ...authHeaders(), ...options?.headers },
  })
  if (!res.ok) {
    const body = await res.json().catch(() => ({}))
    throw new Error(body?.message ?? `HTTP ${res.status}`)
  }
  return res.json() as Promise<T>
}

// ── Composable ────────────────────────────────────────────────────────
export function useRolePermissions() {
  const roles           = ref<ApiRole[]>([])
  const allPermissions  = ref<ApiPermission[]>([])
  // rolePermissionIds[roleId] = Set of permission IDs currently assigned
  const rolePermissionIds = ref<Record<number, Set<number>>>({})
  const isLoading       = ref(false)
  const error           = ref<string | null>(null)

  /** Convert a DB permission slug to its UI category key (for SLUG_UI_MAP members) */
  function slugToCategory(slug: string): string | null {
    return SLUG_UI_MAP[slug]?.category ?? null
  }

  /** Load roles list */
  async function fetchRoles() {
    const data = await apiFetch<ApiRole[]>('/admin/roles')
    roles.value = data
  }

  /** Load all available permissions */
  async function fetchAllPermissions() {
    const data = await apiFetch<ApiPermission[]>('/admin/permissions')
    // Only keep CRMS CRUD permissions that appear in the UI map
    allPermissions.value = data.filter(p => SLUG_UI_MAP[p.slug] !== undefined)
  }

  /** Load permissions already assigned to a single role */
  async function fetchRolePermissions(roleId: number) {
    const ids = await apiFetch<number[]>(`/admin/roles/${roleId}/permissions`)
    // Filter to only include IDs that exist in allPermissions (which contains only crms slugs)
    const allowedIds = new Set(allPermissions.value.map(p => p.id))
    const validIds = ids.filter(id => allowedIds.has(id))
    rolePermissionIds.value[roleId] = new Set(validIds)
  }

  /** Initialise: load everything in parallel */
  async function init() {
    isLoading.value = true
    error.value = null
    try {
      await Promise.all([fetchRoles(), fetchAllPermissions()])
      await Promise.all(roles.value.map(r => fetchRolePermissions(r.id)))
    } catch (e: any) {
      error.value = e.message ?? 'Failed to load permissions'
    } finally {
      isLoading.value = false
    }
  }

  /** Toggle a single permission for a role (local, optimistic) */
  function togglePermission(roleId: number, permId: number) {
    const set = rolePermissionIds.value[roleId] ??= new Set()
    set.has(permId) ? set.delete(permId) : set.add(permId)
    // Replace the inner Set AND the top-level object so all computed
    // properties (enabledCounts, activePermSlugs) re-evaluate.
    rolePermissionIds.value = {
      ...rolePermissionIds.value,
      [roleId]: new Set(set),
    }
  }

  /** Toggle an entire category for a role (local, optimistic) */
  function toggleCategory(roleId: number, categoryKey: string, allOn: boolean) {
    const categoryPerms = allPermissions.value.filter(
      p => SLUG_UI_MAP[p.slug]?.category === categoryKey
    )
    const set = new Set(rolePermissionIds.value[roleId] ?? [])
    for (const p of categoryPerms) {
      allOn ? set.delete(p.id) : set.add(p.id)
    }
    // Replace both the inner Set and the top-level object for full reactivity
    rolePermissionIds.value = {
      ...rolePermissionIds.value,
      [roleId]: set,
    }
  }

  /** Persist current state for a role to the backend */
  async function saveRolePermissions(roleId: number): Promise<void> {
    const permIds = [...(rolePermissionIds.value[roleId] ?? new Set())]
    await apiFetch(`/admin/roles/${roleId}/permissions`, {
      method: 'POST',
      body: JSON.stringify({ permissions: permIds }),
    })
    // Re-sync from server so pill state and count badge always match reality
    await fetchRolePermissions(roleId)
    rolePermissionIds.value = { ...rolePermissionIds.value }
  }

  /** Get the active permission IDs for a role as an array (for prop passing) */
  function getActivePermIds(roleId: number): number[] {
    return [...(rolePermissionIds.value[roleId] ?? new Set())]
  }

  /** Look up a DB permission ID by its slug */
  function permIdBySlug(slug: string): number | undefined {
    return allPermissions.value.find(p => p.slug === slug)?.id
  }

  return {
    roles,
    allPermissions,
    rolePermissionIds,
    isLoading,
    error,
    UI_CATEGORIES,
    init,
    fetchRoles,
    fetchAllPermissions,
    fetchRolePermissions,
    togglePermission,
    toggleCategory,
    saveRolePermissions,
    getActivePermIds,
    permIdBySlug,
    slugToCategory,
  }
}
