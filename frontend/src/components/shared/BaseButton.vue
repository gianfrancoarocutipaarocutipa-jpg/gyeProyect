<template>
  <button
    :class="[
      'inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm transition-colors duration-200',
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
    validator: (v) => ['primary', 'secondary', 'outline', 'danger', 'link'].includes(v)
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

// Retorna directamente el string de clases correspondiente a la propiedad enviada
const variantClasses = computed(() => {
  const variants = {
    primary: 'bg-indigo-600 text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500',
    secondary: 'bg-gray-200 text-gray-800 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500',
    outline: 'border-2 border-indigo-600 text-indigo-600 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500',
    danger: 'bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500',
    link: 'text-indigo-600 hover:text-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
  }
  return variants[props.variant] || variants.primary
})

// Retorna directamente el string de clases correspondiente al tamaño enviado
const sizeClasses = computed(() => {
  const sizes = {
    sm: 'px-3 py-1 text-xs',
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