<template>
  <div class="p-6">
    <!-- VISTA PRINCIPAL: TARJETAS DE VEHÍCULOS -->
    <div v-if="activeView === 'grid'">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Gestión de Vehículos</h2>
        <div class="flex gap-4 w-full sm:w-auto">
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Buscar vehículo..." 
            class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-full sm:w-64"
          />
          <BaseButton variant="primary" @click="openCreate" class="whitespace-nowrap">Nuevo Vehículo</BaseButton>
        </div>
      </div>

      <div v-if="loading" class="text-center py-8 text-gray-500">
        <svg class="animate-spin h-8 w-8 text-indigo-500 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        Cargando vehículos...
      </div>

      <div v-else-if="filteredVehicles.length === 0" class="text-center py-12 text-gray-500 bg-white rounded-xl border border-gray-200">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
        <p class="mt-4 text-lg font-medium text-gray-900">No hay vehículos registrados</p>
        <p class="mt-1 text-sm text-gray-500">Agrega un nuevo vehículo para comenzar.</p>
      </div>

      <!-- Grid de Vehículos -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="v in filteredVehicles" :key="v.id" 
             class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow cursor-pointer group flex flex-col"
             @click="openHistory(v)">
          <div class="h-48 bg-gray-200 relative overflow-hidden">
            <img v-if="v.foto_url" :src="v.foto_url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
            <div v-else class="w-full h-full flex items-center justify-center text-gray-400 bg-gray-100">
              <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
            </div>
            <div class="absolute top-2 right-2 bg-black/60 text-white px-2 py-1 rounded text-xs font-bold uppercase tracking-wider backdrop-blur-sm">
              {{ v.placa }}
            </div>
          </div>
          <div class="p-5 flex-grow flex flex-col justify-between">
            <div>
              <h3 class="text-lg font-bold text-gray-900">{{ v.marca }} {{ v.modelo }} <span v-if="v.anio" class="text-gray-500 text-sm font-normal">({{ v.anio }})</span></h3>
              <p class="text-sm text-gray-600 mt-1 flex items-center gap-1">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                {{ v.cliente_nombre }}
              </p>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
              <span class="text-xs text-gray-500">VIN: {{ v.vin || 'N/A' }}</span>
              <span class="text-indigo-600 text-sm font-medium flex items-center gap-1 group-hover:text-indigo-700">
                Ver Historial
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- VISTA DE HISTORIAL (PANTALLA COMPLETA) -->
    <div v-else-if="activeView === 'history' && selectedVehicle">
      <!-- Cabecera de Historial -->
      <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-4">
          <button @click="closeHistory" class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-full transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
          </button>
          <div>
            <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
              {{ selectedVehicle.marca }} {{ selectedVehicle.modelo }}
              <span class="bg-indigo-100 text-indigo-800 text-sm font-bold px-2.5 py-0.5 rounded uppercase">{{ selectedVehicle.placa }}</span>
            </h2>
            <p class="text-sm text-gray-500">Propietario: {{ selectedVehicle.cliente_nombre }}</p>
          </div>
        </div>
        <div class="flex gap-3">
          <BaseButton variant="outline" @click="openEditVehicle">Editar Vehículo</BaseButton>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Columna Izquierda: Info y Foto -->
        <div class="space-y-6">
          <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
            <div class="h-48 bg-gray-200">
              <img v-if="selectedVehicle.foto_url" :src="selectedVehicle.foto_url" class="w-full h-full object-cover" />
              <div v-else class="w-full h-full flex items-center justify-center text-gray-400 bg-gray-100">Sin foto</div>
            </div>
            <div class="p-5">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Detalles del Vehículo</h3>
              <dl class="space-y-3 text-sm">
                <div class="flex justify-between border-b border-gray-100 pb-2">
                  <dt class="text-gray-500">Año</dt>
                  <dd class="font-medium text-gray-900">{{ selectedVehicle.anio || 'No registrado' }}</dd>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                  <dt class="text-gray-500">Color</dt>
                  <dd class="font-medium text-gray-900">{{ selectedVehicle.color || 'No registrado' }}</dd>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                  <dt class="text-gray-500">VIN / Chasis</dt>
                  <dd class="font-medium text-gray-900">{{ selectedVehicle.vin || 'N/A' }}</dd>
                </div>
                <div class="flex justify-between pt-1">
                  <dt class="text-gray-500">Registrado en sistema</dt>
                  <dd class="font-medium text-gray-900">{{ new Date(selectedVehicle.created_at).toLocaleDateString() }}</dd>
                </div>
              </dl>
            </div>
          </div>
        </div>

        <!-- Columna Derecha: Tabs de Historial -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col h-full">
            <!-- Tabs Nav -->
            <div class="border-b border-gray-200 px-6 pt-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
              <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button
                  @click="activeHistoryTab = 'ordenes'"
                  :class="[
                    activeHistoryTab === 'ordenes' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
                  ]"
                >
                  Órdenes de Trabajo ({{ historialFiltrado.length }})
                </button>
                <button
                  @click="activeHistoryTab = 'diagnosticos'"
                  :class="[
                    activeHistoryTab === 'diagnosticos' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
                  ]"
                >
                  Diagnósticos OBD-II ({{ diagnosticosFiltrados.length }})
                </button>
              </nav>
              <div class="pb-2 sm:pb-0 w-full sm:w-64">
                <input 
                  v-model="historySearchQuery" 
                  type="text" 
                  :placeholder="activeHistoryTab === 'ordenes' ? 'Buscar en órdenes...' : 'Buscar en diagnósticos...'" 
                  class="w-full px-3 py-1.5 text-sm border rounded-md focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50"
                />
              </div>
            </div>

            <!-- Contenido de Tabs -->
            <div class="p-6 flex-grow bg-gray-50/50">
              
              <!-- Tab: Órdenes de Trabajo -->
              <div v-if="activeHistoryTab === 'ordenes'" class="space-y-4">
                <div v-if="loadingHistory" class="text-center py-12 text-gray-500">Cargando historial de órdenes...</div>
                <div v-else-if="historialFiltrado.length === 0" class="text-center py-12 text-gray-500 bg-white rounded-lg border border-gray-100">
                  <p v-if="historySearchQuery">No se encontraron órdenes que coincidan con la búsqueda.</p>
                  <p v-else>Este vehículo no tiene órdenes de trabajo previas.</p>
                </div>
                <div v-else class="space-y-4">
                  <div v-for="ot in historialFiltrado" :key="ot.id" class="bg-white border border-gray-200 rounded-lg p-5 relative overflow-hidden hover:shadow-sm transition-shadow">
                    <div class="absolute left-0 top-0 bottom-0 w-1.5" :class="getBorderColor(ot.estado)"></div>
                    <div class="flex justify-between items-start mb-3">
                      <div>
                        <div class="flex items-center gap-3 mb-1">
                          <span class="font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded text-sm tracking-wide">OT #{{ ot.numero_ot }}</span>
                          <span class="px-2.5 py-0.5 bg-gray-100 text-gray-700 rounded-full text-xs font-semibold" :class="getBadgeColor(ot.estado)">
                            {{ formatEstado(ot.estado) }}
                          </span>
                        </div>
                        <span class="text-xs text-gray-500">{{ formatDate(ot.created_at) }}</span>
                      </div>
                      <div v-if="ot.fecha_cierre" class="text-xs text-green-700 bg-green-50 border border-green-100 px-2 py-1 rounded font-medium">
                        Entregado: {{ formatDate(ot.fecha_cierre) }}
                      </div>
                    </div>
                    
                    <div class="mb-4 text-sm text-gray-700">
                      <span class="font-semibold text-gray-900">Problema:</span> {{ ot.descripcion_problema }}
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                      <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Mecánicos Asignados</div>
                        <div v-if="ot.mecanicos && ot.mecanicos.length > 0" class="flex flex-wrap gap-1">
                          <span v-for="(m, i) in ot.mecanicos" :key="i" class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">
                            {{ m.nombre }}
                          </span>
                        </div>
                        <span v-else class="text-xs text-gray-400 italic">No asignado</span>
                      </div>
                      <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Repuestos ({{ ot.repuestos_count }})</div>
                        <ul v-if="ot.repuestos && ot.repuestos.length > 0" class="text-xs space-y-1">
                          <li v-for="(rep, i) in ot.repuestos" :key="i" class="text-gray-600 flex justify-between border-b border-gray-50 pb-1">
                            <span>{{ rep.nombre }}</span>
                            <span class="font-medium text-gray-900">x{{ rep.cantidad }}</span>
                          </li>
                        </ul>
                        <span v-else class="text-xs text-gray-400 italic">Ninguno</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Tab: Diagnósticos OBD-II -->
              <div v-if="activeHistoryTab === 'diagnosticos'" class="space-y-4">
                <div v-if="loadingDiag" class="text-center py-12 text-gray-500">Cargando diagnósticos...</div>
                <div v-else-if="diagnosticosFiltrados.length === 0" class="text-center py-12 text-gray-500 bg-white rounded-lg border border-gray-100">
                  <p v-if="historySearchQuery">No se encontraron diagnósticos que coincidan con la búsqueda.</p>
                  <p v-else>No se registran diagnósticos previos para este vehículo.</p>
                </div>
                <div v-else class="space-y-4">
                  <div v-for="d in diagnosticosFiltrados" :key="d.id" class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">

                    <!-- Cabecera de la tarjeta -->
                    <div class="flex justify-between items-center px-5 py-3 bg-blue-50 border-b border-blue-100">
                      <div class="flex items-center gap-3">
                        <span class="font-bold text-sm bg-white border border-blue-200 text-blue-800 px-2 py-0.5 rounded tracking-wide">OT #{{ d.numero_ot || d.orden_id }}</span>
                        <span class="text-xs text-gray-500">{{ formatDate(d.created_at || d.fecha) }}</span>
                      </div>
                      <div class="text-xs text-gray-600">
                        <span class="text-gray-400">Mecánico:</span>
                        <span class="font-medium text-gray-800 ml-1">{{ d.mecanico_nombre }}</span>
                      </div>
                    </div>

                    <div class="p-5 space-y-4">

                      <!-- Códigos de falla con descripción completa -->
                      <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                          Códigos de Falla Detectados
                          <span class="ml-1 bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded-full font-bold">{{ getDtcs(d).length }}</span>
                        </div>

                        <div v-if="getDtcs(d).length === 0" class="flex items-center gap-2 text-sm text-green-700 bg-green-50 border border-green-100 px-3 py-2.5 rounded-lg">
                          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                          Sin códigos de falla detectados
                        </div>

                        <div v-else class="space-y-2">
                          <div
                            v-for="dtc in getDtcs(d)"
                            :key="dtc.codigo || dtc"
                            class="flex items-start gap-3 p-3 rounded-lg border"
                            :class="getDtcCardClass(dtc)"
                          >
                            <!-- Código DTC -->
                            <span class="font-mono font-bold text-sm shrink-0 mt-0.5 w-16" :class="getDtcCodeClass(dtc)">
                              {{ dtc.codigo || dtc }}
                            </span>
                            <!-- Descripción y sistema -->
                            <div class="flex-1 min-w-0">
                              <p class="text-sm font-semibold text-gray-800 leading-snug">
                                {{ dtc.descripcion || 'Descripción no disponible' }}
                              </p>
                              <p v-if="dtc.sistema" class="text-xs text-gray-500 mt-0.5">
                                Sistema: <span class="font-medium">{{ dtc.sistema }}</span>
                              </p>
                            </div>
                            <!-- Severidad -->
                            <span class="shrink-0 text-xs font-bold px-2 py-0.5 rounded-full" :class="getDtcSeveridadClass(dtc)">
                              {{ getDtcSeveridad(dtc) }}
                            </span>
                          </div>
                        </div>
                      </div>

                      <!-- Observaciones del mecánico -->
                      <div class="bg-gray-50 border border-gray-100 rounded-lg p-3">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Observaciones del Mecánico</div>
                        <p v-if="d.observaciones && d.observaciones !== 'Sin observaciones'" class="text-sm text-gray-800 italic leading-relaxed">"{{ d.observaciones }}"</p>
                        <p v-else class="text-sm text-gray-400 italic">Sin observaciones registradas</p>
                      </div>

                      <!-- Trama RAW (expandible) -->
                      <div v-if="d.tramas_hex" class="border border-gray-200 rounded-lg overflow-hidden">
                        <button
                          @click="expandedTrama = expandedTrama === d.id ? null : d.id"
                          class="w-full flex justify-between items-center px-3 py-2 bg-gray-50 hover:bg-gray-100 transition-colors text-left"
                        >
                          <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Trama RAW OBD-II</span>
                          <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': expandedTrama === d.id }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                          </svg>
                        </button>
                        <div v-if="expandedTrama === d.id" class="px-4 py-3 bg-gray-900">
                          <code class="text-xs text-green-400 font-mono break-all">{{ d.tramas_hex }}</code>
                        </div>
                      </div>

                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Formulario -->
    <VehiculoFormModal 
      v-model:show="showForm" 
      :data="editingVehicle" 
      :clients="clients"
      @saved="handleFormSaved" 
    />
    
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { vehiculoService } from '@/services/vehiculoService'
import { clienteService } from '@/services/clienteService'
import { useDataFetch } from '@/composables/useDataFetch'
import { useAuthStore } from '@/stores/auth'
import { useNotificacionesStore } from '@/stores/notificaciones'
import BaseButton from '@/components/shared/BaseButton.vue'
import VehiculoFormModal from '@/views/VehiculoFormModal.vue'

