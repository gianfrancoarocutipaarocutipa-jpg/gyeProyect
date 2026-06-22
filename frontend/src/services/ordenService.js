import { apiService } from './api'

export const ordenService = {
  getAll(params = {}) {
    return apiService.get('/ordenes', params)
  },

  getById(id) {
    return apiService.get(`/ordenes/${id}`)
  },

  create(data) {
    return apiService.post('/ordenes', data)
  },

  update(id, data) {
    return apiService.put(`/ordenes/${id}`, data)
  },

  delete(id) {
    return apiService.delete(`/ordenes/${id}`)
  },

  cambiarEstado(id, data) {
    return apiService.put(`/ordenes/${id}/estado`, data)
  },

  getEstadisticas() {
    return apiService.get('/ordenes/estadisticas')
  },

  getPresupuesto(id) {
    return apiService.get(`/ordenes/${id}/presupuesto`)
  },

  getEvidencias(id) {
    return apiService.get(`/evidencias/orden/${id}`)
  },

  crearEvidencia(id, data) {
    return apiService.post(`/evidencias`, { orden_id: id, ...data })
  },

  actualizarEvidencia(evidenciaId, data) {
    return apiService.put(`/evidencias/${evidenciaId}`, data)
  },

  getRepuestos(id) {
    return apiService.get(`/ordenes/${id}/repuestos`)
  },

  agregarRepuesto(id, data) {
    return apiService.post(`/ordenes/${id}/repuestos`, data)
  },

  eliminarRepuesto(ordenId, repuestoId) {
    return apiService.delete(`/ordenes/${ordenId}/repuestos/${repuestoId}`)
  },

  eliminarEvidencia(ordenId, evidenciaId) {
    return apiService.delete(`/evidencias/${evidenciaId}`)
  },

  getPorCodigo(codigo) {
    return apiService.get(`/seguimiento/${codigo}`)
  },

  responderPresupuestoPublico(codigo, presupuestoId, data) {
    return apiService.put(`/seguimiento/${codigo}/presupuestos/${presupuestoId}/respuesta`, data)
  },

  asignarMecanico(id, data) {
    return apiService.post(`/ordenes/${id}/mecanico`, data)
  },

  actualizarHorasMecanico(id, mecanicoId, data) {
    return apiService.put(`/ordenes/${id}/mecanico/${mecanicoId}/horas`, data)
  },

  vehiculoRecibido(codigo) {
    return apiService.put(`/seguimiento/${codigo}/vehiculo-recibido`, {})
  },

  confirmarReparacion(codigo) {
    return apiService.put(`/seguimiento/${codigo}/confirmar-reparacion`, {})
  },

  actualizarDetalleReparacion(id, data) {
    return apiService.put(`/ordenes/${id}/detalle-reparacion`, data)
  }
}