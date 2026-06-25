<template>
  <BaseModal :show="show" @close="$emit('update:show', false)">
    <template #header>
      <h3 class="text-lg font-medium text-slate-100">
        Historial de Consumo: {{ repuesto?.nombre }}
      </h3>
    </template>

    <div v-if="loading" class="text-center py-6">Cargando historial...</div>
    
    <div v-else-if="historial.length === 0" class="text-center py-6 text-slate-400">
      Este repuesto no ha sido utilizado en ninguna orden de trabajo.
    </div>

    <div v-else class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
      <div v-for="h in historial" :key="h.orden_id + '_' + h.fecha_asignacion" class="border rounded-lg p-4 bg-slate-800/40 flex justify-between items-center relative overflow-hidden">
        <div class="absolute left-0 top-0 bottom-0 w-1 bg-green-500"></div>
        <div>
          <div class="font-bold text-cyan-300 bg-cyan-500/10 px-2 py-0.5 rounded inline-block mb-1">OT #{{ h.numero_ot }}</div>
          <div class="text-xs text-slate-400">{{ formatDate(h.fecha_asignacion) }}</div>
          <div class="text-xs text-slate-400 mt-1">Estado OT: {{ formatEstado(h.estado) }}</div>
        </div>
        <div class="text-right">
          <div class="text-sm font-medium text-slate-300">Cantidad usada</div>
          <div class="text-xl font-bold text-slate-100">{{ h.cantidad }} und.</div>
        </div>
      </div>
    </div>

    <template #footer>
      <BaseButton variant="outline" @click="$emit('update:show', false)">Cerrar</BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, watch } from 'vue'
import { repuestoService } from '@/services/repuestoService'
import { useDataFetch } from '@/composables/useDataFetch'
import BaseModal from '@/components/shared/BaseModal.vue'
import BaseButton from '@/components/shared/BaseButton.vue'

const props = defineProps({
  show: Boolean,
  repuesto: Object
})

const historial = ref([])
const { loading, execute: fetchHistory } = useDataFetch((id) => repuestoService.getHistorial(id))

watch(() => props.show, async (isShown) => {
  if (isShown && props.repuesto) {
    try {
      const response = await fetchHistory(props.repuesto.id)
      if (response && response.success) {
        historial.value = response.data || []
      } else {
        historial.value = []
      }
    } catch (err) {
      historial.value = []
    }
  }
})

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

function formatDate(dateStr) {
  if (!dateStr) return 'Fecha no disponible'
  const d = new Date(dateStr.replace(' ', 'T'))
  return isNaN(d.getTime()) 
    ? 'Fecha inválida' 
    : d.toLocaleString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
