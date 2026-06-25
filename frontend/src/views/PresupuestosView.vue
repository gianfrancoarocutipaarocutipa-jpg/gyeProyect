<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-slate-100">Gestión de Presupuestos</h2>
    </div>

    <BaseAlert
      v-if="alert"
      :show="true"
      :variant="alert.type"
      :message="alert.message"
      @update:show="show => { if (!show) alert = null }"
    />

    <div v-if="loading" class="text-center py-8">
      Cargando presupuestos...
    </div>

    <div v-else>
      <BaseTable
        :columns="presupuestosColumns"
        :data="presupuestos"
        :loading="false"
        @row-click="verDetalle"
      />
    </div>

    <BaseModal v-model:show="showDetailModal" @close="resetDetail">
      <template #header>
        <h3 class="text-lg font-medium text-slate-100">
          Detalle de Presupuesto #{{ presupuestoSeleccionado?.id || '' }}
        </h3>
      </template>

      <div v-if="presupuestoSeleccionado" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-sm font-medium text-slate-400">Orden de Trabajo</p>
            <p class="text-sm text-slate-100">{{ presupuestoSeleccionado.numero_ot }}</p>
          </div>
          <div>
            <p class="text-sm font-medium text-slate-400">Total</p>
            <p class="text-sm font-bold text-slate-100">S/ {{ formatCurrency(presupuestoSeleccionado.total) }}</p>
          </div>
        </div>

        <div>
          <p class="text-sm font-medium text-slate-400">Estado</p>
          <span :class="[
            'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
            presupuestoSeleccionado.estado === 'aprobado' ? 'bg-green-100 text-green-800' : 
            presupuestoSeleccionado.estado === 'rechazado' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'
          ]">
            {{ presupuestoSeleccionado.estado === 'aprobado' ? 'Aprobado' : 
               presupuestoSeleccionado.estado === 'rechazado' ? 'Rechazado' : 'Pendiente' }}
          </span>
        </div>

        <div v-if="presupuestoSeleccionado.motivo_rechazo">
          <p class="text-sm font-medium text-slate-400">Motivo de Rechazo</p>
          <p class="text-sm text-slate-100">{{ presupuestoSeleccionado.motivo_rechazo }}</p>
        </div>

        <div v-if="presupuestoSeleccionado.estado === 'pendiente'">
          <BaseButton variant="primary" size="md" @click="abrirResponderModal">
            Responder Presupuesto
          </BaseButton>
        </div>
      </div>

      <template #footer>
        <BaseButton variant="outline" size="md" @click="showDetailModal = false">
          Cerrar
        </BaseButton>
      </template>
    </BaseModal>

    <BaseModal v-model:show="showResponderModal" @close="resetResponder">
      <template #header>
        <h3 class="text-lg font-medium text-slate-100">Responder Presupuesto</h3>
      </template>

      <form @submit.prevent="responderPresupuesto" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-2">Decisión</label>
          <select v-model="respuesta.estado" class="w-full px-3 py-2 border border-slate-600 rounded-md" required>
            <option value="">Seleccionar...</option>
            <option value="aprobado">Aprobar</option>
            <option value="rechazado">Rechazar</option>
          </select>
        </div>

        <div v-if="respuesta.estado === 'rechazado'">
          <label class="block text-sm font-medium text-slate-300 mb-2">Motivo de Rechazo</label>
          <textarea v-model="respuesta.motivo_rechazo" class="w-full px-3 py-2 border border-slate-600 rounded-md" rows="3" required></textarea>
        </div>
      </form>

      <template #footer>
        <BaseButton variant="outline" size="md" @click="showResponderModal = false">
          Cancelar
        </BaseButton>
        <BaseButton variant="primary" size="md" @click="responderPresupuesto">
          Confirmar
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useNotificacionesStore } from '@/stores/notificaciones'
import { ordenService } from '@/services/ordenService'
import { presupuestoService } from '@/services/presupuestoService'
import { useDataFetch } from '@/composables/useDataFetch'
import BaseTable from '@/components/shared/BaseTable.vue'
import BaseModal from '@/components/shared/BaseModal.vue'
import BaseButton from '@/components/shared/BaseButton.vue'
import BaseAlert from '@/components/shared/BaseAlert.vue'

const notificacionesStore = useNotificacionesStore()

const loading = ref(false)
const presupuestos = ref([])
const alert = ref(null)
const showDetailModal = ref(false)
const showResponderModal = ref(false)
const presupuestoSeleccionado = ref(null)
const respuesta = ref({ estado: '', motivo_rechazo: '' })

