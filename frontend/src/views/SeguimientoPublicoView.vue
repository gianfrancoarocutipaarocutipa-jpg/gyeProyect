<template>
  <div class="min-h-screen bg-gray-50 flex flex-col font-sans">
    <!-- Header -->
    <header class="bg-gradient-to-r from-blue-900 to-indigo-800 text-white shadow-lg">
      <div class="max-w-4xl mx-auto px-4 py-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <svg class="h-10 w-10 text-white mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <h1 class="text-3xl font-extrabold tracking-tight">G&E Motors</h1>
          </div>
          <span class="text-sm font-medium bg-white/20 px-3 py-1 rounded-full backdrop-blur-sm">Portal del Cliente</span>
        </div>
      </div>
    </header>

    <main class="flex-1 max-w-4xl mx-auto px-4 py-8 w-full">
      <!-- Search Form -->
      <div v-if="!orden && !loading" class="bg-white rounded-2xl shadow-xl p-8 mb-8 transform transition-all hover:scale-[1.01]">
        <h2 class="text-2xl font-bold text-gray-800 mb-2 text-center">Consulta tu Vehículo</h2>
        <p class="text-gray-500 text-center mb-6">Ingresa tu código de seguimiento único</p>
        
        <form @submit.prevent="buscarOrden" class="max-w-md mx-auto space-y-4">
          <div>
            <input
              id="codigo_seguimiento"
              v-model="codigo"
              type="text"
              required
              placeholder="Ej: CLI-XYZ123"
              class="w-full px-5 py-4 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 text-center text-xl font-mono uppercase tracking-widest transition-all"
              :disabled="loading"
              autocomplete="off"
            />
          </div>
          <BaseButton type="submit" variant="primary" size="lg" :loading="loading" :disabled="!codigo.trim()" class="w-full rounded-xl py-4 font-bold text-lg shadow-md hover:shadow-lg">
            Consultar Estado
          </BaseButton>
        </form>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-indigo-600"></div>
      </div>

      <!-- Results Section -->
      <div v-if="orden && !loading" class="space-y-8 animate-fade-in-up">
        
        <div class="flex justify-between items-center">
          <h2 class="text-2xl font-bold text-gray-800">Detalles del Servicio</h2>
          <button @click="resetSearch" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Nueva Consulta
          </button>
        </div>

        <!-- Info Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Vehículo y Cliente -->
          <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-bl from-blue-100 to-transparent rounded-bl-full opacity-50"></div>
            <h3 class="text-sm uppercase tracking-wider font-bold text-gray-400 mb-4">Información General</h3>
            <div class="space-y-3">
              <div>
                <p class="text-xs text-gray-500">Cliente</p>
                <p class="font-semibold text-gray-800 text-lg">{{ cliente?.nombre }}</p>
              </div>
              <div class="flex justify-between">
                <div>
                  <p class="text-xs text-gray-500">Vehículo</p>
                  <p class="font-medium text-gray-800">{{ orden.vehiculo?.marca }} {{ orden.vehiculo?.modelo }}</p>
                </div>
                <div class="text-right">
                  <p class="text-xs text-gray-500">Placa</p>
                  <p class="font-mono font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">{{ orden.vehiculo?.placa }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Estado Actual -->
          <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100 flex flex-col justify-center relative overflow-hidden">
            <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-bl from-green-100 to-transparent rounded-bl-full opacity-50"></div>
            <h3 class="text-sm uppercase tracking-wider font-bold text-gray-400 mb-2">Estado Actual</h3>
            <div class="mt-2">
              <span :class="['inline-flex px-4 py-2 text-base font-bold rounded-full shadow-sm', estadoBadgeClass]">
                {{ estadoLabel }}
              </span>
            </div>
            <p class="mt-4 text-sm text-gray-500">
              Ingresado: <span class="font-medium">{{ formatDate(orden.created_at) }}</span>
            </p>
            <div v-if="fechaEstimada && presupuesto && presupuesto.estado === 'aprobado'" class="mt-3 pt-3 border-t border-gray-100">
              <template v-if="fechaEntrega">
                <p class="text-sm text-gray-500 flex items-center">
                  <svg class="w-4 h-4 mr-1.5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                  Entrega realizada:
                </p>
                <p class="mt-1 font-semibold text-green-700">{{ formatDateTime(fechaEntrega.toISOString()) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ formatHorasMinutos(mecanicoAsignado.horas_trabajadas) }} de trabajo</p>
              </template>
              <template v-else>
                <p class="text-sm text-gray-500 flex items-center">
                  <svg class="w-4 h-4 mr-1.5 text-orange-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  Entrega estimada:
                </p>
                <p class="mt-1 font-semibold text-orange-700">{{ formatDateTime(fechaEstimada.toISOString()) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ formatHorasMinutos(mecanicoAsignado.horas_trabajadas) }} de trabajo</p>
                <p v-if="orden.estado === 'reparacion'" class="mt-1.5 text-xs font-medium"
                   :class="tiempoRestanteTexto === 'Completado' ? 'text-green-600' : 'text-orange-500'">
                  {{ tiempoRestanteTexto }}
                </p>
              </template>
            </div>
          </div>
        </div>

        <!-- Notificación: Reparación completada -->
        <div v-if="orden.estado === 'control_calidad' && presupuesto && presupuesto.estado === 'aprobado' && fechaEstimada"
             class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-lg p-1 text-white">
          <div class="bg-white/10 backdrop-blur-md rounded-xl p-6 flex items-start gap-4">
            <div class="flex-shrink-0 bg-white/20 rounded-full p-2">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-bold mb-1">¡Reparación completada!</h3>
              <p class="text-green-100 text-sm">El tiempo estimado de reparación ha concluido. Su vehículo está siendo sometido al control de calidad antes de la entrega.</p>
            </div>
          </div>
        </div>

        <!-- Presupuesto Card -->
        <div v-if="presupuesto && presupuesto.estado === 'pendiente'" class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg p-1 text-white animate-pulse-slow">
          <div class="bg-white/10 backdrop-blur-md rounded-xl p-6">
            <div class="flex items-start justify-between">
              <div>
                <h3 class="text-xl font-bold mb-1 flex items-center">
                  <svg class="w-6 h-6 mr-2 text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                  Presupuesto Requiere Aprobación
                </h3>
                <p class="text-indigo-100 text-sm">Por favor, revise y apruebe el presupuesto para continuar con la reparación.</p>
              </div>
              <div class="text-right">
                <p class="text-xs text-indigo-200 uppercase tracking-wider font-bold">Total</p>
                <p class="text-3xl font-extrabold text-white">S/ {{ presupuesto.total.toFixed(2) }}</p>
              </div>
            </div>
            
            <div v-if="!mostrandoMotivo" class="mt-6 flex space-x-4">
              <button @click="responderPresupuesto('aprobar')" class="flex-1 bg-white text-indigo-600 font-bold py-3 px-4 rounded-xl hover:bg-indigo-50 transition-colors shadow-sm" :disabled="respuestaLoading">
                Aprobar Presupuesto
              </button>
              <button @click="mostrandoMotivo = true" class="flex-1 bg-transparent border-2 border-white/50 text-white font-bold py-3 px-4 rounded-xl hover:bg-white/10 transition-colors" :disabled="respuestaLoading">
                Rechazar
              </button>
            </div>
            
            <div v-else class="mt-6 space-y-3">
              <input v-model="motivoRechazo" type="text" placeholder="Motivo del rechazo..." class="w-full px-4 py-3 bg-white/20 border border-white/30 rounded-xl text-white placeholder-indigo-200 focus:outline-none focus:ring-2 focus:ring-white" />
              <div class="flex space-x-4">
                <button @click="responderPresupuesto('rechazar')" class="flex-1 bg-red-500 text-white font-bold py-3 px-4 rounded-xl hover:bg-red-600 transition-colors shadow-sm" :disabled="respuestaLoading || !motivoRechazo">
                  Confirmar Rechazo
                </button>
                <button @click="mostrandoMotivo = false" class="flex-1 bg-transparent text-white font-bold py-3 px-4 rounded-xl hover:bg-white/10 transition-colors">
                  Cancelar
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div v-else-if="presupuesto" class="bg-white rounded-2xl shadow-md p-6 border-l-4" :class="presupuesto.estado === 'aprobado' ? 'border-green-500' : 'border-red-500'">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="font-bold text-gray-800">Presupuesto {{ presupuesto.estado.charAt(0).toUpperCase() + presupuesto.estado.slice(1) }}</h3>
              <p v-if="presupuesto.estado === 'rechazado'" class="text-sm text-gray-500 mt-1">Motivo: {{ presupuesto.motivo_rechazo }}</p>
            </div>
            <p class="font-bold text-xl text-gray-800">S/ {{ presupuesto.total.toFixed(2) }}</p>
          </div>
        </div>

        <!-- Orden Cancelada -->
        <div v-if="orden.estado === 'cancelado'" class="bg-red-50 rounded-2xl shadow-md p-6 border border-red-200">
          <div class="flex items-start space-x-3">
            <div class="flex-shrink-0 mt-0.5">
              <svg class="h-6 w-6 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-bold text-red-800 mb-1">Orden Rechazada</h3>
              <p class="text-sm text-red-700">El presupuesto fue rechazado y la orden de servicio ha sido cancelada.</p>
              <div v-if="presupuesto && presupuesto.motivo_rechazo" class="mt-2 p-3 bg-red-100 rounded-lg">
                <p class="text-xs text-red-600 font-medium">Motivo del rechazo:</p>
                <p class="text-sm text-red-700">{{ presupuesto.motivo_rechazo }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Vehiculo Recibido -->
        <div v-if="orden.estado === 'control_calidad' && presupuesto && presupuesto.estado === 'aprobado'" class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
          <h3 class="text-lg font-bold text-gray-800 mb-2">Entrega del Vehículo</h3>
          <p class="text-sm text-gray-600 mb-4">El vehículo ha superado el control de calidad y está listo para ser entregado al cliente.</p>
          <button
            @click="marcarVehiculoRecibido"
            :disabled="vehiculoRecibidoLoading"
            class="w-full bg-green-600 text-white font-bold py-3 px-4 rounded-xl hover:bg-green-700 transition-colors shadow-sm disabled:opacity-50"
          >
            {{ vehiculoRecibidoLoading ? 'Procesando...' : 'Conforme con la reparación' }}
          </button>
        </div>

        <!-- Timeline -->
        <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
          <h3 class="text-lg font-bold text-gray-800 mb-6">Historial del Servicio</h3>
          
          <div class="relative pl-8 space-y-8 before:absolute before:inset-0 before:ml-4 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-gray-300 before:to-transparent">
            
            <!-- Initial Status -->
            <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
              <div class="flex items-center justify-center w-8 h-8 rounded-full border-4 border-white bg-indigo-500 text-white shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 relative z-10">
                <div class="w-2 h-2 bg-white rounded-full"></div>
              </div>
              <div class="w-[calc(100%-3rem)] md:w-[calc(50%-2rem)] p-4 rounded-xl shadow-sm bg-gray-50 border border-gray-100">
                <div class="flex justify-between items-center mb-1">
                  <span class="font-bold text-gray-800 text-sm">Ingreso al Taller</span>
                  <span class="text-xs font-medium text-gray-500">{{ formatDateTime(orden.created_at) }}</span>
                </div>
              </div>
            </div>

            <!-- History Items -->
            <div v-for="(hist, idx) in historial" :key="idx" class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
              <div class="flex items-center justify-center w-8 h-8 rounded-full border-4 border-white bg-indigo-500 text-white shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 relative z-10">
                <div class="w-2 h-2 bg-white rounded-full"></div>
              </div>
              <div class="w-[calc(100%-3rem)] md:w-[calc(50%-2rem)] p-4 rounded-xl shadow-sm bg-gray-50 border border-gray-100 transform transition hover:-translate-y-1 hover:shadow-md">
                <div class="flex justify-between items-center mb-1">
                  <span class="font-bold text-gray-800 text-sm">Cambio a {{ estadoLabels[hist.estado_nuevo] || hist.estado_nuevo }}</span>
                  <span class="text-xs font-medium text-gray-500">{{ formatDateTime(hist.created_at) }}</span>
                </div>
                <p v-if="hist.estado_nuevo === 'reparacion'" class="text-xs text-gray-600 mt-2">Iniciando trabajos de reparación autorizados.</p>
                <p v-if="hist.estado_nuevo === 'esperando_repuesto'" class="text-xs text-yellow-600 mt-2">Pausa temporal a la espera de repuestos.</p>
                <p v-if="hist.estado_nuevo === 'entregado'" class="text-xs text-green-600 mt-2">Vehículo listo y entregado al cliente.</p>
              </div>
            </div>

          </div>
        </div>

        <!-- Evidencias -->
        <div v-if="evidencias?.length > 0" class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
          <h3 class="text-lg font-bold text-gray-800 mb-6">Evidencias Fotográficas</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div v-for="evidencia in evidencias" :key="evidencia.id" class="bg-gray-50 rounded-xl overflow-hidden shadow-sm border border-gray-100 flex flex-col group">
              <div class="relative h-48 bg-gray-200">
                <img v-if="evidencia.tipo === 'imagen' || evidencia.tipo === 'foto'" :src="evidencia.url" :alt="evidencia.descripcion || evidencia.etiqueta" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                <video v-else :src="evidencia.url" class="w-full h-full object-cover" controls :aria-label="evidencia.descripcion || evidencia.etiqueta">
                  <track kind="captions" />
                </video>
                <div class="absolute top-3 left-3">
                  <span class="inline-flex px-3 py-1 text-xs font-extrabold rounded-full bg-indigo-600 text-white uppercase tracking-wider shadow-md">{{ evidencia.etiqueta }}</span>
                </div>
              </div>
              <div class="p-5 flex-grow bg-white">
                <p class="text-sm text-gray-700 leading-relaxed font-medium">{{ evidencia.descripcion || 'Sin descripción adicional.' }}</p>
                <p class="text-xs text-gray-400 mt-3 font-semibold">{{ formatDateTime(evidencia.created_at) }}</p>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Error Message -->
      <div v-if="error" class="max-w-md mx-auto bg-red-50 border-l-4 border-red-500 rounded-r-lg p-4 mt-6 animate-shake">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-red-700 font-medium">{{ error }}</p>
          </div>
        </div>
      </div>

    </main>

    <!-- Footer -->
    <footer class="bg-gray-100 border-t border-gray-200 mt-auto">
      <div class="max-w-4xl mx-auto px-4 py-6 flex flex-col md:flex-row justify-between items-center text-xs text-gray-500">
        <p>© {{ currentYear }} G&E Motors. Todos los derechos reservados.</p>
        <p class="mt-2 md:mt-0 flex items-center">
          <svg class="h-4 w-4 mr-1 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          Ley 29733 Protección de Datos
        </p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ordenService } from '@/services/ordenService'
import { useDataFetch } from '@/composables/useDataFetch'
import { useNotificacionesStore } from '@/stores/notificaciones'
import BaseButton from '@/components/shared/BaseButton.vue'

const route = useRoute()
const router = useRouter()
const noti = useNotificacionesStore()

const codigo = ref('')
const loading = ref(false)
const orden = ref(null)
const cliente = ref(null)
const evidencias = ref([])
const diagnosticos = ref([])
const historial = ref([])
const presupuesto = ref(null)
const error = ref(null)

const mostrandoMotivo = ref(false)
const motivoRechazo = ref('')
const respuestaLoading = ref(false)
const vehiculoRecibidoLoading = ref(false)
const mecanicoAsignado = ref(null)
const autoTransicionado = ref(false)
const tiempoActual = ref(new Date())

const fechaEstimada = computed(() => {
  if (!mecanicoAsignado.value?.horas_trabajadas || !mecanicoAsignado.value?.fecha_asignacion) return null
  const raw = mecanicoAsignado.value.fecha_asignacion
  const utc = raw.includes('Z') || raw.includes('+') ? raw : raw.replace(' ', 'T') + 'Z'
  const fecha = new Date(utc)
  fecha.setMinutes(fecha.getMinutes() + Math.round(mecanicoAsignado.value.horas_trabajadas * 60))
  return fecha
})

const fechaEntrega = computed(() => {
  const entrada = historial.value.find(h => h.estado_nuevo === 'entregado')
  if (!entrada) return null
  const raw = entrada.created_at
  return new Date(raw.includes('Z') || raw.includes('+') ? raw : raw.replace(' ', 'T') + 'Z')
})

const tiempoRestanteTexto = computed(() => {
  if (!fechaEstimada.value) return null
  const diff = fechaEstimada.value - tiempoActual.value
  if (diff <= 0) return 'Completado'
  const dias = Math.floor(diff / (1000 * 60 * 60 * 24))
  const horas = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  const minutos = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
  if (dias > 0) return `${dias}d ${horas}h restantes`
  if (horas > 0) return `${horas}h ${minutos}min restantes`
  return `${minutos}min restantes`
})

const currentYear = computed(() => new Date().getFullYear())

const estadoLabels = {
  diagnostico: 'En Diagnóstico',
  reparacion: 'En Reparación',
  esperando_repuesto: 'Esperando Repuestos',
  control_calidad: 'En Control de Calidad',
  entregado: 'Entregado',
  cancelado: 'Orden Rechazada'
}

const estadoLabel = computed(() => {
  return estadoLabels[orden.value?.estado] || orden.value?.estado || ''
})

const estadoBadgeClass = computed(() => {
  const classes = {
    diagnostico: 'bg-blue-100 text-blue-800',
    reparacion: 'bg-orange-100 text-orange-800',
    esperando_repuesto: 'bg-yellow-100 text-yellow-800',
    control_calidad: 'bg-purple-100 text-purple-800',
    entregado: 'bg-green-100 text-green-800',
    cancelado: 'bg-red-100 text-red-800'
  }
  return classes[orden.value?.estado] || 'bg-gray-100 text-gray-800'
})

function formatDate(dateString) {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' })
}

function formatHorasMinutos(decimal) {
  if (!decimal && decimal !== 0) return '0h'
  const h = Math.floor(decimal)
  const m = Math.round((decimal - h) * 60)
  return m > 0 ? `${h}h ${m}min` : `${h}h`
}

function formatDateTime(dateString) {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('es-PE', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute:'2-digit' })
}

