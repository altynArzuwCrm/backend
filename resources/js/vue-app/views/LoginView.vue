<template>
  <div
    class="h-screen w-screen flex items-center justify-center bg-gradient-to-br from-blue-600 via-purple-600 to-indigo-700 relative overflow-hidden"
  >
    <div class="absolute inset-0 bg-black opacity-10"></div>
    <div class="absolute top-10 left-10 w-20 h-20 bg-white opacity-10 rounded-full"></div>
    <div class="absolute top-40 right-20 w-32 h-32 bg-white opacity-5 rounded-full"></div>
    <div class="absolute bottom-20 left-20 w-16 h-16 bg-white opacity-10 rounded-full"></div>

    <div class="relative z-10 max-w-md w-full px-4">
      <div class="bg-white rounded-2xl shadow-xl p-8">
        <LoginForm ref="loginFormRef" :loading="loading" @submit="handleLogin" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import LoginForm from '@/components/auth/LoginForm.vue'
import { API_CONFIG } from '@/config/api'

const router = useRouter()
const loading = ref(false)
const loginFormRef = ref(null)

const handleLogin = async (formData) => {
  loading.value = true

  try {
    const response = await fetch(`${API_CONFIG.BASE_URL}/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify(formData),
    })

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}))
      throw new Error(errorData.message || 'Ошибка входа')
    }

    const data = await response.json()

    if (data.token) {
      localStorage.setItem('auth_token', data.token)
      localStorage.setItem('user', JSON.stringify(data.user))
      router.push('/')
    } else {
      throw new Error('Токен не получен')
    }
  } catch (err) {
    const errorMessage = err instanceof Error ? err.message : 'Неизвестная ошибка'
    loginFormRef.value?.setError(errorMessage)
    console.error('Ошибка входа:', err)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped></style>
