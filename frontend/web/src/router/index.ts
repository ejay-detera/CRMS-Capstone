import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

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
        component: () => import('@/views/admin/AdminDashboard.vue')
      },
      {
        path: 'contracts',
        name: 'admin-contracts',
        component: () => import('@/views/admin/Contracts.vue')
      },
      {
        path: 'users',
        name: 'admin-users',
        component: () => import('@/views/admin/Users.vue')
      },
      {
        path: 'roles',
        name: 'admin-roles',
        component: () => import('@/views/admin/Roles.vue')
      },
      {
        path: 'partners',
        name: 'admin-partners',
        component: () => import('@/views/admin/Partners.vue')
      },
      {
        path: 'audit-log',
        name: 'admin-audit-log',
        component: () => import('@/views/admin/AuditLog.vue')
      },
      {
        path: 'system-config',
        name: 'admin-system-config',
        component: () => import('@/views/admin/SystemConfig.vue')
      }
    ]
  },
  
  // Manager Route Group
  {
    path: '/manager',
    name: 'manager',
    component: () => import('@/layouts/AdminLayout.vue'),
    redirect: '/manager/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'manager-dashboard',
        component: () => import('@/views/manager/ManagerDashboard.vue')
      }
    ]
  },

  // Sales Route Group
  {
    path: '/sales',
    name: 'sales',
    component: () => import('@/layouts/AdminLayout.vue'),
    redirect: '/sales/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'sales-dashboard',
        component: () => import('@/views/sales/SalesDashboard.vue')
      }
    ]
  },

  // Fallback route (redirects to 404 or can be left empty as per requirements)
  // For now, any unknown route will just render nothing or can be configured to redirect.
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    redirect: '/admin/dashboard' // Temp redirect since no root route was allowed
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
