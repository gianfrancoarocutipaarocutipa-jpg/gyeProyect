<template>
  <div class="p-6 shadow-md rounded-xl bg-white">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-2xl font-bold text-gray-900">
        Gestión de Clientes
      </h2>
      <BaseButton
        variant="primary"
        size="md"
        @click="showCreateClientModal = true"
        :loading="savingClient"
      >
        Nuevo Cliente
      </BaseButton>
    </div>

    <BaseAlert
      v-if="alert"
      :show="true"
      variant="error"
      :message="alert"
      @update:show="show => { if (!show) alert = null }"
    />

    <!-- Skeleton Loader -->
    <div v-if="clientsLoading" class="space-y-4">
      <div class="h-4 bg-gray-200 rounded w-full mb-2"></div>
      <div class="h-4 bg-gray-200 rounded w-full mb-2"></div>
      <div class="h-4 bg-gray-200 rounded w-full mb-2"></div>
      <div class="h-4 bg-gray-200 rounded w-full mb-2"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!clientsLoading && clients.length === 0" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="mt-4 text-lg text-gray-500">No hay clientes registrados</p>
      <p class="mt-2 text-sm text-gray-400">Haz clic en "Nuevo Cliente" para comenzar</p>
    </div>

    <!-- Data Table -->
    <div v-else>
      <BaseTable
        :columns="clientColumns"
        :data="clients"
        :loading="false"
        @row-click="showClientDetailModal = true; selectedClient = $event"
      />
    </div>

    <BaseModal
      v-model:show="showCreateClientModal"
      @close="resetClientForm"
    >
      <template #header>
        <h3 class="text-lg font-medium text-gray-900">
          {{ editingClient ? 'Editar Cliente' : 'Nuevo Cliente' }}
        </h3>
      </template>

      <div class="space-y-4">
        <div>
          <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">
            Nombre Completo
          </label>
          <input
            id="nombre"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            v-model="clientForm.nombre"
          />
        </div>

        <div>
          <label for="dni_ruc" class="block text-sm font-medium text-gray-700 mb-1">
            DNI/RUC
          </label>
          <input
            id="dni_ruc"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            v-model="clientForm.dni_ruc"
          />
        </div>

        <div>
          <label for="telefono" class="block text-sm font-medium text-gray-700 mb-1">
            Teléfono
          </label>
          <input
            id="telefono"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            v-model="clientForm.telefono"
          />
        </div>

        <div>
          <label for="correo" class="block text-sm font-medium text-gray-700 mb-1">
            Correo Electrónico
          </label>
          <input
            id="correo"
            type="email"
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            v-model="clientForm.correo"
          />
        </div>
      </div>

      <div class="modal-footer flex justify-end space-x-3 mt-6">
        <BaseButton
          variant="outline"
          size="md"
          @click="showCreateClientModal = false"
        >
          Cancelar
        </BaseButton>
        <BaseButton
          variant="primary"
          size="md"
          :loading="savingClient"
          @click="saveClient"
        >
          {{ editingClient ? 'Actualizar' : 'Crear' }}
        </BaseButton>
      </div>
    </BaseModal>

    <BaseModal
      v-model:show="showClientDetailModal"
      @close="resetClientDetail"
    >
      <template #header>
        <h3 class="text-lg font-medium text-gray-900">
          Detalle del Cliente
        </h3>
      </template>

      <div class="space-y-4">
        <div v-if="!selectedClient" class="text-center py-4">
          Cargando detalles del cliente...
        </div>
        <div v-else>
          <div class="space-y-2">
            <p><span class="font-medium">Nombre:</span> {{ selectedClient.nombre }}</p>
            <p><span class="font-medium">DNI/RUC:</span> {{ selectedClient.dni_ruc }}</p>
            <p><span class="font-medium">Teléfono:</span> {{ selectedClient.telefono }}</p>
            <p><span class="font-medium">Correo:</span> {{ selectedClient.correo }}</p>
            <p><span class="font-medium">Código de Seguimiento:</span> {{ selectedClient.codigo_seguimiento }}</p>
          </div>
        </div>
      </div>

      <div class="modal-footer flex justify-end space-x-3 mt-6">
        <BaseButton
          variant="outline"
          size="md"
          @click="editClient(selectedClient)"
        >
          Editar
        </BaseButton>
        <BaseButton
          variant="outline"
          size="md"
          @click="loadHistorial(selectedClient)"
        >
          Historial
        </BaseButton>
        <BaseButton
          variant="danger"
          size="md"
          @click="handleDeleteClient(selectedClient.id)"
        >
          Eliminar
        </BaseButton>
      </div>
    </BaseModal>

    <BaseModal
      v-model:show="showHistorialModal"
      @close="resetHistorial"
    >
      <template #header>
        <h3 class="text-lg font-medium text-gray-900">
          Historial de Reparaciones: {{ historialCliente?.nombre || '' }}
        </h3>
      </template>

      <div class="space-y-4">
        <div v-if="historialLoading" class="text-center py-4">
          <!-- Skeleton Loader for Historial -->
          <div class="space-y-2">
            <div class="h-4 bg-gray-200 rounded w-full mb-2"></div>
            <div class="h-4 bg-gray-200 rounded w-full mb-2"></div>
            <div class="h-4 bg-gray-200 rounded w-full mb-2"></div>
          </div>
        </div>
        <div v-else-if="!historialLoading && (!historialCliente || !historialCliente.ordenes || historialCliente.ordenes.length === 0)" class="text-center py-4 text-gray-500">
          Este cliente no tiene órdenes de trabajo registradas.
        </div>
        <div v-else>
          <BaseTable
            :columns="historialColumns"
            :data="historial"
          />
        </div>
      </div>

      <div class="modal-footer flex justify-end mt-6">
        <BaseButton
          variant="outline"
          size="md"
          @click="showHistorialModal = false"
        >
          Cerrar
        </BaseButton>
      </div>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { clienteService } from '@/services/clienteService'
