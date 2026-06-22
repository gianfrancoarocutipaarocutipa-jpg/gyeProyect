import { apiService } from './api'

export const presupuestoService = {
  getByOrden(ordenId) {
    return apiService.get(`/presupuestos/orden/${ordenId}`)
  },

  create(data) {
    return apiService.post('/presupuestos', data)
  },

  responder(id, data) {
    return apiService.put(`/presupuestos/${id}/respuesta`, data)
  }
}