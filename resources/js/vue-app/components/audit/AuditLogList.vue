<template>
  <div class="space-y-6">
    <!-- Заголовок и статистика -->
    <div class="flex items-center justify-between py-2 px-4 bg-white border-b mb-2">
      <div class="flex items-center gap-6 text-gray-700 text-base font-medium">
        <div class="flex items-center gap-1">
          <span class="text-gray-500 font-semibold">Всего:</span>
          <span class="text-blue-600 font-bold">{{ pagination?.total || 0 }}</span>
        </div>
        <div class="flex items-center gap-1">
          <span class="text-gray-500 font-semibold">Страницы:</span>
          <span class="text-blue-600 font-bold">{{ pagination?.last_page || 1 }}</span>
        </div>
      </div>
      <div
        class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-1 shadow-sm border border-gray-200"
      >
        <span class="text-gray-600 font-semibold">На странице:</span>
        <select
          v-model.number="filters.per_page"
          @change="changePerPage"
          class="bg-white border border-gray-300 rounded px-2 py-1 focus:ring-2 focus:ring-blue-400 focus:outline-none text-gray-900 font-semibold"
        >
          <option v-for="n in [10, 20, 50, 100, 200, 500]" :key="n" :value="n">{{ n }}</option>
        </select>
      </div>
    </div>

    <!-- Фильтры -->
    <div class="bg-white p-6 rounded-lg shadow">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Тип модели</label>
          <select
            v-model="filters.auditable_type"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="loadLogs"
          >
            <option value="">Все типы</option>
            <option value="App\Models\Order">Заказы</option>
            <option value="App\Models\Product">Товары</option>
            <option value="App\Models\Project">Проекты</option>
            <option value="App\Models\User">Пользователи</option>
            <option value="App\Models\Client">Клиенты</option>
            <option value="App\Models\ClientContact">Контакты клиентов</option>
            <option value="App\Models\Comment">Комментарии</option>
            <option value="App\Models\OrderAssignment">Назначения заказов</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Действие</label>
          <select
            v-model="filters.action"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="loadLogs"
          >
            <option value="">Все действия</option>
            <option value="created">Создание</option>
            <option value="updated">Обновление</option>
            <option value="deleted">Удаление</option>
            <option value="restored">Восстановление</option>
            <option value="force_deleted">Полное удаление</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Дата от</label>
          <input
            ref="dateFromInput"
            v-model="filters.date_from"
            type="text"
            placeholder="Выберите дату..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            readonly
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Дата до</label>
          <input
            ref="dateToInput"
            v-model="filters.date_to"
            type="text"
            placeholder="Выберите дату..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            readonly
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">ID</label>
          <input
            v-model="filters.auditable_id"
            type="number"
            placeholder="ID записи..."
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            @input="debouncedSearch"
          />
        </div>
        <div class="flex items-end">
          <button
            @click="clearFilters"
            class="w-full px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600"
          >
            Очистить фильтры
          </button>
        </div>
      </div>
    </div>

    <!-- Список логов -->
    <div class="bg-white border border-gray-200">
      <div v-if="loading" class="p-8 text-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-2 text-gray-600">Загрузка логов...</p>
      </div>

      <div v-else-if="logs.length === 0" class="p-8 text-center">
        <p class="text-gray-600">Логи не найдены</p>
      </div>

      <div v-else>
        <table class="w-full border-collapse border-gray-300 text-gray-900 text-base">
          <thead class="bg-gray-50 text-gray-900 font-medium">
            <tr ref="columnsHeader">
              <th
                v-for="col in columns"
                :key="col.key"
                class="border border-gray-200 px-3 py-2 text-left whitespace-nowrap select-none cursor-move"
              >
                {{ col.label }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(log, $index) in logs"
              :key="log.id"
              :class="[
                'cursor-pointer border-b border-gray-100',
                $index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                'hover:bg-blue-50 transition-colors',
              ]"
              style="height: 56px"
            >
              <td
                v-for="col in columns"
                :key="col.key"
                class="border-r border-gray-200 px-4 py-4 text-base whitespace-nowrap align-middle"
              >
                <template v-if="col.key === 'created_at'">
                  {{ formatDate(log.created_at) }}
                </template>
                <template v-else-if="col.key === 'user'">
                  <div class="text-sm font-medium text-gray-900">
                    {{ log.user?.name || 'Система' }}
                  </div>
                  <div v-if="log.user?.role" class="text-xs text-gray-500">
                    {{ USER_ROLE_LABELS[log.user.role] || log.user.role }}
                  </div>
                </template>
                <template v-else-if="col.key === 'action'">
                  <span
                    :class="[
                      'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                      AUDIT_ACTION_COLORS[log.action],
                    ]"
                  >
                    {{ AUDIT_ACTION_LABELS[log.action] }}
                  </span>
                </template>
                <template v-else-if="col.key === 'auditable_type'">
                  {{ AUDIT_MODEL_LABELS[log.auditable_type] || log.auditable_type }}
                </template>
                <template v-else-if="col.key === 'auditable_id'">
                  <span v-if="log.auditable_type === 'App\\Models\\User'">
                    {{ log.auditable?.name || log.auditable_id }}
                  </span>
                  <span v-else-if="log.auditable_type === 'App\\Models\\Client'">
                    {{ log.auditable?.name || log.auditable?.company_name || log.auditable_id }}
                  </span>
                  <span v-else-if="log.auditable_type === 'App\\Models\\Product'">
                    {{ log.auditable?.name || log.auditable_id }}
                  </span>
                  <span v-else-if="log.auditable_type === 'App\\Models\\Project'">
                    {{ log.auditable?.title || log.auditable_id }}
                  </span>
                  <span v-else>
                    {{ log.auditable_id }}
                  </span>
                </template>

                <template v-else-if="col.key === 'actions'">
                  <button @click="showDetails(log)" class="text-blue-600 hover:text-blue-900">
                    Детали
                  </button>
                </template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <Pagination
      v-if="pagination && pagination.last_page > 1"
      :current-page="pagination.current_page"
      :last-page="pagination.last_page"
      @go-to-page="changePage"
      class="mt-4 shrink-0"
    />

    <!-- Модальное окно с деталями -->
    <AuditLogDetailsModal v-if="selectedLog" :log="selectedLog" @close="selectedLog = null" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick, watch } from 'vue'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'
