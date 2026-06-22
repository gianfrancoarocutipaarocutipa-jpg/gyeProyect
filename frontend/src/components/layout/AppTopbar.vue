<template>
  <header class="bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <img
              src="/logo.png"
              alt="G&E Motors Logo"
              class="h-8 w-auto"
            />
          </div>
          <div class="hidden md:block">
            <div class="ml-10 flex items-baseline space-x-4">
              <!-- Current route name for active state -->
              <a
                href="#"
                class="px-3 py-2 rounded-md text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
                aria-current="page"
              >
                Dashboard
              </a>
            </div>
          </div>
        </div>

        <div class="flex items-center space-x-4">
          <!-- Notifications -->
          <div class="relative">
            <button
              class="relative p-2 rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
              @click="toggleNotificationsDropdown"
            >
              <span class="absolute -inset-0.5"></span>
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L10 9.586l1.293-1.293a1 1 0 00-1.414-1.414L10 7.293z" clip-rule="evenodd" />
              </svg>
              <!-- Badge for unread notifications -->
              <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center text-xs font-bold text-white bg-red-500 rounded-full">
                {{ unreadCount }}
              </span>
            </button>

            <!-- Dropdown menu -->
            <div v-if="showNotificationsDropdown" class="absolute right-0 mt-2 w-56 origin-top-right bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-20 transform scale-95 opacity-0 transition-transform transition-opacity duration-200"
                 :class="{ 'scale-100 opacity-100': showNotificationsDropdown }">
              <div class="py-1">
                <!-- Header -->
                <div class="px-4 py-2 text-sm font-medium text-gray-700 border-b border-gray-200">
                  Notificaciones
                </div>
                <!-- List -->
                <div class="space-y-1">
                  <template v-for="notification in notifications" :key="notification.id">
                    <div
                      class="flex items-start px-4 py-2 text-sm text-gray-700"
                      :class="{ 'bg-gray-50': !notification.read }"
                    >
                      <div class="flex-shrink-0 h-3 w-3"
                           :class="{ 'bg-red-500': notification.type === 'error', 'bg-yellow-500': notification.type === 'warning', 'bg-green-500': notification.type === 'success', 'bg-blue-500': notification.type === 'info' }"
                      ></div>
                      <div class="ml-3 w-0 flex-1">
                        <p class="">{{ notification.message }}</p>
                        <p class="mt-1 text-xs text-gray-500">
                          {{ formatDate(notification.createdAt || notification.created_at) }}
                        </p>
                      </div>
                    </div>
                  </template>
                  <div v-if="notifications.length === 0" class="px-4 py-2 text-sm text-gray-500">
                    No hay notificaciones
                  </div>
                </div>
                <!-- Footer -->
                <div class="px-4 py-2 text-sm text-center text-gray-500 border-t border-gray-200">
                  <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500" @click="markAllAsRead">
                    Marcado como leído
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- User Menu -->
          <div class="relative ml-3">
            <div>
              <button @click="toggleUserMenu" class="flex max-w-xs items-center space-x-2 rounded-md pl-3 pr-2 py-2 text-left text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                <span class="sr-only">Open user menu</span>
                <img class="h-8 w-8 rounded-full" :src="userPhoto" alt="" />
                <span class="hidden md:block">
                  {{ userName }}
                </span>
              </button>
            </div>

            <div v-if="showUserMenu" class="absolute right-0 z-10 mt-2 w-48 origin-top-right bg-white ring-1 ring-black ring-opacity-5 focus:outline-none" :class="{ 'scale-95 opacity-0 transform': !showUserMenu, 'scale-100 opacity-100': showUserMenu }" transition>
              <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button">
                <!-- Profile link -->
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                  Mi Perfil
                </a>
                <!-- Sign out -->
                <button @click="logout" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                  Cerrar Sesión
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotificacionesStore } from '@/stores/notificaciones'

const router = useRouter()
const authStore = useAuthStore()
const notificacionesStore = useNotificacionesStore()

// State for dropdowns
const showNotificationsDropdown = ref(false)
const showUserMenu = ref(false)

// Toggle functions
function toggleNotificationsDropdown() {
  showNotificationsDropdown.value = !showNotificationsDropdown.value
  // Close user menu if open
  showUserMenu.value = false
}

function toggleUserMenu() {
  showUserMenu.value = !showUserMenu.value
  // Close notifications dropdown if open
  showNotificationsDropdown.value = false
}

// Close dropdowns when clicking outside
// We can handle this with a global click listener, but for simplicity we'll rely on the user clicking the button again.
// In a real app, you'd add a click listener on the document.

// Computed properties
const unreadCount = computed(() => {
  return notificacionesStore.notifications.filter(n => !n.read).length
})

const notifications = computed(() => {
  return notificacionesStore.notifications
})

const userPhoto = computed(() => {
  // In a real app, you might have a user photo URL
  // For now, we'll use a placeholder or initials
  return '/user-placeholder.png'
})

const userName = computed(() => {
  if (!authStore.user?.nombre || !authStore.user?.apellido) return ''
  return `${authStore.user.nombre} ${authStore.user.apellido}`.trim()
})

// Format date for display
function formatDate(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  if (isNaN(date.getTime())) return ''
  const options = { hour: '2-digit', minute: '2-digit' }
  return date.toLocaleTimeString(undefined, options)
}

// Mark all notifications as read
function markAllAsRead() {
  notificacionesStore.notifications.forEach(n => {
    n.read = true
  })
}

// Logout function
function logout() {
  authStore.logout()
  router.push({ name: 'Login' })
}

// Close dropdowns when navigating away
// We can use a router afterEach hook, but we'll do it in the component for simplicity.
// Alternatively, we can close dropdowns when the route changes by watching the $route.
// Since we are in a setup script, we can use watch from vue.
import { watch } from 'vue'
import { useRoute } from 'vue-router'
const route = useRoute()

watch(
  () => route.fullPath,
  () => {
    showNotificationsDropdown.value = false
    showUserMenu.value = false
  }
)
</script>

<style scoped>
/* You can add scoped styles if needed */
</style>