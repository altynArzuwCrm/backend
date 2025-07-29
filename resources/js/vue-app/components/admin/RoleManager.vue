<template>
  <div class="role-manager">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Управление ролями</h2>
      <button
        @click="openCreateModal"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          ></path>
        </svg>
        Добавить роль
      </button>
    </div>

    <!-- Список ролей -->
    <div class="bg-white rounded-lg shadow">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Роль
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Название
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Описание
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Пользователи
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Действия
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="role in roles" :key="role.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm font-medium text-gray-900">{{ role.name }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ role.display_name }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                <div class="max-w-xs truncate" :title="role.description">
                  {{ role.description || 'Нет описания' }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <button
                  @click="openUsersModal(role)"
                  class="text-blue-600 hover:text-blue-900 text-sm"
                >
                  {{ role.users_count || 0 }} пользователей
                </button>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex gap-2">
                  <button @click="openEditModal(role)" class="text-blue-600 hover:text-blue-900">
                    Редактировать
                  </button>
                  <button @click="deleteRole(role.id)" class="text-red-600 hover:text-red-900">
                    Удалить
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Модальное окно создания/редактирования роли -->
    <Modal v-if="showModal" @close="closeModal" :title="modalTitle">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"> Название (slug) </label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="designer"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Отображаемое название
          </label>
          <input
            v-model="form.display_name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Дизайнер"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"> Описание </label>
          <textarea
            v-model="form.description"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Описание роли..."
          ></textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4">
          <button
            type="button"
            @click="closeModal"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
          >
            Отмена
          </button>
          <button
            type="submit"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700"
          >
            {{ isEditing ? 'Обновить' : 'Создать' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- Модальное окно управления пользователями роли -->
    <Modal
      v-if="showUsersModal"
      @close="closeUsersModal"
      :title="`Пользователи роли: ${selectedRole?.display_name}`"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Назначить пользователей
          </label>
          <div class="space-y-2 max-h-40 overflow-y-auto">
            <label
              v-for="user in availableUsers"
              :key="user.id"
              class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded"
            >
              <input
                type="checkbox"
                :value="user.id"
                v-model="selectedUserIds"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <span class="text-sm text-gray-900">{{ user.name }}</span>
            </label>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-4">
          <button
            type="button"
            @click="closeUsersModal"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
          >
            Отмена
          </button>
          <button
            @click="saveUserAssignments"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700"
          >
            Сохранить
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import {
  createRole,
  updateRole,
  deleteRole,
  getAllRoles,
  assignUsersToRole,
  removeUsersFromRole,
} from '@/services/api'
import Modal from '@/components/ui/Modal.vue'
import { useToast } from '@/stores/toast'

interface Role {
  id: number
  name: string
  display_name: string
  description?: string
  users_count?: number
}

interface RoleForm {
  name: string
  display_name: string
  description?: string
}

interface User {
  id: number
  name: string
}

const toast = useToast()
const roles = ref<Role[]>([])
const availableUsers = ref<User[]>([])
const showModal = ref(false)
const showUsersModal = ref(false)
const isEditing = ref(false)
const editingRole = ref<Role | null>(null)
const selectedRole = ref<Role | null>(null)
const selectedUserIds = ref<number[]>([])

const form = ref<RoleForm>({
  name: '',
  display_name: '',
  description: '',
})

const modalTitle = computed(() => (isEditing.value ? 'Редактировать роль' : 'Создать роль'))

onMounted(async () => {
  await loadRoles()
  await loadUsers()
})

async function loadRoles() {
  try {
    const response = await getAllRoles()
    roles.value = response.data || []
  } catch (error) {
    console.error('Ошибка загрузки ролей:', error)
    toast.error('Ошибка загрузки ролей')
  }
}

async function loadUsers() {
  try {
    // Здесь нужно добавить API для получения пользователей
    // Пока используем заглушку
    availableUsers.value = [
      { id: 1, name: 'Иван Иванов' },
      { id: 2, name: 'Петр Петров' },
      { id: 3, name: 'Анна Сидорова' },
    ]
  } catch (error) {
    console.error('Ошибка загрузки пользователей:', error)
    toast.error('Ошибка загрузки пользователей')
  }
}

function openCreateModal() {
  isEditing.value = false
  editingRole.value = null
  resetForm()
  showModal.value = true
}

function openEditModal(role: Role) {
  isEditing.value = true
  editingRole.value = role
  form.value = { ...role }
  showModal.value = true
}

function openUsersModal(role: Role) {
  selectedRole.value = role
  selectedUserIds.value = [] // Здесь нужно загрузить текущих пользователей роли
  showUsersModal.value = true
}

function closeModal() {
  showModal.value = false
  resetForm()
}

function closeUsersModal() {
  showUsersModal.value = false
  selectedRole.value = null
  selectedUserIds.value = []
}

function resetForm() {
  form.value = {
    name: '',
    display_name: '',
    description: '',
  }
}

async function handleSubmit() {
  try {
    if (isEditing.value && editingRole.value) {
      await updateRole(editingRole.value.id, form.value)
      toast.success('Роль обновлена')
    } else {
      await createRole(form.value)
      toast.success('Роль создана')
    }

    await loadRoles()
    closeModal()
  } catch (error) {
    console.error('Ошибка сохранения роли:', error)
    toast.error('Ошибка сохранения роли')
  }
}

async function deleteRole(id: number) {
  if (!confirm('Вы уверены, что хотите удалить эту роль?')) {
    return
  }

  try {
    await deleteRole(id)
    toast.success('Роль удалена')
    await loadRoles()
  } catch (error) {
    console.error('Ошибка удаления роли:', error)
    toast.error('Ошибка удаления роли')
  }
}

async function saveUserAssignments() {
  if (!selectedRole.value) return

  try {
    // Здесь нужно реализовать логику назначения пользователей
    // await assignUsersToRole(selectedRole.value.id, selectedUserIds.value)
    toast.success('Пользователи назначены')
    closeUsersModal()
    await loadRoles()
  } catch (error) {
    console.error('Ошибка назначения пользователей:', error)
    toast.error('Ошибка назначения пользователей')
  }
}
</script>
