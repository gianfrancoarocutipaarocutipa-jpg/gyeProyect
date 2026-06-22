<template>
  <div class="formato-boleta">
    <div class="print-only">
      <div class="print-boleta">
        <!-- Company Letterhead -->
        <div class="print-header">
          <div class="print-logo">G&amp;E Motors</div>
          <div class="print-ruc">RUC: 20123456789 - Tacna, Perú</div>
          <div class="text-xs text-gray-600">Boleta de Servicio Técnico</div>
        </div>

        <!-- Order Info -->
        <div class="print-mb-4">
          <div class="print-section-title">Datos de la Orden</div>
          <div class="print-info-row">
            <span class="print-info-label">Número de OT:</span>
            <span class="print-info-value">{{ orden.numero_ot }}</span>
          </div>
          <div class="print-info-row">
            <span class="print-info-label">Fecha de Ingreso:</span>
            <span class="print-info-value">{{ formatDate(orden.fecha_ingreso) }}</span>
          </div>
          <div class="print-info-row">
            <span class="print-info-label">Estado:</span>
            <span class="print-info-value">{{ estadoLabel }}</span>
          </div>
        </div>

        <!-- Vehicle Info -->
        <div class="print-mb-4">
          <div class="print-section-title">Datos del Vehículo</div>
          <div class="print-info-row">
            <span class="print-info-label">Marca / Modelo:</span>
            <span class="print-info-value">{{ orden.vehiculo?.marca }} {{ orden.vehiculo?.modelo }}</span>
          </div>
          <div class="print-info-row">
            <span class="print-info-label">Placa:</span>
            <span class="print-info-value">{{ orden.vehiculo?.placa }}</span>
          </div>
          <div class="print-info-row">
            <span class="print-info-label">Año:</span>
            <span class="print-info-value">{{ orden.vehiculo?.anio }}</span>
          </div>
        </div>

        <!-- Problem Description -->
        <div class="print-mb-4">
          <div class="print-section-title">Descripción del Problema</div>
          <p class="text-sm text-gray-800">{{ orden.descripcion_problema }}</p>
        </div>

        <!-- Parts Table -->
        <div v-if="repuestos?.length > 0" class="print-mb-4">
          <div class="print-section-title">Repuestos Utilizados</div>
          <table class="print-table">
            <thead>
              <tr>
                <th>Código OEM</th>
                <th>Repuesto</th>
                <th>Cantidad</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="repuesto in repuestos" :key="repuesto.id">
                <td>{{ repuesto.codigo_oem }}</td>
                <td>{{ repuesto.nombre }}</td>
                <td>{{ repuesto.cantidad }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer -->
        <div class="print-footer">
          <p>Gracias por confiar en G&amp;E Motors</p>
          <p>Esta boleta se genera automáticamente por el sistema SIW-GEM</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  orden: {
    type: Object,
    required: true
  },
  repuestos: {
    type: Array,
    default: () => []
  }
})

const estadoLabels = {
  diagnostico: 'Diagnóstico',
  presupuesto: 'Presupuesto',
  reparacion: 'Reparación',
  control_calidad: 'Control de Calidad',
  entregado: 'Entregado'
}

const estadoLabel = computed(() => {
  return estadoLabels[props.orden?.estado] || props.orden?.estado || ''
})

function formatDate(dateString) {
  if (!dateString) return 'N/A'
  const d = new Date(dateString)
  return d.toLocaleDateString('es-PE')
}
</script>