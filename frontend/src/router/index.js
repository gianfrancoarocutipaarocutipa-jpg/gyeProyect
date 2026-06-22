import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppLayout from '@/layouts/AppLayout.vue'

const routes = [
  {
    path: '/',
    name: 'Layout',
    component: AppLayout,
    redirect: { name: 'Dashboard' },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('@/views/DashboardView.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: 'ordenes',
        name: 'Ordenes',
        component: () => import('@/views/OrdenesView.vue'),
        meta: { requiresAuth: true, roles: ['administrador', 'mecanico'] }
      },
      {
        path: 'ordenes/nueva',
        name: 'NuevaOrden',
        component: () => import('@/views/OrdenDetalleView.vue'),
        meta: { requiresAuth: true, roles: ['administrador', 'mecanico'] }
      },
      {
        path: 'ordenes/:id',
        name: 'OrdenDetalle',
        component: () => import('@/views/OrdenDetalleView.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: 'clientes',
        name: 'Clientes',
        component: () => import('@/views/ClientesView.vue'),
        meta: { requiresAuth: true, roles: ['administrador', 'mecanico'] }
      },
      {
        path: 'clientes/nuevo',
        name: 'NuevoCliente',
        component: () => import('@/views/ClientesView.vue'),
        meta: { requiresAuth: true, roles: ['administrador', 'mecanico'] }
      },
      {
        path: 'clientes/:id',
        name: 'ClienteDetalle',
        component: () => import('@/views/ClientesView.vue'),
        meta: { requiresAuth: true, roles: ['administrador', 'mecanico'] }
      },
      {
        path: 'vehiculos',
        name: 'Vehiculos',
        component: () => import('@/views/VehiculosView.vue'),
        meta: { requiresAuth: true, roles: ['administrador', 'mecanico', 'cliente'] }
      },
      {
        path: 'vehiculos/nuevo',
        name: 'NuevoVehiculo',
        component: () => import('@/views/VehiculosView.vue'),
        meta: { requiresAuth: true, roles: ['administrador', 'mecanico'] }
      },
      {
        path: 'vehiculos/:id',
        name: 'VehiculoDetalle',
        component: () => import('@/views/VehiculosView.vue'),
        meta: { requiresAuth: true, roles: ['administrador', 'mecanico'] }
      },
      {
        path: 'inventario',
        name: 'Inventario',
        component: () => import('@/views/InventarioView.vue'),
        meta: { requiresAuth: true, roles: ['administrador', 'mecanico'] }
      },
      {
        path: 'inventario/nuevo',
        name: 'NuevoRepuesto',
        component: () => import('@/views/InventarioView.vue'),
        meta: { requiresAuth: true, roles: ['administrador'] }
      },
      {
        path: 'diagnostico/:id?',
        name: 'Diagnostico',
        component: () => import('@/views/DiagnosticoView.vue'),
        meta: { requiresAuth: true, roles: ['administrador', 'mecanico'] }
      },
      {
        path: 'presupuestos',
        name: 'Presupuestos',
        component: () => import('@/views/PresupuestosView.vue'),
        meta: { requiresAuth: true, roles: ['administrador', 'mecanico'] }
      },
      {
        path: 'reportes',
        name: 'Reportes',
        component: () => import('@/views/ReportesView.vue'),
        meta: { requiresAuth: true, roles: ['administrador'] }
      },
      {
        path: 'usuarios',
        name: 'Usuarios',
        component: () => import('@/views/UsuariosView.vue'),
        meta: { requiresAuth: true, roles: ['administrador'] }
      }
    ]
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/LoginView.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/registro',
    name: 'Registro',
    component: () => import('@/views/LoginView.vue'),
    meta: { requiresAuth: false }
  },
  {
    path: '/seguimiento/:codigo?',
    name: 'SeguimientoPublico',
    component: () => import('@/views/SeguimientoPublicoView.vue'),
    meta: { requiresAuth: false }
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior: () => ({ left: 0, top: 0 })
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  const requiresAuth = to.meta.requiresAuth
  const allowedRoles = to.meta.roles

  if (requiresAuth) {
    const isAuthenticated = authStore.isAuthenticated
    if (!isAuthenticated) {
      // Redirect to login if not authenticated
      return next({ name: 'Login', query: { redirect: to.fullPath } })
    }

    // Evitar navegación si el token exige cambiar contraseña
    if (authStore.token) {
      try {
        const payload = JSON.parse(atob(authStore.token.split('.')[1]))
        if (payload.requires_password_change) {
          return next({ name: 'Login' })
        }
      } catch (e) {
        // Ignorar errores de parsing aquí
      }
    }

    // If roles specified, check user role
    if (allowedRoles && allowedRoles.length) {
      const userRole = authStore.user?.rol || authStore.userRole
      if (!allowedRoles.includes(userRole)) {
        // Unauthorized role: redirect to home or show 403
        return next({ name: 'Dashboard' })
      }
    }
  }

  // If not requiring auth or checks passed, proceed
  next()
})

export default router