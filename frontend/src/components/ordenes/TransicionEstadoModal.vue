<template>
  <BaseModal :show="show" z-index="z-[60]" @update:show="$emit('update:show', $event)" @close="handleClose">
    <template #header>
      <h3 class="text-lg font-medium text-slate-100">
        Cambiar Estado: {{ estadoActualLabel }} → {{ nuevoEstadoLabel }}
      </h3>
    </template>

    <div class="space-y-4">
      <!-- Warning for skipping states (RN-03) -->
      <div v-if="estadoSaltado" class="p-4 bg-red-50 border border-red-200 rounded-md">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9 0h18M12 9l-3 3m0 0l3 3m-3-3h6" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-red-800">
              Regla de Negocio RN-03: No se pueden saltar estados
            </p>
            <p class="mt-1 text-sm text-red-700">
              Debe completar primero el estado anterior antes de avanzar.
            </p>
          </div>
        </div>
      </div>

      <!-- Confirmation message for normal transition -->
      <div v-if="!estadoSaltado" class="text-center py-4">
        <p class="text-slate-400">
          ¿Confirma cambiar el estado de la OT #{{ otId }} a <strong>{{ nuevoEstadoLabel }}</strong>?
        </p>
      </div>

      <!-- Notes field for specific states -->
      <div v-if="mostrarCampoNotas && !estadoSaltado">
        <label for="observaciones" class="block text-sm font-medium text-slate-300 mb-1">
          {{ labelObservaciones }}
        </label>
        <textarea
          id="observaciones"
          rows="3"
          v-model="observaciones"
          class="w-full px-3 py-2 border border-slate-600 rounded-md shadow-md shadow-black/10 focus:outline-none focus:ring-cyan-500/40 focus:border-cyan-500 sm:text-sm"
          :placeholder="placeholderObservaciones"
        ></textarea>
      </div>
    </div>

    <template #footer>
      <BaseButton
        variant="outline"
        size="md"
        @click="$emit('update:show', false)"
        :disabled="loading"
      >
        Cancelar
      </BaseButton>
      <BaseButton
        variant="primary"
        size="md"
        :disabled="botonDeshabilitado"
        :loading="loading"
        @click="confirmarTransicion"
      >
        Confirmar Cambio
      </BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import BaseModal from '@/components/shared/BaseModal.vue'
import BaseButton from '@/components/shared/BaseButton.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  otId: {
    type: [Number, String],
    required: true
  },
  estadoActual: {
    type: String,
    required: true,
    validator: (v) => ['diagnostico', 'esperando_repuesto', 'reparacion', 'control_calidad', 'entregado'].includes(v)
  },
  nuevoEstado: {
    type: String,
    required: true,
    validator: (v) => ['diagnostico', 'esperando_repuesto', 'reparacion', 'control_calidad', 'entregado'].includes(v)
  },
  presupuestoAprobado: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:show', 'confirmar', 'close'])

const observaciones = ref('')

const ordenEstados = [
  { key: 'diagnostico', label: 'Diagnóstico' },
  { key: 'esperando_repuesto', label: 'Esperando Repuesto' },
  { key: 'reparacion', label: 'Reparación' },
  { key: 'control_calidad', label: 'Control de Calidad' },
  { key: 'entregado', label: 'Entregado' }
]

const estadoIndexActual = computed(() => ordenEstados.findIndex(e => e.key === props.estadoActual))
const estadoIndexNuevo = computed(() => ordenEstados.findIndex(e => e.key === props.nuevoEstado))

const estadoSaltado = computed(() => {
  return estadoIndexNuevo.value > estadoIndexActual.value + 1
})

const estadoActualLabel = computed(() => {
  const estado = ordenEstados.find(e => e.key === props.estadoActual)
  return estado ? estado.label : props.estadoActual
})

const nuevoEstadoLabel = computed(() => {
  const estado = ordenEstados.find(e => e.key === props.nuevoEstado)
  return estado ? estado.label : props.nuevoEstado
})

const mostrarCampoNotas = computed(() => {
  return ['control_calidad', 'entregado'].includes(props.nuevoEstado)
})

const labelObservaciones = computed(() => {
  const labels = {
    control_calidad: 'Observaciones de Control de Calidad',
    entregado: 'Observaciones de Entrega'
  }
  return labels[props.nuevoEstado] || 'Observaciones'
})

const placeholderObservaciones = computed(() => {
  const placeholders = {
    control_calidad: 'Detalles del control de calidad realizado, pruebas efectuadas...',
    entregado: 'Estado final del vehículo, kilometraje de entrega, observaciones adicionales...'
  }
  return placeholders[props.nuevoEstado] || 'Ingrese observaciones...'
})

const botonDeshabilitado = computed(() => {
  return estadoSaltado.value || props.loading
})

function handleClose() {
  emit('close')
  observaciones.value = ''
}

function confirmarTransicion() {
  if (botonDeshabilitado.value) return

  emit('confirmar', {
    otId: props.otId,
    nuevoEstado: props.nuevoEstado,
    observaciones: observaciones.value
  })
}

watch(() => props.show, (newVal) => {
  if (!newVal) {
    observaciones.value = ''
  }
})
</script>