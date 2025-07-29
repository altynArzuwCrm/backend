<template>
  <div
    class="bg-gradient-to-br from-blue-50 via-purple-50 to-white rounded-3xl shadow-2xl p-8 flex flex-col gap-4"
  >
    <h3 class="text-xl font-bold text-gray-900 mb-4">Быстрые действия</h3>
    <div v-if="hasAnyActions()" class="space-y-3">
      <router-link
        v-for="action in actions.filter((a) => a.show() && a.type === 'link')"
        :key="action.id"
        :to="action.to || '/'"
        class="w-full flex items-center justify-between p-3 bg-white rounded-xl hover:bg-blue-50 transition-all duration-200 group border border-gray-100 shadow-sm"
      >
        <div class="flex items-center">
          <div
            class="w-10 h-10 rounded-lg flex items-center justify-center mr-3"
            :class="action.iconBg"
          >
            <svg
              class="w-5 h-5"
              :class="action.iconColor"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                v-if="action.icon === 'UsersIcon'"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
              />
              <path
                v-else-if="action.icon === 'DocumentIcon'"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
              <path
                v-else-if="action.icon === 'UserAddIcon'"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
              />
            </svg>
          </div>
          <span class="text-gray-900 font-medium">{{ action.title }}</span>
        </div>
        <svg
          class="w-5 h-5 text-gray-400 opacity-70 group-hover:opacity-100 transition-opacity"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </router-link>
      <button
        v-for="action in actions.filter((a) => a.show() && a.type === 'modal')"
        :key="action.id"
        @click="action.handler"
        class="w-full flex items-center justify-between p-3 bg-white rounded-xl hover:bg-green-50 transition-all duration-200 group border border-gray-100 shadow-sm"
      >
        <div class="flex items-center">
          <div
            class="w-10 h-10 rounded-lg flex items-center justify-center mr-3"
            :class="action.iconBg"
          >
            <svg
              class="w-5 h-5"
              :class="action.iconColor"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                v-if="action.icon === 'DocumentIcon'"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
          </div>
          <span class="text-gray-900 font-medium">{{ action.title }}</span>
        </div>
        <svg
          class="w-5 h-5 text-gray-400 opacity-70 group-hover:opacity-100 transition-opacity"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>
    <OrderFormModal v-if="showOrderModal" @close="showOrderModal = false" />
  </div>
</template>

<script setup lang="ts">
import { ref, defineAsyncComponent } from 'vue'
import { canViewAllUsers, canViewAllClients, canCreateEdit } from '../../utils/permissions'

const showOrderModal = ref(false)

// Динамический импорт OrderFormModal
const OrderFormModal = defineAsyncComponent(() => import('../orders/OrderList/OrderFormModal.vue'))

const actions = [
  {
    id: 1,
    title: 'Управление пользователями',
    to: '/users',
    icon: 'UsersIcon',
    iconBg: 'bg-blue-100',
    iconColor: 'text-blue-600',
    type: 'link',
    show: () => canViewAllUsers(),
  },
  {
    id: 2,
    title: 'Создать заказ',
    icon: 'DocumentIcon',
    iconBg: 'bg-green-100',
    iconColor: 'text-green-600',
    type: 'modal',
    handler: () => {
      showOrderModal.value = true
    },
    show: () => canCreateEdit(),
  },
  {
    id: 3,
    title: 'Добавить клиента',
    to: '/clients/',
    icon: 'UserAddIcon',
    iconBg: 'bg-purple-100',
    iconColor: 'text-purple-600',
    type: 'link',
    show: () => canViewAllClients(),
  },
]

function hasAnyActions() {
  return actions.some((action) => action.show())
}
</script>
