<template>
  <Layout v-slot="{ search }">
    <div class="flex items-center justify-between mb-3 gap-4">
      <div class="flex items-center gap-4">
        <select
          v-model="selectedRole"
          @change="handleRoleChange"
          class="px-3 py-2 border border-gray-300 rounded bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
          style="min-width: 180px"
        >
          <option value="">Все роли</option>
          <option value="admin">Администратор</option>
          <option value="manager">Менеджер</option>
          <option value="designer">Дизайнер</option>
          <option value="print_operator">Печатник</option>
          <option value="workshop_worker">Работник цеха</option>
        </select>
        <select
          v-model="activeFilter"
          class="px-3 py-2 border border-gray-300 rounded bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
          style="min-width: 180px"
        >
          <option value="">Все пользователи</option>
          <option value="1">Только активные</option>
          <option value="0">Только неактивные</option>
        </select>
      </div>
      <UIButton @click="openCreateModal" variant="primary"> Добавить пользователя </UIButton>
    </div>
    <div class="flex-1 flex flex-col h-full min-h-0 overflow-hidden">
      <UserList
        :search="search"
        :role="selectedRole"
        :activeFilter="activeFilter"
        :showCreateModal="showCreateModal"
        @close-create-modal="closeCreateModal"
      />
    </div>
  </Layout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import UserList from '../components/users/UserList/UserList.vue'
import UIButton from '../components/ui/UIButton.vue'
import Layout from '../components/layout/Layout.vue'

const showCreateModal = ref(false)
const selectedRole = ref('')
const activeFilter = ref('')

function openCreateModal() {
  showCreateModal.value = true
}

function closeCreateModal() {
  showCreateModal.value = false
}

function handleRoleChange(e: Event) {
  selectedRole.value = (e.target as HTMLSelectElement).value
}
// handleActiveFilterChange больше не нужен, фильтрация будет реактивной
</script>
