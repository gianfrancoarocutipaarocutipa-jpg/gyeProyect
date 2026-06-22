import { apiService } from './api'

export const diagnosticoService = {
  create: (data) => apiService.post('/diagnosticos', data),
  getByOrden: (ordenId) => apiService.get(`/diagnosticos/orden/${ordenId}`),
  parsearTramaHex: (tramaHex) => apiService.post('/diagnosticos/parsear-trama', { trama_hex: tramaHex }),
  interpretarCodigo: (codigo) => apiService.get(`/diagnosticos/interpretar/${codigo}`)
}