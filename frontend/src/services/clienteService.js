import { apiService } from './api'

export const clienteService = {
  // GET /api/clientes
  getAll(params = {}) {
    return apiService.get('/clientes', params)
  },
  
  // POST /api/clientes
  create(data) {
    return apiService.post('/clientes', data)
  },
  
  // GET /api/clientes/{id}
  getById(id) {
    return apiService.get(`/clientes/${id}`)
  },
  
  // PUT /api/clientes/{id}
  update(id, data) {
    return apiService.put(`/clientes/${id}`, data)
  },
  
  // GET /api/clientes/{id}/vehiculos
  getVehiculos(id) {
    return apiService.get(`/clientes/${id}/vehiculos`)
  },
  
  // GET /api/clientes/{id}/historial
  getHistorial(id) {
    return apiService.get(`/clientes/${id}/historial`)
  }
}