import { useNotificacionesStore } from '@/stores/notificaciones'
import { useDataFetch } from '@/composables/useDataFetch'
import BaseTable from '@/components/shared/BaseTable.vue'
import BaseButton from '@/components/shared/BaseButton.vue'
import BaseModal from '@/components/shared/BaseModal.vue'
import BaseAlert from '@/components/shared/BaseAlert.vue'

const notificacionesStore = useNotificacionesStore()

// State for clients list
const { loading: clientsLoading, data: clientsData, error: clientsError, execute: fetchClients } = 
  useDataFetch(clienteService.getAll)

// State for client historial
const { loading: historialLoading, data: historialData, error: historialError, execute: fetchHistorial } = 
  useDataFetch(clienteService.getHistorial)

// Referencias locales para datos limpios (Arrays)
const clients = ref([])
const historial = ref([])

watch(() => clientsData.value, (val) => {
  if (val && val.data) clients.value = val.data
})

// State
const alert = ref(null)

// Form state for create/edit
const editingClient = ref(null)
const showCreateClientModal = ref(false)
const clientForm = reactive({
  nombre: '',
  dni_ruc: '',
  telefono: '',
  correo: ''
})
const savingClient = ref(false)

// Client detail modal state
const showClientDetailModal = ref(false)
const selectedClient = ref(null)

// Historial state
const showHistorialModal = ref(false)
const historialCliente = ref(null)

// Columns for client table
const clientColumns = [
  { key: 'nombre', label: 'Nombre', width: 'full' },
  { key: 'dni_ruc', label: 'DNI/RUC', width: 'half' },
  { key: 'telefono', label: 'Teléfono', width: 'third' },
  { key: 'correo', label: 'Correo', width: 'full' }
]

// Columns for historial table with formatters
const historialColumns = [
  { key: 'numero_ot', label: 'OT', width: 'quarter' },
  { key: 'vehiculo', label: 'Vehículo', width: 'half' },
  {
    key: 'estado',
    label: 'Estado',
    width: 'quarter',
    format: (value) => {
      const estados = {
        diagnostico: { text: 'Diagnóstico', color: 'bg-blue-100 text-blue-800' },
        reparacion: { text: 'Reparación', color: 'bg-yellow-100 text-yellow-800' },
        esperando_repuesto: { text: 'Esperando Repuesto', color: 'bg-orange-100 text-orange-800' },
        control_calidad: { text: 'Control Calidad', color: 'bg-purple-100 text-purple-800' },
        entregado: { text: 'Entregado', color: 'bg-green-100 text-green-800' }
      }
      const e = estados[value] || { text: value || 'Sin estado', color: 'bg-gray-100 text-gray-800' }
      return `<span class="px-2 py-1 rounded-full text-xs font-semibold ${e.color}">${e.text}</span>`
    }
  },
  {
    key: 'fecha_cierre',
    label: 'Fecha Cierre',
    width: 'quarter',
    format: (value) => {
      if (!value) return '<span class="text-gray-400">Pendiente</span>'
      const d = new Date(value.replace(' ', 'T'))
      return isNaN(d.getTime()) ? 'Fecha inválida' : d.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' })
    }
  }
]

