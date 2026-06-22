<template>
  <BaseModal :show="show" @update:show="$emit('update:show', $event)" @close="handleClose">
    <template #header>
      <h3 class="text-lg font-medium text-gray-900">
        {{ usuario ? 'Editar Usuario' : 'Nuevo Usuario' }}
      </h3>
    </template>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Nombre</label>
          <input v-model="form.nombre" type="text" class="w-full px-3 py-2 border rounded-md" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Apellido</label>
          <input v-model="form.apellido" type="text" class="w-full px-3 py-2 border rounded-md" required />
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
        <input v-model="form.email" type="email" class="w-full px-3 py-2 border rounded-md" required :disabled="!!usuario" />
      </div>

      <div v-if="!usuario" class="p-3 bg-blue-50 text-blue-700 rounded-md text-sm">
        La contraseña temporal se generará automáticamente y será enviada por correo al usuario.
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Rol del Sistema</label>
        <select v-model="form.rol" class="w-full px-3 py-2 border rounded-md" required>
          <option value="">Seleccione un rol...</option>
          <option value="mecanico">Mecánico</option>
          <option value="administrador">Administrador</option>
        </select>
      </div>

      <div v-if="usuario" class="flex items-center space-x-2 pt-2">
        <input type="checkbox" v-model="form.activo" id="user-active" class="rounded text-indigo-600" />
        <label for="user-active" class="text-sm text-gray-700">Usuario Activo</label>
      </div>
    </form>

    <template #footer>
      <BaseButton variant="outline" @click="handleClose">Cancelar</BaseButton>
      <BaseButton 
        variant="primary" 
        :loading="loading" 
        @click="handleSubmit"
      >
        {{ usuario ? 'Actualizar' : 'Crear Usuario' }}
      </BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import { reactive, watch, ref } from 'vue'
import { usuarioService } from '@/services/usuarioService'
import { useDataFetch } from '@/composables/useDataFetch'
import BaseModal from '@/components/shared/BaseModal.vue'
import BaseButton from '@/components/shared/BaseButton.vue'

const props = defineProps({
  show: Boolean,
  usuario: Object
})

const emit = defineEmits(['update:show', 'saved', 'close'])

const form = reactive({
  nombre: '',
  apellido: '',
  email: '',
  rol: '',
  activo: true
})

// Usamos una función anónima para decidir si crear o actualizar
const { loading, execute: saveUserAction } = useDataFetch(
  (payload) => {
    if (props.usuario) {
      return usuarioService.update(props.usuario.id, payload)
    }
    return usuarioService.create(payload)
  }
)

watch(() => props.usuario, (val) => {
  if (val) {
    form.nombre = val.nombre
    form.apellido = val.apellido || ''
    form.email = val.email
    form.rol = val.rol
    form.activo = val.activo
  } else {
    resetForm()
  }
}, { immediate: true })

async function handleSubmit() {
  try {
    await saveUserAction(form)
    emit('saved')
    handleClose()
  } catch (err) {
    // Error manejado por el composable
  }
}

function handleClose() {
  emit('update:show', false)
  emit('close')
  if (!props.usuario) resetForm()
}

function resetForm() {
  form.nombre = ''
  form.apellido = ''
  form.email = ''
  form.rol = ''
  form.activo = true
}
</script>