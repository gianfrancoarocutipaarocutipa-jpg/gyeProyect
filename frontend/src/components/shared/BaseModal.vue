<template>
  <div 
    v-if="show" 
    :class="['fixed inset-0 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-fade-in', zIndex]"
    @click.self="close"
  >
    <div class="relative w-full max-w-lg m-auto animate-slide-in">
      <div class="relative bg-slate-800 border border-slate-700/60 rounded-xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        
        <div v-if="$slots.header" class="flex items-center justify-between py-4 px-6 border-b border-slate-700/60">
          <div class="flex-1 text-slate-100 font-semibold">
            <slot name="header"></slot>
          </div>
          <button
            @click="close"
            class="ml-4 flex h-8 w-8 rounded-lg items-center justify-center text-slate-500 hover:bg-slate-700 hover:text-slate-300 transition-colors"
            aria-label="Close"
          >
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="flex-1 overflow-y-auto p-6 text-slate-300">
          <slot></slot>
        </div>

        <div v-if="$slots.footer" class="flex items-center justify-end py-4 px-6 space-x-3 bg-slate-800/50 border-t border-slate-700/60">
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
@keyframes slideIn {
  from { opacity: 0; transform: scale(0.95) translateY(-10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}
.animate-fade-in {
  animation: fadeIn 0.15s ease-out;
}
.animate-slide-in {
  animation: slideIn 0.2s ease-out;
}
</style>
