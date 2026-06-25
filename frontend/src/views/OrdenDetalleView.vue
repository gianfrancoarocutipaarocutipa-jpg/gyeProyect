<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-slate-100">Expediente OT #{{ orden?.numero_ot }}</h2>
      <div class="flex items-center space-x-2">
        <select v-model="pdfType" class="border-slate-600 rounded-md shadow-md shadow-black/10 text-sm focus:ring-blue-500 focus:border-blue-500">
          <option value="">Generar Reporte...</option>
          <option value="diagnostico">Diagnóstico OBD-II</option>
          <option value="hoja_ruta">Hoja de Ruta</option>
          <option value="boleta">Boleta de Servicio</option>
        </select>
        <BaseButton v-if="pdfType" variant="primary" size="sm" @click="descargarPdf" :disabled="isPdfLoading">
          <span v-if="isPdfLoading">...</span><span v-else>Descargar</span>
        </BaseButton>
        <BaseButton v-if="pdfType" variant="secondary" size="sm" @click="enviarPdf" :disabled="isPdfLoading">
          <span v-if="isPdfLoading">...</span><span v-else>Enviar al Cliente</span>
        </BaseButton>
        <BaseButton variant="outline" @click="volverALista">Volver</BaseButton>
      </div>
    </div>

    <BaseAlert v-if="alert" :show="true" :variant="alert.type" :message="alert.message" @update:show="alert = null" />

    <div v-if="loading" class="text-center py-12">Cargando datos...</div>

    <div v-else-if="orden" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="space-y-6">
        <InfoCard title="Cliente" :data="orden.cliente" />
        <InfoCard title="Vehículo" :data="orden.vehiculo" />
      </div>

      <div class="lg:col-span-2 space-y-6">
        <div class="bg-slate-800/60 rounded-lg shadow p-4">
          <h3 class="font-bold mb-3">Estado del Proceso</h3>
          <OrdenEstadoPipeline :estado-actual="orden.estado" :ot-id="orden.id" @transicion-estado="abrirTransicion" />
        </div>

        <EvidenciasSection 
          :evidencias="evidencias" 
          :bloqueado="estadoBloqueado"
          @upload="handleFiles" 
          @delete="eliminarEvidencia" 
        />

        <div class="bg-slate-800/60 rounded-lg shadow p-4">
          <div class="flex justify-between items-center mb-3">
            <h3 class="font-bold">Repuestos Asignados</h3>
            <BaseButton v-if="!estadoBloqueado" size="sm" @click="showModalRep = true">Asignar</BaseButton>
          </div>
          <BaseTable :columns="cols" :data="repuestos" />
        </div>
      </div>
    </div>

    <TransicionEstadoModal v-model:show="showModalTrans" :ot-id="orden?.id" :nuevo-estado="objEstado" @confirmar="ejecutarTransicion" />
    <AsignarRepuestoModal v-model:show="showModalRep" :ot-id="orden?.id" @saved="fetchRepuestos" />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ordenService } from '@/services/ordenService'
import { apiService } from '@/services/api'
import { useNotificacionesStore } from '@/stores/notificaciones'
import { useDataFetch } from '@/composables/useDataFetch'

// Componentes (Asumiendo que los extrajiste para reducir líneas)
import InfoCard from './components/InfoCard.vue'
import EvidenciasSection from './components/EvidenciasSection.vue'

const route = useRoute()
const router = useRouter()
const noti = useNotificacionesStore()

const orden = ref(null)
const evidencias = ref([])
const repuestos = ref([])
const loading = ref(false)
const alert = ref(null)

const showModalTrans = ref(false)
const showModalRep = ref(false)
const objEstado = ref(null)

const estadoBloqueado = computed(() => orden.value?.estado === 'entregado')
const cols = [{ key: 'codigo_oem', label: 'OEM' }, { key: 'nombre', label: 'Repuesto' }, { key: 'cantidad', label: 'Cant' }]

// ===== Data fetching for orden, evidencias, repuestos =====
const { loading: loadOrdenLoading, data: ordenData, error: ordenError, execute: fetchOrden } = 
  useDataFetch(() => ordenService.getById(route.params.id))

const { loading: loadEvidenciasLoading, data: evidenciasData, error: evidenciasError, execute: fetchEvidencias } = 
  useDataFetch(() => ordenService.getEvidencias(route.params.id))

const { loading: loadRepuestosLoading, data: repuestosData, error: repuestosError, execute: fetchRepuestos } = 
  useDataFetch(() => ordenService.getRepuestos(route.params.id))

// Combined loading state
watch([loadOrdenLoading, loadEvidenciasLoading, loadRepuestosLoading], ([oL, eL, rL]) => {
  loading.value = oL || eL || rL
})

// Update data refs from composables
watch(() => ordenData.value, (val) => { if (val) orden.value = val })
watch(() => evidenciasData.value, (val) => { if (val) evidencias.value = val })
watch(() => repuestosData.value, (val) => { if (val) repuestos.value = val })

