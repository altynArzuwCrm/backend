<template>
  <div
    class="bg-gradient-to-br from-purple-50 via-blue-50 to-white rounded-3xl shadow-2xl p-8 flex flex-col gap-4"
  >
    <h3 class="text-xl font-bold text-gray-900 mb-4">Последние действия</h3>
    <div class="space-y-4 overflow-y-auto max-h-[260px] pr-2">
      <div
        v-for="activity in activities"
        :key="activity.id"
        class="flex items-center p-3 bg-white rounded-xl border border-gray-100 shadow-sm"
      >
        <div
          class="w-8 h-8 rounded-full flex items-center justify-center mr-3"
          :class="activity.iconBg"
        >
          <svg
            class="w-4 h-4"
            :class="activity.iconColor"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              v-if="activity.icon === 'UsersIcon'"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
            />
            <path
              v-else-if="activity.icon === 'DocumentIcon'"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
            <path
              v-else-if="activity.icon === 'CurrencyIcon'"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"
            />
            <path
              v-else-if="activity.icon === 'UserAddIcon'"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
            />
          </svg>
        </div>
        <div class="flex-1">
          <p class="text-gray-900 text-sm font-medium">{{ activity.title }}</p>
          <p class="text-gray-400 text-xs">{{ activity.time }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { safeApiRequest, safeProcessActivityData } from '@/utils/safeData'

const activities = ref([])

onMounted(async () => {
  try {
    const data = await safeApiRequest('/api/recent-activity')
    if (data) {
      activities.value = safeProcessActivityData(data).map((a: any) => ({
        ...a,
        iconBg:
          a.icon === 'UsersIcon'
            ? 'bg-blue-100'
            : a.icon === 'DocumentIcon'
              ? 'bg-green-100'
              : a.icon === 'UserAddIcon'
                ? 'bg-purple-100'
                : 'bg-gray-100',
        iconColor:
          a.icon === 'UsersIcon'
            ? 'text-blue-600'
            : a.icon === 'DocumentIcon'
              ? 'text-green-600'
              : a.icon === 'UserAddIcon'
                ? 'text-purple-600'
                : 'text-gray-400',
      }))
    } else {
      activities.value = []
    }
  } catch (e) {
    console.error('Ошибка загрузки активности:', e)
    activities.value = []
  }
})
</script>
