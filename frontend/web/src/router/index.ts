import { createRouter, createWebHistory, type RouteRecordRaw, type RouteLocationNormalized } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { useLoader } from '@/composables/useLoader'

const routes: Array<RouteRecordRaw> = [
  // Admin Route Group
  {
    path: '/admin',
    name: 'admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    redirect: '/admin/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'admin-dashboard',
        component: () => import('@/views/admin/Dashboard/index.vue'),
      },
      {
        path: 'contracts',
        name: 'admin-contracts',
        component: () => import('@/views/admin/Contracts/index.vue'),
      },
      {
        path: 'contracts/create',
        name: 'admin-contracts-create',
        component: () => import('@/views/admin/Contracts/CreateContract.vue'),
      },
      {
        path: 'contracts/:id',
        name: 'admin-contracts-detail',
        component: () => import('@/views/sales/Contracts/ContractDetail/index.vue'),
      },
      {
        path: 'contracts/:id/documents/:docId',
        name: 'admin-contracts-document-view',
        component: () => import('@/views/sales/Contracts/DocumentViewer.vue'),
      },
      {
        path: 'users',
        name: 'admin-users',
        component: () => import('@/views/admin/Users/index.vue'),
      },
      {
        path: 'roles',
        name: 'admin-roles',
        component: () => import('@/views/admin/Roles/index.vue'),
      },
      {
        path: 'partners',
        name: 'admin-partners',
        component: () => import('@/views/admin/Partners/index.vue'),
      },
      {
        path: 'partners/create',
        name: 'admin-partners-create',
        component: () => import('@/views/admin/Partners/AddPartnerPage.vue'),
      },
      {
        path: 'partners/:code/edit',
        name: 'admin-partners-edit',
        component: () => import('@/views/admin/Partners/EditPartnerPage.vue'),
      },
      {
        path: 'partners/:code',
        name: 'admin-partners-detail',
        component: () => import('@/views/admin/Partners/PartnerDetail/index.vue'),
      },
      {
        path: 'notifications',
        name: 'admin-notifications',
        component: () => import('@/views/admin/Notifications/index.vue'),
      },
      {
        path: 'audit-log',
        name: 'admin-audit-log',
        component: () => import('@/views/admin/AuditLog/index.vue'),
      },
      {
        path: 'system-config',
        name: 'admin-system-config',
        component: () => import('@/views/admin/SystemConfig/index.vue'),
      },
      {
        path: 'profile',
        name: 'admin-profile',
        component: () => import('@/views/admin/Profile/index.vue'),
      },
    ],
  },

  // Manager Route Group
  {
    path: '/manager',
    name: 'manager',
    component: () => import('@/layouts/ManagerLayout.vue'),
    redirect: '/manager/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'manager-dashboard',
        component: () => import('@/views/manager/Dashboard/index.vue'),
      },
      {
        path: 'contracts',
        name: 'manager-contracts',
        component: () => import('@/views/manager/Contracts/index.vue'),
      },
      {
        path: 'contracts/create',
        name: 'manager-contracts-create',
        component: () => import('@/views/manager/Contracts/CreateContract.vue'),
      },
      {
        path: 'contracts/:id',
        name: 'manager-contracts-detail',
        component: () => import('@/views/sales/Contracts/ContractDetail/index.vue'),
      },
      {
        path: 'contracts/:id/amend',
        name: 'manager-contracts-amend',
        component: () => import('@/views/sales/ContractAmendments/CreateAmendment.vue'),
      },
      {
        path: 'contracts/:id/documents/:docId',
        name: 'manager-contracts-document-view',
        component: () => import('@/views/sales/Contracts/DocumentViewer.vue'),
      },

      {
        path: 'contract-requests',
        name: 'manager-contract-requests',
        component: () => import('@/views/manager/ContractRequests/index.vue'),
      },
      {
        path: 'contract-requests/:id',
        name: 'manager-contract-requests-detail',
        component: () => import('@/views/sales/ContractRequests/RequestDetail/index.vue'),
      },
      {
        path: 'amendment-requests',
        name: 'manager-amendment-requests',
        component: () => import('@/views/manager/AmendmentRequests/index.vue'),
      },
      {
        path: 'amendment-requests/:id',
        name: 'manager-amendment-requests-detail',
        component: () => import('@/views/manager/AmendmentRequests/RequestDetail.vue'),
      },
      {
        path: 'notifications',
        name: 'manager-notifications',
        component: () => import('@/views/manager/Notifications/index.vue'),
      },
      {
        path: 'partners',
        name: 'manager-partners',
        component: () => import('@/views/manager/Partners/index.vue'),
      },
      {
        path: 'partners/create',
        name: 'manager-partners-create',
        component: () => import('@/views/admin/Partners/AddPartnerPage.vue'),
        meta: { requiresPermission: 'cms.partners.create' },
      },
      {
        path: 'partners/:code/edit',
        name: 'manager-partners-edit',
        component: () => import('@/views/admin/Partners/EditPartnerPage.vue'),
        meta: { requiresPermission: 'cms.partners.edit' },
      },
      {
        path: 'partners/:code',
        name: 'manager-partners-detail',
        component: () => import('@/views/admin/Partners/PartnerDetail/index.vue'),
      },
      {
        path: 'profile',
        name: 'manager-profile',
        component: () => import('@/views/manager/Profile/index.vue'),
      },
    ],
  },

  // Sales Route Group
  {
    path: '/sales',
    name: 'sales',
    component: () => import('@/layouts/SalesLayout.vue'),
    redirect: '/sales/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'sales-dashboard',
        component: () => import('@/views/sales/Dashboard/index.vue'),
      },
      {
        path: 'contracts',
        name: 'sales-contracts',
        component: () => import('@/views/sales/Contracts/index.vue'),
      },
      {
        path: 'contracts/create',
        name: 'sales-contracts-create',
        component: () => import('@/views/sales/Contracts/CreateContract.vue'),
      },
      {
        path: 'contracts/:id',
        name: 'sales-contracts-detail',
        component: () => import('@/views/sales/Contracts/ContractDetail/index.vue'),
      },
      {
        path: 'contracts/:id/documents/:docId',
        name: 'sales-contracts-document-view',
        component: () => import('@/views/sales/Contracts/DocumentViewer.vue'),
      },
      {
        path: 'contract-requests',
        name: 'sales-contract-requests',
        component: () => import('@/views/sales/ContractRequests/index.vue'),
      },
      {
        path: 'contract-requests/:id',
        name: 'sales-contract-requests-detail',
        component: () => import('@/views/sales/ContractRequests/RequestDetail/index.vue'),
      },
      {
        path: 'contracts/:id/amend',
        name: 'sales-contracts-amend',
        component: () => import('@/views/sales/ContractAmendments/CreateAmendment.vue'),
      },
      {
        path: 'contract-amendments',
        name: 'sales-contract-amendments',
        component: () => import('@/views/sales/ContractAmendments/index.vue'),
      },
      {
        path: 'contract-amendments/:id',
        name: 'sales-contract-amendments-detail',
        component: () => import('@/views/sales/ContractAmendments/AmendmentDetail.vue'),
      },
      {
        path: 'notifications',
        name: 'sales-notifications',
        component: () => import('@/views/sales/Notifications/index.vue'),
      },
      {
        path: 'partners',
        name: 'sales-partners',
        component: () => import('@/views/sales/Partners/index.vue'),
        meta: { requiresPermission: 'cms.partners.view' },
      },
      {
        path: 'partners/create',
        name: 'sales-partners-create',
        component: () => import('@/views/admin/Partners/AddPartnerPage.vue'),
        meta: { requiresPermission: 'cms.partners.create' },
      },
      {
        path: 'partners/:code/edit',
        name: 'sales-partners-edit',
        component: () => import('@/views/admin/Partners/EditPartnerPage.vue'),
        meta: { requiresPermission: 'cms.partners.edit' },
      },
      {
        path: 'partners/:code',
        name: 'sales-partners-detail',
        component: () => import('@/views/admin/Partners/PartnerDetail/index.vue'),
        meta: { requiresPermission: 'cms.partners.view' },
      },
      {
        path: 'profile',
        name: 'sales-profile',
        component: () => import('@/views/sales/Profile/index.vue'),
      },
    ],
  },

  {
    path: '/auth/callback',
    name: 'auth-callback',
    alias: '/cms/auth/callback',
    redirect: (to) => {
      // The Auth module redirects here with ?state=/cms/admin/dashboard
      const state = to.query.state as string
      if (state) {
        // Strip the /cms base since the Vue router already handles it
        const targetPath = state.replace(/^\/cms/, '')
        return targetPath || '/admin/dashboard'
      }
      return '/admin/dashboard'
    }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    redirect: (to) => {
      // Intercept any mangled auth/callback URLs from the proxy
      if (to.fullPath.includes('auth/callback')) {
        console.log('%c[CMS Router] Caught mangled callback URL inside catch-all route!', 'color: #f59e0b; font-weight: bold;')
        console.log('Router Full Path:', to.fullPath)

        const match = to.fullPath.match(/state=([^&]+)/)
        const target = match ? decodeURIComponent(match[1]) : '/admin/dashboard'
        
        console.log('Extracted Target from Vue Router:', target)
        return target.replace(/^\/cms/, '') || '/admin/dashboard'
      }

      const { role } = useAuth()
      if (role.value === 'Admin') return '/admin/dashboard'
      if (role.value === 'Manager') return '/manager/dashboard'
      if (['Sales', 'Employee', 'Finance'].includes(role.value || '')) return '/sales/dashboard'

      // Fallback: escape CMS router entirely and go to auth-module login
      window.location.href = '/'
      return '/'
    },
  },
]