const { loading: searchLoading, data: searchData, error: searchError, execute: searchOrden } = 
  useDataFetch(() => ordenService.getPorCodigo(codigo.value))

watch(() => searchLoading.value, (val) => { loading.value = val })

watch(() => searchData.value, (val) => {
  if (val && val.data) {
    orden.value = val.data.orden
    cliente.value = val.data.cliente
    evidencias.value = val.data.evidencias || []
    diagnosticos.value = val.data.diagnosticos || []
    historial.value = val.data.historial_estados || []
    presupuesto.value = val.data.presupuesto || null
    mecanicoAsignado.value = val.data.mecanico_asignado || null
    autoTransicionado.value = false
    setTimeout(agendarTransicion, 0)
  }
})

watch(searchError, (err) => {
  if (err) {
    error.value = err.message || 'No encontramos ninguna orden asociada a este código.'
    orden.value = null
  } else {
    error.value = null
  }
})

async function buscarOrden() {
  if (!codigo.value.trim()) return
  router.replace({ params: { codigo: codigo.value } })
  await searchOrden()
}

function resetSearch() {
  orden.value = null
  codigo.value = ''
  error.value = null
  router.replace({ params: { codigo: '' } })
}

async function responderPresupuesto(respuesta) {
  respuestaLoading.value = true
  try {
    const data = { respuesta }
    if (respuesta === 'rechazar') {
      data.motivo_rechazo = motivoRechazo.value
    }
    const res = await ordenService.responderPresupuestoPublico(codigo.value, presupuesto.value.id, data)
    noti.addNotification({ type: 'success', message: res.mensaje || `Presupuesto ${respuesta}do` })
    await searchOrden() // recargar datos
  } catch (err) {
    noti.addNotification({ type: 'error', message: err.message || 'Error al responder el presupuesto' })
  } finally {
    respuestaLoading.value = false
    mostrandoMotivo.value = false
  }
}