// Estado principal
const activeView = ref('grid') // 'grid' | 'history'
const selectedVehicle = ref(null)
const activeHistoryTab = ref('ordenes') // 'ordenes' | 'diagnosticos'

// Datos de vehículos
const vehicles = ref([])
const clients = ref([])
const loading = ref(false)
const showForm = ref(false)
const editingVehicle = ref(null)

// Filtros
const searchQuery = ref('')
const historySearchQuery = ref('')

const authStore = useAuthStore()
const notificacionesStore = useNotificacionesStore()

const userRole = computed(() => authStore.user?.rol || authStore.userRole)
const userId = computed(() => authStore.user?.id)

// Datos de Historial
const historial = ref([])
const diagnosticos = ref([])

const { loading: loadingHistory, execute: fetchHistory } = useDataFetch((id) => vehiculoService.getHistorial(id))
const { loading: loadingDiag, execute: fetchDiag } = useDataFetch((id) => vehiculoService.getDiagnosticos(id))

// ===== Data fetching for vehicles =====
const { loading: vehiclesLoading, data: vehiclesData, error: vehiclesError, execute: fetchVehiclesList } =
  useDataFetch((params) => vehiculoService.getAll(params))

// ===== Data fetching for clients =====
const { loading: clientsLoading, data: clientsData, error: clientsError, execute: fetchClientsList } =
  useDataFetch(() => clienteService.getAll())