const router = createRouter({
  history: createWebHistory('/cms/'),
  routes,
})

router.beforeEach((to: RouteLocationNormalized) => {
  const { isAuthenticated, role } = useAuth()
  const { error } = useToast()

  const requiresAuth = to.path.startsWith('/admin') ||
    to.path.startsWith('/manager') ||
    to.path.startsWith('/sales')

  if (requiresAuth && !isAuthenticated.value) {
    try {
      throw new Error('Not authenticated')
    } catch (err) {
      const { hideLoader } = useLoader()
      hideLoader()
      error('Access Denied', 'You must log in to access this system.')
      console.warn('User not authenticated, redirecting to auth-service:', to.path)

      // Delay redirection slightly so the user can see the error toast
      setTimeout(() => {
        window.location.href = '/'
      }, 1500)

      return false
    }
  }

  // Role-based access control (Strict Role Isolation)
  // CMS-capstone only supports standard roles: Admin, Manager, Sales, Employee, Finance
  const allowedRoles = ['Admin', 'Manager', 'Sales', 'Employee', 'Finance']

  // Allow the auth callback route to bypass the role check
  if (to.path.includes('auth/callback')) {
    return true
  }

  // If the user's role isn't recognized by CMS (e.g., IT Admin, Super Admin), block them entirely
  if (!allowedRoles.includes(role.value || '')) {
    return { name: 'not-found' }
  }

  // Admin can ONLY access /admin
  if (to.path.startsWith('/admin') && role.value !== 'Admin') {
    return { name: 'not-found' }
  }

  // Manager can access /manager
  if (to.path.startsWith('/manager') && role.value !== 'Manager') {
    return { name: 'not-found' }
  }

  // Sales/Employee/Finance can access /sales
  if (to.path.startsWith('/sales') && !['Sales', 'Employee', 'Finance'].includes(role.value || '')) {
    return { name: 'not-found' }
  }

  // Permission-gated routes: redirect to dashboard if user lacks the required permission
  if (to.meta?.requiresPermission) {
    const { hasPermission } = useAuth()
    if (!hasPermission(to.meta.requiresPermission as string)) {
      const dashName = to.path.startsWith('/manager') ? 'manager-dashboard' : 'sales-dashboard'
      return { name: dashName }
    }
  }

  return true
})

let isInitialNavigation = true

router.afterEach(() => {
  if (isInitialNavigation) {
    const { hideLoader } = useLoader()
    hideLoader()
    isInitialNavigation = false
  }
})

router.onError(() => {
  if (isInitialNavigation) {
    const { hideLoader } = useLoader()
    hideLoader()
    isInitialNavigation = false
  }
})

export default router