// Fetch clients on mount
const loadClients = async () => {
  try {
    await fetchClients()
  } catch (err) {
    // Error already handled by composable, but we can set alert if needed for UI
    alert.value = err.message || 'Error al cargar clientes'
  }
}

// Create client
const { execute: createClientAction } = useDataFetch(clienteService.create)
const handleCreateClient = async () => {
  try {
    await createClientAction(clientForm)
    notificacionesStore.addNotification({
      type: 'success',
      message: 'Cliente creado exitosamente',
      timeout: 3000
    })
    await loadClients()
    resetClientForm()
  } catch (err) {
    // Error already handled by composable, but we set alert for the UI alert component
    alert.value = err.message || 'Error al crear cliente'
  }
}

// Update client
const { execute: updateClientAction } = useDataFetch(clienteService.update)
const handleUpdateClient = async () => {
  try {
    await updateClientAction(editingClient.value.id, clientForm)
    notificacionesStore.addNotification({
      type: 'success',
      message: 'Cliente actualizado exitosamente',
      timeout: 3000
    })
    await loadClients()
    resetClientForm()
  } catch (err) {
    alert.value = err.message || 'Error al actualizar cliente'
  }
}

// Delete client
const { execute: deleteClientAction } = useDataFetch(clienteService.delete)
const handleDeleteClient = async (id) => {
  if (!confirm('¿Está seguro de eliminar este cliente? Esta acción no se puede deshacer.')) return
  try {
    await deleteClientAction(id)
    notificacionesStore.addNotification({
      type: 'success',
      message: 'Cliente eliminado exitosamente',
      timeout: 3000
    })
    await loadClients()
    resetClientDetail()
  } catch (err) {
    alert.value = err.message || 'Error al eliminar cliente'
  }
}

// Save client
const saveClient = async () => {
  if (editingClient.value) {
    await handleUpdateClient()
  } else {
    await handleCreateClient()
  }
}

// Edit client
function editClient(client) {
  editingClient.value = client
  clientForm.nombre = client.nombre
  clientForm.dni_ruc = client.dni_ruc
  clientForm.telefono = client.telefono
  clientForm.correo = client.correo
  showClientDetailModal.value = false
  showCreateClientModal.value = true
}

// Reset form
function resetClientForm() {
  clientForm.nombre = ''
  clientForm.dni_ruc = ''
  clientForm.telefono = ''
  clientForm.correo = ''
  editingClient.value = null
  showCreateClientModal.value = false
  savingClient.value = false
}

// Reset client detail modal
function resetClientDetail() {
  selectedClient.value = null
  showClientDetailModal.value = false
}

// Load historial for a client
const loadHistorial = async (client) => {
  showClientDetailModal.value = false
  showHistorialModal.value = true
  historialCliente.value = client
  historial.value = []
  try {
    const response = await fetchHistorial(client.id)
    
    if (response && response.success && Array.isArray(response.data)) {
      const ordenesTransformed = response.data.map(orden => {
        // Construir string de vehículo desde el objeto anidado
        let vehiculoStr = 'Vehículo no especificado'
        if (orden.vehiculo && typeof orden.vehiculo === 'object') {
          const { marca, modelo, placa } = orden.vehiculo
          if (marca || modelo || placa) {
            vehiculoStr = `${marca || ''} ${modelo || ''} - ${placa || ''}`.trim()
          }
        } else if (typeof orden.vehiculo === 'string' && orden.vehiculo) {
          vehiculoStr = orden.vehiculo
        }

        return {
          id: orden.id,
          numero_ot: orden.numero_ot || 'N/A',
          vehiculo: vehiculoStr,
          estado: orden.estado || 'Sin estado',
          fecha_cierre: orden.fecha_cierre || null,
          descripcion_problema: orden.descripcion_problema || '',
          created_at: orden.created_at
        }
      })
      
      historial.value = ordenesTransformed
      historialCliente.value = {
        ...client,
        ordenes: ordenesTransformed
      }
    } else {
      historial.value = []
      historialCliente.value = { ...client, ordenes: [] }
    }
  } catch (err) {
    alert.value = err.message || 'Error al obtener historial'
    historial.value = []
  }
}

// Reset historial modal
function resetHistorial() {
  historialCliente.value = null
  showHistorialModal.value = false
}

// Initial fetch
loadClients()
</script>