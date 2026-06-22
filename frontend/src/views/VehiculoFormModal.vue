<template>
  <BaseModal :show="show" @close="handleClose">
    <template #header>
      <h3 class="text-lg font-medium text-gray-900">
        {{ data ? 'Editar Vehículo' : 'Registrar Nuevo Vehículo' }}
      </h3>
    </template>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Placa</label>
        <input 
          v-model="form.placa" 
          type="text" 
          class="w-full px-3 py-2 border rounded-md uppercase" 
          placeholder="ABC-123"
          required 
        />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Marca</label>
          <input v-model="form.marca" type="text" class="w-full px-3 py-2 border rounded-md" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Modelo</label>
          <input v-model="form.modelo" type="text" class="w-full px-3 py-2 border rounded-md" required />
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Propietario</label>
        <select v-model="form.cliente_id" class="w-full px-3 py-2 border rounded-md" required>
          <option value="">Seleccione un cliente...</option>
          <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.nombre }}</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">VIN (Nro. Chasis)</label>
        <input v-model="form.vin" type="text" class="w-full px-3 py-2 border rounded-md uppercase" placeholder="Ej: 1HGCM82633A004352" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Color</label>
        <input v-model="form.color" type="text" class="w-full px-3 py-2 border rounded-md" placeholder="Ej: Blanco, Gris, Azul..." />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Año (Opcional)</label>
        <input 
          v-model.number="form.anio" 
          type="number" 
          class="w-full px-3 py-2 border rounded-md" 
          min="1900" 
          :max="new Date().getFullYear() + 1" 
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Foto del Vehículo</label>
        <div class="flex items-center justify-center w-full">
          <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 relative overflow-hidden">
            <div v-if="!form.foto_url" class="flex flex-col items-center justify-center pt-5 pb-6">
              <svg class="w-8 h-8 mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
              <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click para subir foto</span></p>
              <p class="text-xs text-gray-500">PNG, JPG (Automáticamente se asignará un auto HQ de prueba)</p>
            </div>
            <img v-else :src="form.foto_url" class="absolute inset-0 w-full h-full object-cover" />
            <input type="file" accept="image/*" class="hidden" @change="handlePhotoChange" />
          </label>
        </div>
      </div>
    </form>

    <template #footer>
      <BaseButton variant="outline" @click="handleClose">Cancelar</BaseButton>
      <BaseButton variant="primary" :loading="loading" @click="handleSubmit">
        {{ data ? 'Actualizar' : 'Guardar' }}
      </BaseButton>
    </template>
  </BaseModal>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { vehiculoService } from '@/services/vehiculoService'
import { useDataFetch } from '@/composables/useDataFetch'
import BaseModal from '@/components/shared/BaseModal.vue'
import BaseButton from '@/components/shared/BaseButton.vue'

const props = defineProps({
  show: Boolean,
  data: Object,
  clients: Array
})

const emit = defineEmits(['update:show', 'saved', 'close'])

const form = reactive({
  placa: '',
  marca: '',
  modelo: '',
  cliente_id: '',
  anio: null,
  vin: '',
  color: '',
  foto_url: ''
})

function handlePhotoChange(event) {
  const file = event.target.files[0]
  if (!file) return

  const carImages = [
    'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1542282088-fe8426682b8f?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1503376710356-69865111a33a?auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1580273916550-e323be2ae537?auto=format&fit=crop&w=800&q=80'
  ]
  form.foto_url = carImages[Math.floor(Math.random() * carImages.length)]
}

const { loading, execute: saveVehiculo } = useDataFetch(
  (payload) => props.data ? vehiculoService.update(props.data.id, payload) : vehiculoService.create(payload)
)

watch(() => props.data, (val) => {
  if (val) {
    Object.assign(form, val)
  } else {
    resetForm()
  }
}, { immediate: true })

async function handleSubmit() {
  try {
    await saveVehiculo(form)
    emit('saved')
    handleClose()
  } catch (err) {
    // Error handled by composable
  }
}

function handleClose() {
  emit('update:show', false)
  emit('close')
  if (!props.data) resetForm()
}

function resetForm() {
  form.placa = ''
  form.marca = ''
  form.modelo = ''
  form.cliente_id = ''
  form.anio = null
  form.vin = ''
  form.color = ''
  form.foto_url = ''
}
</script>