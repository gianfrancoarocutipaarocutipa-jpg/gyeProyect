<template>
  <div class="p-6">
    <!-- VISTA PARA EL MECÁNICO -->
    <template v-if="userRole === 'mecanico'">
      <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-100 mb-2">Panel de Trabajo</h2>
        <p class="text-slate-400">Bienvenido a tu panel de control. Aquí puedes ver un resumen de tus asignaciones actuales.</p>
      </div>

      <!-- Métricas del Mecánico -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="stat-card border-l-4 border-l-cyan-500">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-cyan-500/10 rounded-lg flex items-center justify-center mr-4">
              <svg class="w-6 h-6 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-slate-400">Órdenes en Progreso</p>
              <p class="text-2xl font-bold text-slate-100">{{ misOrdenesActivas.length }}</p>
            </div>
          </div>
        </div>

        <div class="stat-card border-l-4 border-l-emerald-500">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-emerald-500/10 rounded-lg flex items-center justify-center mr-4">
              <svg class="w-6 h-6 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-slate-400">Órdenes Completadas</p>
              <p class="text-2xl font-bold text-slate-100">{{ misOrdenesCompletadas.length }}</p>
            </div>
          </div>
        </div>

        <div class="stat-card border-l-4 border-l-amber-500">
          <div class="flex items-center">
            <div class="w-12 h-12 bg-amber-500/10 rounded-lg flex items-center justify-center mr-4">
              <svg class="w-6 h-6 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-slate-400">Stock Bajo (Total)</p>
              <p class="text-2xl font-bold text-slate-100">{{ stockBajo.length }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Acciones Rápidas del Mecánico -->
      <div class="mb-8">
        <h2 class="section-title mb-4">Acceso Rápido</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <router-link to="/ordenes" class="action-card group">
            <div class="w-12 h-12 bg-cyan-500/10 rounded-lg flex items-center justify-center mb-3 group-hover:bg-cyan-500/20 transition-colors">
              <svg class="w-6 h-6 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
              </svg>
            </div>
            <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Mis Órdenes</span>
          </router-link>
          <router-link to="/diagnostico" class="action-card group">
            <div class="w-12 h-12 bg-purple-500/10 rounded-lg flex items-center justify-center mb-3 group-hover:bg-purple-500/20 transition-colors">
              <svg class="w-6 h-6 text-purple-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
              </svg>
            </div>
            <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Diagnóstico</span>
          </router-link>
          <router-link to="/vehiculos" class="action-card group">
            <div class="w-12 h-12 bg-blue-500/10 rounded-lg flex items-center justify-center mb-3 group-hover:bg-blue-500/20 transition-colors">
              <svg class="w-6 h-6 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
              </svg>
            </div>
            <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Vehículos</span>
          </router-link>
          <router-link to="/inventario" class="action-card group">
            <div class="w-12 h-12 bg-emerald-500/10 rounded-lg flex items-center justify-center mb-3 group-hover:bg-emerald-500/20 transition-colors">
              <svg class="w-6 h-6 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
              </svg>
            </div>
            <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Inventario</span>
          </router-link>
        </div>
      </div>
    </template>

    <!-- VISTA PARA EL ADMINISTRADOR (O DEFAULT) -->
    <template v-else>
      <!-- Sección de Acciones Rápidas -->
      <div class="mb-8">
        <h2 class="section-title mb-4">Acciones Rápidas</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
          <!-- Gestionar Órdenes -->
          <router-link
            v-if="esPersonal"
            to="/ordenes" 
            class="action-card group"
          >
            <div class="w-12 h-12 bg-cyan-500/10 rounded-lg flex items-center justify-center mb-3 group-hover:bg-cyan-500/20 transition-colors">
              <svg class="w-6 h-6 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
              </svg>
            </div>
            <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Órdenes</span>
          </router-link>

          <!-- Clientes -->
          <router-link 
            v-if="esPersonal"
            to="/clientes" 
            class="action-card group"
          >
            <div class="w-12 h-12 bg-blue-500/10 rounded-lg flex items-center justify-center mb-3 group-hover:bg-blue-500/20 transition-colors">
              <svg class="w-6 h-6 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
              </svg>
            </div>
            <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Clientes</span>
          </router-link>

          <!-- Inventario -->
          <router-link 
            v-if="esPersonal"
            to="/inventario" 
            class="action-card group"
          >
            <div class="w-12 h-12 bg-emerald-500/10 rounded-lg flex items-center justify-center mb-3 group-hover:bg-emerald-500/20 transition-colors">
              <svg class="w-6 h-6 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
              </svg>
            </div>
            <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Almacén</span>
          </router-link>

          <!-- Diagnóstico -->
          <router-link 
            v-if="esPersonal"
            to="/diagnostico" 
            class="action-card group"
          >
            <div class="w-12 h-12 bg-purple-500/10 rounded-lg flex items-center justify-center mb-3 group-hover:bg-purple-500/20 transition-colors">
              <svg class="w-6 h-6 text-purple-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
              </svg>
            </div>
            <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Diagnóstico</span>
          </router-link>

          <!-- Mis Vehículos (Solo Cliente) -->
          <router-link 
            v-if="userRole === 'cliente'"
            to="/vehiculos" 
            class="action-card group"
          >
            <div class="w-12 h-12 bg-blue-500/10 rounded-lg flex items-center justify-center mb-3 group-hover:bg-blue-500/20 transition-colors">
              <svg class="w-6 h-6 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
              </svg>
            </div>
            <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Mis Vehículos</span>
          </router-link>

          <!-- Reportes (Solo Admin) -->
          <router-link 
            v-if="userRole === 'administrador'"
            to="/reportes" 
            class="action-card group"
          >
            <div class="w-12 h-12 bg-amber-500/10 rounded-lg flex items-center justify-center mb-3 group-hover:bg-amber-500/20 transition-colors">
              <svg class="w-6 h-6 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
              </svg>
            </div>
            <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider">Reportes</span>
          </router-link>
        </div>
      </div>

      <div class="flex flex-col">
        <div class="flex justify-between items-center mb-4">
          <h2 class="section-title">
            Resumen de Métricas
          </h2>
        </div>
        <div class="overflow-x-auto">
          <div class="inline-block min-w-full">
            <div class="overflow-hidden border border-slate-700/50 rounded-xl bg-slate-900/50">
              <table class="min-w-full divide-y divide-slate-800">
                <thead class="bg-slate-800/80">
                  <tr>
                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">
                      Métrica
                    </th>
                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">
                      Valor
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70">
                  <tr v-if="loading" class="hover:bg-slate-800/50">
                    <td colspan="2" class="px-6 py-4 text-center text-slate-500">
                      Cargando métricas...
                    </td>
                  </tr>
                  <tr v-else-if="!loading && Object.keys(ordenesPorEstado).length === 0 && stockBajo.length === 0 && !ultimaActividad" class="hover:bg-slate-800/50">
                    <td colspan="2" class="px-6 py-4 text-center text-slate-500">
                      No hay datos disponibles
                    </td>
                  </tr>
                  <template v-else>
                  <tr v-for="(count, estado) in ordenesPorEstado" :key="estado" class="hover:bg-slate-800/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-200">
                      OTs {{ capitalize(estado) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-cyan-500/10 text-cyan-400">
                        {{ count }}
                      </span>
                    </td>
                  </tr>

                  <tr v-if="stockBajo.length > 0" class="hover:bg-slate-800/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-200">
                      Repuestos con Stock Bajo
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-500/15 text-red-400">
                        {{ stockBajo.length }}
                      </span>
                    </td>
                  </tr>

                  <tr v-if="ultimaActividad" class="hover:bg-slate-800/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-200">
                      Última Actividad
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">
                      {{ ultimaActividad.created_at }}
                    </td>
                  </tr>

                  <tr v-if="ordenesSinMecanico.length > 0" class="hover:bg-slate-800/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-200">
                      Órdenes sin Mecánico Asignado
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-500/15 text-amber-400">
                        {{ ordenesSinMecanico.length }}
                      </span>
                    </td>
                  </tr>

                  <tr v-if="userRole === 'administrador'" class="hover:bg-slate-800/50 transition-colors">
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-200">
                        Ingresos Totales (Aprobados)
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/15 text-emerald-400">
                          {{ formatCurrency(totalIngresos) }}
                        </span>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="mt-6">
          <h2 class="section-title">
            Órdenes por Semana
          </h2>
          <div class="mt-4 h-48 w-full overflow-hidden rounded-xl border border-slate-700/50 bg-slate-900/50 relative">
            <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-slate-900/80">
              <div class="text-slate-500">Cargando gráfico...</div>
            </div>
            <div v-else class="h-full flex items-end px-2 pb-6 pt-2">
              <template v-for="(semana, index) in otPorSemana" :key="index">
                <div
                  class="flex-1 bg-gradient-to-t from-cyan-600/80 to-cyan-400/60 hover:from-cyan-500 hover:to-cyan-300/80 rounded-t-md transition-all duration-300 mx-1 relative group"
                  :style="{ height: `${(semana / maxOtSemana) * 100}%` }"
                  :title="`Semana ${index + 1}: ${semana} OTs`"
                >
                  <div class="absolute -top-6 left-1/2 -translate-x-1/2 text-xs text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity font-medium">
                    {{ semana }}
                  </div>
                </div>
              </template>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-5 flex items-center justify-between px-3 text-xs text-slate-600 border-t border-slate-800">
              <span v-for="(semana, index) in otPorSemana" :key="'label-' + index" class="w-full text-center">
                S{{ index + 1 }}
              </span>
            </div>
          </div>
        </div>

        <div class="mt-6">
          <h2 class="section-title">Alertas Críticas</h2>
          <div class="mt-4 space-y-3">
            <div v-if="loading" class="text-center text-slate-500 py-4">
              Cargando alertas...
            </div>
            <template v-else-if="alertas.length > 0">
              <div v-for="alerta in alertas" :key="alerta.id" class="p-4 rounded-xl border transition-colors" :class="{
                'bg-red-500/5 border-red-500/20 border-l-4 border-l-red-500': alerta.type === 'error',
                'bg-amber-500/5 border-amber-500/20 border-l-4 border-l-amber-500': alerta.type === 'warning',
                'bg-cyan-500/5 border-cyan-500/20 border-l-4 border-l-cyan-500': alerta.type === 'info',
                'bg-emerald-500/5 border-emerald-500/20 border-l-4 border-l-emerald-500': alerta.type === 'success'
              }">
                <div class="flex">
                  <div class="ml-3 w-0 flex-1 pt-0.5">
                    <p class="text-sm font-semibold text-slate-200">{{ alerta.title }}</p>
                    <p class="mt-1 text-sm text-slate-400">{{ alerta.message }}</p>
                  </div>
                </div>
              </div>
            </template>
            <template v-else>
              <div class="text-center text-slate-500 py-8 bg-slate-900/30 rounded-xl border border-slate-800/50">
                <svg class="w-8 h-8 mx-auto text-slate-600 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                No hay alertas críticas
              </div>
            </template>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { repuestoService } from '@/services/repuestoService'
import { apiService } from '@/services/api'
import { useNotificacionesStore } from '@/stores/notificaciones'

const authStore = useAuthStore()

// Obtenemos el rol de forma segura y reactiva
const userRole = computed(() => {
  return authStore.user?.rol || authStore.userRole || ''
})

// Helper para simplificar v-if en la plantilla
const esPersonal = computed(() => {
  const role = userRole.value?.toLowerCase()
  return ['administrador', 'mecanico'].includes(role)
})

const notificacionesStore = useNotificacionesStore()

const ordenesPorEstado = ref({})
const stockBajo = ref([])
const ultimaActividad = ref(null)
const ordenesSinMecanico = ref([])
const totalIngresos = ref(0)
const otPorSemana = ref([0, 0, 0, 0, 0, 0, 0])
const maxOtSemana = ref(1)
const alertas = ref([])
const loading = ref(true)

// Variables adicionales para el mecánico
const misOrdenesActivas = ref([])
const misOrdenesCompletadas = ref([])

function capitalize(str) {
  if (!str) return ''
  return str.charAt(0).toUpperCase() + str.slice(1)
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-PE', {
    style: 'currency',
    currency: 'PEN'
  }).format(value)
}

