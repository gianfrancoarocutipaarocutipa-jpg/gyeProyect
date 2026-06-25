<template>
  <div class="p-6">
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-slate-100">Reportes del Taller</h2>
      <p class="text-sm text-slate-400 mt-1">Análisis de ingresos, productividad y rotación</p>
    </div>

    <nav class="flex space-x-4 mb-6 border-b border-slate-700/50">
      <button
        v-for="tab in tabs" :key="tab.id"
        @click="switchTab(tab.id)"
        :class="['px-4 py-2 text-sm font-medium border-b-2', activeTab === tab.id ? 'border-cyan-500 text-cyan-400' : 'border-transparent text-slate-400']"
      >
        {{ tab.label }}
      </button>
    </nav>

    <div v-if="loading" class="text-center py-8">Cargando datos...</div>

    <div v-else class="space-y-6">
      <div v-show="activeTab === 'ingresos'" class="bg-slate-800/60 rounded-lg shadow-lg shadow-black/20 p-6">
        <div class="grid grid-cols-2 gap-4 mb-4">
          <input v-model="fechas.desde" type="date" class="px-3 py-2 border rounded-md" />
          <input v-model="fechas.hasta" type="date" class="px-3 py-2 border rounded-md" />
        </div>
        <BaseButton variant="primary" @click="fetchIngresos">Actualizar Reporte</BaseButton>
        <div v-if="reporteIngresos" class="mt-6 text-3xl font-bold">S/ {{ formatCurrency(reporteIngresos.total_ingresos) }}</div>
      </div>

      <div v-show="activeTab === 'productividad'" class="bg-slate-800/60 rounded-lg shadow-lg shadow-black/20 p-6">
        <BaseTable :columns="productividadColumns" :data="reporteProductividad" />
      </div>

      <div v-show="activeTab === 'rotacion'" class="bg-slate-800/60 rounded-lg shadow-lg shadow-black/20 p-6">
        <BaseButton variant="primary" class="mb-4" @click="exportarInventario">Exportar CSV</BaseButton>
        <BaseTable :columns="rotacionColumns" :data="reporteRotacion" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { reporteService } from '@/services/reporteService'
import { useNotificacionesStore } from '@/stores/notificaciones'
import { useDataFetch } from '@/composables/useDataFetch'
import BaseButton from '@/components/shared/BaseButton.vue'
import BaseTable from '@/components/shared/BaseTable.vue'

const noti = useNotificacionesStore()
const activeTab = ref('ingresos')
const loading = ref(false)
const loaded = ref({ ingresos: false, productividad: false, rotacion: false })

const productividadColumns = [
  { key: 'mecanico_nombre', label: 'Mecánico' },
  { key: 'ordenes_completadas', label: 'OTs Completadas' },
  { key: 'total_horas', label: 'Horas Totales' }
]

const rotacionColumns = [
  { key: 'nombre', label: 'Repuesto' },
  { key: 'codigo_oem', label: 'OEM' },
  { key: 'veces_utilizado', label: 'Uso (unidades)' }
]

const fechas = ref({
  desde: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
  hasta: new Date().toISOString().split('T')[0]
})

// Data refs...
const reporteIngresos = ref(null)
const reporteProductividad = ref([])
const reporteRotacion = ref([])

const tabs = [
  { id: 'ingresos', label: 'Ingresos' },
  { id: 'productividad', label: 'Productividad' },
  { id: 'rotacion', label: 'Rotación' }
]

// ===== Data fetching for ingresos =====
const { loading: ingresosLoading, data: ingresosData, error: ingresosError, execute: fetchIngresos } = 
  useDataFetch(() => reporteService.getIngresos(fechas.value))

// ===== Data fetching for productividad =====
const { loading: productividadLoading, data: productividadData, error: productividadError, execute: fetchProductividad } = 
  useDataFetch(() => reporteService.getProductividad(fechas.value))

// ===== Data fetching for rotacion =====
const { loading: rotacionLoading, data: rotacionData, error: rotacionError, execute: fetchRotacion } = 
  useDataFetch(() => reporteService.getRotacionRepuestos())

// ===== Mutation for exporting inventario =====
const { loading: exportarLoading, data: exportarData, error: exportarError, execute: exportarInventarioFile } = 
  useDataFetch(() => reporteService.exportarExcelInventario())

// Update data refs from composables
watch(() => ingresosData.value, (val) => { 
  if (val && val.data) reporteIngresos.value = val.data 
})
watch(() => productividadData.value, (val) => { 
  reporteProductividad.value = (val && Array.isArray(val.data)) ? val.data : [] 
})
watch(() => rotacionData.value, (val) => { 
  reporteRotacion.value = (val && Array.isArray(val.data)) ? val.data : [] 
})

// Handle errors and show notifications
watch([ingresosError, productividadError, rotacionError, exportarError], ([iErr, pErr, rErr, eErr]) => {
  if (iErr) {
    noti.addNotification({ type: 'error', message: 'Error en ingresos', timeout: 3000 })
  }
  if (pErr) {
    noti.addNotification({ type: 'error', message: 'Error en productividad', timeout: 3000 })
  }
  if (rErr) {
    noti.addNotification({ type: 'error', message: 'Error en rotación', timeout: 3000 })
  }
  if (eErr) {
    noti.addNotification({ type: 'error', message: 'Error al exportar', timeout: 3000 })
  }
})

// Update loading state based on the active tab's fetch loading
watch([ingresosLoading, productividadLoading, rotacionLoading, exportarLoading], ([iL, pL, rL, eL]) => {
  if (activeTab.value === 'ingresos') {
    loading.value = iL
  } else if (activeTab.value === 'productividad') {
    loading.value = pL
  } else if (activeTab.value === 'rotacion') {
    loading.value = rL || eL // For rotacion tab, we also consider the export loading
  }
})

// Función para cambiar de pestaña y cargar datos solo si no se han cargado antes
async function switchTab(tabId) {
  activeTab.value = tabId
  if (!loaded.value[tabId]) {
    if (tabId === 'ingresos') {
      await fetchIngresos()
    } else if (tabId === 'productividad') {
      await fetchProductividad()
    } else if (tabId === 'rotacion') {
      await fetchRotacion()
    }
    loaded.value[tabId] = true
  }
}

async function exportarInventario() {
  try {
    const blob = await exportarInventarioFile()
    // Trigger download
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'inventario.xlsx'
    a.click()
    window.URL.revokeObjectURL(url)
    noti.addNotification({ type: 'success', message: 'Exportado exitosamente', timeout: 3000 })
  } catch (err) {
    // Error handled by watcher
  }
}

function formatCurrency(value) {
  return parseFloat(value).toFixed(2)
}

onMounted(() => switchTab('ingresos'))
</script>