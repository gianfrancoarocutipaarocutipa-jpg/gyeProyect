<template>
  <div class="p-6">
    <!-- VISTA PARA EL MECÁNICO -->
    <template v-if="userRole === 'mecanico'">
      <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Panel de Trabajo</h2>
        <p class="text-gray-600">Bienvenido a tu panel de control. Aquí puedes ver un resumen de tus asignaciones actuales.</p>
      </div>

      <!-- Métricas del Mecánico -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 border-l-4 border-l-indigo-500">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-indigo-50 rounded-lg flex items-center justify-center mr-4">
              <span class="text-2xl">🔧</span>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-500">Órdenes en Progreso</p>
              <p class="text-2xl font-bold text-gray-900">{{ misOrdenesActivas.length }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 border-l-4 border-l-green-500">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center mr-4">
              <span class="text-2xl">✅</span>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-500">Órdenes Completadas</p>
              <p class="text-2xl font-bold text-gray-900">{{ misOrdenesCompletadas.length }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 border-l-4 border-l-yellow-500">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-yellow-50 rounded-lg flex items-center justify-center mr-4">
              <span class="text-2xl">📦</span>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-500">Stock Bajo (Total)</p>
              <p class="text-2xl font-bold text-gray-900">{{ stockBajo.length }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Acciones Rápidas del Mecánico -->
      <div class="mb-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4 tracking-tight">Acceso Rápido</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <router-link to="/ordenes" class="flex flex-col items-center justify-center p-4 bg-white rounded-xl shadow-sm border border-gray-200 hover:border-indigo-500 hover:shadow-md transition-all group text-center">
            <div class="w-12 h-12 bg-indigo-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-indigo-100 transition-colors">
              <span class="text-2xl">📋</span>
            </div>
            <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Mis Órdenes</span>
          </router-link>
          <router-link to="/diagnostico" class="flex flex-col items-center justify-center p-4 bg-white rounded-xl shadow-sm border border-gray-200 hover:border-purple-500 hover:shadow-md transition-all group text-center">
            <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-purple-100 transition-colors">
              <span class="text-2xl">🔍</span>
            </div>
            <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Diagnóstico</span>
          </router-link>
          <router-link to="/vehiculos" class="flex flex-col items-center justify-center p-4 bg-white rounded-xl shadow-sm border border-gray-200 hover:border-blue-500 hover:shadow-md transition-all group text-center">
            <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-blue-100 transition-colors">
              <span class="text-2xl">🚗</span>
            </div>
            <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Vehículos</span>
          </router-link>
          <router-link to="/inventario" class="flex flex-col items-center justify-center p-4 bg-white rounded-xl shadow-sm border border-gray-200 hover:border-green-500 hover:shadow-md transition-all group text-center">
            <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-green-100 transition-colors">
              <span class="text-2xl">📦</span>
            </div>
            <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Inventario</span>
          </router-link>
        </div>
      </div>
    </template>

    <!-- VISTA PARA EL ADMINISTRADOR (O DEFAULT) -->
    <template v-else>
      <!-- Sección de Acciones Rápidas -->
      <div class="mb-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4 tracking-tight">Acciones Rápidas</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          <!-- Gestionar Órdenes -->
          <router-link
            v-if="esPersonal"
            to="/ordenes" 
            class="flex flex-col items-center justify-center p-4 bg-white rounded-xl shadow-sm border border-gray-200 hover:border-indigo-500 hover:shadow-md transition-all group text-center"
          >
            <div class="w-12 h-12 bg-indigo-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-indigo-100 transition-colors">
              <span class="text-2xl">🔧</span>
            </div>
            <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Órdenes</span>
          </router-link>

          <!-- Clientes -->
          <router-link 
            v-if="esPersonal"
            to="/clientes" 
            class="flex flex-col items-center justify-center p-4 bg-white rounded-xl shadow-sm border border-gray-200 hover:border-indigo-500 hover:shadow-md transition-all group text-center"
          >
            <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-blue-100 transition-colors">
              <span class="text-2xl">👥</span>
            </div>
            <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Clientes</span>
          </router-link>

          <!-- Inventario -->
          <router-link 
            v-if="esPersonal"
            to="/inventario" 
            class="flex flex-col items-center justify-center p-4 bg-white rounded-xl shadow-sm border border-gray-200 hover:border-indigo-500 hover:shadow-md transition-all group text-center"
          >
            <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-green-100 transition-colors">
              <span class="text-2xl">📦</span>
            </div>
            <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Almacén</span>
          </router-link>

          <!-- Diagnóstico -->
          <router-link 
            v-if="esPersonal"
            to="/diagnostico" 
            class="flex flex-col items-center justify-center p-4 bg-white rounded-xl shadow-sm border border-gray-200 hover:border-indigo-500 hover:shadow-md transition-all group text-center"
          >
            <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-purple-100 transition-colors">
              <span class="text-2xl">🔍</span>
            </div>
            <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Diagnóstico</span>
          </router-link>

          <!-- Mis Vehículos (Solo Cliente) -->
          <router-link 
            v-if="userRole === 'cliente'"
            to="/vehiculos" 
            class="flex flex-col items-center justify-center p-4 bg-white rounded-xl shadow-sm border border-gray-200 hover:border-indigo-500 hover:shadow-md transition-all group text-center"
          >
            <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-blue-100 transition-colors">
              <span class="text-2xl">🚗</span>
            </div>
            <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Mis Vehículos</span>
          </router-link>

          <!-- Reportes (Solo Admin) -->
          <router-link 
            v-if="userRole === 'administrador'"
            to="/reportes" 
            class="flex flex-col items-center justify-center p-4 bg-white rounded-xl shadow-sm border border-gray-200 hover:border-indigo-500 hover:shadow-md transition-all group text-center"
          >
            <div class="w-12 h-12 bg-yellow-50 rounded-lg flex items-center justify-center mb-3 group-hover:bg-yellow-100 transition-colors">
              <span class="text-2xl">📈</span>
            </div>
            <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider">Reportes</span>
          </router-link>
        </div>
      </div>

      <div class="flex flex-col">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-medium text-gray-900 tracking-tight">
            Resumen de Métricas
          </h2>
        </div>
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
            <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Métrica
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Valor
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-if="loading" class="hover:bg-gray-50">
                    <td colspan="2" class="px-6 py-4 text-center text-gray-500">
                      Cargando métricas...
                    </td>
                  </tr>
                  <tr v-else-if="!loading && Object.keys(ordenesPorEstado).length === 0 && stockBajo.length === 0 && !ultimaActividad" class="hover:bg-gray-50">
                    <td colspan="2" class="px-6 py-4 text-center text-gray-500">
                      No hay datos disponibles
                    </td>
                  </tr>
                  <template v-else>
                  <tr v-for="(count, estado) in ordenesPorEstado" :key="estado" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      OTs {{ capitalize(estado) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ count }}
                    </td>
                  </tr>

                  <tr v-if="stockBajo.length > 0" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      Repuestos con Stock Bajo
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ stockBajo.length }}
                    </td>
                  </tr>

                  <tr v-if="ultimaActividad" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      Última Actividad
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ ultimaActividad.created_at }}
                    </td>
                  </tr>

                  <tr v-if="ordenesSinMecanico.length > 0" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      Órdenes sin Mecánico Asignado
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ ordenesSinMecanico.length }}
                    </td>
                  </tr>

                  <tr v-if="userRole === 'administrador'" class="hover:bg-gray-50">
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        Ingresos Totales (Aprobados)
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ formatCurrency(totalIngresos) }}
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="mt-6">
          <h2 class="text-lg font-medium text-gray-900">
            Órdenes por Semana
          </h2>
          <div class="mt-4 h-48 w-full overflow-hidden rounded-md shadow-inner relative">
            <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-50">
              <div class="text-gray-500">Cargando gráfico...</div>
            </div>
            <div v-else class="h-full flex items-end">
              <template v-for="(semana, index) in otPorSemana" :key="index">
                <div
                  class="flex-1 bg-indigo-200 hover:bg-indigo-300 transition-colors duration-200 mx-1"
                  :style="{ height: `${(semana / maxOtSemana) * 100}%` }"
                  :title="`Semana ${index + 1}: ${semana} OTs`"
                ></div>
              </template>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-4 flex items-center justify-between px-2 text-xs text-gray-500">
              <span v-for="(semana, index) in otPorSemana" :key="'label-' + index" class="w-full text-center">
                {{ index + 1 }}
              </span>
            </div>
          </div>
        </div>

        <div class="mt-6">
          <h2 class="text-lg font-medium text-gray-900">Alertas Críticas</h2>
          <div class="mt-4 space-y-3">
            <div v-if="loading" class="text-center text-gray-500 py-4">
              Cargando alertas...
            </div>
            <template v-else-if="alertas.length > 0">
              <div v-for="alerta in alertas" :key="alerta.id" class="p-4 rounded-lg" :class="{
                'bg-red-50 border-l-4 border-red-500': alerta.type === 'error',
                'bg-yellow-50 border-l-4 border-yellow-500': alerta.type === 'warning',
                'bg-blue-50 border-l-4 border-blue-500': alerta.type === 'info',
                'bg-green-50 border-l-4 border-green-500': alerta.type === 'success'
              }">
                <div class="flex">
                  <div class="ml-3 w-0 flex-1 pt-0.5">
                    <p class="text-sm font-medium text-gray-900">{{ alerta.title }}</p>
                    <p class="mt-1 text-base text-gray-500">{{ alerta.message }}</p>
                  </div>
                </div>
              </div>
            </template>
            <template v-else>
              <p class="text-center text-gray-500 py-4">No hay alertas críticas</p>
            </template>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { repuestoService } from '@/services/repuestoService'
