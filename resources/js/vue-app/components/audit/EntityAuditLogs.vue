<template>
  <div class="space-y-4">
    <!-- Заголовок -->
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-semibold text-gray-900">История изменений</h3>
      <button
        v-if="logs.length > 0"
        @click="showAllLogs = !showAllLogs"
        class="text-sm text-blue-600 hover:text-blue-800"
      >
        {{ showAllLogs ? 'Скрыть' : 'Показать все' }}
      </button>
    </div>

    <!-- Список логов -->
    <div v-if="loading" class="text-center py-4">
      <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
      <p class="mt-2 text-sm text-gray-600">Загрузка истории...</p>
    </div>

    <div v-else-if="logs.length === 0" class="text-center py-4">
      <p class="text-sm text-gray-600">История изменений отсутствует</p>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="log in displayedLogs"
        :key="log.id"
        class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <span
                :class="[
                  'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                  AUDIT_ACTION_COLORS[log.action],
                ]"
              >
                {{ AUDIT_ACTION_LABELS[log.action] }}
              </span>
              <span class="text-sm text-gray-500">
                {{ formatDate(log.created_at) }}
              </span>
            </div>

            <div class="text-sm text-gray-700 mb-2">
              <span class="font-medium">{{ log.user?.name || 'Система' }}</span>
              <span v-if="log.user?.username" class="text-gray-500">
                ({{ log.user.username }})</span
              >
            </div>

            <!-- Краткое описание изменений -->
            <div v-if="log.action === 'updated' && hasChanges(log)" class="text-sm text-gray-600">
              <span class="font-medium">Изменены поля:</span>
              {{ getChangedFields(log).join(', ') }}
            </div>
          </div>

          <button @click="showLogDetails(log)" class="text-blue-600 hover:text-blue-800 text-sm">
            Детали
          </button>
        </div>
      </div>

      <!-- Кнопка "Показать больше" -->
      <div v-if="!showAllLogs && logs.length > 3" class="text-center">
        <button @click="showAllLogs = true" class="text-blue-600 hover:text-blue-800 text-sm">
          Показать еще {{ logs.length - 3 }} записей
        </button>
      </div>
    </div>

    <!-- Модальное окно с деталями -->
    <AuditLogDetailsModal v-if="selectedLog" :log="selectedLog" @close="selectedLog = null" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { AuditController } from '@/controllers/AuditController'
import type { AuditLog } from '@/types/audit'
import { AUDIT_ACTION_LABELS, AUDIT_ACTION_COLORS } from '@/types/audit'
import AuditLogDetailsModal from './AuditLogDetailsModal.vue'

interface Props {
  auditableType: string
  auditableId: number
  limit?: number
}

const props = withDefaults(defineProps<Props>(), {
  limit: 3,
})

// Состояние
const logs = ref<AuditLog[]>([])
const loading = ref(false)
const showAllLogs = ref(false)
const selectedLog = ref<AuditLog | null>(null)

// Вычисляемые свойства
const displayedLogs = computed(() => {
  if (showAllLogs.value) {
    return logs.value
  }
  return logs.value.slice(0, props.limit)
})

// Загрузка логов
const loadLogs = async () => {
  loading.value = true
  try {
    const response = await AuditController.getEntityAuditLogs(
      props.auditableType,
      props.auditableId,
    )
    logs.value = response.data
  } catch (error) {
    console.error('Ошибка загрузки логов сущности:', error)
  } finally {
    loading.value = false
  }
}

// Проверка наличия изменений
const hasChanges = (log: AuditLog): boolean => {
  return !!(log.old_values && Object.keys(log.old_values).length > 0)
}

// Получение списка измененных полей
const getChangedFields = (log: AuditLog): string[] => {
  if (!log.old_values) return []

  return Object.keys(log.old_values).map((field) => {
    // Попытка получить человекочитаемое название поля
    const fieldLabels: Record<string, string> = {
      name: 'Название',
      title: 'Заголовок',
      description: 'Описание',
      status: 'Статус',
      price: 'Цена',
      quantity: 'Количество',
      email: 'Email',
      phone: 'Телефон',
      address: 'Адрес',
      role: 'Роль',
      created_at: 'Дата создания',
      updated_at: 'Дата обновления',
    }

    return fieldLabels[field] || field
  })
}

// Показать детали лога
const showLogDetails = (log: AuditLog) => {
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

// Инициализация
onMounted(() => {
  loadLogs()
})
</script>