// Computados de filtrado
const filteredVehicles = computed(() => {
  if (!searchQuery.value) return vehicles.value
  const lowerQuery = searchQuery.value.toLowerCase()
  return vehicles.value.filter(v => 
    v.placa?.toLowerCase().includes(lowerQuery) ||
    v.marca?.toLowerCase().includes(lowerQuery) ||
    v.modelo?.toLowerCase().includes(lowerQuery) ||
    v.cliente_nombre?.toLowerCase().includes(lowerQuery) ||
    (v.vin && v.vin.toLowerCase().includes(lowerQuery))
  )
})

const historialFiltrado = computed(() => {
  if (!historySearchQuery.value) return historial.value
  const q = historySearchQuery.value.toLowerCase()
  return historial.value.filter(ot => 
    ot.numero_ot?.toLowerCase().includes(q) ||
    ot.descripcion_problema?.toLowerCase().includes(q) ||
    formatEstado(ot.estado).toLowerCase().includes(q)
  )
})

const diagnosticosFiltrados = computed(() => {
  if (!historySearchQuery.value) return diagnosticos.value
  const q = historySearchQuery.value.toLowerCase()
  return diagnosticos.value.filter(d => {
    const codigosStr = normalizeCodigos(d.codigos_falla).join(' ').toLowerCase()
    return (d.numero_ot && String(d.numero_ot).toLowerCase().includes(q)) ||
           (d.observaciones && d.observaciones.toLowerCase().includes(q)) ||
           codigosStr.includes(q)
  })
})

