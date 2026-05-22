import { reactive, computed } from 'vue'

const AUTH_API = import.meta.env.VITE_AUTH_API_URL as string

interface User {
  id: number
  email: string
  first_name: string
  last_name: string
  role: string
  permissions: string[]
  department?: string
}

interface AuthState {
  user: User | null
  token: string | null
  isLoading: boolean
}

const state = reactive<AuthState>({
  user: JSON.parse(localStorage.getItem('user') || 'null'),
  token: localStorage.getItem('access_token'),
  isLoading: false,
})

export function useAuth() {
  const isAuthenticated = computed(() => !!state.token)
  const user = computed(() => state.user)
  // Safely extract role, handling both direct role property and nested profile.role.name
  const role = computed(() => {
    if (!state.user) return undefined
    // Handle nested profile structure (from auth-module)
    const profileRole = (state.user as any).profile?.role?.name
    return profileRole || state.user.role
  })
  
  // Robustly extract and normalize permissions from state.user
  const permissions = computed<string[]>(() => {
    const u = state.user as any
    if (!u) return []

    let rawPerms: any[] = []
    if (Array.isArray(u.permissions)) {
      rawPerms = u.permissions
    } else if (Array.isArray(u.profile?.permissions)) {
      rawPerms = u.profile.permissions
    } else if (Array.isArray(u.profile?.role?.permissions)) {
      rawPerms = u.profile.role.permissions
    } else if (Array.isArray(u.role?.permissions)) {
      rawPerms = u.role.permissions
    }

    return rawPerms.map((p: any) => {
      if (!p) return ''
      if (typeof p === 'string') return p
      return p.slug || p.name || p.key || ''
    }).filter(Boolean)
  })

  const setAuth = (user: User, token: string) => {
    state.user = user
    state.token = token
    localStorage.setItem('user', JSON.stringify(user))
    localStorage.setItem('access_token', token)
  }

  /**
   * Re-fetch the current user's permissions from the auth-service.
   * Called on layout mount so sidebar items always reflect the latest
   * role permissions without requiring a re-login.
   */
  const refreshPermissions = async () => {
    const token = state.token
    if (!token) return
    try {
      // Fetch /api/user and /api/me/permissions in parallel
      const [userRes, permRes] = await Promise.all([
        fetch(`${AUTH_API}/user`, {
          headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`,
            ...(localStorage.getItem('session_id')
              ? { 'X-Session-ID': localStorage.getItem('session_id') as string }
              : {}),
          },
        }),
        fetch(`${AUTH_API}/me/permissions`, {
          headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`,
            ...(localStorage.getItem('session_id')
              ? { 'X-Session-ID': localStorage.getItem('session_id') as string }
              : {}),
          },
        })
      ])

      if (userRes.ok && permRes.ok) {
        const freshUser = await userRes.json()
        const freshPerms = await permRes.json()

        if (state.user && freshUser) {
          // Extract permissions array robustly
          let rawPerms: any[] = []
          const permsSource = freshPerms?.permissions ?? freshUser?.permissions
          if (Array.isArray(permsSource)) {
            rawPerms = permsSource
          } else if (Array.isArray(freshUser?.profile?.permissions)) {
            rawPerms = freshUser.profile.permissions
          } else if (Array.isArray(freshUser?.profile?.role?.permissions)) {
            rawPerms = freshUser.profile.role.permissions
          } else if (Array.isArray(freshUser?.role?.permissions)) {
            rawPerms = freshUser.role.permissions
          }

          const parsedPerms = rawPerms.map((p: any) => {
            if (!p) return ''
            if (typeof p === 'string') return p
            return p.slug || p.name || p.key || ''
          }).filter(Boolean)

          // Update user in state with fresh data and the extracted permissions list
          state.user = {
            ...state.user,
            ...freshUser,
            permissions: parsedPerms
          }
          localStorage.setItem('user', JSON.stringify(state.user))
        }
      }
    } catch {
      // Silent fail — stale permissions are better than a broken page
    }
  }

  const logout = async () => {
    const token = state.token

    // 1. Clear local state INSTANTLY so the user sees no delay
    state.user = null
    state.token = null
    localStorage.removeItem('user')
    localStorage.removeItem('session_id')
    localStorage.removeItem('access_token')

    // 2. Redirect immediately
    window.location.href = '/'

    // 3. Fire and forget the API call in the background
    if (token) {
      try {
        fetch('/api/logout', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
          }
        })
      } catch (e) {
        console.error('Background API logout failed', e)
      }
    }
  }

  const hasPermission = (permission: string) => {
    // 1. Exact match
    if (permissions.value.includes(permission)) return true

    // 2. Map standard dot slugs like "crms.partners.view" to alternative formats:
    // e.g. "view-partners", "partners.view", "view_partners", "partners-view", "view partners"
    const match = permission.match(/^crms\.([^.]+)\.([^.]+)$/)
    if (match) {
      const [, resource, action] = match
      const alts = [
        `${action}-${resource}`,      // view-partners
        `${resource}.${action}`,      // partners.view
        `${action}_${resource}`,      // view_partners
        `${resource}-${action}`,      // partners-view
        `${action} ${resource}`,      // view partners
      ]
      for (const alt of alts) {
        if (permissions.value.includes(alt)) return true
      }
    }
    return false
  }

  const hasRole = (roleName: string) => {
    return role.value === roleName
  }

  return {
    state,
    isAuthenticated,
    user,
    role,
    permissions,
    setAuth,
    refreshPermissions,
    logout,
    hasPermission,
    hasRole,
  }
}
