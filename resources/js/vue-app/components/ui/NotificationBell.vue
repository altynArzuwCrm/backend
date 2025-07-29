<template>
  <div class="relative notification-bell-container">
    <button
      @click="toggleDropdown"
      :class="[
        'relative flex items-center justify-center w-10 h-10 rounded-full hover:bg-blue-100 transition',
        hasNewNotifications ? 'animate-pulse bg-blue-50' : '',
      ]"
    >
      <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
        />
      </svg>
      <span
        v-if="unreadCount > 0"
        class="absolute top-1 right-1 bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 font-bold shadow"
        >{{ unreadCount }}</span
      >
    </button>
    <Teleport to="body">
      <div
        v-if="dropdownOpen"
        class="fixed right-6 top-20 w-96 max-w-[95vw] bg-white rounded-xl shadow-xl border border-blue-100 z-[9999] animate-slide-down"
      >
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
          <span class="font-semibold text-blue-900 text-base">Уведомления</span>
          <div class="flex items-center gap-2">
            <button
              @click="fetchNotifications"
              :disabled="loading"
              class="text-xs text-blue-600 hover:underline disabled:opacity-50"
              title="Обновить"
            >
              {{ loading ? '...' : '↻' }}
            </button>
            <button
              v-if="unreadCount > 0"
              @click="markAllRead"
              class="text-xs text-blue-600 hover:underline"
            >
              Прочитать все
            </button>
          </div>
        </div>
        <div v-if="loading" class="p-6 text-center text-gray-500">Загрузка...</div>
        <div v-else-if="notifications.length === 0" class="p-6 text-center text-gray-400">
          Нет уведомлений
        </div>
        <ul v-else class="max-h-96 overflow-y-auto divide-y divide-gray-100">
          <li
            v-for="notif in notifications"
            :key="notif.id"
            :class="[
              'px-4 py-3 flex gap-3 items-start cursor-pointer hover:bg-blue-50 transition',
              notif.read_at ? 'opacity-70' : 'bg-blue-50/30',
            ]"
            @click="handleClick(notif)"
          >
            <div
              class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-lg"
            >
              <svg
                v-if="notif.data?.icon === 'assignment'"
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12h6m2 0a2 2 0 100-4 2 2 0 000 4zm-2 0a2 2 0 11-4 0 2 2 0 014 0zm-2 0v6m0 0a2 2 0 100-4 2 2 0 000 4zm0 0a2 2 0 11-4 0 2 2 0 014 0z"
                />
              </svg>
              <svg
                v-else-if="notif.data?.icon === 'status'"
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                />
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" stroke-width="2" />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div
                class="text-sm text-gray-900 font-medium leading-snug"
                v-html="
                  translateStatusInText(
                    notif.data?.message || notif.data?.text || notif.text || 'Уведомление',
                  )
                "
              />
              <div class="text-xs text-gray-500 mt-1">
                {{ formatDate(notif.created_at) }}
                <span v-if="notif.data?.action_user" class="text-blue-600">
                  • {{ notif.data.action_user.name }} ({{
                    getRoleLabel(notif.data.action_user.role)
                  }})
                </span>
              </div>
            </div>
            <span v-if="!notif.read_at" class="w-2 h-2 mt-2 rounded-full bg-blue-500"></span>
          </li>
        </ul>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { API_CONFIG } from '@/config/api'

// Звук уведомления
const notificationAudio =
  typeof window !== 'undefined'
    ? new Audio('/sounds/mixkit-software-interface-start-2574.wav')
    : null

// Добавляем обработчик клика вне dropdown'а
function handleClickOutside(event: Event) {
  const target = event.target as HTMLElement
  // Проверяем, что клик был не на кнопке уведомлений и не внутри dropdown'а
  if (!target.closest('.notification-bell-container')) {
    dropdownOpen.value = false
  }
}

const props = defineProps<{ user: any }>()

const dropdownOpen = ref(false)
const notifications = ref<any[]>([])
const unreadCount = ref(0)
const loading = ref(false)
const hasNewNotifications = ref(false)
let pollInterval: any = null
let previousUnreadCount = 0

function toggleDropdown() {
  dropdownOpen.value = !dropdownOpen.value
  if (dropdownOpen.value) {
    fetchNotifications()
  }
}