async function marcarVehiculoRecibido() {
  vehiculoRecibidoLoading.value = true
  try {
    const res = await ordenService.vehiculoRecibido(codigo.value)
    noti.addNotification({ type: 'success', message: res.mensaje || 'Vehículo marcado como entregado' })
    await searchOrden()
  } catch (err) {
    noti.addNotification({ type: 'error', message: err.message || 'Error al marcar el vehículo como recibido' })
  } finally {
    vehiculoRecibidoLoading.value = false
  }
}

let timerClock = null
let timerTransicion = null

async function ejecutarTransicionAutomatica() {
  if (autoTransicionado.value) return
  autoTransicionado.value = true
  try {
    await ordenService.confirmarReparacion(codigo.value)
    noti.addNotification({
      type: 'success',
      message: '¡Reparación completada! Su vehículo pasó a Control de Calidad y está siendo revisado.'
    })
    await searchOrden()
  } catch {
    autoTransicionado.value = false
  }
}

function agendarTransicion() {
  if (timerTransicion) clearTimeout(timerTransicion)
  if (!fechaEstimada.value) return
  if (!presupuesto.value || presupuesto.value.estado !== 'aprobado') return
  if (!orden.value || orden.value.estado !== 'reparacion') return
  if (autoTransicionado.value) return

  const diff = fechaEstimada.value.getTime() - Date.now()
  if (diff <= 0) {
    ejecutarTransicionAutomatica()
  } else {
    timerTransicion = setTimeout(ejecutarTransicionAutomatica, diff)
  }
}

onMounted(() => {
  if (route.params.codigo) {
    codigo.value = route.params.codigo
    buscarOrden()
  }
  timerClock = setInterval(() => { tiempoActual.value = new Date() }, 60000)
})

onUnmounted(() => {
  if (timerClock) clearInterval(timerClock)
  if (timerTransicion) clearTimeout(timerTransicion)
})
</script>

<style>
.animate-fade-in-up {
  animation: fadeInUp 0.5s ease-out forwards;
}
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-shake {
  animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
}
@keyframes shake {
  10%, 90% { transform: translate3d(-1px, 0, 0); }
  20%, 80% { transform: translate3d(2px, 0, 0); }
  30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
  40%, 60% { transform: translate3d(4px, 0, 0); }
}
.animate-pulse-slow {
  animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>