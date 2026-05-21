import { createRouter, createWebHistory, type RouteRecordRaw, type RouteLocationNormalized } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'

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
        component: () => import('@/views/admin/AdminDashboard.vue'),
      },
      {
        path: 'contracts',
        name: 'admin-contracts',
        component: () => import('@/views/admin/Contracts/index.vue'),
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
        path: 'contract-requests',
        name: 'manager-contract-requests',
        component: () => import('@/views/manager/ContractRequests/index.vue'),
      },
      {
        path: 'notifications',
        name: 'manager-notifications',
        component: () => import('@/views/manager/Notifications/index.vue'),
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
        path: 'contract-requests',
        name: 'sales-contract-requests',
        component: () => import('@/views/sales/ContractRequests/index.vue'),
      },
      {
        path: 'notifications',
        name: 'sales-notifications',
        component: () => import('@/views/sales/Notifications/index.vue'),
      },
      {
        path: 'profile',
        name: 'sales-profile',
        component: () => import('@/views/sales/Profile/index.vue'),
      },
    ],
  },

  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    redirect: () => {
      const { role } = useAuth()
      if (role.value === 'Admin') return '/admin/dashboard'
      if (role.value === 'Manager') return '/manager/dashboard'
      if (role.value === 'Sales' || role.value === 'Employee') return '/sales/dashboard'
      
      // Fallback: escape CRMS router entirely and go to auth-module login
      window.location.href = '/'
      return '/'
    },
  },
]

const router = createRouter({
  history: createWebHistory('/crms/'),
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
  // CRMS-capstone only supports 3 roles: Admin, Manager, and Sales (or Employee)
  const allowedRoles = ['Admin', 'Manager', 'Sales', 'Employee']

  // If the user's role isn't recognized by CRMS (e.g., IT Admin, Super Admin), block them entirely
  if (!allowedRoles.includes(role.value || '')) {
    return { name: 'not-found' }
  }

  // Admin can ONLY access /admin
  if (to.path.startsWith('/admin') && role.value !== 'Admin') {
    return { name: 'not-found' }
  }

  // Manager can ONLY access /manager
  if (to.path.startsWith('/manager') && role.value !== 'Manager') {
    return { name: 'not-found' }
  }

  // Sales/Employee can ONLY access /sales
  if (to.path.startsWith('/sales') && !['Sales', 'Employee'].includes(role.value || '')) {
    return { name: 'not-found' }
  }

  return true
})

export default router