async function fetchNotifications() {
  loading.value = true
  try {
    const res = await fetch(`${API_CONFIG.BASE_URL}/notifications`, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      },
    })
    const data = await res.json()
    notifications.value = Array.isArray(data) ? data : data.data || []
    const newUnreadCount = notifications.value.filter((n) => !n.read_at).length
    // Открываем dropdown при любом увеличении количества непрочитанных уведомлений
    if (newUnreadCount > previousUnreadCount) {
      if (notificationAudio) {
        notificationAudio.currentTime = 0
        notificationAudio.play().catch(() => {})
      }
      dropdownOpen.value = true
      hasNewNotifications.value = true
      setTimeout(() => {
        hasNewNotifications.value = false
      }, 5000)
    }
    unreadCount.value = newUnreadCount
    previousUnreadCount = newUnreadCount
  } catch (e) {
    notifications.value = []
    unreadCount.value = 0
    previousUnreadCount = 0
  } finally {
    loading.value = false
  }
}

async function markAllRead() {
  await fetch(`${API_CONFIG.BASE_URL}/notifications/read-all`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
  })
  fetchNotifications()
}

async function handleClick(notif: any) {
  if (!notif.read_at) {
    await fetch(`${API_CONFIG.BASE_URL}/notifications/${notif.id}/read`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      },
    })
    notif.read_at = new Date().toISOString()
    unreadCount.value = notifications.value.filter((n) => !n.read_at).length
  }
  // Если есть ссылка — перейти
  if (notif.data?.url) {
    window.open(notif.data.url, '_blank')
  }
}

function formatDate(date: string) {
  if (!date) return ''
  return new Date(date).toLocaleString('ru-RU', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getRoleLabel(role: string) {
  const roleLabels: Record<string, string> = {
    admin: 'Администратор',
    manager: 'Менеджер',
    designer: 'Дизайнер',
    user: 'Пользователь',
  }
  return roleLabels[role] || role
}

// Перевод статусов на русский язык
function getStatusLabelRu(status: string) {
  const map: Record<string, string> = {
    approved: 'Одобрено',
    pending: 'Ожидание',
    completed: 'Завершено',
    cancelled: 'Отменено',
    in_progress: 'В работе',
    under_review: 'На проверке',
    refused: 'Отказано',
    draft: 'Черновик',
    design: 'Дизайн',
    print: 'Печать',
    engraving: 'Гравировка',
    workshop: 'Цех',
    final: 'Финальный',
    user: 'Пользователь',
    manager: 'Менеджер',
    designer: 'Дизайнер',
    print_operator: 'Печатник',
    workshop_worker: 'Работник цеха',
    completed_stage: 'Завершён',
    // Добавьте другие статусы по необходимости
  }
  return map[status] || status
}

function translateStatusInText(text: string): string {
  return text.replace(
    /\b(approved|pending|completed|cancelled|in_progress|under_review|refused|draft|design|print|engraving|workshop|final)\b/gi,
    (m) => getStatusLabelRu(m.toLowerCase()),
  )
}

onMounted(() => {
  fetchNotifications()
  // Проверяем уведомления каждые 10 секунд для более быстрого реагирования
  pollInterval = setInterval(fetchNotifications, 10000)

  // Добавляем обработчик клика вне dropdown'а
  document.addEventListener('click', handleClickOutside)

  // Добавляем обработчик фокуса окна для обновления при возвращении на вкладку
  window.addEventListener('focus', fetchNotifications)

  // Добавляем обработчик видимости страницы
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      fetchNotifications()
    }
  })
})

// Очищаем интервал при размонтировании компонента
onUnmounted(() => {
  if (pollInterval) {
    clearInterval(pollInterval)
  }

  // Удаляем обработчик клика
  document.removeEventListener('click', handleClickOutside)

  // Удаляем обработчики фокуса и видимости
  window.removeEventListener('focus', fetchNotifications)
  document.removeEventListener('visibilitychange', () => {
    if (!document.hidden) {
      fetchNotifications()
    }
  })
})
</script>

<style scoped>
.animate-slide-down {
  animation: slide-down-fade 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
@keyframes slide-down-fade {
  from {
    opacity: 0;
    transform: translateY(-16px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}
</style>
