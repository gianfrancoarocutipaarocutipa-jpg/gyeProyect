<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-2xl font-bold text-slate-100">Gestión de Inventario</h2>
      <div class="flex space-x-3">
        <BaseButton variant="primary" size="md" @click="openCreateModal" :loading="savingRepuesto">
          Nuevo Repuesto
        </BaseButton>
        <BaseButton variant="secondary" size="md" @click="exportToExcel">
          Exportar Excel
        </BaseButton>
        <BaseButton variant="danger" size="md" @click="exportToPDF">
          Exportar PDF
        </BaseButton>
      </div>
    </div>
    
    <div class="mb-4 flex space-x-3">
      <input
        v-model="search"
        placeholder="Buscar por nombre, OEM o categoría..."
        class="flex-1 px-3 py-2 border border-slate-600 rounded-md shadow-md shadow-black/10 focus:outline-none focus:ring-cyan-500/40 focus:border-cyan-500 sm:text-sm"
        @keyup.enter="fetchRepuestos"
      />
      <BaseButton variant="outline" size="md" @click="fetchRepuestos">Buscar</BaseButton>
      <BaseButton variant="outline" size="md" @click="search = ''">Limpiar</BaseButton>
    </div>

    <BaseAlert
      v-if="alert"
      :show="true"
      variant="error"
      :message="alert"
      @update:show="show => { if (!show) alert = null }"
    />

    <div v-if="listLoading" class="text-center py-8">Cargando repuestos...</div>
    <div v-else>
      <BaseTable
        :columns="repuestoColumns"
        :data="repuestos"
        @row-click="openDetailModal"
      />
    </div>

    <BaseModal v-model:show="showCreateRepuestoModal" @close="resetRepuestoForm">
      <template #header>
        <h3 class="text-lg font-medium text-slate-100">{{ editingRepuesto ? 'Editar Repuesto' : 'Nuevo Repuesto' }}</h3>
      </template>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-300">Código OEM</label>
          <input v-model="repuestoForm.codigo_oem" @input="validateOEM" class="w-full px-3 py-2 border rounded-md" required />
          <p v-if="oemError" class="text-red-500 text-xs mt-1">{{ oemError }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-300">Nombre</label>
          <input v-model="repuestoForm.nombre" class="w-full px-3 py-2 border rounded-md" required />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-300">Stock Actual</label>
            <input type="number" v-model.number="repuestoForm.stock" class="w-full px-3 py-2 border rounded-md" min="0" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-300">Stock Mínimo</label>
            <input type="number" v-model.number="repuestoForm.stock_minimo" class="w-full px-3 py-2 border rounded-md" min="0" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-300">Precio Unitario</label>
          <input type="number" v-model.number="repuestoForm.precio_unitario" class="w-full px-3 py-2 border rounded-md" step="0.01" />
        </div>
      </div>
      <div class="flex justify-end space-x-3 mt-6">
        <BaseButton variant="outline" @click="showCreateRepuestoModal = false">Cancelar</BaseButton>
        <BaseButton variant="primary" :loading="savingRepuesto" @click="saveRepuesto">Guardar</BaseButton>
      </div>
    </BaseModal>

    <BaseModal v-model:show="showRepuestoDetailModal" @close="resetRepuestoDetail">
      <template #header><h3 class="text-lg font-medium text-slate-100">Detalle del Repuesto</h3></template>
      <div v-if="selectedRepuesto" class="space-y-2">
        <p><strong>OEM:</strong> {{ selectedRepuesto.codigo_oem }}</p>
        <p><strong>Nombre:</strong> {{ selectedRepuesto.nombre }}</p>
        <p><strong>Stock:</strong> {{ selectedRepuesto.stock }}</p>
        <div class="flex justify-between mt-4">
          <BaseButton variant="outline" @click="showHistorialModal = true; showRepuestoDetailModal = false">
            Ver Historial
          </BaseButton>
          <div class="flex space-x-3">
            <BaseButton variant="outline" @click="editRepuesto(selectedRepuesto)">Editar</BaseButton>
            <BaseButton variant="danger" @click="deleteRepuesto(selectedRepuesto.id)">Eliminar</BaseButton>
          </div>
        </div>
      </div>
    </BaseModal>

    <RepuestoHistorialModal 
      v-model:show="showHistorialModal" 
      :repuesto="selectedRepuesto" 
    />
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { repuestoService } from '@/services/repuestoService'
import { reporteService } from '@/services/reporteService'
import { useNotificacionesStore } from '@/stores/notificaciones'
import { useDataFetch } from '@/composables/useDataFetch'
import BaseModal from '@/components/shared/BaseModal.vue' // Importar BaseModal
import BaseButton from '@/components/shared/BaseButton.vue' // Importar BaseButton
import BaseTable from '@/components/shared/BaseTable.vue'
import BaseAlert from '@/components/shared/BaseAlert.vue'
import RepuestoHistorialModal from '@/views/RepuestoHistorialModal.vue'

const notificacionesStore = useNotificacionesStore()

// Search term
const search = ref('')

const alert = ref(null)
// Form state for create/edit
const editingRepuesto = ref(null)
const showCreateRepuestoModal = ref(false)
const showRepuestoDetailModal = ref(false)
const showHistorialModal = ref(false)
const selectedRepuesto = ref(null)
const savingRepuesto = ref(false)
const oemError = ref('')

const repuestoForm = reactive({
  codigo_oem: '',
  nombre: '',
  descripcion: '',
  categoria: '',
  marca_fabricante: '',
  stock: 0,
  stock_minimo: null,
  precio_unitario: 0
})

// Columns for the table
const repuestoColumns = [
  { key: 'codigo_oem', label: 'Código OEM' },
  { key: 'nombre', label: 'Nombre' },
  { key: 'categoria', label: 'Categoría' },
  { key: 'stock', label: 'Stock', format: (v, r) => `<span class="${v < r.stock_minimo ? 'text-red-600' : 'text-green-600'} font-bold">${v}</span>` }
]

// Referencia local para la lista limpia de repuestos que usará la tabla
const repuestos = ref([])

// ===== Data fetching for list =====
const { loading: listLoading, data: repuestosData, error: listError, execute: fetchRepuestosList } = 
  useDataFetch(() => repuestoService.getAll({ q: search.value }))

// Sincronizar los datos del composable con nuestra lista local limpia
watch(() => repuestosData.value, (val) => {
  if (val && val.data) {
    repuestos.value = val.data
  } else {
    repuestos.value = []
  }
})

// Refetch cuando cambie la búsqueda
watch(search, () => {
  fetchRepuestosList()
})

// ===== Mutations (using composable) =====
const { loading: createLoading, error: createError, execute: createRepuesto } = 
  useDataFetch(repuestoService.create)

const { loading: updateLoading, error: updateError, execute: updateRepuesto } = 
  useDataFetch((id, data) => repuestoService.update(id, data))

const { loading: deleteLoading, error: deleteError, execute: deleteRepuestoUse } = 
  useDataFetch(repuestoService.delete)

// ===== Helper functions =====
function validateOEM() {
  repuestoForm.codigo_oem = repuestoForm.codigo_oem.toUpperCase()
  oemError.value = /^[A-Z0-9-]+$/.test(repuestoForm.codigo_oem) ? '' : 'Formato inválido (Solo A-Z, 0-9, -)'
}

async function fetchRepuestos() {
  try {
    await fetchRepuestosList()
  } catch (err) {
    // Error handled by composable, but we can set alert for the UI alert component
    alert.value = err.message || 'Error al cargar repuestos'
  }
}

async function exportToExcel() {
  try {
    const blob = await reporteService.exportarExcelInventario()
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'inventario_gem_motors.csv')
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    notificacionesStore.addNotification({ type: 'success', message: 'Excel exportado exitosamente', timeout: 3000 })
  } catch (err) {
    alert.value = err.message || 'Error al exportar a Excel'
  }
}

