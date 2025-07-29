<template>
  <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <!-- Заголовок -->
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-medium text-gray-900">Детали аудит-лога #{{ log.id }}</h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <!-- Основная информация -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Время действия</label>
              <p class="mt-1 text-sm text-gray-900">{{ formatDate(log.created_at) }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Пользователь</label>
              <p class="mt-1 text-sm text-gray-900">
                {{ log.user?.name || 'Система' }}
                <span v-if="log.user?.role" class="text-xs text-gray-500">{{
                  USER_ROLE_LABELS[log.user.role] || log.user.role
                }}</span>
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Действие</label>
              <span
                :class="[
                  'inline-flex px-2 py-1 text-xs font-semibold rounded-full mt-1',
                  AUDIT_ACTION_COLORS[log.action],
                ]"
              >
                {{ AUDIT_ACTION_LABELS[log.action] }}
              </span>
            </div>
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Тип модели</label>
              <p class="mt-1 text-sm text-gray-900">
                {{ AUDIT_MODEL_LABELS[log.auditable_type] || log.auditable_type }}
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">ID записи</label>
              <p class="mt-1 text-sm text-gray-900">{{ log.auditable_id }}</p>
            </div>

            <div v-if="log.ip_address">
              <label class="block text-sm font-medium text-gray-700">IP адрес</label>
              <p class="mt-1 text-sm text-gray-900">{{ log.ip_address }}</p>
            </div>
          </div>
        </div>

        <!-- Изменения -->
        <div v-if="log.action === 'updated' && (log.old_values || log.new_values)" class="mb-6">
          <h4 class="text-md font-medium text-gray-900 mb-4">Изменения</h4>
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Старые значения -->
              <div v-if="log.old_values">
                <h5 class="text-sm font-medium text-red-700 mb-2">Старые значения</h5>
                <div class="bg-white rounded border p-3 max-h-64 overflow-y-auto">
                  <pre class="text-xs text-gray-700 whitespace-pre-wrap">{{
                    formatJson(log.old_values)
                  }}</pre>
                </div>
              </div>

              <!-- Новые значения -->
              <div v-if="log.new_values">
                <h5 class="text-sm font-medium text-green-700 mb-2">Новые значения</h5>
                <div class="bg-white rounded border p-3 max-h-64 overflow-y-auto">
                  <pre class="text-xs text-gray-700 whitespace-pre-wrap">{{
                    formatJson(log.new_values)
                  }}</pre>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Новые значения для создания -->
        <div v-if="log.action === 'created' && log.new_values" class="mb-6">
          <h4 class="text-md font-medium text-gray-900 mb-4">Созданные данные</h4>
          <div class="bg-green-50 rounded-lg p-4">
            <div class="bg-white rounded border p-3 max-h-64 overflow-y-auto">
              <pre class="text-xs text-gray-700 whitespace-pre-wrap">{{
                formatJson(log.new_values)
              }}</pre>
            </div>
          </div>
        </div>

        <!-- Старые значения для удаления -->
        <div
          v-if="(log.action === 'deleted' || log.action === 'force_deleted') && log.old_values"
          class="mb-6"
        >
          <h4 class="text-md font-medium text-gray-900 mb-4">Удаленные данные</h4>
          <div class="bg-red-50 rounded-lg p-4">
            <div class="bg-white rounded border p-3 max-h-64 overflow-y-auto">
              <pre class="text-xs text-gray-700 whitespace-pre-wrap">{{
                formatJson(log.old_values)
              }}</pre>
            </div>
          </div>
        </div>

        <!-- User Agent -->
        <div v-if="log.user_agent" class="mb-6">
          <h4 class="text-md font-medium text-gray-900 mb-2">User Agent</h4>
          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-700 break-all">{{ log.user_agent }}</p>
          </div>
        </div>

        <!-- Кнопки -->
        <div class="flex justify-end space-x-3">
          <button
            @click="$emit('close')"
            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
          >
            Закрыть
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { AuditLog } from '@/types/audit'
import { AUDIT_ACTION_LABELS, AUDIT_ACTION_COLORS, AUDIT_MODEL_LABELS } from '@/types/audit'

interface Props {
  log: AuditLog
}

defineProps<Props>()
defineEmits<{
  close: []
}>()

// Форматирование даты
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleString('ru-RU', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

// Форматирование JSON
const formatJson = (obj: Record<string, unknown>) => {
  try {
    return JSON.stringify(obj, null, 2)
  } catch {
    return String(obj)
  }
}

const USER_ROLE_LABELS: Record<string, string> = {
  admin: 'Администратор',
  manager: 'Менеджер',
  designer: 'Дизайнер',
  print_operator: 'Печатник',
  workshop_worker: 'Работник цеха',
  user: 'Пользователь',
}
</script>
