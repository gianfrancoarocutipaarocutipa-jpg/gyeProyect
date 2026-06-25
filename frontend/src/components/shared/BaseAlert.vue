<template>
  <transition name="fade">
    <div v-if="show" :class="['fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm', alertClasses]">
      <div class="w-full max-w-sm m-4 animate-slide-in">
        <div class="bg-slate-800 border border-slate-700/60 rounded-xl shadow-2xl overflow-hidden">
          <div class="px-5 py-5">
            <div class="flex">
              <div class="flex-shrink-0">
                <template v-if="variant === 'success'">
                  <div class="h-10 w-10 rounded-full bg-emerald-500/15 flex items-center justify-center">
                    <svg class="h-5 w-5 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                </template>
                <template v-else-if="variant === 'error'">
                  <div class="h-10 w-10 rounded-full bg-red-500/15 flex items-center justify-center">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                  </div>
                </template>
                <template v-else-if="variant === 'warning'">
                  <div class="h-10 w-10 rounded-full bg-amber-500/15 flex items-center justify-center">
                    <svg class="h-5 w-5 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                  </div>
                </template>
                <template v-else>
                  <div class="h-10 w-10 rounded-full bg-cyan-500/15 flex items-center justify-center">
                    <svg class="h-5 w-5 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                  </div>
                </template>
              </div>
              <div class="ml-4 w-0 flex-1 pt-0.5">
                <p v-if="title" class="text-sm font-semibold text-slate-100 mb-1">{{ title }}</p>
                <p class="text-sm text-slate-400">{{ message }}</p>
              </div>
            </div>
          </div>
          <div class="px-5 py-3 bg-slate-900/50 flex justify-end space-x-2 border-t border-slate-700/60">
            <template v-if="showConfirm">
              <BaseButton
                variant="ghost"
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
                variant="secondary"
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
import { computed } from 'vue'
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
  return '' // Overlay is already dark
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
@keyframes slideIn {
  from { opacity: 0; transform: scale(0.95) translateY(-10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}
.animate-slide-in {
  animation: slideIn 0.2s ease-out;
}
</style>