async function exportToPDF() {
  try {
    const blob = await reporteService.exportarPdfInventario()
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'inventario_gem_motors.pdf')
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    notificacionesStore.addNotification({ type: 'success', message: 'PDF exportado exitosamente', timeout: 3000 })
  } catch (err) {
    alert.value = err.message || 'Error al exportar a PDF'
  }
}

async function saveRepuesto() {
  savingRepuesto.value = true
  try {
    if (editingRepuesto.value) {
      await updateRepuesto(editingRepuesto.value.id, repuestoForm)
      notificacionesStore.addNotification({ type: 'success', message: 'Repuesto actualizado exitosamente', timeout: 3000 })
    } else {
      await createRepuesto(repuestoForm)
      notificacionesStore.addNotification({ type: 'success', message: 'Repuesto creado exitosamente', timeout: 3000 })
    }
    await fetchRepuestosList() // Refresh list
    resetRepuestoForm()
  } catch (err) {
    alert.value = err.message || 'Error al guardar repuesto'
  } finally {
    savingRepuesto.value = false
  }
}

async function deleteRepuesto(id) {
  if (!confirm('¿Eliminar repuesto?')) return
  try {
    await deleteRepuestoUse(id)
    notificacionesStore.addNotification({ type: 'success', message: 'Repuesto eliminado exitosamente', timeout: 3000 })
    await fetchRepuestosList() // Refresh list
    resetRepuestoDetail()
  } catch (err) {
    alert.value = err.message || 'Error al eliminar repuesto'
  }
}

function openDetailModal(repuesto) {
  selectedRepuesto.value = repuesto
  showRepuestoDetailModal.value = true
}

function resetRepuestoForm() {
  Object.assign(repuestoForm, { codigo_oem: '', nombre: '', descripcion: '', categoria: '', marca_fabricante: '', stock: 0, stock_minimo: null, precio_unitario: 0 })
  editingRepuesto.value = null
  showCreateRepuestoModal.value = false
  oemError.value = ''
}

function openCreateModal() {
  resetRepuestoForm()
  showCreateRepuestoModal.value = true
}

function resetRepuestoDetail() {
  selectedRepuesto.value = null
  showRepuestoDetailModal.value = false
}

function editRepuesto(repuesto) { 
  Object.assign(repuestoForm, repuesto); 
  editingRepuesto.value = repuesto; 
  showRepuestoDetailModal.value = false; 
  showCreateRepuestoModal.value = true 
}

// Initial fetch
fetchRepuestos()
</script>