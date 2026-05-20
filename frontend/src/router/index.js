import { createRouter, createWebHistory } from 'vue-router'
import { useAdminStore } from '@/stores/admin'
import { useBarberStore } from '@/stores/barber'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // ===== مسارات المدير =====
    {
      path: '/admin',
      children: [
        {
          path: '',
          redirect: '/admin/login',
        },
        {
          path: 'login',
          name: 'AdminLogin',
          component: () => import('@/views/admin/Login.vue'),
          meta: { guest: true, role: 'admin' },
        },
        {
          path: 'dashboard',
          name: 'AdminDashboard',
          component: () => import('@/views/admin/Dashboard.vue'),
          meta: { requiresAuth: true, role: 'admin' },
        },
      ],
    },

    // ===== مسارات الحلاق =====
    {
      path: '/barber',
      children: [
        {
          path: '',
          redirect: '/barber/login',
        },
        {
          path: 'login',
          name: 'BarberLogin',
          component: () => import('@/views/barber/Login.vue'),
          meta: { guest: true, role: 'barber' },
        },
        {
          path: 'dashboard',
          name: 'BarberDashboard',
          component: () => import('@/views/barber/Dashboard.vue'),
          meta: { requiresAuth: true, role: 'barber' },
        },
        {
          path: 'services',
          name: 'BarberServices',
          component: () => import('@/views/barber/Services.vue'),
          meta: { requiresAuth: true, role: 'barber' },
        },
        {
          path: 'qr',
          name: 'BarberQR',
          component: () => import('@/views/barber/QRCode.vue'),
          meta: { requiresAuth: true, role: 'barber' },
        },
      ],
    },

    // ===== مسارات الزبون =====
    {
      path: '/shop/:barberId',
      name: 'ShopPage',
      component: () => import('@/views/customer/ShopPage.vue'),
    },
    {
      path: '/queue/:entryId',
      name: 'QueueStatus',
      component: () => import('@/views/customer/QueueStatus.vue'),
    },
    {
      path: '/queue/:entryId/confirm',
      name: 'QueueConfirm',
      component: () => import('@/views/customer/QueueStatus.vue'),
    },

    // الصفحة الرئيسية
    {
      path: '/',
      name: 'Home',
      redirect: '/barber/login',
    },

    // 404
    {
      path: '/:pathMatch(.*)*',
      name: 'NotFound',
      component: () => import('@/views/NotFound.vue'),
    },
  ],
})

// حراسة المسارات
router.beforeEach((to, from, next) => {
  const adminStore = useAdminStore()
  const barberStore = useBarberStore()

  if (to.meta.requiresAuth) {
    if (to.meta.role === 'admin' && !adminStore.isLoggedIn) {
      return next('/admin/login')
    }
    if (to.meta.role === 'barber' && !barberStore.isLoggedIn) {
      return next('/barber/login')
    }
  }

  if (to.meta.guest) {
    if (to.meta.role === 'admin' && adminStore.isLoggedIn) {
      return next('/admin/dashboard')
    }
    if (to.meta.role === 'barber' && barberStore.isLoggedIn) {
      return next('/barber/dashboard')
    }
  }

  next()
})

export default router
