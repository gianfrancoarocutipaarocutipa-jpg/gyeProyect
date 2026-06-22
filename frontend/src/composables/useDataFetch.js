import { ref } from 'vue'
import { useNotificacionesStore } from '@/stores/notificaciones'

/**
 * Composable para gestión unificada de peticiones de datos
 * @param {Function} serviceFn - Función que retorna una promesa (ej: servicio.getDatos)
 * @returns {Object} Objeto con propiedades loading, data, error y función execute que acepta argumentos
 */
export function useDataFetch(serviceFn) {
  const loading = ref(false)
  const data = ref(null)
  const error = ref(null)

  const execute = async (...args) => {
    loading.value = true
    error.value = null
    try {
      const result = await serviceFn(...args)
      data.value = result
      return result
    } catch (err) {
      error.value = err
      // Notificar error mediante el store centralizado
      const notificacionesStore = useNotificacionesStore()
      notificacionesStore.addNotification({
        type: 'error',
        message: err.message || 'Error al cargar los datos',
        timeout: 5000
      })
      // Re-lanzar el error para que el llamador pueda manejarlo si es necesario
      throw err
    } finally {
      loading.value = false
    }
  }

  return { loading, data, error, execute }
}