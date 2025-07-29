<template>
  <nav
    class="h-16 bg-white bg-opacity-10 backdrop-blur-sm border-b border-white border-opacity-20 flex items-center justify-between px-6 bg-gradient-to-r from-blue-600 via-purple-700 to-purple-600"
  >
    <div class="flex items-center gap-4">
      <div class="text-lg font-semibold text-white">{{ pageTitle }}</div>
      <SearchInput
        v-if="showSearch"
        v-model="searchQuery"
        :placeholder="searchPlaceholder"
        class="ml-6"
        @input="handleSearchInput"
      />
    </div>
    <div class="flex items-center gap-2">
      <NotificationBell :user="currentUser" />
      <UserProfile :user="currentUser" @logout="$emit('logout')" @settings="handleSettings" />
    </div>
  </nav>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import UserProfile from './UserProfile.vue'
import { authApi } from '@/services/api'
import { useRoute, useRouter } from 'vue-router'
import SearchInput from '@/components/ui/SearchInput.vue'
import NotificationBell from '@/components/ui/NotificationBell.vue'

const emit = defineEmits(['logout', 'search'])

const route = useRoute()
const router = useRouter()
const pageTitle = computed(() => route.meta.title || 'Панель управления')

const currentUser = ref({
  name: '',
  role: '',
  image: null,
})

const searchQuery = ref('')

// Показывать поиск только на orders, projects, products
const showSearch = computed(() => {
  return ['/orders', '/projects', '/products', '/users', '/clients'].includes(route.path)
})

watch(searchQuery, (val) => {
  if (showSearch.value) {
    router.replace({ query: { ...route.query, search: val || undefined } })
  }
})

const searchPlaceholder = computed(() =>
  route.path === '/users'
    ? 'Поиск по имени или логину...'
    : route.path === '/clients'
      ? 'Поиск по имени клиента...'
      : 'Поиск...',
)

function handleSearchInput() {
  emit('search', searchQuery.value)
}

onMounted(async () => {
  try {
    const response = await authApi.me()
    const user = response.data
    currentUser.value = user // <-- теперь весь объект, включая roles
  } catch (e) {
    currentUser.value = { name: 'Гость', role: '', image: null, roles: [] }
  }
})

const handleSettings = () => {}
</script>