// Observers
watch(() => vehiclesData.value, (val) => {
  if (val && val.data) {
    vehicles.value = val.data.map(v => ({
      ...v,
      cliente_nombre: v.cliente?.nombre || 'N/A'
    }))
    
    // Actualizar selectedVehicle si está activo
    if (selectedVehicle.value) {
      const updated = vehicles.value.find(v => v.id === selectedVehicle.value.id)
      if (updated) selectedVehicle.value = updated
    }
  }
})

watch(() => clientsData.value, (val) => {
  if (val && val.data) {
    clients.value = val.data
  }
})

watch([vehiclesLoading, clientsLoading], ([vL, cL]) => {
  loading.value = vL || cL
})

// Handlers
async function fetchData() {
  try {
    if (userRole.value === 'cliente') {
      await fetchVehiclesList({ cliente_id: userId.value })
      clients.value = authStore.user ? [authStore.user] : []
    } else {
      await fetchVehiclesList()
      await fetchClientsList()
    }
  } catch (err) {
    //
  }
}

function handleFormSaved() {
  fetchData()
}

function openCreate() {
  editingVehicle.value = null
  showForm.value = true
}

function openEditVehicle() {
  editingVehicle.value = selectedVehicle.value
  showForm.value = true
}

async function openHistory(vehicle) {
  selectedVehicle.value = vehicle
  activeView.value = 'history'
  activeHistoryTab.value = 'ordenes'
  historySearchQuery.value = ''
  
  // Cargar info del historial
  try {
    const resHist = await fetchHistory(vehicle.id)
    historial.value = (resHist && resHist.success) ? (resHist.data || []) : []
  } catch { historial.value = [] }

  try {
    const resDiag = await fetchDiag(vehicle.id)
    diagnosticos.value = (resDiag && resDiag.success) ? (resDiag.data || []) : []
  } catch { diagnosticos.value = [] }
}

