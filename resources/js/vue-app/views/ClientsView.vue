<template>
  <Layout v-slot="{ search }">
    <div class="flex flex-col gap-4">
      <ClientList :search="search" />
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import Layout from '../components/layout/Layout.vue'
import ClientList from '@/components/clients/ClientList/ClientList.vue'
import { authApi } from '@/services/api'

const router = useRouter()

async function handleLogout() {
  try {
    await authApi.logout()
    router.push('/login')
  } catch (error) {
    console.error('Ошибка выхода:', error)
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')
    router.push('/login')
  }
}
</script>
