<template>
  <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-md space-y-8">
      <div>
        <img src="/logo.png" alt="G&E Motors" class="mx-auto h-12 w-auto" />
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          {{ requiresPasswordChange ? 'Cambio Obligatorio de Contraseña' : 'Iniciar Sesión' }}
        </h2>
      </div>

      <form v-if="!requiresPasswordChange" class="mt-8 space-y-6" @submit.prevent="handleLogin">
        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="email-address" class="sr-only">Correo electrónico</label>
            <input
              id="email-address"
              v-model="form.email"
              type="email"
              required
              class="appearance-none rounded-t-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              :class="{ 'border-red-500': errors.email }"
              placeholder="Correo electrónico"
            />
          </div>
          <div>
            <label for="password" class="sr-only">Contraseña</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              class="appearance-none rounded-b-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              :class="{ 'border-red-500': errors.password }"
              placeholder="Contraseña"
            />
          </div>
        </div>

        <div v-if="errors.general" class="text-sm text-red-600 text-center">
          {{ errors.general }}
        </div>

        <BaseButton variant="primary" size="lg" class="w-full" :loading="loading" type="submit">
          Iniciar Sesión
        </BaseButton>
      </form>

      <form v-else class="mt-8 space-y-6" @submit.prevent="handleChangePassword">
        <p class="text-sm text-gray-600 text-center">
          Por razones de seguridad, debes actualizar tu contraseña temporal antes de continuar.
        </p>
        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="password-actual" class="sr-only">Contraseña Actual (Temporal)</label>
            <input
              id="password-actual"
              v-model="changePasswordForm.password_actual"
              type="password"
              required
              class="appearance-none rounded-t-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              placeholder="Contraseña Actual (Temporal)"
            />
          </div>
          <div>
            <label for="password-nuevo" class="sr-only">Nueva Contraseña</label>
            <input
              id="password-nuevo"
              v-model="changePasswordForm.password_nuevo"
              type="password"
              required
              class="appearance-none rounded-b-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              placeholder="Nueva Contraseña"
            />
          </div>
        </div>

        <div v-if="errors.general" class="text-sm text-red-600 text-center">
          {{ errors.general }}
        </div>

        <BaseButton variant="primary" size="lg" class="w-full" :loading="changePasswordLoading" type="submit">
          Guardar y Entrar
        </BaseButton>
        <div class="text-center mt-2">
          <button type="button" @click="cancelPasswordChange" class="text-sm text-indigo-600 hover:text-indigo-500">
            Cancelar e intentar con otra cuenta
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotificacionesStore } from '@/stores/notificaciones'
import { useDataFetch } from '@/composables/useDataFetch'
import { authService } from '@/services/authService'
import BaseButton from '@/components/shared/BaseButton.vue'

const router = useRouter()
const authStore = useAuthStore()
const notificacionesStore = useNotificacionesStore()

const form = reactive({ email: '', password: '' })
const changePasswordForm = reactive({ password_actual: '', password_nuevo: '' })
const requiresPasswordChange = ref(false)
const loading = ref(false)
const errors = ref({})

// Composable for login request
const { loading: loginLoading, execute: loginRequest } =
  useDataFetch((payload) => authService.login(payload))

// Composable for change password request
const { loading: changePasswordLoading, execute: changePasswordRequest } =
  useDataFetch((payload) => authService.cambiarPassword(payload))

async function handleLogin() {
  loading.value = true
  errors.value = {}

  try {
    // Execute the login request via the composable
    const response = await loginRequest(form)
    
    if (response.data.requires_password_change) {
      // Configurar estado para cambio de contraseña
      requiresPasswordChange.value = true
      changePasswordForm.password_actual = form.password
      
      // Guardar token temporalmente en la aplicación pero no redireccionar
      localStorage.setItem('gem_motors_token', response.data.token)
      authStore.setToken(response.data.token)
      loading.value = false
      
      notificacionesStore.addNotification({
        type: 'warning',
        message: 'Debe cambiar su contraseña para continuar',
        timeout: 5000
      })
      return
    }

    // Update the auth store with the response data
    authStore.token = response.data.token
    authStore.user = response.data.usuario
    authStore.isAuthenticated = true
    localStorage.setItem('gem_motors_token', response.data.token)

    const redirect = router.currentRoute.value.query.redirect || '/'
    router.push(redirect)
  } catch (err) {
    // Error already handled by composable for notifications, but we need to set field errors if any
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors
    } else {
      errors.value = { general: err.message || 'Credenciales inválidas' }
    }

    // Show a general notification (the composable already shows one, but we can also show a specific one if needed)
    notificacionesStore.addNotification({
      type: 'error',
      message: errors.value.general || 'Error al intentar iniciar sesión',
      timeout: 5000
    })
  } finally {
    loading.value = false
  }
}

async function handleChangePassword() {
  errors.value = {}

  try {
    const response = await changePasswordRequest(changePasswordForm)
    
    // Update the auth store with the new token
    authStore.token = response.data.token
    authStore.user = response.data.usuario
    authStore.isAuthenticated = true
    localStorage.setItem('gem_motors_token', response.data.token)

    notificacionesStore.addNotification({
      type: 'success',
      message: 'Contraseña actualizada correctamente',
      timeout: 3000
    })

    const redirect = router.currentRoute.value.query.redirect || '/'
    router.push(redirect)
  } catch (err) {
    if (err.response?.data?.error) {
      errors.value = { general: err.response.data.error }
    } else {
      errors.value = { general: err.message || 'Error al cambiar la contraseña' }
    }
  }
}

function cancelPasswordChange() {
  requiresPasswordChange.value = false
  changePasswordForm.password_actual = ''
  changePasswordForm.password_nuevo = ''
  authStore.logout()
}
</script>