<template>
  <transition name="fade">
    <div v-if="show" :class="['fixed inset-0 z-50 flex items-center justify-center bg-black/40', alertClasses]">
      <div class="w-full max-w-sm m-4">
        <div class="bg-white rounded-lg shadow-xl overflow-hidden">
          <div class="px-4 py-5 sm:px-6">
            <div class="flex">
              <div class="flex-shrink-0">
                <template v-if="variant === 'success'">
                  <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.88 9.22l-7-7a1 1 0 00-1.42 1.42l5.25 5.25a1 1 0 001.42 0l7.75-2.25a1 1 0 00-.01-1.42z" clip-rule="evenodd" />
                  </svg>
                </template>
                <template v-else-if="variant === 'error'">
                  <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2v-2a1 1 0 10-2 0z" clip-rule="evenodd" />
                  </svg>
                </template>
                <template v-else-if="variant === 'warning'">
                  <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099a.75.75 0 00-1.082.038l-3.99 11a.75.75 0 001.08 1.272l11-3.99a.75.75 0 00.038-1.082zm-.465 11.43a.75.75 0 01-1.08-.038l-2.913-.698a1.5 1.5 0 01-2.121.75l-.378 1.008a1.5 1.5 0 01.429 1.429l1.008.378a.75.75 0 01.038 1.082z" clip-rule="evenodd" />
                  </svg>
                </template>
                <template v-else> <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                  </svg>
                </template>
              </div>
              <div class="ml-3 w-0 flex-1 pt-0.5">
                <p v-if="title" class="text-sm font-medium text-gray-900 mb-1">{{ title }}</p>
                <p class="text-base text-gray-600">{{ message }}</p>
              </div>
            </div>
          </div>
          <div class="px-4 py-3 bg-gray-50 sm:px-6 flex justify-end space-x-2 border-t border-gray-100">
            <template v-if="showConfirm">
              <BaseButton
                variant="outline"
                size="sm"
                @click="cancel"
              >
                Cancelar
              </BaseButton>
              <BaseButton
                variant="primary"
                size="sm"
                @click="confirm"
              >
                Confirmar
              </BaseButton>
            </template>
            <template v-else>
              <BaseButton
                variant="outline"
                size="sm"
                @click="close"
              >
                Cerrar
              </BaseButton>
            </template>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { computed } from 'vue' // <-- Importación corregida
import BaseButton from '@/components/shared/BaseButton.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  variant: {
    type: String,
    default: 'info',
    validator: v => ['success', 'error', 'warning', 'info'].includes(v)
  },
  title: {
    type: String,
    default: ''
  },
  message: {
    type: String,
    default: ''
  },
  showConfirm: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:show', 'confirm', 'cancel', 'close'])

const alertClasses = computed(() => {
  const variants = {
    success: 'bg-green-500/10',
    error: 'bg-red-500/10',
    warning: 'bg-yellow-500/10',
    info: 'bg-blue-500/10'
  }
  return variants[props.variant] || variants.info // <-- Uso corregido de props.variant
})

function close() {
  emit('update:show', false)
  emit('close')
}

function cancel() {
  emit('update:show', false)
  emit('cancel')
}

function confirm() {
  emit('update:show', false)
  emit('confirm')
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity .2s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>