import { apiService } from '@/services/api'
import { useNotificacionesStore } from '@/stores/notificaciones'

const authStore = useAuthStore()

// Obtenemos el rol de forma segura y reactiva
const userRole = computed(() => {
  return authStore.user?.rol || authStore.userRole || ''
})

// Helper para simplificar v-if en la plantilla
const esPersonal = computed(() => {
  const role = userRole.value?.toLowerCase()
  return ['administrador', 'mecanico'].includes(role)
})

const notificacionesStore = useNotificacionesStore()

const ordenesPorEstado = ref({})
const stockBajo = ref([])
const ultimaActividad = ref(null)
const ordenesSinMecanico = ref([])
const totalIngresos = ref(0)
const otPorSemana = ref([0, 0, 0, 0, 0, 0, 0])
const maxOtSemana = ref(1)
const alertas = ref([])
const loading = ref(true)

// Variables adicionales para el mecánico
const misOrdenesActivas = ref([])
const misOrdenesCompletadas = ref([])

function capitalize(str) {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-PE', {
    style: 'currency',
    currency: 'PEN'
  }).format(value)
}

async function fetchDashboardData() {
  try {
    loading.value = true
    
    // Prepare ingresos promise (only for admins) usando la variable computed
    const ingresosPromise = userRole.value === 'administrador'
      ? apiService.get(`/reportes/ingresos?desde=${new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]}&hasta=${new Date().toISOString().split('T')[0]}`)
      : Promise.resolve({ success: false, data: null })

    // Only admins fetch global stats and incomes
    const statsPromise = userRole.value === 'administrador'
      ? apiService.get('/ordenes/estadisticas')
      : Promise.resolve({ success: true, data: {} })

    const ordenesPorSemanaPromise = userRole.value === 'administrador'
      ? apiService.get('/ordenes/por-semana')
      : Promise.resolve({ success: true, data: [0,0,0,0,0,0,0] })

    // Execute all API calls in parallel
    const [
      statsResponse,
      stockBajoResponse,
      ordenesResponse,
      ordenesPorSemanaResponse,
      ingresosResponse
    ] = await Promise.all([
      statsPromise,
      repuestoService.getStockBajo(),
      apiService.get('/ordenes', { limit: 100 }),
      ordenesPorSemanaPromise,
      ingresosPromise
    ])

    // Process responses
    if (statsResponse.success) {
      const stats = statsResponse.data
      if (Array.isArray(stats)) {
        stats.forEach(item => { ordenesPorEstado.value[item.estado] = item.total })
      } else if (typeof stats === 'object') {
        Object.assign(ordenesPorEstado.value, stats)
      }
    }

    if (stockBajoResponse.success) {
      stockBajo.value = stockBajoResponse.data
    }

    if (ordenesResponse.success) {
      const ordenes = ordenesResponse.data
      
      if (userRole.value === 'administrador') {
        if (ordenes.length > 0) {
          ultimaActividad.value = ordenes.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0]
        }
        ordenesSinMecanico.value = ordenes.filter(o => 
          !o.mecanico_id && ['diagnostico', 'reparacion', 'esperando_repuesto', 'control_calidad'].includes(o.estado)
        )
      } else if (userRole.value === 'mecanico') {
        // En el backend las órdenes ya vienen filtradas por su ID
        misOrdenesActivas.value = ordenes.filter(o => o.estado !== 'entregado')
        misOrdenesCompletadas.value = ordenes.filter(o => o.estado === 'entregado')
      }
    }

    if (ordenesPorSemanaResponse.success) {
      otPorSemana.value = ordenesPorSemanaResponse.data
      maxOtSemana.value = Math.max(...otPorSemana.value) || 1
    }

    if (userRole.value === 'administrador' && ingresosResponse.success) {
      totalIngresos.value = ingresosResponse.data.total_ingresos || 0
    }

    // Generate alerts
    const newAlertas = []
    if (stockBajo.value.length > 0) {
      newAlertas.push({ 
        id: Date.now() + '-stock', 
        type: 'error', 
        title: 'Stock Crítico', 
        message: `Hay ${stockBajo.value.length} repuestos bajo o igual al mínimo.` 
      })
    }
    if (ordenesSinMecanico.value.length > 0) {
      newAlertas.push({ 
        id: Date.now() + '-sin-mecanico', 
        type: 'error', 
        title: 'Órdenes sin Mecánico', 
        message: `${ordenesSinMecanico.value.length} órdenes sin asignar.` 
      })
    }
    alertas.value = newAlertas

  } catch (err) {
    notificacionesStore.addNotification({ 
      type: 'error', 
      message: err.message || 'Error al cargar el dashboard', 
      timeout: 5000 
    })
  } finally {
    loading.value = false
  }
}

onMounted(fetchDashboardData)

onMounted(() => { console.log('Dashboard userRole:', userRole.value, 'esPersonal:', esPersonal.value) })
</script>