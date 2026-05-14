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
    component: () => import('@/layouts/AdminLayout.vue'),
    redirect: '/manager/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'manager-dashboard',
        component: () => import('@/views/manager/ManagerDashboard.vue'),
      },
    ],
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
        component: () => import('@/views/sales/SalesDashboard.vue'),
      },
    ],
  },

  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    redirect: '/admin/dashboard',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
