import { defineStore } from 'pinia'
import { authService } from '@/services/authService'
import { ref, computed } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  // State
  const token = ref(null)
  const user = ref(null)
  const isAuthenticated = ref(false)

  // Getters
  const userRole = computed(() => {
    if (!user.value) return null
    return user.value.rol
  })

  const userName = computed(() => {
    if (!user.value) return ''
    return `${user.value.nombre} ${user.value.apellido}`
  })

  // Actions
  async function login(credentials) {
    try {
      const response = await authService.login(credentials)
      token.value = response.data.token
      user.value = response.data.usuario
      isAuthenticated.value = true
      localStorage.setItem('gem_motors_token', response.data.token)
      return response
    } catch (error) {
      throw error
    }
  }

  async function registro(userData) {
    try {
      const response = await authService.registro(userData)
      token.value = response.data.token
      user.value = response.data.usuario
      isAuthenticated.value = true
      localStorage.setItem('gem_motors_token', response.data.token)
      return response
    } catch (error) {
      throw error
    }
  }

  async function fetchProfile() {
    if (!token.value) return
    try {
      const response = await authService.perfil()
      user.value = response.data
      return response
    } catch (error) {
      logout()
      throw error
    }
  }

  function logout() {
    token.value = null
    user.value = null
    isAuthenticated.value = false
    localStorage.removeItem('gem_motors_token')
  }

  function setToken(newToken) {
    token.value = newToken
    if (newToken) {
      try {
        const payload = JSON.parse(atob(newToken.split('.')[1]))
        user.value = {
          id: payload.id,
          rol: payload.rol,
          nombre: payload.nombre || '',
          apellido: payload.apellido || ''
        }
        isAuthenticated.value = true
      } catch (e) {
        logout()
      }
    } else {
      logout()
    }
  }

  function initializeFromLocalStorage() {
    const storedToken = localStorage.getItem('gem_motors_token')
    if (storedToken) {
      token.value = storedToken
      isAuthenticated.value = true
      fetchProfile().catch(() => {
        logout()
      })
    }
  }

  return {
    token,
    user,
    isAuthenticated,
    userRole,
    userName,
    login,
    registro,
    fetchProfile,
    logout,
    setToken,
    initializeFromLocalStorage
  }
})