import Sortable from 'sortablejs'
import { AuditController } from '@/controllers/AuditController'
import type { AuditLog, AuditLogFilters, AuditLogResponse } from '@/types/audit'
import { AUDIT_ACTION_LABELS, AUDIT_ACTION_COLORS, AUDIT_MODEL_LABELS } from '@/types/audit'
import AuditLogDetailsModal from './AuditLogDetailsModal.vue'
import Pagination from '@/components/users/UserList/Pagination.vue'
// import { getAllUsers } from '@/services/api'

// Состояние
const logs = ref<AuditLog[]>([])
const loading = ref(false)
const selectedLog = ref<AuditLog | null>(null)
const users = ref([])

// Фильтры
const filters = ref<AuditLogFilters>({
  page: 1,
  per_page: 30,
})

// Пагинация
const pagination = ref<{
  current_page: number
  last_page: number
  per_page: number
  total: number
} | null>(null)

// Описание столбцов для drag-n-drop
const columns = ref([
  { key: 'created_at', label: 'Время' },
  { key: 'user', label: 'Пользователь' },
  { key: 'action', label: 'Действие' },
  { key: 'auditable_type', label: 'Модель' },
  { key: 'auditable_id', label: 'ID' },
  { key: 'actions', label: 'Действия' },
])
const columnsHeader = ref(null)

// Загрузка логов
const loadLogs = async () => {
  loading.value = true
  try {
    const response: AuditLogResponse = await AuditController.getAuditLogs(filters.value)
    logs.value = response.data
    pagination.value = {
      current_page: response.pagination?.current_page || response.current_page,
      last_page: response.pagination?.last_page || response.last_page,
      per_page: response.pagination?.per_page || response.per_page,
      total: response.pagination?.total || response.total,
    }
  } catch (error) {
    console.error('Ошибка загрузки логов:', error)
    // Показываем сообщение об ошибке пользователю
    logs.value = []
    pagination.value = null
  } finally {
    loading.value = false
  }
}

// Изменение страницы
const changePage = (page: number) => {
  if (page >= 1 && page <= (pagination.value?.last_page || 1)) {
    filters.value.page = page
    loadLogs()
  }
}

// Очистка фильтров
const clearFilters = () => {
  filters.value = {
    page: 1,
    per_page: 30,
  }
  loadLogs()
}

// Поиск с задержкой
let searchTimeout: number
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = window.setTimeout(() => {
    filters.value.page = 1
    loadLogs()
  }, 500)
}

// Показать детали лога
const showDetails = (log: AuditLog) => {
  selectedLog.value = log
}

// Форматирование даты
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleString('ru-RU', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// flatpickr refs
const dateFromInput = ref(null)
const dateToInput = ref(null)

// Словарь ролей пользователей
const USER_ROLE_LABELS: Record<string, string> = {
  admin: 'Администратор',
  manager: 'Менеджер',
  designer: 'Дизайнер',
  print_operator: 'Печатник',
  workshop_worker: 'Работник цеха',
  user: 'Пользователь',
}

const allowedPerPage = [10, 20, 50, 100, 200, 500]
function validatePerPage(val) {
  if (!allowedPerPage.includes(val)) return 30
  return val
}
function changePerPage() {
  filters.value.per_page = validatePerPage(filters.value.per_page)
  filters.value.page = 1
  loadLogs()
}
watch(
  () => filters.value.per_page,
  (newVal) => {
    filters.value.per_page = validatePerPage(newVal)
    filters.value.page = 1
    loadLogs()
  },
)

// Инициализация
onMounted(async () => {
  loadLogs()
  nextTick(() => {
    if (dateFromInput.value) {
      flatpickr(dateFromInput.value, {
        dateFormat: 'Y-m-d',
        onChange: ([date]) => {
          filters.value.date_from = date ? date.toISOString().slice(0, 10) : ''
          loadLogs()
        },
      })
    }
    if (dateToInput.value) {
      flatpickr(dateToInput.value, {
        dateFormat: 'Y-m-d',
        onChange: ([date]) => {
          filters.value.date_to = date ? date.toISOString().slice(0, 10) : ''
          loadLogs()
        },
      })
    }
    // Drag-n-drop для заголовков
    if (columnsHeader.value) {
      Sortable.create(columnsHeader.value, {
        animation: 150,
        direction: 'horizontal',
        onEnd(evt) {
          if (evt.oldIndex !== undefined && evt.newIndex !== undefined) {
            const moved = columns.value.splice(evt.oldIndex, 1)[0]
            columns.value.splice(evt.newIndex, 0, moved)
          }
        },
      })
    }
  })
})
</script>

<style scoped>
table {
  table-layout: fixed;
  width: 100%;
}
td {
  white-space: normal;
  word-break: break-word;
  max-width: 180px;
}
</style>
