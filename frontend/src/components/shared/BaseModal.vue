<template>
  <div 
    v-if="show" 
    :class="['fixed inset-0 flex items-center justify-center bg-gray-600/50 backdrop-blur-sm p-4 animate-fade-in', zIndex]"
    @click.self="close"
  >
    <div class="relative w-full max-w-lg m-auto">
      <div class="relative bg-white rounded-lg shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        
        <div v-if="$slots.header" class="flex items-center justify-between py-4 px-6 border-b border-gray-100">
          <div class="flex-1">
            <slot name="header"></slot>
          </div>
          <button
            @click="close"
            class="ml-4 flex h-8 w-8 rounded-md items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
            aria-label="Close"
          >
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
          <slot></slot>
        </div>

        <div v-if="$slots.footer" class="flex items-center justify-end py-4 px-6 space-x-3 bg-gray-50 border-t border-gray-100">
          <slot name="footer"></slot>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  zIndex: {
    type: String,
    default: 'z-50'
  }
})

const emit = defineEmits(['update:show', 'close'])

function close() {
  emit('update:show', false)
  emit('close')
}
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
.animate-fade-in {
  animation: fadeIn 0.15s ease-out;
}
</style>
