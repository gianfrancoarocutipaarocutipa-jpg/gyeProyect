<template>
  <div class="p-6 shadow-lg shadow-black/20 rounded-xl bg-slate-900">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-slate-100">
        Gestión de Órdenes de Trabajo
      </h2>
      <BaseButton
        v-if="userRole === 'administrador'"
        variant="primary"
        size="md"
        @click="abrirModalCreacion"
      >
        Nueva Orden
      </BaseButton>
    </div>

    <BaseAlert
      v-if="alert"
      :show="true"
      :variant="alert.type"
      :message="alert.message"
      @update:show="show => { if (!show) alert = null }"
    />

    <!-- Skeleton Loader -->
    <div v-if="listLoading" class="space-y-4">
      <div class="h-4 bg-slate-700 rounded w-full mb-2"></div>
      <div class="h-4 bg-slate-700 rounded w-full mb-2"></div>
      <div class="h-4 bg-slate-700 rounded w-full mb-2"></div>
      <div class="h-4 bg-slate-700 rounded w-full mb-2"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!listLoading && (!ordenesData || ordenesData.length === 0)" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="mt-4 text-lg text-slate-400">No hay órdenes de trabajo registradas</p>
      <p class="mt-2 text-sm text-slate-500">Haz clic en "Nueva Orden" para comenzar</p>
    </div>

    <!-- Vista Mecánico: Detalle Inline -->
    <div v-else-if="userRole === 'mecanico'">
      <div v-if="otSeleccionada" class="bg-slate-800/60 rounded-xl border border-slate-700/50 p-6 shadow-sm">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold text-slate-100">Detalle de Orden #{{ otSeleccionada?.numero_ot || '' }}</h3>
        </div>

        <!-- Pipeline -->
        <div class="mb-6">
          <h4 class="text-sm font-medium text-slate-300 mb-3">Estado actual del proceso</h4>
          <OrdenEstadoPipeline
            :estado-actual="otSeleccionada.estado"
            :presupuesto-aprobado="otSeleccionada.presupuesto_aprobado || false"
            :ot-id="otSeleccionada.id"
            @transicion-estado="abrirTransicion"
          />
        </div>

        <!-- Order details -->
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm font-medium text-slate-400">Cliente</p>
              <p class="text-sm text-slate-100">{{ otSeleccionada.cliente?.nombre || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-slate-400">Vehículo</p>
              <p class="text-sm text-slate-100">
                {{ otSeleccionada.vehiculo ? `${otSeleccionada.vehiculo.marca} ${otSeleccionada.vehiculo.modelo} - ${otSeleccionada.vehiculo.placa}` : 'N/A' }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm font-medium text-slate-400">Fecha de ingreso</p>
              <p class="text-sm text-slate-100">{{ formatDate(otSeleccionada.created_at) }}</p>
            </div>
          </div>

          <!-- Mecánicos Asignados List -->
          <div class="pt-4 border-t border-slate-700/50">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-sm font-medium text-slate-300">Mecánicos Asignados</h4>
            </div>
            <ul v-if="otSeleccionada.mecanicos_asignados && otSeleccionada.mecanicos_asignados.length > 0" class="space-y-2">
              <li v-for="m in otSeleccionada.mecanicos_asignados" :key="m.asignacion_id" class="flex justify-between items-center bg-slate-800/40 p-2 rounded border border-slate-700/40">
                <div>
                  <span class="text-sm font-medium text-slate-200">{{ m.nombre }} {{ m.apellido }}</span>
                </div>
                <div class="flex items-center space-x-2">
                  <span class="text-xs text-slate-400">Horas:</span>
                  <template v-if="(authStore.user?.id === m.mecanico_id) && otSeleccionada.estado !== 'entregado'">
                    <input type="number" min="0" class="w-12 px-1 py-1 text-sm border rounded text-center"
                           :value="m.horas_int ?? Math.floor(m.horas_trabajadas)"
                           @change="m.horas_int = Math.max(0, parseInt($event.target.value) || 0)" placeholder="0" />
                    <span class="text-xs text-slate-500">h</span>
                    <input type="number" min="0" max="59" class="w-12 px-1 py-1 text-sm border rounded text-center"
                           :value="m.minutos_int ?? Math.round((m.horas_trabajadas % 1) * 60)"
                           @change="m.minutos_int = Math.min(59, Math.max(0, parseInt($event.target.value) || 0))" placeholder="0" />
                    <span class="text-xs text-slate-500">min</span>
                  </template>
                  <span v-else class="text-sm font-medium text-slate-100">{{ formatHorasMinutos(m.horas_trabajadas) }}</span>
                  <BaseButton v-if="(authStore.user?.id === m.mecanico_id) && otSeleccionada.estado !== 'entregado'"
                              variant="primary" size="sm" class="px-2 py-1 h-auto text-xs"
                              @click="guardarHorasMecanico(m)" :loading="m.saving">Guardar</BaseButton>
                </div>
              </li>
            </ul>
            <p v-else class="text-sm text-slate-400 italic">No hay mecánicos asignados a esta orden.</p>
          </div>

          <!-- Repuestos Asignados List -->
          <div class="pt-4 border-t border-slate-700/50">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-sm font-medium text-slate-300">Repuestos Utilizados</h4>
              <BaseButton
                v-if="otSeleccionada.estado === 'esperando_repuesto'"
                variant="link" size="sm" class="p-0" @click="abrirAsignarRepuesto"
              >
                + Añadir Repuesto
              </BaseButton>
            </div>
            <ul v-if="otSeleccionada.repuestos_asignados && otSeleccionada.repuestos_asignados.length > 0" class="space-y-2">
              <li v-for="r in otSeleccionada.repuestos_asignados" :key="r.id" class="flex justify-between items-center bg-slate-800/40 p-2 rounded border border-slate-700/40">
                <div class="flex items-center space-x-2">
                  <span class="text-sm font-medium text-slate-200">{{ r.repuesto_nombre }}</span>
                  <span class="text-xs text-slate-400">({{ r.codigo_oem }})</span>
                </div>
                <div class="flex items-center space-x-3">
                  <span class="text-sm text-slate-400">Cant: {{ r.cantidad }}</span>
                  <BaseButton v-if="otSeleccionada.estado === 'esperando_repuesto'"
                              variant="danger" size="sm" class="px-2 py-1 h-auto text-xs bg-red-100 text-red-700 border-red-200 hover:bg-red-200"
                              @click="eliminarRepuesto(r.repuesto_id)">
                    ✕
                  </BaseButton>
                </div>
              </li>
            </ul>
            <p v-else class="text-sm text-slate-400 italic">No hay repuestos asignados a esta orden.</p>
          </div>

          <!-- Confirmar asignaciones (vista mecánico) -->
          <div v-if="otSeleccionada.estado === 'esperando_repuesto'" class="pt-4 border-t border-slate-700/50">
            <div v-if="otSeleccionada.mecanicos_asignados?.length > 0 && otSeleccionada.repuestos_asignados?.length > 0" class="p-3 bg-green-50 border border-green-200 rounded-md">
              <p class="text-sm text-green-700 mb-3">Mecánicos y repuestos asignados. Confirme para iniciar la reparación.</p>
              <BaseButton variant="primary" class="w-full" @click="confirmarInicioReparacion" :loading="transicionLoading">
                Confirmar e Iniciar Reparación
              </BaseButton>
            </div>
            <div v-else class="p-3 bg-yellow-50 border border-yellow-200 rounded-md">
              <p class="text-sm text-yellow-700">Asigne al menos un mecánico y un repuesto para continuar.</p>
            </div>
          </div>

          <!-- Evidencias Multimedia List -->
          <div class="pt-4 border-t border-slate-700/50">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-sm font-medium text-slate-300">Evidencia Multimedia</h4>
              <BaseButton
                v-if="otSeleccionada.estado !== 'entregado'"
                variant="link" size="sm" class="p-0" @click="abrirAñadirEvidencia"
              >
                + Añadir Evidencia
              </BaseButton>
            </div>
            <div v-if="evidencias && evidencias.length > 0" class="grid grid-cols-2 gap-4">
              <div v-for="e in evidencias" :key="e.id" class="bg-slate-800/40 rounded-lg p-3 border border-slate-700/40 flex flex-col relative group">
                <div v-if="otSeleccionada.estado !== 'entregado'" class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity z-10 flex gap-1">
                  <BaseButton variant="primary" size="sm" class="px-2 py-1 h-auto text-xs rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 border-blue-200 shadow-sm" 
                              @click="abrirEditarEvidencia(e)">
                    ✎
                  </BaseButton>
                  <BaseButton variant="danger" size="sm" class="px-2 py-1 h-auto text-xs rounded-full bg-red-100 text-red-700 hover:bg-red-200 border-red-200 shadow-sm" 
                              @click="eliminarEvidencia(e.id)">
                    ✕
                  </BaseButton>
                </div>
                <div class="mb-2">
                  <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-cyan-500/15 text-cyan-300 uppercase tracking-wide">{{ e.etiqueta }}</span>
                </div>
                <div class="flex-grow">
                  <img v-if="e.tipo === 'foto' || e.tipo === 'imagen'" :src="e.url_cloudinary || e.url" class="w-full h-32 object-cover rounded shadow-md shadow-black/10 mb-2 cursor-pointer hover:opacity-90" @click="window.open(e.url_cloudinary || e.url, '_blank')" title="Click para ampliar" />
                  <video v-else :src="e.url_cloudinary || e.url" class="w-full h-32 object-cover rounded shadow-md shadow-black/10 mb-2 cursor-pointer hover:opacity-90" @click="window.open(e.url_cloudinary || e.url, '_blank')" controls title="Click para ampliar"></video>
                </div>
                <p class="text-sm text-slate-400 mt-2 line-clamp-2" :title="e.descripcion">{{ e.descripcion }}</p>
              </div>
            </div>
            <p v-else class="text-sm text-slate-400 italic">No hay evidencias multimedia en esta orden.</p>
          </div>
        </div>
        
        <div class="mt-8 pt-4 border-t border-slate-700/50 flex gap-2 overflow-x-auto" v-if="ordenes.length > 1">
           <span class="text-sm text-slate-400 mr-2 flex items-center">Tus otras asignaciones:</span>
           <button v-for="o in ordenes" :key="o.id" @click="verDetalle(o)" 
              :class="['px-3 py-1 rounded text-sm transition-colors border', o.id === otSeleccionada?.id ? 'bg-cyan-500/10 border-cyan-500 font-bold text-cyan-300' : 'bg-slate-800/60 hover:bg-slate-800/40']">
              {{ o.numero_ot }}
           </button>
        </div>
      </div>
      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-cyan-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-4 text-slate-400">Cargando tu orden...</p>
      </div>
    </div>

    <!-- Data Table -->
    <div v-else>
      <BaseTable
        :columns="ordenesColumns"
        :data="ordenes"
        :loading="false"
        @row-click="verDetalle"
      />
    </div>

    <!-- State Transition Modal -->
    <TransicionEstadoModal
      v-model:show="showTransicionModal"
      :ot-id="otSeleccionada?.id"
      :estado-actual="otSeleccionada?.estado"
      :nuevo-estado="estadoObjetivo"
      :presupuesto-aprobado="otSeleccionada?.presupuesto_aprobado || false"
      :loading="transicionLoading"
      @confirmar="ejecutarTransicion"
      @close="resetTransicion"
      v-if="showTransicionModal"
    />

    <!-- Create Order Modal -->
    <BaseModal v-model:show="showCreateModal" @close="resetCreateForm">
      <template #header>
        <h3 class="text-lg font-medium text-slate-100">Nueva Orden de Trabajo</h3>
      </template>

      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-300">Número de OT</label>
          <input v-model="orderForm.numero_ot" type="text" class="w-full px-3 py-2 border rounded-md" placeholder="Ej: OT-1001" />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-300">Cliente</label>
          <select v-model="orderForm.cliente_id" class="w-full px-3 py-2 border rounded-md">
            <option value="">Seleccione un cliente...</option>
            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.nombre }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-300">Vehículo</label>
          <select v-model="orderForm.vehiculo_id" class="w-full px-3 py-2 border rounded-md" :disabled="!orderForm.cliente_id">
            <option value="">{{ orderForm.cliente_id ? 'Seleccione un vehículo...' : 'Primero seleccione un cliente' }}</option>
            <option v-for="v in clientVehicles" :key="v.id" :value="v.id">{{ v.marca }} {{ v.modelo }} ({{ v.placa }})</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-300">Descripción del Problema</label>
          <textarea v-model="orderForm.descripcion_problema" rows="3" class="w-full px-3 py-2 border rounded-md" placeholder="Describa la falla reportada..."></textarea>
        </div>

        <div v-if="orderForm.cliente_id && clientVehicles.length === 0 && !loadingVehicles" class="text-xs text-amber-600 bg-amber-50 p-2 rounded">
          Este cliente no tiene vehículos registrados.
        </div>
      </div>

      <template #footer>
        <BaseButton variant="outline" @click="showCreateModal = false">Cancelar</BaseButton>
        <BaseButton 
          variant="primary" 
          :loading="createLoading" 
          :disabled="!orderForm.numero_ot || !orderForm.vehiculo_id"
          @click="handleCreate"
        >
          Crear Orden
        </BaseButton>
      </template>
    </BaseModal>

    <!-- Detail Modal -->
    <BaseModal v-if="userRole !== 'mecanico'" v-model:show="showDetailModal" @close="resetDetail">
      <template #header>
        <h3 class="text-lg font-medium text-slate-100">
          Detalle de Orden #{{ otSeleccionada?.numero_ot || '' }}
        </h3>
      </template>

      <div v-if="otSeleccionada">
        <!-- Pipeline -->
        <div class="mb-6">
          <h4 class="text-sm font-medium text-slate-300 mb-3">Estado actual del proceso</h4>
          <OrdenEstadoPipeline
            :estado-actual="otSeleccionada.estado"
            :presupuesto-aprobado="otSeleccionada.presupuesto_aprobado || false"
            :ot-id="otSeleccionada.id"
            @transicion-estado="abrirTransicion"
          />
        </div>

        <!-- Order details -->
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm font-medium text-slate-400">Cliente</p>
              <p class="text-sm text-slate-100">{{ otSeleccionada.cliente?.nombre || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-slate-400">Vehículo</p>
              <p class="text-sm text-slate-100">
                {{ otSeleccionada.vehiculo ? `${otSeleccionada.vehiculo.marca} ${otSeleccionada.vehiculo.modelo} - ${otSeleccionada.vehiculo.placa}` : 'N/A' }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm font-medium text-slate-400">Fecha de ingreso</p>
              <p class="text-sm text-slate-100">{{ formatDate(otSeleccionada.created_at) }}</p>
            </div>
            <!-- El botón de Asignar ahora se movió al título de Mecánicos Asignados -->
          </div>

          <!-- Mecánicos Asignados List -->
          <div class="pt-4 border-t border-slate-700/50">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-sm font-medium text-slate-300">Mecánicos Asignados</h4>
              <BaseButton
                v-if="userRole === 'administrador' && otSeleccionada.estado === 'esperando_repuesto'"
                variant="link" size="sm" class="p-0" @click="abrirAsignarMecanico"
              >
                + Añadir Mecánico
              </BaseButton>
            </div>
            <ul v-if="otSeleccionada.mecanicos_asignados && otSeleccionada.mecanicos_asignados.length > 0" class="space-y-2">
              <li v-for="m in otSeleccionada.mecanicos_asignados" :key="m.asignacion_id" class="flex justify-between items-center bg-slate-800/40 p-2 rounded border border-slate-700/40">
                <div>
                  <span class="text-sm font-medium text-slate-200">{{ m.nombre }} {{ m.apellido }}</span>
                </div>
                <div class="flex items-center space-x-2">
                  <span class="text-xs text-slate-400">Horas:</span>
                  <template v-if="(userRole === 'administrador' || authStore.user?.id === m.mecanico_id) && otSeleccionada.estado !== 'entregado'">
                    <input type="number" min="0" class="w-12 px-1 py-1 text-sm border rounded text-center"
                           :value="m.horas_int ?? Math.floor(m.horas_trabajadas)"
                           @change="m.horas_int = Math.max(0, parseInt($event.target.value) || 0)" placeholder="0" />
                    <span class="text-xs text-slate-500">h</span>
                    <input type="number" min="0" max="59" class="w-12 px-1 py-1 text-sm border rounded text-center"
                           :value="m.minutos_int ?? Math.round((m.horas_trabajadas % 1) * 60)"
                           @change="m.minutos_int = Math.min(59, Math.max(0, parseInt($event.target.value) || 0))" placeholder="0" />
                    <span class="text-xs text-slate-500">min</span>
                  </template>
                  <span v-else class="text-sm font-medium text-slate-100">{{ formatHorasMinutos(m.horas_trabajadas) }}</span>
                  <BaseButton v-if="(userRole === 'administrador' || authStore.user?.id === m.mecanico_id) && otSeleccionada.estado !== 'entregado'"
                              variant="primary" size="sm" class="px-2 py-1 h-auto text-xs"
                              @click="guardarHorasMecanico(m)" :loading="m.saving">Guardar</BaseButton>
                </div>
              </li>
            </ul>
            <p v-else class="text-sm text-slate-400 italic">No hay mecánicos asignados a esta orden.</p>
          </div>

          <!-- Repuestos Asignados List -->
          <div class="pt-4 border-t border-slate-700/50">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-sm font-medium text-slate-300">Repuestos Utilizados</h4>
              <BaseButton
                v-if="(userRole === 'administrador' || userRole === 'mecanico') && otSeleccionada.estado === 'esperando_repuesto'"
                variant="link" size="sm" class="p-0" @click="abrirAsignarRepuesto"
              >
                + Añadir Repuesto
              </BaseButton>
            </div>
            <ul v-if="otSeleccionada.repuestos_asignados && otSeleccionada.repuestos_asignados.length > 0" class="space-y-2">
              <li v-for="r in otSeleccionada.repuestos_asignados" :key="r.id" class="flex justify-between items-center bg-slate-800/40 p-2 rounded border border-slate-700/40">
                <div class="flex items-center space-x-2">
                  <span class="text-sm font-medium text-slate-200">{{ r.repuesto_nombre }}</span>
                  <span class="text-xs text-slate-400">({{ r.codigo_oem }})</span>
                </div>
                <div class="flex items-center space-x-3">
                  <span class="text-sm text-slate-400">Cant: {{ r.cantidad }}</span>
                  <BaseButton v-if="(userRole === 'administrador' || userRole === 'mecanico') && otSeleccionada.estado === 'esperando_repuesto'"
                              variant="danger" size="sm" class="px-2 py-1 h-auto text-xs bg-red-100 text-red-700 border-red-200 hover:bg-red-200"
                              @click="eliminarRepuesto(r.repuesto_id)">
                    ✕
                  </BaseButton>
                </div>
              </li>
            </ul>
            <p v-else class="text-sm text-slate-400 italic">No hay repuestos asignados a esta orden.</p>
          </div>

          <!-- Confirmar asignaciones (modal admin) -->
          <div v-if="otSeleccionada.estado === 'esperando_repuesto'" class="pt-4 border-t border-slate-700/50">
            <div v-if="otSeleccionada.mecanicos_asignados?.length > 0 && otSeleccionada.repuestos_asignados?.length > 0" class="p-3 bg-green-50 border border-green-200 rounded-md">
              <p class="text-sm text-green-700 mb-3">Mecánicos y repuestos asignados. Confirme para iniciar la reparación.</p>
              <BaseButton variant="primary" class="w-full" @click="confirmarInicioReparacion" :loading="transicionLoading">
                Confirmar e Iniciar Reparación
              </BaseButton>
            </div>
            <div v-else class="p-3 bg-yellow-50 border border-yellow-200 rounded-md">
              <p class="text-sm text-yellow-700">Asigne al menos un mecánico y un repuesto para continuar.</p>
            </div>
          </div>

          <!-- Evidencias Multimedia List (Admin) -->
          <div class="pt-4 border-t border-slate-700/50">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-sm font-medium text-slate-300">Evidencia Multimedia</h4>
              <BaseButton 
                v-if="(userRole === 'administrador' || userRole === 'mecanico') && otSeleccionada.estado !== 'entregado'" 
                variant="link" size="sm" class="p-0" @click="abrirAñadirEvidencia"
              >
                + Añadir Evidencia
              </BaseButton>
            </div>
            <div v-if="evidencias && evidencias.length > 0" class="grid grid-cols-2 gap-4">
              <div v-for="e in evidencias" :key="e.id" class="bg-slate-800/40 rounded-lg p-3 border border-slate-700/40 flex flex-col relative group">
                <div v-if="(userRole === 'administrador' || userRole === 'mecanico') && otSeleccionada.estado !== 'entregado'" class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity z-10 flex gap-1">
                  <BaseButton variant="primary" size="sm" class="px-2 py-1 h-auto text-xs rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 border-blue-200 shadow-sm" 
                              @click="abrirEditarEvidencia(e)">
                    ✎
                  </BaseButton>
                  <BaseButton variant="danger" size="sm" class="px-2 py-1 h-auto text-xs rounded-full bg-red-100 text-red-700 hover:bg-red-200 border-red-200 shadow-sm" 
                              @click="eliminarEvidencia(e.id)">
                    ✕
                  </BaseButton>
                </div>
                <div class="mb-2">
                  <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-cyan-500/15 text-cyan-300 uppercase tracking-wide">{{ e.etiqueta }}</span>
                </div>
                <div class="flex-grow">
                  <img v-if="e.tipo === 'foto' || e.tipo === 'imagen'" :src="e.url_cloudinary || e.url" class="w-full h-32 object-cover rounded shadow-md shadow-black/10 mb-2 cursor-pointer hover:opacity-90" @click="window.open(e.url_cloudinary || e.url, '_blank')" title="Click para ampliar" />
                  <video v-else :src="e.url_cloudinary || e.url" class="w-full h-32 object-cover rounded shadow-md shadow-black/10 mb-2 cursor-pointer hover:opacity-90" @click="window.open(e.url_cloudinary || e.url, '_blank')" controls title="Click para ampliar"></video>
                </div>
                <p class="text-sm text-slate-400 mt-2 line-clamp-2" :title="e.descripcion">{{ e.descripcion }}</p>
              </div>
            </div>
            <p v-else class="text-sm text-slate-400 italic">No hay evidencias multimedia en esta orden.</p>
          </div>

          <!-- Budget status -->
          <div v-if="otSeleccionada.presupuesto_id" class="pt-4 border-t border-slate-700/50">
            <div class="flex items-center justify-between">
              <p class="text-sm font-medium text-slate-400">Presupuesto</p>
              <span :class="[
                'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                presupuestoAprobado ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
              ]">
                {{ presupuestoAprobado ? 'Aprobado' : 'Pendiente de aprobación' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-between w-full">
          <BaseButton
            v-if="otSeleccionada?.cliente?.codigo_seguimiento"
            variant="outline"
            size="md"
            class="text-cyan-400 border-cyan-500 hover:bg-cyan-500/10"
            @click="copiarLinkSeguimiento(otSeleccionada.cliente.codigo_seguimiento)"
          >
            Copiar Link de Seguimiento
          </BaseButton>
          <BaseButton
            variant="outline"
            size="md"
            @click="showDetailModal = false"
            class="ml-auto"
          >
            Cerrar
          </BaseButton>
        </div>
      </template>
    </BaseModal>

    <!-- Assign Mechanic Modal -->
    <BaseModal v-model:show="showAssignMecanicoModal" @close="showAssignMecanicoModal = false">
      <template #header>
        <h3 class="text-lg font-medium text-slate-100">Asignar Mecánico a OT #{{ otSeleccionada?.numero_ot }}</h3>
      </template>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-300">Seleccionar Mecánico</label>
          <select v-model="mecanicoForm.mecanico_id" class="w-full px-3 py-2 border rounded-md">
            <option value="">Seleccione...</option>
            <option v-for="m in listaMecanicos" :key="m.id" :value="m.id">
              {{ m.nombre }} {{ m.apellido }} ({{ m.ots_activas }} OTs activas)
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-300">Tiempo Estimado / Inicial</label>
          <div class="flex items-center gap-2">
            <div class="flex items-center gap-1 flex-1">
              <input v-model.number="mecanicoForm.horas_trabajadas" type="number" min="0" class="w-full px-3 py-2 border rounded-md" placeholder="0" />
              <span class="text-sm text-slate-400 whitespace-nowrap">h</span>
            </div>
            <div class="flex items-center gap-1 flex-1">
              <input v-model.number="mecanicoForm.minutos_trabajados" type="number" min="0" max="59" class="w-full px-3 py-2 border rounded-md" placeholder="0" />
              <span class="text-sm text-slate-400 whitespace-nowrap">min</span>
            </div>
          </div>
        </div>
      </div>
      <template #footer>
        <BaseButton variant="outline" @click="showAssignMecanicoModal = false">Cancelar</BaseButton>
        <BaseButton 
          variant="primary" 
          :loading="assignLoadingState" 
          :disabled="!mecanicoForm.mecanico_id"
          @click="handleAssignMecanico"
        >
          Confirmar Asignación
        </BaseButton>
      </template>
    </BaseModal>

    <!-- Assign Repuesto Modal -->
    <BaseModal v-model:show="showAssignRepuestoModal" @close="showAssignRepuestoModal = false">
      <template #header>
        <h3 class="text-lg font-medium text-slate-100">Añadir Repuesto a OT #{{ otSeleccionada?.numero_ot }}</h3>
      </template>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1">Buscar y Seleccionar Repuesto</label>
          <div class="relative mb-2">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input v-model="repuestoSearch" type="text" class="w-full pl-9 pr-3 py-2 border rounded-md focus:ring-cyan-500/40 focus:border-cyan-500" placeholder="Buscar por nombre o código OEM..." />
          </div>
          <select v-model="repuestoForm.repuesto_id" class="w-full px-3 py-2 border rounded-md focus:ring-cyan-500/40 focus:border-cyan-500" size="5">
            <option value="" disabled>Seleccione un repuesto de la lista...</option>
            <option v-for="r in filteredRepuestos" :key="r.id" :value="r.id" :disabled="r.stock <= 0">
              {{ r.nombre }} - {{ r.codigo_oem }} (Stock: {{ r.stock }})
            </option>
          </select>
          <p v-if="filteredRepuestos.length === 0" class="text-xs text-amber-600 mt-1">No se encontraron repuestos con esa búsqueda.</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-300">Cantidad a utilizar</label>
          <input v-model.number="repuestoForm.cantidad" type="number" min="1" class="w-full px-3 py-2 border rounded-md" />
        </div>
      </div>
      <template #footer>
        <BaseButton variant="outline" @click="showAssignRepuestoModal = false">Cancelar</BaseButton>
        <BaseButton 
          variant="primary" 
          :loading="assignRepuestoLoadingState" 
          :disabled="!repuestoForm.repuesto_id || repuestoForm.cantidad < 1"
          @click="handleAssignRepuesto"
        >
          Confirmar Repuesto
        </BaseButton>
      </template>
    </BaseModal>

    <!-- Assign Evidencia Modal -->
    <BaseModal v-model:show="showAssignEvidenciaModal" @close="showAssignEvidenciaModal = false">
      <template #header>
        <h3 class="text-lg font-medium text-slate-100">Añadir Evidencia a OT #{{ otSeleccionada?.numero_ot }}</h3>
      </template>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1">Archivo de Evidencia</label>
          <div class="flex items-center justify-center w-full">
            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-600 rounded-lg cursor-pointer bg-slate-800/40 hover:bg-slate-800/50">
              <div class="flex flex-col items-center justify-center pt-5 pb-6">
                <svg class="w-8 h-8 mb-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                <p class="mb-2 text-sm text-slate-400"><span class="font-semibold">Click para subir</span> o arrastre un archivo</p>
                <p class="text-xs text-slate-400">Imágenes (PNG, JPG) o Video (MP4)</p>
              </div>
              <input type="file" accept="image/*,video/*" class="hidden" @change="handleFileChange" />
            </label>
          </div>
          <p v-if="evidenciaForm.url_cloudinary" class="text-xs text-green-600 mt-2 font-medium">✓ Archivo cargado correctamente (Simulación activa)</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1">Etapa de la Reparación</label>
          <select v-model="evidenciaForm.etiqueta" class="w-full px-3 py-2 border rounded-md focus:ring-cyan-500/40 focus:border-cyan-500">
            <option value="antes">Antes de la reparación</option>
            <option value="durante">Durante la reparación</option>
            <option value="despues">Después de la reparación</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1">Descripción de lo reparado</label>
          <textarea v-model="evidenciaForm.descripcion" rows="3" class="w-full px-3 py-2 border rounded-md focus:ring-cyan-500/40 focus:border-cyan-500" placeholder="Indique qué problema ocurría o qué se reparó..."></textarea>
        </div>
      </div>
      <template #footer>
        <BaseButton variant="outline" @click="showAssignEvidenciaModal = false">Cancelar</BaseButton>
        <BaseButton 
          variant="primary" 
          :loading="assignEvidenciaLoadingState" 
          :disabled="!evidenciaForm.url_cloudinary"
          @click="handleAssignEvidencia"
        >
          Subir Evidencia
        </BaseButton>
      </template>
    </BaseModal>

    <!-- Edit Evidencia Modal -->
    <BaseModal v-model:show="showEditEvidenciaModal" @close="showEditEvidenciaModal = false">
      <template #header>
        <h3 class="text-lg font-medium text-slate-100">Editar Evidencia</h3>
      </template>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1">Etapa de la Reparación</label>
          <select v-model="evidenciaEditForm.etiqueta" class="w-full px-3 py-2 border rounded-md focus:ring-cyan-500/40 focus:border-cyan-500">
            <option value="antes">Antes de la reparación</option>
            <option value="durante">Durante la reparación</option>
            <option value="despues">Después de la reparación</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-1">Descripción de lo reparado</label>
          <textarea v-model="evidenciaEditForm.descripcion" rows="3" class="w-full px-3 py-2 border rounded-md focus:ring-cyan-500/40 focus:border-cyan-500"></textarea>
        </div>
      </div>
      <template #footer>
        <BaseButton variant="outline" @click="showEditEvidenciaModal = false">Cancelar</BaseButton>
        <BaseButton variant="primary" :loading="editEvidenciaLoadingState" @click="handleEditEvidencia">Guardar Cambios</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useNotificacionesStore } from '@/stores/notificaciones'
import { ordenService } from '@/services/ordenService'
import { clienteService } from '@/services/clienteService'
import { vehiculoService } from '@/services/vehiculoService'
import { useDataFetch } from '@/composables/useDataFetch'
import BaseTable from '@/components/shared/BaseTable.vue'
import BaseModal from '@/components/shared/BaseModal.vue'
import BaseButton from '@/components/shared/BaseButton.vue'
import BaseAlert from '@/components/shared/BaseAlert.vue'
import OrdenEstadoPipeline from '@/components/ordenes/OrdenEstadoPipeline.vue'
import TransicionEstadoModal from '@/components/ordenes/TransicionEstadoModal.vue'
import { usuarioService } from '@/services/usuarioService'
import { repuestoService } from '@/services/repuestoService'

const authStore = useAuthStore()
const notificacionesStore = useNotificacionesStore()

// Propiedad computada para el rol, asegura reactividad y evita errores de undefined
const userRole = computed(() => {
  return authStore.user?.rol || authStore.userRole || ''
})

const alert = ref(null)
const showCreateModal = ref(false)
const showDetailModal = ref(false)
const showTransicionModal = ref(false)
const showAssignMecanicoModal = ref(false)
const showAssignRepuestoModal = ref(false)
const showAssignEvidenciaModal = ref(false)
const showEditEvidenciaModal = ref(false)

const otSeleccionada = ref(null)
const estadoObjetivo = ref(null)
const transicionLoading = ref(false)
const assignLoading = ref(false)
const assignEvidenciaLoadingState = ref(false)
const editEvidenciaLoadingState = ref(false)

const listaMecanicos = ref([])
const mecanicoForm = reactive({ mecanico_id: '', horas_trabajadas: 0, minutos_trabajados: 0 })

const evidencias = ref([])
const evidenciaForm = reactive({ tipo: 'foto', etiqueta: 'durante', descripcion: '', url_cloudinary: '' })
const evidenciaEditForm = reactive({ id: null, etiqueta: '', descripcion: '' })

const listaRepuestos = ref([])
const repuestoSearch = ref('')
const repuestoForm = reactive({ repuesto_id: '', cantidad: 1 })


const filteredRepuestos = computed(() => {
  if (!repuestoSearch.value) return listaRepuestos.value
  const query = repuestoSearch.value.toLowerCase()
  return listaRepuestos.value.filter(r => 
    r.nombre.toLowerCase().includes(query) || 
    (r.codigo_oem && r.codigo_oem.toLowerCase().includes(query))
  )
})

// Form state
const orderForm = reactive({
  numero_ot: '',
  cliente_id: '',
  vehiculo_id: '',
  descripcion_problema: ''
})
const clients = ref([])
const clientVehicles = ref([])
const loadingVehicles = ref(false)

const presupuestoAprobado = computed(() => {
  return otSeleccionada.value?.presupuesto_aprobado || false
})

const ordenesColumns = [
  { key: 'numero_ot', label: 'OT', width: 'quarter' },
  { key: 'cliente_nombre', label: 'Cliente', width: 'half' },
  {
    key: 'vehiculo_info',
    label: 'Vehículo',
    width: 'half',
    format: (value, row) => {
      if (row.vehiculo) {
        return `${row.vehiculo.marca} ${row.vehiculo.modelo} - ${row.vehiculo.placa}`
      }
      return 'N/A'
    }
  },
  {
    key: 'estado',
    label: 'Estado',
    width: 'quarter',
    format: (value) => {
      const labels = {
        diagnostico: 'Diagnóstico',
        reparacion: 'Reparación',
        esperando_repuesto: 'Esperando Repuesto',
        control_calidad: 'Control Calidad',
        entregado: 'Entregado',
        cancelado: 'Cancelado'
      }
      return labels[value] || value
    }
  }
]

function formatDate(dateString) {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('es-PE')
}

function formatHorasMinutos(decimal) {
  if (!decimal && decimal !== 0) return '0h 0m'
  const h = Math.floor(decimal)
  const m = Math.round((decimal - h) * 60)
  return m > 0 ? `${h}h ${m}m` : `${h}h`
}

// ===== Data fetching for list =====
const { loading: listLoading, data: ordenesData, error: listError, execute: fetchOrdenesList } = 
  useDataFetch(ordenService.getAll)

// ===== Mutation for creating order =====
const { loading: createLoading, execute: createOrder } = useDataFetch(ordenService.create)

// ===== Mutation for assigning mechanic =====
const { loading: assignLoadingState, error: assignError, execute: assignMecanicoAction } = useDataFetch(ordenService.asignarMecanico)

// ===== Mutation for assigning repuesto =====
const { loading: assignRepuestoLoadingState, error: assignRepuestoError, execute: assignRepuestoAction } = useDataFetch(ordenService.agregarRepuesto)

// ===== Mutation for changing state =====
const { loading: transicionLoadingState, error: transicionError, execute: cambiarEstado } = 
  useDataFetch(ordenService.cambiarEstado)

// Update the ordenes ref from the composable's data
const ordenes = ref([])

// Watch client selection to fetch their vehicles
watch(() => orderForm.cliente_id, async (newClientId) => {
  orderForm.vehiculo_id = ''
  clientVehicles.value = []
  if (newClientId) {
    loadingVehicles.value = true
    try {
      const response = await vehiculoService.getAll({ cliente_id: newClientId })
      clientVehicles.value = response.data || []
    } finally {
      loadingVehicles.value = false
    }
  }
})

watch(() => ordenesData.value, (val) => {
  if (val && val.data) {
    ordenes.value = val.data.map(orden => ({
      ...orden,
      cliente_nombre: orden.cliente?.nombre || 'N/A'
    }))

    // Si es mecánico y hay órdenes, cargar el detalle de la primera automáticamente
    if (userRole.value === 'mecanico' && ordenes.value.length > 0 && !otSeleccionada.value) {
      verDetalle(ordenes.value[0])
    }
  }
})

// Handle errors
watch(listError, (err) => {
  if (err) {
    alert.value = { type: 'error', message: err.message || 'Error al obtener órdenes' }
    notificacionesStore.addNotification({
      type: 'error',
      message: err.message || 'Error inesperado',
      timeout: 5000
    })
  } else {
    alert.value = null
  }
})

watch(assignError, (err) => {
  if (err) {
    notificacionesStore.addNotification({
      type: 'error',
      message: err.message || 'Error al asignar mecánico',
      timeout: 5000
    })
  }
})

watch(assignRepuestoError, (err) => {
  if (err) {
    notificacionesStore.addNotification({
      type: 'error',
      message: err.message || 'Error al asignar repuesto',
      timeout: 5000
    })
  }
})

watch(transicionError, (err) => {
  if (err) {
    notificacionesStore.addNotification({
      type: 'error',
      message: err.message || 'Error inesperado',
      timeout: 5000
    })
  }
})

async function fetchOrdenes() {
  try {
    await fetchOrdenesList()
  } catch (err) {
    // Error handled by watcher
  }
}

async function abrirAsignarMecanico() {
  try {
    const response = await usuarioService.getCargaMecanicos()
    if (response && response.success) {
      listaMecanicos.value = response.data || []
      showAssignMecanicoModal.value = true
    }
  } catch (err) {
    console.error('Fallo al recuperar lista de mecánicos:', err)
  }
}

async function handleAssignMecanico() {
  try {
    const payload = {
      mecanico_id: mecanicoForm.mecanico_id,
      horas_trabajadas: (mecanicoForm.horas_trabajadas || 0) + (mecanicoForm.minutos_trabajados || 0) / 60
    }
    await assignMecanicoAction(otSeleccionada.value.id, payload)
    notificacionesStore.addNotification({ type: 'success', message: 'Mecánico asignado correctamente' })
    showAssignMecanicoModal.value = false
    
    mecanicoForm.mecanico_id = ''
    mecanicoForm.horas_trabajadas = 0
    mecanicoForm.minutos_trabajados = 0
    await fetchOrdenes()
    
    // Refrescar modal con info actualizada
    if (otSeleccionada.value) {
      verDetalle({ id: otSeleccionada.value.id })
    }
  } catch (err) {
  }
}

async function abrirAsignarRepuesto() {
  try {
    const response = await repuestoService.getAll()
    if (response && response.success) {
      listaRepuestos.value = response.data || []
      repuestoForm.cantidad = 1
      repuestoForm.repuesto_id = ''
      repuestoSearch.value = ''
      showAssignRepuestoModal.value = true
    }
  } catch (err) {
    notificacionesStore.addNotification({ type: 'error', message: 'No se pudo cargar el inventario' })
  }
}

async function handleAssignRepuesto() {
  try {
    await assignRepuestoAction(otSeleccionada.value.id, repuestoForm)
    notificacionesStore.addNotification({ type: 'success', message: 'Repuesto asignado correctamente' })
    showAssignRepuestoModal.value = false
    
    // Refrescar modal con info actualizada
    if (otSeleccionada.value) {
      verDetalle({ id: otSeleccionada.value.id })
    }
  } catch (err) {
  }
}

async function eliminarRepuesto(repuestoId) {
  if (!confirm('¿Seguro que desea remover este repuesto de la orden?')) return
  try {
    await ordenService.eliminarRepuesto(otSeleccionada.value.id, repuestoId)
    notificacionesStore.addNotification({ type: 'success', message: 'Repuesto removido correctamente' })
    
    // Refrescar modal con info actualizada
    if (otSeleccionada.value) {
      verDetalle({ id: otSeleccionada.value.id })
    }
  } catch (err) {
    notificacionesStore.addNotification({ type: 'error', message: err.message || 'Error al remover repuesto' })
  }
}

function handleFileChange(event) {
  const file = event.target.files[0]
  if (!file) return
  
  if (file.type.startsWith('video/')) {
    evidenciaForm.tipo = 'video'
    evidenciaForm.url_cloudinary = 'https://www.w3schools.com/html/mov_bbb.mp4'
  } else {
    evidenciaForm.tipo = 'foto'
    const carImages = [
      'https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?auto=format&fit=crop&w=800&q=80',
      'https://images.unsplash.com/photo-1530046339160-ce3e530c7d2f?auto=format&fit=crop&w=800&q=80',
      'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?auto=format&fit=crop&w=800&q=80',
      'https://images.unsplash.com/photo-1487754180451-c456f719a1fc?auto=format&fit=crop&w=800&q=80',
      'https://images.unsplash.com/photo-1503376710356-69865111a33a?auto=format&fit=crop&w=800&q=80'
    ]
    evidenciaForm.url_cloudinary = carImages[Math.floor(Math.random() * carImages.length)]
  }
}

function abrirEditarEvidencia(evidencia) {
  evidenciaEditForm.id = evidencia.id
  evidenciaEditForm.etiqueta = evidencia.etiqueta
  evidenciaEditForm.descripcion = evidencia.descripcion || ''
  showEditEvidenciaModal.value = true
}

async function handleEditEvidencia() {
  editEvidenciaLoadingState.value = true
  try {
    await ordenService.actualizarEvidencia(evidenciaEditForm.id, evidenciaEditForm)
    notificacionesStore.addNotification({ type: 'success', message: 'Evidencia actualizada correctamente' })
    showEditEvidenciaModal.value = false
    
    if (otSeleccionada.value) {
      verDetalle({ id: otSeleccionada.value.id })
    }
  } catch (err) {
    notificacionesStore.addNotification({ type: 'error', message: err.message || 'Error al actualizar evidencia' })
  } finally {
    editEvidenciaLoadingState.value = false
  }
}

async function abrirAñadirEvidencia() {
  evidenciaForm.tipo = 'foto'
  evidenciaForm.etiqueta = 'durante'
  evidenciaForm.descripcion = ''
  evidenciaForm.url_cloudinary = ''
  showAssignEvidenciaModal.value = true
}

async function handleAssignEvidencia() {
  assignEvidenciaLoadingState.value = true
  try {
    await ordenService.crearEvidencia(otSeleccionada.value.id, evidenciaForm)
    notificacionesStore.addNotification({ type: 'success', message: 'Evidencia multimedia añadida correctamente' })
    showAssignEvidenciaModal.value = false
    
    if (otSeleccionada.value) {
      verDetalle({ id: otSeleccionada.value.id })
    }
  } catch (err) {
    notificacionesStore.addNotification({ type: 'error', message: err.message || 'Error al subir evidencia' })
  } finally {
    assignEvidenciaLoadingState.value = false
  }
}

async function eliminarEvidencia(evidenciaId) {
  if (!confirm('¿Seguro que desea eliminar esta evidencia?')) return
  try {
    await ordenService.eliminarEvidencia(otSeleccionada.value.id, evidenciaId)
    notificacionesStore.addNotification({ type: 'success', message: 'Evidencia eliminada correctamente' })
    
    if (otSeleccionada.value) {
      verDetalle({ id: otSeleccionada.value.id })
    }
  } catch (err) {
    notificacionesStore.addNotification({ type: 'error', message: err.message || 'Error al eliminar evidencia' })
  }
}

async function guardarHorasMecanico(mecanico) {
  mecanico.saving = true
  try {
    const totalHoras = (mecanico.horas_int ?? Math.floor(mecanico.horas_trabajadas)) +
                       (mecanico.minutos_int ?? Math.round((mecanico.horas_trabajadas % 1) * 60)) / 60
    await ordenService.actualizarHorasMecanico(otSeleccionada.value.id, mecanico.mecanico_id, {
      horas_trabajadas: totalHoras
    })
    notificacionesStore.addNotification({ type: 'success', message: 'Horas actualizadas' })
  } catch (err) {
    notificacionesStore.addNotification({ type: 'error', message: err.message || 'Error al actualizar horas' })
  } finally {
    mecanico.saving = false
  }
}

async function copiarLinkSeguimiento(codigo) {
  if (!codigo) return
  
  const origin = window.location.origin
  // Suponiendo que la ruta en Vue Router para Seguimiento Público es /seguimiento/:codigo
  const link = `${origin}/seguimiento/${codigo}`
  
  try {
    await navigator.clipboard.writeText(link)
    notificacionesStore.addNotification({ 
      type: 'success', 
      message: 'Link copiado al portapapeles' 
    })
  } catch (err) {
    notificacionesStore.addNotification({ 
      type: 'error', 
      message: 'Error al copiar el link. Cópielo manualmente: ' + link 
    })
  }
}


async function abrirModalCreacion() {
  try {
    const response = await clienteService.getAll()
    clients.value = response.data || []
    showCreateModal.value = true
  } catch (err) {
    notificacionesStore.addNotification({ type: 'error', message: 'No se pudieron cargar los clientes' })
  }
}

async function handleCreate() {
  try {
    await createOrder(orderForm)
    notificacionesStore.addNotification({ type: 'success', message: 'Orden creada exitosamente' })
    showCreateModal.value = false
    resetCreateForm()
    await fetchOrdenes()
  } catch (err) {
    // Error handled by composable
  }
}

function resetCreateForm() {
  orderForm.numero_ot = ''
  orderForm.cliente_id = ''
  orderForm.vehiculo_id = ''
  orderForm.descripcion_problema = ''
  clientVehicles.value = []
}

async function verDetalle(orden) {
  try {
    const res = await ordenService.getById(orden.id)
    if (res && res.success) {
      otSeleccionada.value = res.data
      
      try {
        const eviRes = await ordenService.getEvidencias(orden.id)
        if (eviRes && eviRes.success) {
          evidencias.value = eviRes.data
        } else {
          evidencias.value = []
        }
      } catch (err) {
        evidencias.value = []
      }

      if (userRole.value !== 'mecanico') {
        showDetailModal.value = true
      }
    }
  } catch (err) {
    notificacionesStore.addNotification({
      type: 'error',
      message: err.message || 'Error al obtener detalle de la orden'
    })
  }
}

function abrirTransicion({ otId, nuevoEstado }) {
  estadoObjetivo.value = nuevoEstado
  showTransicionModal.value = true
}

function resetTransicion() {
  showTransicionModal.value = false
  estadoObjetivo.value = null
}

function resetDetail() {
  showDetailModal.value = false
  otSeleccionada.value = null
}

async function confirmarInicioReparacion() {
  await ejecutarTransicion({ otId: otSeleccionada.value.id, nuevoEstado: 'reparacion', observaciones: '' })
}


async function ejecutarTransicion({ otId, nuevoEstado, observaciones }) {
  transicionLoading.value = true
  try {
    await cambiarEstado(otId, {
      estado: nuevoEstado,
      observaciones: observaciones
    })
    notificacionesStore.addNotification({
      type: 'success',
      message: 'Estado actualizado correctamente',
      timeout: 3000
    })
    // Refresh the list
    await fetchOrdenesList()
    // If the selected order is the one we just updated, refresh the detail modal
    if (otSeleccionada.value?.id === otId) {
      verDetalle({ id: otId })
    }
  } catch (err) {
    // Error handled by watcher
  } finally {
    transicionLoading.value = false
    showTransicionModal.value = false
  }
}

onMounted(() => {
  fetchOrdenes()
})
</script>