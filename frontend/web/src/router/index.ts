import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

const routes: Array<RouteRecordRaw> = [
  // Admin Route Group
  {
    path: '/admin',
    name: 'admin',
    children: [
      {
        path: 'dashboard',
        name: 'admin-dashboard',
        component: () => import('@/views/admin/AdminDashboard.vue')
      }
    ]
  },
  
  // Manager Route Group
  {
    path: '/manager',
    name: 'manager',
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
