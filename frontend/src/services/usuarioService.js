import { apiService } from './api'

export const usuarioService = {
  getAll: () => apiService.get('/usuarios'),
  
  create: (data) => apiService.post('/usuarios', data),
  
  update: (id, data) => apiService.put(`/usuarios/${id}`, data),
  
  delete: (id) => apiService.delete(`/usuarios/${id}`),
  
  perfil: () => apiService.get('/auth/perfil'),
  
  getCargaMecanicos: () => apiService.get('/usuarios/carga-mecanicos')
}