function closeHistory() {
  activeView.value = 'grid'
  selectedVehicle.value = null
  historySearchQuery.value = ''
}

// Helpers visuales
function formatEstado(estado) {
  const map = {
    'diagnostico': 'En Diagnóstico',
    'reparacion': 'En Reparación',
    'esperando_repuesto': 'Esperando Repuesto',
    'control_calidad': 'Control de Calidad',
    'entregado': 'Entregado'
  }
  return map[estado] || estado
}

function getBadgeColor(estado) {
  const map = {
    'diagnostico': 'bg-blue-100 text-blue-800',
    'reparacion': 'bg-amber-100 text-amber-800',
    'esperando_repuesto': 'bg-orange-100 text-orange-800',
    'control_calidad': 'bg-purple-100 text-purple-800',
    'entregado': 'bg-green-100 text-green-800'
  }
  return map[estado] || 'bg-gray-100 text-gray-800'
}

function getBorderColor(estado) {
  const map = {
    'diagnostico': 'bg-blue-500',
    'reparacion': 'bg-amber-500',
    'esperando_repuesto': 'bg-orange-500',
    'control_calidad': 'bg-purple-500',
    'entregado': 'bg-green-500'
  }
  return map[estado] || 'bg-gray-500'
}

function normalizeCodigos(codigos) {
  if (!codigos) return []
  if (typeof codigos === 'string') {
    try { codigos = JSON.parse(codigos) } catch { return [codigos] }
  }
  if (Array.isArray(codigos)) return codigos.filter(c => c)
  if (typeof codigos === 'object') return Object.values(codigos).filter(c => c)
  return []
}

// ── Helpers para tarjetas de diagnóstico OBD-II ──────────────────────────────

const expandedTrama = ref(null)

function getDtcs(d) {
  if (d.codigos_falla_detalle && d.codigos_falla_detalle.length > 0) return d.codigos_falla_detalle
  return normalizeCodigos(d.codigos_falla).map(c => ({ codigo: c }))
}

function getDtcTipo(dtc) {
  return dtc.tipo || (typeof dtc === 'string' ? dtc[0] : (dtc.codigo?.[0] ?? ''))
}

function getDtcCardClass(dtc) {
  const map = { P: 'bg-red-50 border-red-100', B: 'bg-blue-50 border-blue-100', C: 'bg-green-50 border-green-100', U: 'bg-yellow-50 border-yellow-100' }
  return map[getDtcTipo(dtc)] ?? 'bg-gray-50 border-gray-100'
}

function getDtcCodeClass(dtc) {
  const map = { P: 'text-red-600', B: 'text-blue-600', C: 'text-green-600', U: 'text-yellow-600' }
  return map[getDtcTipo(dtc)] ?? 'text-gray-600'
}

function getDtcSeveridad(dtc) {
  const tipo = getDtcTipo(dtc)
  return (tipo === 'P' || tipo === 'U') ? 'Alta' : 'Media'
}

function getDtcSeveridadClass(dtc) {
  return getDtcSeveridad(dtc) === 'Alta' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A'
  const d = new Date(dateStr.replace(' ', 'T'))
  return isNaN(d.getTime()) 
    ? 'Inválida' 
    : d.toLocaleString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(fetchData)
</script>