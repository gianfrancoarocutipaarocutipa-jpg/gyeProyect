<template>
  <button
    :class="[
      'inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900',
      variantClasses,
      sizeClasses,
      { 'opacity-50 cursor-not-allowed': disabled || loading }
    ]"
    :disabled="disabled || loading"
    @click="handleClick"
  >
    <slot />
    <template v-if="loading">
      <span class="ml-2 animate-spin h-4 w-4 border-2 border-current border-t-transparent rounded-full"></span>
    </template>
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'outline', 'danger', 'link', 'ghost'].includes(v)
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v)
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['click'])

const variantClasses = computed(() => {
  const variants = {
    primary: 'bg-gradient-to-r from-cyan-500 to-blue-600 text-white hover:from-cyan-400 hover:to-blue-500 shadow-lg shadow-cyan-500/20 hover:shadow-cyan-500/30 focus:ring-cyan-500',
    secondary: 'bg-slate-700 text-slate-200 hover:bg-slate-600 border border-slate-600 focus:ring-slate-500',
    outline: 'border border-cyan-500/60 text-cyan-400 hover:bg-cyan-500/10 hover:border-cyan-400 focus:ring-cyan-500',
    danger: 'bg-red-600/80 text-white hover:bg-red-600 border border-red-500/30 shadow-lg shadow-red-500/10 focus:ring-red-500',
    link: 'text-cyan-400 hover:text-cyan-300 focus:ring-cyan-500 shadow-none',
    ghost: 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/70 focus:ring-slate-500'
  }
  return variants[props.variant] || variants.primary
})

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'px-3 py-1.5 text-xs',
    md: 'px-4 py-2 text-sm',
    lg: 'px-6 py-3 text-base'
  }
  return sizes[props.size] || sizes.md
})

function handleClick(event) {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}
</script>