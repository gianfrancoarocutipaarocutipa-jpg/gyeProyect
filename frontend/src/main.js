import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import router from './router'
import './assets/main.css' // Cambiado de print.css a main.css para cargar Tailwind
import './assets/print.css' // Mantener para funciones de impresión

import App from './App.vue'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)

// Inicializar el store con los datos del localStorage antes de cargar el router
const authStore = useAuthStore()
authStore.initializeFromLocalStorage()

app.use(router) // El router debe ir después de Pinia si hay guardias que usen stores

app.mount('#app')