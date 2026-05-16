import { reactive, computed } from 'vue'

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
  const permissions = computed(() => state.user?.permissions || [])

  const setAuth = (user: User, token: string) => {
    state.user = user
    state.token = token
    localStorage.setItem('user', JSON.stringify(user))
    localStorage.setItem('access_token', token)
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
    return permissions.value.includes(permission)
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
    logout,
    hasPermission,
    hasRole,
  }
}
