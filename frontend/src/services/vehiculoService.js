import { apiService } from './api'

export const vehiculoService = {
  // GET /api/vehiculos
  getAll(params = {}) {
    return apiService.get('/vehiculos', params)
  },
  
  // POST /api/vehiculos
  create(data) {
    return apiService.post('/vehiculos', data)
  },
  
  // GET /api/vehiculos/{id}
  getById(id) {
    return apiService.get(`/vehiculos/${id}`)
  },
  
  // PUT /api/vehiculos/{id}
  update(id, data) {
    return apiService.put(`/vehiculos/${id}`, data)
  },
  
  // GET /api/vehiculos/{id}/diagnosticos
  getDiagnosticos(id) {
    return apiService.get(`/vehiculos/${id}/diagnosticos`)
  },
  
  // GET /api/vehiculos/{id}/historial
  getHistorial(id) {
    return apiService.get(`/vehiculos/${id}/historial`)
  }
}