<template>
  <aside
    class="border-r border-white border-opacity-20 flex flex-col py-8 shadow-lg bg-gradient-to-b from-blue-600 via-indigo-500 to-indigo-600"
  >
    <img
      src="/A-A_logotype (colorful) (1) copy.png"
      alt="Altyn-Arzuw Logo"
      class="mx-auto mb-10 max-w-[150px] w-40 h-auto"
    />
    <nav class="flex flex-col gap-2 px-4 flex-1">
      <router-link
        to="/"
        class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 transition-colors text-white"
        active-class="bg-white bg-opacity-20"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"
          ></path>
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"
          ></path>
        </svg>
        <span>Главная</span>
      </router-link>

      <!-- Пользователи - только для админов и менеджеров -->
      <router-link
        v-if="canViewAllUsers()"
        to="/users"
        class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 transition-colors text-white"
        active-class="bg-white bg-opacity-20"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
          ></path>
        </svg>
        <span>Пользователи</span>
      </router-link>

      <!-- Клиенты - только для админов и менеджеров -->
      <router-link
        v-if="canViewAllClients()"
        to="/clients"
        class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 transition-colors text-white"
        active-class="bg-white bg-opacity-20"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-7a4 4 0 11-8 0 4 4 0 018 0z"
          />
        </svg>
        <span>Клиенты</span>
      </router-link>

      <!-- Проекты -->
      <router-link
        to="/projects"
        class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 transition-colors text-white"
        active-class="bg-white bg-opacity-20"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
          />
        </svg>
        <span>{{ getNavigationText('projects') }}</span>
      </router-link>

      <!-- Товары -->
      <router-link
        to="/products"
        class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 transition-colors text-white"
        active-class="bg-white bg-opacity-20"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
          />
        </svg>
        <span>{{ getNavigationText('products') }}</span>
      </router-link>

      <!-- Заказы -->
      <router-link
        to="/orders"
        class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 transition-colors text-white"
        active-class="bg-white bg-opacity-20"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
          />
        </svg>
        <span>{{ getNavigationText('orders') }}</span>
      </router-link>

      <!-- Аудит-логи - только для админов -->
      <router-link
        v-if="canViewAuditLogs()"
        to="/audit-logs"
        class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 transition-colors text-white"
        active-class="bg-white bg-opacity-20"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span>Действия</span>
      </router-link>

      <!-- Управление стадиями - только для админов -->
      <router-link
        v-if="canViewAuditLogs()"
        to="/stages"
        class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 transition-colors text-white"
        active-class="bg-white bg-opacity-20"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
          />
        </svg>
        <span>Стадии</span>
      </router-link>

      <!-- Управление ролями - только для админов -->
      <router-link
        v-if="canViewAuditLogs()"
        to="/roles"
        class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white hover:bg-opacity-10 transition-colors text-white"
        active-class="bg-white bg-opacity-20"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-7a4 4 0 11-8 0 4 4 0 018 0z"
          />
        </svg>
        <span>Роли</span>
      </router-link>
    </nav>
  </aside>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import {
  canViewAllUsers,
  canViewAllClients,
  canViewAuditLogs,
  getNavigationText,
} from '../../utils/permissions'

const isOpen = ref(true)

function hasRole(user, roleName) {
  return user.roles && user.roles.some((r) => r.name === roleName)
}
</script>
