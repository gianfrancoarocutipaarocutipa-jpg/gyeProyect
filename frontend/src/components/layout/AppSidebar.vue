<template>
  <aside
    class="z-30 w-64 bg-white transition-transform duration-300 ease-in-out fixed inset-y-0 left-0 transform sm:sticky sm:top-0 sm:h-screen sm:translate-x-0 sm:shadow-none sm:border-r sm:border-gray-200 flex flex-col"
    :class="{ 'translate-x-0': isSidebarOpen, '-translate-x-full': !isSidebarOpen }"
  >
    <div class="flex flex-col h-full pt-6">
      <!-- Logo -->
      <div class="flex items-center px-4">
        <img
          src="/logo.png"
          alt="G&E Motors Logo"
          class="h-8 w-auto"
        />
        <span class="ml-2 text-xl font-bold text-gray-800">G&E Motors</span>
      </div>

      <!-- Navigation -->
      <nav class="mt-6 space-y-1">
        <template v-for="item in menuItems" :key="item.name">
          <router-link
            v-if="item.route"
            :to="item.route"
            class="flex items-center px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900"
          >
            <template v-if="item.icon">
              <!-- You can replace with actual icon component -->
              <span class="mr-3">[{{ item.icon }}]</span>
            </template>
            <span>{{ item.name }}</span>
          </router-link>

          <div
            v-else
            class="flex items-center px-3 py-2 rounded-md text-base font-medium text-gray-500 cursor-not-allowed"
          >
            <template v-if="item.icon">
              <span class="mr-3">[{{ item.icon }}]</span>
            </template>
            <span>{{ item.name }}</span>
          </div>
        </template>
      </nav>

      <!-- Spacer -->
      <div class="mt-auto"></div>

      <!-- Logout Button -->
      <div class="px-4 pb-4">
        <BaseButton
          variant="outline"
          size="sm"
          class="w-full"
          @click="logout"
        >
          Cerrar Sesión
        </BaseButton>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotificacionesStore } from '@/stores/notificaciones'
import BaseButton from '@/components/shared/BaseButton.vue'

const router = useRouter()
const authStore = useAuthStore()
const notificacionesStore = useNotificacionesStore()

// Sidebar state
const isSidebarOpen = ref(true)

// Menu items with roles
const allMenuItems = [
  { name: 'Dashboard', route: '/', icon: '📊', roles: ['administrador', 'mecanico', 'cliente'] },
  { name: 'Órdenes', route: '/ordenes', icon: '🔧', roles: ['administrador', 'mecanico'] },
  { name: 'Clientes', route: '/clientes', icon: '👥', roles: ['administrador', 'mecanico'] },
  { name: 'Vehículos', route: '/vehiculos', icon: '🚗', roles: ['administrador', 'mecanico'] },
  { name: 'Inventario', route: '/inventario', icon: '📦', roles: ['administrador', 'mecanico'] },
  { name: 'Diagnóstico', route: '/diagnostico', icon: '🔍', roles: ['administrador', 'mecanico'] },
  { name: 'Presupuestos', route: '/presupuestos', icon: '💰', roles: ['administrador'] },
  { name: 'Reportes', route: '/reportes', icon: '📈', roles: ['administrador'] },
  { name: 'Usuarios', route: '/usuarios', icon: '👤', roles: ['administrador'] },
  { name: 'Seguimiento Público', route: '/seguimiento', icon: '🔎', roles: ['administrador', 'cliente'] } // Note: we use a placeholder route; the actual route is /seguimiento/:codigo
]

// Filter menu items based on user role
const menuItems = computed(() => {
  const userRole = authStore.userRole
  if (!userRole) return []
  return allMenuItems.filter(item => item.roles.includes(userRole))
})

// Logout function
function logout() {
  authStore.logout()
  notificacionesStore.addNotification({
    type: 'info',
    message: 'Sesión cerrada exitosamente',
    timeout: 3000
  })
  router.push({ name: 'Login' })
}

// Handle sidebar toggle (for mobile)
function toggleSidebar() {
  isSidebarOpen.value = !isSidebarOpen.value
}
</script>

<style scoped>
/* Optional: Add any specific styles if needed */
</style>