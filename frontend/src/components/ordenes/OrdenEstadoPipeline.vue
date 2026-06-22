<template>
  <div class="w-full">
    <div class="relative">
      <!-- Pipeline line -->
      <div class="absolute top-5 left-0 right-0 h-1 bg-gray-200 rounded-full" aria-hidden="true">
        <div
          class="h-full bg-indigo-600 rounded-full transition-all duration-300"
          :style="{ width: `${progressWidth}%` }"
        ></div>
      </div>

      <!-- States -->
      <div class="relative flex justify-between">
        <div
          v-for="(estado, index) in estados"
          :key="estado.key"
          class="flex flex-col items-center"
        >
          <button
            type="button"
            :disabled="!estado.clickable"
            @click="!estado.completado && estado.clickable ? emitirTransicion(estado.key) : null"
            :class="[
              'w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium transition-all duration-200',
              'focus:outline-none focus:ring-2 focus:ring-offset-2',
              estadoClasses(estado, index)
            ]"
            :title="estado.clickable && !estado.completado ? `Cambiar a ${estado.label}` : estado.completado ? `${estado.label} completado` : `Estado bloqueado: ${estado.label}`"
          >
            <template v-if="estado.completado">
              <svg
                class="w-5 h-5"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </template>
            <template v-else>
              <span>{{ index + 1 }}</span>
            </template>
          </button>
          <span class="mt-2 text-xs font-medium text-gray-600 text-center max-w-[80px]">
            {{ estado.label }}
          </span>
        </div>
      </div>
    </div>

    <!-- Aviso en estado esperando_repuesto -->
    <div v-if="mostrarBloqueoReparacion" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9 0h18M12 9l-3 3m0 0l3 3m-3-3h6" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-yellow-700">
            <strong>Pendiente:</strong> Asigne los mecánicos y repuestos necesarios, luego confirme para pasar a Reparación.
          </p>
        </div>
      </div>
    </div>

    <!-- Already completed state warning -->
    <div v-if="estadoActualFinalizado" class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-md">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-gray-600">
            La orden de trabajo ha finalizado. No se pueden realizar más transiciones de estado.
          </p>
        </div>
      </div>
    </div>

    <!-- Cancelled state -->
    <div v-if="estadoActualCancelado" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-red-700 font-medium">Orden Rechazada</p>
          <p class="text-sm text-red-600">El cliente rechazó el presupuesto. Esta orden ha sido cancelada.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  estadoActual: {
    type: String,
    required: true,
    validator: (v) => ['diagnostico', 'reparacion', 'esperando_repuesto', 'control_calidad', 'entregado', 'cancelado'].includes(v)
  },
  presupuestoAprobado: {
    type: Boolean,
    default: false
  },
  otId: {
    type: [Number, String],
    required: true
  }
})

const emit = defineEmits(['transicion-estado'])

const ordenEstados = [
  'diagnostico',
  'esperando_repuesto',
  'reparacion',
  'control_calidad',
  'entregado'
]

const estadoLabels = {
  diagnostico: 'Diagnóstico',
  reparacion: 'Reparación',
  esperando_repuesto: 'Esperando Repuesto',
  control_calidad: 'Control de Calidad',
  entregado: 'Entregado'
}

const estadoIndex = computed(() => {
  return ordenEstados.indexOf(props.estadoActual)
})

const estados = computed(() => {
  const result = ordenEstados.map((key, index) => {
    const completado = index < estadoIndex.value
    const esActual = index === estadoIndex.value
    const esSiguiente = index === estadoIndex.value + 1

    let clickable = false
    if (esActual) {
      clickable = false
    } else if (completado) {
      clickable = false
    } else if (esSiguiente) {
      if (key === 'reparacion') {
        clickable = false // La transición a reparación se hace via botón de confirmación
      } else {
        clickable = true
      }
    }

    return {
      key,
      label: estadoLabels[key],
      completado,
      actual: esActual,
      clickable
    }
  })
  return result
})

const progressWidth = computed(() => {
  if (estadoIndex.value >= ordenEstados.length - 1) return 100
  const total = ordenEstados.length - 1
  const completed = estadoIndex.value
  return (completed / total) * 100
})

const mostrarBloqueoReparacion = computed(() => {
  return props.estadoActual === 'esperando_repuesto'
})

const estadoActualFinalizado = computed(() => {
  return props.estadoActual === 'entregado'
})

const estadoActualCancelado = computed(() => {
  return props.estadoActual === 'cancelado'
})

function estadoClasses(estado, index) {
  if (estado.completado) {
    return 'bg-blue-600 text-white cursor-not-allowed'
  }
  if (estado.clickable) {
    return 'bg-green-600 text-white ring-4 ring-green-200 cursor-pointer hover:bg-green-700 focus:ring-green-500'
  }
  if (estado.actual) {
    return 'bg-indigo-600 text-white cursor-not-allowed'
  }
  return 'bg-gray-300 text-gray-500 cursor-not-allowed'
}

function emitirTransicion(nuevoEstado) {
  emit('transicion-estado', {
    otId: props.otId,
    nuevoEstado: nuevoEstado
  })
}
</script>