// Handle errors
watch([ordenError, evidenciasError, repuestosError], ([oErr, eErr, rErr]) => {
  if (oErr || eErr || rErr) {
    const error = oErr || eErr || rErr
    alert.value = { type: 'error', message: error.message || 'Error cargando expediente' }
  } else {
    alert.value = null
  }
})

// ===== Mutations =====
const { loading: uploadLoading, data: uploadData, error: uploadError, execute: uploadFile } = 
  useDataFetch(apiService.uploadToCloudinary)

const { loading: createEvidenciaLoading, data: createEvidenciaData, error: createEvidenciaError, execute: createEvidencia } = 
  useDataFetch(ordenService.crearEvidencia)

const { loading: deleteEvidenciaLoading, data: deleteEvidenciaData, error: deleteEvidenciaError, execute: deleteEvidencia } = 
  useDataFetch(ordenService.eliminarEvidencia)

const { loading: cambiarEstadoLoading, data: cambiarEstadoData, error: cambiarEstadoError, execute: cambiarEstado } = 
  useDataFetch(ordenService.cambiarEstado)

// ===== Helper functions =====
async function loadAll() {
  // The data is fetched automatically by the composables when the route.params.id changes or on mount.
  // We don't need to do anything here because the watchers will update the data.
  // However, we need to trigger the initial fetch.
  // We'll do it by calling the execute functions.
  try {
    await fetchOrden()
    await fetchEvidencias()
    await fetchRepuestos()
  } catch (err) {
    // Error handled by composables and watchers
  }
}

async function handleFiles(files) {
  for (const file of files) {
    try {
      const uploadResponse = await uploadFile(file)
      if (uploadResponse.success) {
        const evidenciaResponse = await createEvidencia(orden.value.id, {
          url_cloudinary: uploadResponse.url,
          tipo: file.type.includes('image') ? 'foto' : 'video'
        })
        if (evidenciaResponse.success) {
          // The evidencias composable will be updated when we call fetchEvidencias.execute()
          // But we can also push to the local evidencias array for immediate feedback
          evidencias.value.push(evidenciaResponse.data)
        }
      }
    } catch (err) {
      // Error handled by composables
    }
  }
  noti.addNotification({ type: 'success', message: 'Archivos procesados' })
}

async function eliminarEvidencia(id) {
  try {
    await deleteEvidencia(orden.value.id, id)
    // Remove from local array for immediate feedback
    evidencias.value = evidencias.value.filter(e => e.id !== id)
    // Also refetch to be safe
    await fetchEvidencias()
  } catch (err) {
    // Error handled by composable
  }
}

function abrirTransicion({ nuevoEstado }) {
  objEstado.value = nuevoEstado
  showModalTrans.value = true
}

async function ejecutarTransicion({ observaciones }) {
  try {
    await cambiarEstado(orden.value.id, { estado: objEstado.value, observaciones })
    // Reload the orden, evidencias, and repuestos
    await fetchOrden()
    await fetchEvidencias()
    await fetchRepuestos()
  } catch (err) {
    // Error handled by composable
  } finally {
    showModalTrans.value = false
  }
}

function volverALista() { 
  router.push({ name: 'Ordenes' }) 
}

// ===== PDF Handling =====
const pdfType = ref('')
const isPdfLoading = ref(false)

async function descargarPdf() {
  if (!pdfType.value || !orden.value?.id) return
  isPdfLoading.value = true
  try {
    const url = `${import.meta.env.VITE_API_BASE || 'http://localhost:8000/api'}/reportes/pdf/orden/${orden.value.id}?tipo=${pdfType.value}`
    const response = await fetch(url, {
      method: 'GET',
      headers: { 'Authorization': `Bearer ${apiService.getToken()}` }
    })
    if (!response.ok) throw new Error('Error al generar PDF')
    
    const blob = await response.blob()
    const objectUrl = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = objectUrl
    link.download = `reporte_${pdfType.value}_${orden.value.numero_ot}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(objectUrl)
    
    noti.addNotification({ type: 'success', message: 'PDF descargado exitosamente' })
  } catch (err) {
    noti.addNotification({ type: 'error', message: err.message || 'Error al descargar PDF' })
  } finally {
    isPdfLoading.value = false
  }
}

async function enviarPdf() {
  if (!pdfType.value || !orden.value?.id) return
  isPdfLoading.value = true
  try {
    const response = await apiService.post(`/reportes/pdf/orden/${orden.value.id}/enviar?tipo=${pdfType.value}`)
    noti.addNotification({ type: 'success', message: response.mensaje || 'PDF enviado exitosamente' })
  } catch (err) {
    noti.addNotification({ type: 'error', message: err.message || 'Error al enviar PDF' })
  } finally {
    isPdfLoading.value = false
  }
}

// Initial fetch
loadAll()
</script>