<template>
  <div class="relative" ref="rootRef">
    <button
      @click="toggleDropdown"
      class="flex items-center gap-3 p-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition-all duration-200"
    >
      <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
        <img
          v-if="userImageUrl"
          :src="userImageUrl"
          :alt="user.name"
          class="w-8 h-8 rounded-full object-cover"
        />
        <span v-else class="text-white text-sm font-medium">
          {{ user.name.charAt(0).toUpperCase() }}
        </span>
      </div>
      <div class="text-left">
        <p class="text-white text-sm font-medium">{{ user.name }}</p>
        <p class="text-blue-100 text-xs">
          <template v-if="user.roles && user.roles.length">
            <span
              v-for="role in user.roles"
              :key="role.id"
              class="inline-block bg-blue-200 text-blue-800 rounded px-2 py-0.5 mr-1"
            >
              {{ USER_ROLE_LABELS[role.name] || role.display_name || role.name }}
            </span>
          </template>
          <template v-else>
            {{ USER_ROLE_LABELS[user.role] || user.role }}
          </template>
        </p>
      </div>
      <svg
        class="w-4 h-4 text-white transition-transform duration-200"
        :class="{ 'rotate-180': isDropdownOpen }"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 9l-7 7-7-7"
        ></path>
      </svg>
    </button>

    <div
      v-if="isDropdownOpen"
      class="absolute right-0 top-full mt-2 w-48 bg-white bg-opacity-95 rounded-lg shadow-lg border border-white border-opacity-20 py-2 z-50"
    >
      <div class="px-4 py-3 border-b border-gray-200 border-opacity-20">
        <p class="text-gray-900 text-sm font-medium">{{ user.name }}</p>
        <p class="text-blue-600 text-xs">
          <template v-if="user.roles && user.roles.length">
            <span
              v-for="role in user.roles"
              :key="role.id"
              class="inline-block bg-blue-100 text-blue-800 rounded px-2 py-0.5 mr-1"
            >
              {{ USER_ROLE_LABELS[role.name] || role.display_name || role.name }}
            </span>
          </template>
          <template v-else>
            {{ USER_ROLE_LABELS[user.role] || user.role }}
          </template>
        </p>
      </div>
      <div class="border-t border-gray-200 border-opacity-20 pt-1">
        <button
          @click="handleLogout"
          class="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
            ></path>
          </svg>
          Выйти
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { API_CONFIG } from '@/config/api'

const props = defineProps({
  user: {
    type: Object,
    default: () => ({
      name: 'Пользователь',
      role: 'user',
      image: null,
    }),
  },
})

const emit = defineEmits(['logout'])

const isDropdownOpen = ref(false)
const rootRef = ref<HTMLElement>()
const userImageUrl = ref('')

const USER_ROLE_LABELS: Record<string, string> = {
  admin: 'Администратор',
  manager: 'Менеджер',
  designer: 'Дизайнер',
  print_operator: 'Печатник',
  workshop_worker: 'Работник цеха',
}

// Синхронная функция для получения URL изображения
const getUserImageUrl = (user: any) => {
  if (user.image_url) return user.image_url
  if (user.image && user.image.startsWith('http')) return user.image
  if (user.image) return `${API_CONFIG.BASE_URL.replace('/api', '')}/storage/${user.image}`
  return ''
}

// Функция для загрузки URL изображения
const loadUserImageUrl = () => {
  console.log('loadUserImageUrl called with user:', props.user)
  if (props.user) {
    const url = getUserImageUrl(props.user)
    console.log('Generated image URL:', url)
    userImageUrl.value = url
  } else {
    console.log('No user data provided')
    userImageUrl.value = ''
  }
}

// Загружаем изображение при изменении пользователя
watch(() => props.user, loadUserImageUrl, { immediate: true })

onMounted(() => {
  loadUserImageUrl()
})

const toggleDropdown = () => {
  isDropdownOpen.value = !isDropdownOpen.value
}

const handleLogout = () => {
  isDropdownOpen.value = false
  emit('logout')
}

const closeDropdown = (event: MouseEvent) => {
  if (isDropdownOpen.value && rootRef.value && !rootRef.value.contains(event.target as Node)) {
    isDropdownOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', closeDropdown)
})

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown)
})
</script>
