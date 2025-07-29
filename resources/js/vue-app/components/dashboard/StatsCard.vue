<template>
  <div class="bg-white rounded-2xl shadow-lg p-8 flex items-center justify-between min-h-[120px]">
    <div>
      <p class="text-gray-500 text-base font-semibold mb-1">{{ title }}</p>
      <p class="text-4xl font-extrabold text-gray-900">{{ value }}</p>
    </div>
    <div
      class="w-16 h-16 rounded-full flex items-center justify-center bg-gradient-to-br from-yellow-200 via-yellow-100 to-yellow-300 shadow-sm"
    >
      <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          v-if="icon === 'UsersIcon'"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
        ></path>
        <path
          v-else-if="icon === 'DocumentIcon'"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
        ></path>
        <path
          v-else-if="icon === 'CurrencyIcon'"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
        ></path>
        <path
          v-else-if="icon === 'UserAddIcon'"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
        ></path>
      </svg>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps({
  title: {
    type: String,
    required: true,
  },
  value: {
    type: String,
    required: true,
  },
  icon: {
    type: String,
    required: true,
  },
  iconBgClass: {
    type: String,
    default: 'bg-blue-500 bg-opacity-20',
  },
})
</script>
