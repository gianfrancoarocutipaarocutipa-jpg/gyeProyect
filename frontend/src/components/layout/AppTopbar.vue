<template>
  <header class="bg-slate-900/80 backdrop-blur-xl border-b border-slate-800/60 sticky top-0 z-20">
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
              <span
                class="px-3 py-2 rounded-md text-sm font-medium text-slate-400"
              >
                Dashboard
              </span>
            </div>
          </div>
        </div>

        <div class="flex items-center space-x-3">
          <!-- Notifications -->
          <div class="relative">
            <button
              class="relative p-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800/70 focus:outline-none focus:ring-2 focus:ring-cyan-500/40 transition-colors duration-200"
              @click="toggleNotificationsDropdown"
            >
              <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
              </svg>
              <!-- Badge for unread notifications -->
              <span v-if="unreadCount > 0" class="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center text-[10px] font-bold text-white bg-red-500 rounded-full ring-2 ring-slate-900 animate-pulse">
                {{ unreadCount }}
              </span>
            </button>

            <!-- Dropdown menu -->
            <transition name="dropdown">
              <div v-if="showNotificationsDropdown" class="absolute right-0 mt-2 w-72 bg-slate-800 border border-slate-700/60 rounded-xl shadow-2xl overflow-hidden z-30">
                <div class="py-1">
                  <!-- Header -->
                  <div class="px-4 py-3 text-sm font-semibold text-slate-200 border-b border-slate-700/60">
                    Notificaciones
                  </div>
                  <!-- List -->
                  <div class="max-h-64 overflow-y-auto">
                    <template v-for="notification in notifications" :key="notification.id">
                      <div
                        class="flex items-start px-4 py-3 text-sm transition-colors duration-150"
                        :class="notification.read ? 'text-slate-400' : 'text-slate-200 bg-slate-700/30'"
                      >
                        <div class="flex-shrink-0 h-2 w-2 mt-1.5 rounded-full"
                             :class="{ 'bg-red-400': notification.type === 'error', 'bg-amber-400': notification.type === 'warning', 'bg-emerald-400': notification.type === 'success', 'bg-cyan-400': notification.type === 'info' }"
                        ></div>
                        <div class="ml-3 w-0 flex-1">
                          <p>{{ notification.message }}</p>
                          <p class="mt-1 text-xs text-slate-500">
                            {{ formatDate(notification.createdAt || notification.created_at) }}
                          </p>
                        </div>
                      </div>
                    </template>
                    <div v-if="notifications.length === 0" class="px-4 py-6 text-sm text-slate-500 text-center">
                      No hay notificaciones
                    </div>
                  </div>
                  <!-- Footer -->
                  <div class="px-4 py-2.5 text-sm text-center border-t border-slate-700/60">
                    <button class="font-medium text-cyan-400 hover:text-cyan-300 transition-colors" @click="markAllAsRead">
                      Marcar todo como leído
                    </button>
                  </div>
                </div>
              </div>
            </transition>
          </div>

          <!-- User Menu -->
          <div class="relative ml-1">
            <div>
              <button @click="toggleUserMenu" class="flex max-w-xs items-center space-x-2 rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-300 hover:text-slate-100 hover:bg-slate-800/70 focus:outline-none focus:ring-2 focus:ring-cyan-500/40 transition-colors duration-200" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                <span class="sr-only">Open user menu</span>
                <div class="h-8 w-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white text-xs font-bold">
                  {{ userInitials }}
                </div>
                <span class="hidden md:block">
                  {{ userName }}
                </span>
                <svg class="hidden md:block w-4 h-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
              </button>
            </div>

            <transition name="dropdown">
              <div v-if="showUserMenu" class="absolute right-0 z-30 mt-2 w-48 bg-slate-800 border border-slate-700/60 rounded-xl shadow-2xl overflow-hidden">
                <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button">
                  <!-- Profile link -->
                  <a href="#" class="block px-4 py-2.5 text-sm text-slate-300 hover:bg-slate-700/50 hover:text-slate-100 transition-colors" role="menuitem">
                    Mi Perfil
                  </a>
                  <!-- Sign out -->
                  <button @click="logout" class="block w-full text-left px-4 py-2.5 text-sm text-slate-300 hover:bg-red-500/15 hover:text-red-400 transition-colors" role="menuitem">
                    Cerrar Sesión
                  </button>
                </div>
              </div>
            </transition>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
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
  showUserMenu.value = false
}

function toggleUserMenu() {
  showUserMenu.value = !showUserMenu.value
  showNotificationsDropdown.value = false
}

// Computed properties
const unreadCount = computed(() => {
  return notificacionesStore.notifications.filter(n => !n.read).length
})

const notifications = computed(() => {
  return notificacionesStore.notifications
})

const userName = computed(() => {
  if (!authStore.user?.nombre || !authStore.user?.apellido) return ''
  return `${authStore.user.nombre} ${authStore.user.apellido}`.trim()
})

const userInitials = computed(() => {
  const name = authStore.user?.nombre || ''
  const apellido = authStore.user?.apellido || ''
  return `${name.charAt(0)}${apellido.charAt(0)}`.toUpperCase()
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

// Close dropdowns when route changes
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
.dropdown-enter-active {
  transition: all 0.15s ease-out;
}
.dropdown-leave-active {
  transition: all 0.1s ease-in;
}
.dropdown-enter-from {
  opacity: 0;
  transform: scale(0.95) translateY(-5px);
}
.dropdown-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(-5px);
}
</style>