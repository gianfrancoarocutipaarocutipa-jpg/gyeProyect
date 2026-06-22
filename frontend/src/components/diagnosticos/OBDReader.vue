<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-2xl font-bold text-gray-900">
        Lector OBD-II
      </h2>
      <span class="text-sm text-gray-500">
        Escanea la ECU del vehículo para leer códigos de falla
      </span>
    </div>

    <!-- Selector de Escenario de Prueba (Solo en Desarrollo) -->
    <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
      <label class="block text-sm font-medium text-amber-800 mb-2">Simulador de Fallas (Escenario de prueba)</label>
      <select v-model="selectedScenario" class="w-full px-3 py-2 border-amber-300 rounded-md text-sm focus:ring-amber-500 focus:border-amber-500">
        <option value="43 01 03 00">P0103 - Entrada Alta Sensor Masa Aire (MAF)</option>
        <option value="43 01 00 00">P0000 - Fallos Aleatorios / Sin fallas críticas</option>
        <option value="43 03 00 01 00 00">P0300, P0100 - Fallas múltiples (Encendido + MAF)</option>
        <option value="43 01 30 00">P0130 - Sensor Oxígeno (Banco 1, Sensor 1)</option>
        <option value="43 40 10 00">B0010 - Falla en Iluminación Interior</option>
        <option value="CUSTOM">Ingresar trama manual...</option>
        <option value="SIMULATOR">🔌 Simulador OBD en vivo</option>
      </select>
      <input v-if="selectedScenario === 'CUSTOM'" v-model="customHex" type="text" placeholder="Ej: 43 01 03 00" class="mt-2 w-full px-3 py-2 border rounded-md text-mono" />
    </div>

    <!-- Estado de escaneo -->
    <div v-if="scanning" class="text-center py-8">
      <div class="flex items-center justify-center space-x-4">
        <div class="w-12 h-12 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
        <p class="text-indigo-600 font-medium">Leyendo datos de la ECU...</p>
      </div>
    </div>

    <div v-else>
      <!-- Panel de parámetros del motor (solo modo SIMULATOR) -->
      <div v-if="dashboardData" class="mb-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Parámetros del motor en tiempo real</h3>
        <div class="grid grid-cols-3 gap-2 text-sm">
          <div class="bg-white rounded p-2 border text-center">
            <p class="text-xs text-gray-500">RPM</p>
            <p class="font-bold text-indigo-600">{{ dashboardData.data?.rpm?.valor ?? '—' }}</p>
          </div>
          <div class="bg-white rounded p-2 border text-center">
            <p class="text-xs text-gray-500">Velocidad</p>
            <p class="font-bold text-indigo-600">{{ dashboardData.data?.velocidad?.valor ?? '—' }} km/h</p>
          </div>
          <div class="bg-white rounded p-2 border text-center">
            <p class="text-xs text-gray-500">Temp. Ref.</p>
            <p class="font-bold text-orange-500">{{ dashboardData.data?.temp?.valor ?? '—' }} °C</p>
          </div>
          <div class="bg-white rounded p-2 border text-center">
            <p class="text-xs text-gray-500">Carga motor</p>
            <p class="font-bold text-gray-700">{{ dashboardData.data?.carga?.valor ?? '—' }} %</p>
          </div>
          <div class="bg-white rounded p-2 border text-center">
            <p class="text-xs text-gray-500">Voltaje</p>
            <p class="font-bold text-gray-700">{{ dashboardData.data?.voltaje?.valor ?? '—' }} V</p>
          </div>
          <div class="bg-white rounded p-2 border text-center">
            <p class="text-xs text-gray-500">Km totales</p>
            <p class="font-bold text-gray-700">{{ dashboardData.data?.km_totales?.valor ?? '—' }}</p>
          </div>
        </div>
        <p v-if="dashboardData.ciclo_conduccion" class="mt-2 text-xs text-gray-500 text-center">
          Ciclo: <span class="font-medium capitalize">{{ dashboardData.ciclo_conduccion }}</span>
        </p>
      </div>

      <!-- Botón de escaneo -->
      <div v-if="results.length === 0" class="text-center py-8">
        <BaseButton variant="primary" size="lg" @click="startScan">
          Iniciar Escaneo de la ECU
        </BaseButton>
      </div>

      <!-- Resultados -->
      <div v-else class="mt-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4">
          Códigos de Falla Detectados
        </h3>
        <div class="space-y-3">
          <div
            v-for="(dtc, index) in results"
            :key="index"
            class="p-4 bg-white rounded-lg shadow-md border-l-4"
            :class="{ 'border-indigo-500': dtc.tipo === 'P', 'border-blue-500': dtc.tipo === 'B', 'border-green-500': dtc.tipo === 'C', 'border-yellow-500': dtc.tipo === 'U' }"
          >
            <div class="flex justify-between items-start">
              <div>
                <p class="text-lg font-semibold text-indigo-600">
                  {{ dtc.codigo }}
                </p>
                <p class="text-sm text-gray-500">
                  {{ dtc.sistema || 'Sistema no especificado' }}
                </p>
              </div>
              <div class="text-right">
                <p class="text-sm font-medium text-gray-600">
                  Severidad
                </p>
                <p class="text-lg font-bold" :class="{
                  'text-red-600': dtc.severidad === 'Alta',
                  'text-yellow-600': dtc.severidad === 'Media',
                  'text-green-600': dtc.severidad === 'Baja'
                }">
                  {{ dtc.severidad }}
                </p>
              </div>
            </div>
            <p class="mt-3 text-base text-gray-700 leading-relaxed">
              {{ dtc.descripcion }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Errores -->
    <div v-if="error" class="mt-4 p-4 bg-red-50 border-l-4 border-red-500 text-sm text-red-700">
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { diagnosticoService } from '@/services/diagnosticoService'
import BaseButton from '@/components/shared/BaseButton.vue'

const scanning = ref(false)
const emit = defineEmits(['scan-completed'])
const results = ref([])
const lastRawHex = ref('')
const kilometrajeSimulador = ref(null)
const dashboardData = ref(null)
const selectedScenario = ref('43 01 03 00')
const customHex = ref('')
const error = ref(null)

defineExpose({
  results,
  lastRawHex,
  kilometrajeSimulador,
  hasRawHex: computed(() => !!lastRawHex.value),
  reset: () => {
    results.value = []
    lastRawHex.value = ''
    kilometrajeSimulador.value = null
    dashboardData.value = null
  }
})

function simulateScanDelay() {
  return new Promise(resolve => setTimeout(resolve, 3000))
}

async function fetchWithTimeout(url, timeoutMs = 5000) {
  const controller = new AbortController()
  const id = setTimeout(() => controller.abort(), timeoutMs)
  try {
    const res = await fetch(url, { signal: controller.signal })
    clearTimeout(id)
    return res
  } catch (err) {
    clearTimeout(id)
    throw err
  }
}

async function startSimulatorScan() {
  const simulatorBase = import.meta.env.VITE_SIMULATOR_URL || 'http://localhost:8080'

  // PASO 1: Verificar disponibilidad del simulador
  try {
    const healthRes = await fetchWithTimeout(`${simulatorBase}/health`)
    if (!healthRes.ok) throw new Error()
  } catch {
    error.value = 'Simulador OBD no disponible. Verifica que esté corriendo en puerto 8080.'
    return
  }

  // PASO 2: Obtener trama hex (se enviará al backend al guardar el diagnóstico)
  let rawHex = ''
  try {
    const pidRes = await fetchWithTimeout(`${simulatorBase}/pid?pid=03`)
    const pidData = await pidRes.json()
    rawHex = pidData.data?.hex ?? ''
  } catch {
    // No fatal: los DTCs se obtienen del dashboard en el paso 3
  }
  lastRawHex.value = rawHex

  // PASO 3: Obtener parámetros del motor y DTCs activos del dashboard
  try {
    const dashRes = await fetchWithTimeout(`${simulatorBase}/dashboard`)
    const dash = await dashRes.json()
    dashboardData.value = dash
    kilometrajeSimulador.value = dash.data?.km_totales?.valor ?? null

    const dtcsActivos = dash.dtcs_activos ?? []
    results.value = await Promise.all(
      dtcsActivos.map(async (codigo) => {
        const tipo = codigo[0]
        const severidad = (tipo === 'P' || tipo === 'U') ? 'Alta' : 'Media'
        try {
          const resp = await diagnosticoService.interpretarCodigo(codigo)
          const info = (resp.success && resp.data) ? resp.data : {}
          return { codigo, tipo, sistema: info.sistema || tipo, descripcion: info.descripcion || '', severidad }
        } catch {
          return { codigo, tipo, sistema: tipo, descripcion: '', severidad }
        }
      })
    )
  } catch {
    // Dashboard falló; continuamos con la trama capturada (km y parámetros vacíos)
  }

  emit('scan-completed', results.value)
}

async function startScan() {
  scanning.value = true
  error.value = null
  results.value = []
  dashboardData.value = null
  kilometrajeSimulador.value = null

  try {
    await simulateScanDelay()

    if (selectedScenario.value === 'SIMULATOR') {
      await startSimulatorScan()
      return
    }

    const rawHex = selectedScenario.value === 'CUSTOM' ? customHex.value : selectedScenario.value
    lastRawHex.value = rawHex

    const response = await diagnosticoService.parsearTramaHex(rawHex)
    if (response.success) {
      results.value = response.data.map(dtc => {
        let severidad = 'N/A'
        if (dtc.tipo === 'P' || dtc.tipo === 'U') {
          severidad = 'Alta'
        } else if (dtc.tipo === 'B' || dtc.tipo === 'C') {
          severidad = 'Media'
        }
        return { ...dtc, severidad }
      })
      emit('scan-completed', results.value)
    } else {
      throw new Error(response.mensaje || 'Error al procesar las tramas')
    }
  } catch (err) {
    error.value = err.message || 'Error inesperado durante el escaneo'
  } finally {
    scanning.value = false
  }
}
</script>