async function fetchDashboardData() {
  try {
    loading.value = true
    
    // Prepare ingresos promise (only for admins) usando la variable computed
    const ingresosPromise = userRole.value === 'administrador'
      ? apiService.get(`/reportes/ingresos?desde=${new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]}&hasta=${new Date().toISOString().split('T')[0]}`)
      : Promise.resolve({ success: false, data: null })

    // Only admins fetch global stats and incomes
    const statsPromise = userRole.value === 'administrador'
      ? apiService.get('/ordenes/estadisticas')
      : Promise.resolve({ success: true, data: {} })

    const ordenesPorSemanaPromise = userRole.value === 'administrador'
      ? apiService.get('/ordenes/por-semana')
      : Promise.resolve({ success: true, data: [0,0,0,0,0,0,0] })

    // Execute all API calls in parallel
    const [
      statsResponse,
      stockBajoResponse,
      ordenesResponse,
      ordenesPorSemanaResponse,
      ingresosResponse
    ] = await Promise.all([
      statsPromise,
      repuestoService.getStockBajo(),
      apiService.get('/ordenes', { limit: 100 }),
      ordenesPorSemanaPromise,
      ingresosPromise
    ])

    // Process responses
    if (statsResponse.success) {
      const stats = statsResponse.data
      if (Array.isArray(stats)) {
        stats.forEach(item => { ordenesPorEstado.value[item.estado] = item.total })
      } else if (typeof stats === 'object') {
        Object.assign(ordenesPorEstado.value, stats)
      }
    }

    if (stockBajoResponse.success) {
      stockBajo.value = stockBajoResponse.data
    }

    if (ordenesResponse.success) {
      const ordenes = ordenesResponse.data
      
      if (userRole.value === 'administrador') {
        if (ordenes.length > 0) {
          ultimaActividad.value = ordenes.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0]
        }
        ordenesSinMecanico.value = ordenes.filter(o => 
          !o.mecanico_id && ['diagnostico', 'reparacion', 'esperando_repuesto', 'control_calidad'].includes(o.estado)
        )
      } else if (userRole.value === 'mecanico') {
        // En el backend las órdenes ya vienen filtradas por su ID
        misOrdenesActivas.value = ordenes.filter(o => o.estado !== 'entregado')
        misOrdenesCompletadas.value = ordenes.filter(o => o.estado === 'entregado')
      }
    }

    if (ordenesPorSemanaResponse.success) {
      otPorSemana.value = ordenesPorSemanaResponse.data
      maxOtSemana.value = Math.max(...otPorSemana.value) || 1
    }

    if (userRole.value === 'administrador' && ingresosResponse.success) {
      totalIngresos.value = ingresosResponse.data.total_ingresos || 0
    }

    // Generate alerts
    const newAlertas = []
    if (stockBajo.value.length > 0) {
      newAlertas.push({ 
        id: Date.now() + '-stock', 
        type: 'error', 
        title: 'Stock Crítico', 
        message: `Hay ${stockBajo.value.length} repuestos bajo o igual al mínimo.` 
      })
    }
    if (ordenesSinMecanico.value.length > 0) {
      newAlertas.push({ 
        id: Date.now() + '-sin-mecanico', 
        type: 'error', 
        title: 'Órdenes sin Mecánico', 
        message: `${ordenesSinMecanico.value.length} órdenes sin asignar.` 
      })
    }
    alertas.value = newAlertas

  } catch (err) {
    notificacionesStore.addNotification({ 
      type: 'error', 
      message: err.message || 'Error al cargar el dashboard', 
      timeout: 5000 
    })
  } finally {
    loading.value = false
  }
}

onMounted(fetchDashboardData)

onMounted(() => { console.log('Dashboard userRole:', userRole.value, 'esPersonal:', esPersonal.value) })
</script>