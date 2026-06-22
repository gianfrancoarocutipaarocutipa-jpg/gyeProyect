<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-2xl font-bold text-gray-900">
        Registro de Diagnóstico
      </h2>
      <div class="flex flex-col items-end">
        <span v-if="selectedOrder" class="text-sm font-bold text-indigo-600">
          Orden: {{ selectedOrder.numero_ot }}
        </span>
        <span class="text-xs text-gray-500">
          ID Sistema: {{ otId || 'Sin seleccionar' }}
        </span>
      </div>
    </div>

    <!-- Selector de Orden si no viene por URL -->
    <div v-if="!otId || otId === 'undefined'" class="mb-6 p-4 bg-white rounded-lg shadow border-l-4 border-indigo-500">
      <label class="block text-sm font-medium text-gray-700 mb-2">Seleccione una Orden de Trabajo activa para diagnosticar</label>
      <select v-model="selectedOrderId" class="w-full px-3 py-2 border rounded-md" @change="handleOrderChange">
        <option value="">-- Seleccione una OT --</option>
        <option v-for="o in activeOrders" :key="o.id" :value="o.id">{{ o.numero_ot }} - {{ o.cliente?.nombre }}</option>
      </select>
    </div>

    <BaseAlert
      v-if="notificacion"
      :show="true"
      variant="success"
      :message="notificacion"
      @update:show="show => { if (!show) notificacion = null }"
    />

    <div v-if="diagnosticLoading" class="text-center py-8">
      <div class="flex items-center justify-center space-x-4">
        <div class="w-12 h-12 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
        <p class="text-indigo-600 font-medium">Procesando diagnóstico...</p>
      </div>
    </div>

    <div v-else>
      <div class="mb-6">
        <OBDReader ref="obdReaderRef" @scan-completed="handleScanCompleted" />
      </div>

      <form class="space-y-6" @submit.prevent="handleSubmit">
        <div>
          <label for="kilometraje" class="block text-sm font-medium text-gray-700 mb-1">
            Kilometraje Actual (km)
          </label>
          <input
            id="kilometraje"
            type="number"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            v-model.number="form.kilometraje"
            min="0"
          />
        </div>

        <div>
          <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-1">
            Observaciones Técnicas
          </label>
          <textarea
            id="observaciones"
            rows="4"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            v-model="form.observaciones"
            placeholder="Describa síntomas visuales, ruidos, olores u otros hallazgos..."
          ></textarea>
        </div>

        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-500">
            Códigos DTC detectados: {{ obdReaderResults.length }}
          </span>
          <button
            type="submit"
            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="saving || !form.kilometraje || !form.observaciones || !obdReaderRef?.hasRawHex"
          >
            {{ saving ? 'Guardando...' : 'Registrar Diagnóstico' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { apiService } from '@/services/api'
import { diagnosticoService } from '@/services/diagnosticoService'
import { useNotificacionesStore } from '@/stores/notificaciones'
import { useDataFetch } from '@/composables/useDataFetch'
import OBDReader from '@/components/diagnosticos/OBDReader.vue'
import BaseAlert from '@/components/shared/BaseAlert.vue'

const route = useRoute()
const notificacionesStore = useNotificacionesStore()

const otId = ref(route.params.id)
const selectedOrderId = ref('')
const activeOrders = ref([])
const selectedOrder = ref(null)
const form = reactive({ kilometraje: null, observaciones: '' })
const vehiculoId = ref(null)
const obdReaderResults = ref([])
const saving = ref(false)

const obdReaderRef = ref(null)

async function fetchOrderDetails(id) {
  try {
    const response = await apiService.get(`/ordenes/${id}`)
    if (response.success && response.data && response.data.vehiculo) {
      selectedOrder.value = response.data
      vehiculoId.value = response.data.vehiculo.id
    }
  } catch (err) {
    notificacionesStore.addNotification({ type: 'error', message: 'No se pudo obtener información de la orden' })
  }
}

async function handleOrderChange() {
  if (selectedOrderId.value) {
    otId.value = selectedOrderId.value
    await fetchOrderDetails(selectedOrderId.value)
  }
}

function handleScanCompleted() {
  const km = obdReaderRef.value?.kilometrajeSimulador
  if (km !== null && km !== undefined) {
    form.kilometraje = km
  }
}

// Cargar datos de la orden al montar para obtener el vehiculo_id
onMounted(async () => {
  if (otId.value && otId.value !== 'undefined') {
    await fetchOrderDetails(otId.value)
  } else {
    // Cargar órdenes en estado diagnóstico para poder seleccionar una
    const response = await apiService.get('/ordenes', { estado: 'diagnostico' })
    if (response.success) {
      activeOrders.value = response.data
    }
  }
})

// State for diagnostic creation (mutation)
const { loading: diagnosticLoading, error: diagnosticError, execute: createDiagnosticAction } = 
  useDataFetch(diagnosticoService.create)

const notificacion = ref('')

async function handleSubmit() {
  if (!obdReaderRef.value?.hasRawHex) {
    notificacionesStore.addNotification({
      type: 'error',
      message: 'Primero realice un escaneo OBD-II válido.',
      timeout: 5000
    })
    return
  }

  try {
    await createDiagnosticAction({
      orden_id: Number(otId.value),
      vehiculo_id: vehiculoId.value,
      tramas_hex: obdReaderRef.value.lastRawHex,
      observaciones: form.observaciones,
      kilometraje: form.kilometraje,
      codigos_directos: (obdReaderRef.value.results ?? []).map(r => r.codigo).filter(Boolean)
    })
    notificacionesStore.addNotification({
      type: 'success',
      message: 'Diagnóstico registrado exitosamente',
      timeout: 3000
    })
    
    // Limpiar resultados locales para forzar actualización visual
    obdReaderResults.value = []
    
    form.kilometraje = null
    form.observaciones = ''
    // Reset the OBDReader if it has a reset method
    if (obdReaderRef.value && typeof obdReaderRef.value.reset === 'function') {
      obdReaderRef.value.reset()
    }
  } catch (err) {
    // Error already handled by composable, but we might want to set a local alert? We don't have one in the template for errors, only for success.
    // The composable already shows an error notification.
    // We can do nothing here for error, or we can set a local alert if we want to show it in the template.
    // We don't have an alert for errors in the template, so we'll do nothing.
  }
}
</script>