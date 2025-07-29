import { createRouter, createWebHistory } from 'vue-router'
import ClientsView from '@/views/ClientsView.vue'
import { isAuthenticated } from '@/utils/auth'
import { canViewAllUsers, canViewAllClients, canViewAuditLogs } from '@/utils/permissions'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'dashboard',
      component: () => import('../views/DashboardView.vue'),
      meta: { title: 'Панель управления', requiresAuth: true },
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue'),
      meta: { title: 'Вход в систему' },
    },
    {
      path: '/users',
      name: 'users',
      component: () => import('../views/UsersView.vue'),
      meta: { title: 'Пользователи', requiresAuth: true },
    },
    {
      path: '/clients',
      name: 'clients',
      component: () => import('../views/ClientsView.vue'),
      meta: { title: 'Клиенты', requiresAuth: true },
    },
    {
      path: '/projects',
      name: 'projects',
      component: () => import('../views/ProjectsView.vue'),
      meta: { title: 'Проекты', requiresAuth: true },
    },
    {
      path: '/products',
      name: 'products',
      component: () => import('../views/ProductsView.vue'),
      meta: { title: 'Товары', requiresAuth: true },
    },
    {
      path: '/orders',
      name: 'orders',
      component: () => import('../views/OrdersView.vue'),
      meta: { title: 'Заказы', requiresAuth: true },
    },
    {
      path: '/audit-logs',
      name: 'audit-logs',
      component: () => import('../views/AuditLogsView.vue'),
      meta: { title: 'Аудит-логи', requiresAuth: true },
    },
    {
      path: '/stages',
      name: 'stages',
      component: () => import('../views/StagesView.vue'),
      meta: { title: 'Управление стадиями', requiresAuth: true },
    },
    {
      path: '/roles',
      name: 'roles',
      component: () => import('../views/RolesView.vue'),
      meta: { title: 'Управление ролями', requiresAuth: true },
    },
  ],
})

// Authentication and authorization guard
router.beforeEach((to, from, next) => {
  const authenticated = isAuthenticated()

  // If route requires authentication and user is not authenticated
  if (to.meta.requiresAuth && !authenticated) {
    next({ name: 'login' })
  }
  // If user is authenticated and trying to access login page
  else if (to.name === 'login' && authenticated) {
    next({ name: 'dashboard' })
  }
  // Check role-based access
  else if (to.name === 'users' && !canViewAllUsers()) {
    next({ name: 'dashboard' })
  } else if (to.name === 'clients' && !canViewAllClients()) {
    next({ name: 'dashboard' })
  } else if (to.name === 'audit-logs' && !canViewAuditLogs()) {
    next({ name: 'dashboard' })
  }
  // Allow access to all other routes
  else {
    next()
  }
})

router.afterEach((to) => {
  const defaultTitle = 'Панель управления'
  document.title = (to.meta.title as string) || defaultTitle
})

export default router
