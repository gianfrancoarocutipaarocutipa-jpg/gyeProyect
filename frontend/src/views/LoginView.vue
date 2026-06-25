<template>
  <div class="min-h-screen bg-slate-950 flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Background decoration -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-cyan-500/5 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-blue-500/5 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-md space-y-8 relative z-10">
      <div class="text-center">
        <img src="/logo.png" alt="G&E Motors" class="mx-auto h-14 w-auto" />
        <h2 class="mt-6 text-3xl font-extrabold text-slate-100">
          {{ requiresPasswordChange ? 'Cambio Obligatorio de Contraseña' : 'Iniciar Sesión' }}
        </h2>
        <p class="mt-2 text-sm text-slate-500">
          Sistema de Gestión — G&E Motors
        </p>
      </div>

      <form v-if="!requiresPasswordChange" class="mt-8 space-y-6" @submit.prevent="handleLogin">
        <div class="space-y-4">
          <div>
            <label for="email-address" class="block text-sm font-medium text-slate-400 mb-1.5">Correo electrónico</label>
            <input
              id="email-address"
              v-model="form.email"
              type="email"
              required
              class="input-dark"
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/30': errors.email }"
              placeholder="tu@correo.com"
            />
          </div>
          <div>
            <label for="password" class="block text-sm font-medium text-slate-400 mb-1.5">Contraseña</label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              required
              class="input-dark"
              :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/30': errors.password }"
              placeholder="••••••••"
            />
          </div>
        </div>

        <div v-if="errors.general" class="text-sm text-red-400 text-center bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">
          {{ errors.general }}
        </div>

        <BaseButton variant="primary" size="lg" class="w-full" :loading="loading" type="submit">
          Iniciar Sesión
        </BaseButton>
      </form>

      <form v-else class="mt-8 space-y-6" @submit.prevent="handleChangePassword">
        <div class="text-sm text-slate-400 text-center bg-amber-500/10 border border-amber-500/20 rounded-lg px-4 py-3">
          Por razones de seguridad, debes actualizar tu contraseña temporal antes de continuar.
        </div>
        <div class="space-y-4">
          <div>
            <label for="password-actual" class="block text-sm font-medium text-slate-400 mb-1.5">Contraseña Actual (Temporal)</label>
            <input
              id="password-actual"
              v-model="changePasswordForm.password_actual"
              type="password"
              required
              class="input-dark"
              placeholder="Contraseña temporal"
            />
          </div>
          <div>
            <label for="password-nuevo" class="block text-sm font-medium text-slate-400 mb-1.5">Nueva Contraseña</label>
            <input
              id="password-nuevo"
              v-model="changePasswordForm.password_nuevo"
              type="password"
              required
              class="input-dark"
              placeholder="Tu nueva contraseña"
            />
          </div>
        </div>

        <div v-if="errors.general" class="text-sm text-red-400 text-center bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">
          {{ errors.general }}
        </div>

        <BaseButton variant="primary" size="lg" class="w-full" :loading="changePasswordLoading" type="submit">
          Guardar y Entrar
        </BaseButton>
        <div class="text-center mt-2">
          <button type="button" @click="cancelPasswordChange" class="text-sm text-cyan-400 hover:text-cyan-300 transition-colors">
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
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors
    } else {
      errors.value = { general: err.message || 'Credenciales inválidas' }
    }

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