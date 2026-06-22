import { apiService } from './api'

export const repuestoService = {
  // GET /api/repuestos
  getAll(params = {}) {
    return apiService.get('/repuestos', params)
  },
  
  // POST /api/repuestos
  create(data) {
    return apiService.post('/repuestos', data)
  },
  
  // GET /api/repuestos/{id}
  getById(id) {
    return apiService.get(`/repuestos/${id}`)
  },
  
  // PUT /api/repuestos/{id}
  update(id, data) {
    return apiService.put(`/repuestos/${id}`, data)
  },
  
  // DELETE /api/repuestos/{id}
  delete(id) {
    return apiService.delete(`/repuestos/${id}`)
  },
  
  // GET /api/repuestos/oem/{codigo}
  getByOem(codigo) {
    return apiService.get(`/repuestos/oem/${codigo}`)
  },
  
  // GET /api/repuestos/stock-bajo
  getStockBajo() {
    return apiService.get('/repuestos/stock-bajo')
  },
  
  // GET /api/repuestos/{id}/historial
  getHistorial(id) {
    return apiService.get(`/repuestos/${id}/historial`)
  }
}