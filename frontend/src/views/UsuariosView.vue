<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Gestión de Usuarios</h2>
      <BaseButton variant="primary" @click="abrirModalUsuario()">
        Nuevo Usuario
      </BaseButton>
    </div>

    <BaseAlert 
      v-if="alert" 
      :show="true" 
      :variant="alert.type" 
      :message="alert.message" 
      @update:show="alert = null" 
    />

    <div v-if="listLoading" class="text-center py-8">
      Cargando usuarios...
    </div>

    <div v-else-if="!listLoading && (!usuarios || usuarios.length === 0)" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="mt-4 text-lg text-gray-500">No hay usuarios registrados</p>
      <p class="mt-2 text-sm text-gray-400">Haz clic en "Nuevo Usuario" para comenzar</p>
    </div>

    <div v-else class="bg-white shadow rounded-lg overflow-hidden">
      <BaseTable :columns="usuariosColumns" :data="usuarios">
        <template #actions="{ row }">
          <div class="flex space-x-2">
            <button @click="abrirModalUsuario(row)" class="text-indigo-600 hover:text-indigo-900">
              Editar
            </button>
            <button @click="confirmarToggleEstado(row)" class="text-gray-600 hover:text-gray-900">
              {{ row.activo ? 'Desactivar' : 'Activar' }}
            </button>
          </div>
        </template>
      </BaseTable>
    </div>

    <UsuarioFormModal v-model:show="showModal" :usuario="usuarioSeleccionado" @saved="fetchUsuarios" />
  </div>
</template>
<script setup>
import { ref, watch, onMounted } from 'vue'
import { usuarioService } from '@/services/usuarioService'
import { useNotificacionesStore } from '@/stores/notificaciones'
import { useDataFetch } from '@/composables/useDataFetch'
import BaseTable from '@/components/shared/BaseTable.vue'
import BaseButton from '@/components/shared/BaseButton.vue'
import BaseAlert from '@/components/shared/BaseAlert.vue'
import UsuarioFormModal from '@/views/UsuarioFormModal.vue'

const noti = useNotificacionesStore()

const usuarioSeleccionado = ref(null)
const usuarios = ref([])
const alert = ref(null)
const showModal = ref(false)

const usuariosColumns = [
  { key: 'nombre', label: 'Nombre' },
  { key: 'apellido', label: 'Apellido' },
  { key: 'email', label: 'Email' },
  { key: 'rol', label: 'Rol', format: (v) => v?.toUpperCase() },
  { key: 'activo', label: 'Estado', format: (v) => v ? 'Activo' : 'Inactivo' },
  { key: 'created_at', label: 'Fecha Registro', format: (v) => v ? new Date(v).toLocaleDateString() : '-' },
  { key: 'actions', label: 'Acciones' }
]

// ===== Data fetching for list =====
const { loading: listLoading, data: usuariosData, error: listError, execute: fetchUsuariosList } =
  useDataFetch(() => usuarioService.getAll())

// ===== Mutation for updating usuario =====
const { loading: updateLoading, data: updateData, error: updateError, execute: updateUsuario } = 
  useDataFetch((id, data) => usuarioService.update(id, data))

// Update usuarios ref from composable data
watch(() => usuariosData.value, (val) => {
  if (val && val.data) {
    usuarios.value = val.data
  }
})

// Handle errors from fetch
watch(listError, (err) => {
  if (err) {
    alert.value = { type: 'error', message: err.message || 'Error al cargar usuarios' }
    noti.addNotification({
      type: 'error',
      message: 'Error al cargar usuarios',
      timeout: 3000
    })
  } else {
    alert.value = null
  }
})

// Handle errors from update
watch(updateError, (err) => {
  if (err) {
    noti.addNotification({
      type: 'error',
      message: 'Error al actualizar estado',
      timeout: 3000
    })
  }
})

async function fetchUsuarios() {
  try {
    await fetchUsuariosList()
  } catch (err) {
    // Error handled by watcher
  }
}

function abrirModalUsuario(usuario = null) {
  usuarioSeleccionado.value = usuario
  showModal.value = true
}

async function confirmarToggleEstado(usuario) {
  try {
    await updateUsuario(usuario.id, { activo: !usuario.activo })
    noti.addNotification({
      type: 'success',
      message: 'Estado actualizado',
      timeout: 3000
    })
    await fetchUsuariosList()
  } catch (err) {
    // Error handled by watcher
  }
}

onMounted(fetchUsuarios)
</script>