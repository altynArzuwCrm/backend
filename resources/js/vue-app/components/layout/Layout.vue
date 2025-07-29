<template>
  <div
    class="min-h-screen w-full flex"
  >
    <Sidebar class="fixed left-0 top-0 bottom-0 z-20 w-48" />
    <div class="flex-1 flex flex-col min-h-screen">
      <Navbar class="fixed top-0 left-48 right-0 z-30" @search="onSearch" @logout="handleLogout" />
      <main
        class="flex-1 flex flex-col h-[calc(100vh-4rem)] min-h-0 mt-16 overflow-auto p-5 pl-52 bg-gradient-to-br from-blue-600 via-purple-600 to-indigo-700"
      >
        <slot :search="search" />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Sidebar from '@/components/layout/Sidebar.vue'
import Navbar from '@/components/layout/Navbar.vue'
import { authApi } from '@/services/api'

const search = ref('')
function onSearch(value: string) {
  search.value = value
}

const router = useRouter()
async function handleLogout() {
  try {
    await authApi.logout()
    router.push('/login')
  } catch {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')
    router.push('/login')
  }
}
</script>
