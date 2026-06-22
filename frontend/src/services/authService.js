import { apiService } from './api'

export const authService = {
  login(credentials) {
    return apiService.post('/auth/login', credentials)
  },
  
  registro(userData) {
    return apiService.post('/auth/registro', userData)
  },
  
  perfil() {
    return apiService.get('/auth/perfil')
  },
  
  cambiarPassword(data) {
    return apiService.put('/auth/cambiar-password', data)
  }
}