const presupuestosColumns = [
  { key: 'id', label: 'ID', width: 'quarter' },
  { key: 'numero_ot', label: 'Orden', width: 'half' },
  {
    key: 'total',
    label: 'Total',
    width: 'quarter',
    format: (value) => `S/ ${parseFloat(value).toFixed(2)}`
  },
  {
    key: 'estado',
    label: 'Estado',
    width: 'quarter',
    format: (value) => {
      const labels = { aprobado: 'Aprobado', rechazado: 'Rechazado', pendiente: 'Pendiente' }
      return labels[value] || value
    }
  }
]

function formatCurrency(value) {
  return parseFloat(value).toFixed(2)
}

// ===== Data fetching for orders =====
const { loading: ordersLoading, data: ordersData, error: ordersError, execute: fetchOrdersList } = 
  useDataFetch(() => ordenService.getAll())

// ===== Data fetching for presupuesto by order id =====
const { loading: presupuestoLoading, data: presupuestoData, error: presupuestoError, execute: fetchPresupuestoByOrden } = 
  useDataFetch((ordenId) => presupuestoService.getByOrden(ordenId))

// ===== Mutation for responding to a presupuesto =====
const { loading: responderLoading, data: responderData, error: responderError, execute: ejecutarRespuestaMutation } = 
  useDataFetch((id, data) => presupuestoService.responder(id, data))

// Combine orders and their presupuestos
watch(() => ordersData.value, async (val) => {
  const orders = val?.data
  if (!orders || !Array.isArray(orders)) {
    presupuestos.value = []
    return
  }

  try {
    // Filter orders that have a presupuesto_id
    const ordersWithPresupuesto = orders.filter(order => order.presupuesto_id)
    if (ordersWithPresupuesto.length === 0) {
      presupuestos.value = []
      loading.value = false
      return
    }

    loading.value = true

    // Fetch presupuestos for each order
    // IMPORTANTE: Usamos el servicio directamente en el loop para no corromper el estado del composable
    const presupuestoPromises = ordersWithPresupuesto.map(order => 
      presupuestoService.getByOrden(order.id)
    )

    const presupuestoResults = await Promise.all(presupuestoPromises)

    // Map results to the expected format
    const presupuestosList = []
    for (let i = 0; i < presupuestoResults.length; i++) {
      const result = presupuestoResults[i]
      const order = ordersWithPresupuesto[i]
      if (result && !result.error) {
        presupuestosList.push({
          ...result.data,
          numero_ot: order.numero_ot,
          cliente_nombre: order.cliente?.nombre
        })
      }
    }

    presupuestos.value = presupuestosList
  } catch (err) {
    // Error handled by composables
  } finally {
    loading.value = false
  }
})

// Handle errors from orders fetch
watch(ordersError, (err) => {
  if (err) {
    alert.value = { type: 'error', message: err.message || 'Error al obtener órdenes' }
    notificacionesStore.addNotification({
      type: 'error',
      message: err.message || 'Error inesperado',
      timeout: 5000
    })
    loading.value = false
  } else {
    alert.value = null
  }
})

// Handle errors from presupuesto fetch (individual errors are handled by the composable's notification)
// We don't need to do anything here for the alert because the composable already shows a notification.
// However, we might want to set a general alert if any of the presupuesto fetches fail.
// We'll leave it to the composable's notification.

// Handle errors from responder mutation
watch(responderError, (err) => {
  if (err) {
    notificacionesStore.addNotification({
      type: 'error',
      message: err.message || 'Error inesperado',
      timeout: 5000
    })
  }
})

async function fetchPresupuestos() {
  try {
    await fetchOrdersList()
  } catch (err) {
    // Error handled by watcher
  }
}

function verDetalle(presupuesto) {
  presupuestoSeleccionado.value = presupuesto
  showDetailModal.value = true
}

function abrirResponderModal() {
  respuesta.value = { estado: '', motivo_rechazo: '' }
  showResponderModal.value = true
  showDetailModal.value = false
}

function resetResponder() {
  showResponderModal.value = false
  respuesta.value = { estado: '', motivo_rechazo: '' }
}

function resetDetail() {
  showDetailModal.value = false
  presupuestoSeleccionado.value = null
}

async function responderPresupuesto() {
  try {
    await ejecutarRespuestaMutation(presupuestoSeleccionado.value.id, respuesta.value)
    notificacionesStore.addNotification({
      type: 'success',
      message: 'Presupuesto respondido exitosamente',
      timeout: 3000
    })
    // Refresh the list
    await fetchPresupuestos()
  } catch (err) {
    // Error handled by watcher
  } finally {
    showResponderModal.value = false
  }
}

onMounted(() => {
  fetchPresupuestos()
})
</script>