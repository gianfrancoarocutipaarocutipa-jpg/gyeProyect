import { apiService } from './api'

const apiBase = import.meta.env.VITE_API_BASE || 'http://localhost:8000/api'

export const reporteService = {
  getIngresos(params = {}) {
    return apiService.get('/reportes/ingresos', params)
  },

  getProductividad(params = {}) {
    return apiService.get('/reportes/productividad', params)
  },

  getRotacionRepuestos() {
    return apiService.get('/reportes/rotacion-repuestos')
  },

  getTiempoPromedio() {
    return apiService.get('/reportes/tiempo-promedio')
  },

  generarPdf(ordenId, tipo) {
    return apiService.get(`/reportes/pdf/orden/${ordenId}`, { tipo })
  },

  async exportarExcelInventario() {
    const response = await fetch(`${apiBase}/reportes/excel/inventario`, {
      headers: { Authorization: `Bearer ${apiService.getToken()}` }
    })
    if (!response.ok) {
      let msg = 'Error al exportar inventario'
      try {
        const err = await response.json()
        msg = err.mensaje || msg
      } catch (_) { /* response was not JSON */ }
      throw new Error(msg)
    }
    return response.blob()
  },

  async exportarPdfInventario() {
    const response = await fetch(`${apiBase}/reportes/pdf/inventario`, {
      headers: { Authorization: `Bearer ${apiService.getToken()}` }
    })
    if (!response.ok) {
      let msg = 'Error al exportar inventario a PDF'
      try {
        const err = await response.json()
        msg = err.mensaje || msg
      } catch (_) { /* response was not JSON */ }
      throw new Error(msg)
    }
    return response.blob()
  }
}