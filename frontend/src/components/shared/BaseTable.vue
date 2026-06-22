<template>
  <div class="overflow-x-auto border border-gray-100 rounded-lg shadow-sm">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-50">
        <tr>
          <template v-for="column in columns" :key="column.key">
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider select-none"
              :class="{
                'w-1/2': column.width === 'full',
                'w-1/4': column.width === 'half',
                'w-1/6': column.width === 'third',
                'w-1/12': column.width === 'quarter'
              }"
            >
              <div class="flex items-center space-x-1">
                <span>{{ column.label }}</span>
                <template v-if="column.sortable">
                  <span>
                    <svg v-if="sortColumn === column.key && sortDirection === 'asc'"
                         class="h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                    <svg v-else-if="sortColumn === column.key && sortDirection === 'desc'"
                         class="h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                    </svg>
                  </span>
                </template>
              </div>
            </th>
          </template>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <template v-if="loading">
          <tr>
            <td :colspan="columns.length" class="px-6 py-8 text-center text-gray-400 text-sm">
              <div class="inline-block animate-spin h-5 w-5 border-2 border-indigo-500 border-t-transparent rounded-full mr-2 align-middle"></div>
              Cargando registros...
            </td>
          </tr>
        </template>
        <template v-else-if="!loading && data.length === 0">
          <tr>
            <td :colspan="columns.length" class="px-6 py-8 text-center text-gray-400 text-sm">
              No se encontraron registros.
            </td>
          </tr>
        </template>
        <template v-else>
          <tr
            v-for="(row, rowIndex) in data"
            :key="row.id || rowIndex"
            class="hover:bg-gray-50 cursor-pointer transition-colors"
            @click="emitRowClick(row)"
          >
            <td 
              v-for="column in columns" 
              :key="column.key + '-' + (row.id || rowIndex)"
              class="px-6 py-4 text-sm text-gray-600 truncate max-w-xs"
              :class="{
                'w-1/2': column.width === 'full',
                'w-1/4': column.width === 'half',
                'w-1/6': column.width === 'third',
                'w-1/12': column.width === 'quarter'
              }"
            >
              <div v-if="column.format" v-html="column.format(row[column.key], row)"></div>
              <template v-else>
                {{ row[column.key] !== null && row[column.key] !== undefined ? row[column.key] : '' }}
              </template>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>

  <div v-if="pagination && pagination.total > 0" class="mt-4 flex items-center justify-between px-4 py-3 bg-white border border-gray-100 rounded-lg shadow-sm">
    <span class="text-xs sm:text-sm text-gray-500">
      Mostrando <span class="font-medium text-gray-700">{{ pagination.from }}</span> a <span class="font-medium text-gray-700">{{ pagination.to }}</span> de <span class="font-medium text-gray-700">{{ pagination.total }}</span> registros
    </span>
    <div class="flex space-x-2">
      <button
        :disabled="pagination.current_page <= 1"
        @click="prevPage"
        class="px-3 py-1.5 border border-gray-300 rounded-md text-xs sm:text-sm font-medium bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
      >
        Anterior
      </button>
      <span class="px-2 py-1.5 text-xs sm:text-sm text-gray-500 self-center">
        Página {{ pagination.current_page }} de {{ pagination.last_page }}
      </span>
      <button
        :disabled="pagination.current_page >= pagination.last_page"
        @click="nextPage"
        class="px-3 py-1.5 border border-gray-300 rounded-md text-xs sm:text-sm font-medium bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
      >
        Siguiente
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue' // <-- Importación corregida de ref

const props = defineProps({
  columns: {
    type: Array,
    required: true
  },
  data: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  pagination: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:page', 'row-click'])

const sortColumn = ref(null)
const sortDirection = ref('asc')

function prevPage() {
  if (props.pagination && props.pagination.current_page > 1) {
    emit('update:page', props.pagination.current_page - 1)
  }
}

function nextPage() {
  if (props.pagination && props.pagination.current_page < props.pagination.last_page) {
    emit('update:page', props.pagination.current_page + 1)
  }
}

function emitRowClick(row) {
  emit('row-click', row)
}
</script>