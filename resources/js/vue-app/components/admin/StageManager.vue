<template>
  <div class="stage-manager">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Управление стадиями</h2>
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
        Добавить стадию
      </button>
    </div>

    <!-- Список стадий -->
    <div class="bg-white rounded-lg shadow">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Стадия
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Название
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Порядок
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Тип
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Статус
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Действия
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="stage in stages" :key="stage.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-3">
                  <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: stage.color }"></div>
                  <span class="text-sm font-medium text-gray-900">{{ stage.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ stage.display_name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ stage.order }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex gap-1">
                  <span
                    v-if="stage.is_initial"
                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800"
                  >
                    Начальная
                  </span>
                  <span
                    v-if="stage.is_final"
                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800"
                  >
                    Финальная
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="
                    stage.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                  "
                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                >
                  {{ stage.is_active ? 'Активна' : 'Неактивна' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex gap-2">
                  <button @click="openEditModal(stage)" class="text-blue-600 hover:text-blue-900">
                    Редактировать
                  </button>
                  <button @click="deleteStage(stage.id)" class="text-red-600 hover:text-red-900">
                    Удалить
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Модальное окно создания/редактирования -->
    <Modal v-if="showModal" @close="closeModal" :title="modalTitle">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"> Название (slug) </label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="design"
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
            placeholder="Дизайн"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"> Описание </label>
          <textarea
            v-model="form.description"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Описание стадии..."
          ></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"> Порядок </label>
            <input
              v-model.number="form.order"
              type="number"
              required
              min="1"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1"> Цвет </label>
            <input
              v-model="form.color"
              type="color"
              required
              class="w-full h-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        <div class="space-y-2">
          <label class="flex items-center">
            <input
              v-model="form.is_active"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-700">Активна</span>
          </label>

          <label class="flex items-center">
            <input
              v-model="form.is_initial"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-700">Начальная стадия</span>
          </label>

          <label class="flex items-center">
            <input
              v-model="form.is_final"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <span class="ml-2 text-sm text-gray-700">Финальная стадия</span>
          </label>
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
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { createStage, updateStage, deleteStage, getAllStages } from '@/services/api'
import Modal from '@/components/ui/Modal.vue'
import { useToast } from '@/stores/toast'

interface Stage {
  id: number
  name: string
  display_name: string
  description?: string
  order: number
  color: string
  is_active: boolean
  is_initial: boolean
  is_final: boolean
}

interface StageForm {
  name: string
  display_name: string
  description?: string
  order: number
  color: string
  is_active: boolean
  is_initial: boolean
  is_final: boolean
}

const toast = useToast()
const stages = ref<Stage[]>([])
const showModal = ref(false)
const isEditing = ref(false)
const editingStage = ref<Stage | null>(null)

const form = ref<StageForm>({
  name: '',
  display_name: '',
  description: '',
  order: 1,
  color: '#6b7280',
  is_active: true,
  is_initial: false,
  is_final: false,
})

const modalTitle = computed(() => (isEditing.value ? 'Редактировать стадию' : 'Создать стадию'))

onMounted(async () => {
  await loadStages()
})

async function loadStages() {
  try {
    const response = await getAllStages()
    stages.value = response.data || []
  } catch (error) {
    console.error('Ошибка загрузки стадий:', error)
    toast.error('Ошибка загрузки стадий')
  }
}

function openCreateModal() {
  isEditing.value = false
  editingStage.value = null
  resetForm()
  showModal.value = true
}

function openEditModal(stage: Stage) {
  isEditing.value = true
  editingStage.value = stage
  form.value = { ...stage }
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  resetForm()
}

function resetForm() {
  form.value = {
    name: '',
    display_name: '',
    description: '',
    order: 1,
    color: '#6b7280',
    is_active: true,
    is_initial: false,
    is_final: false,
  }
}

async function handleSubmit() {
  try {
    if (isEditing.value && editingStage.value) {
      await updateStage(editingStage.value.id, form.value)
      toast.success('Стадия обновлена')
    } else {
      await createStage(form.value)
      toast.success('Стадия создана')
    }

    await loadStages()
    closeModal()
  } catch (error) {
    console.error('Ошибка сохранения стадии:', error)
    toast.error('Ошибка сохранения стадии')
  }
}

async function deleteStage(id: number) {
  if (!confirm('Вы уверены, что хотите удалить эту стадию?')) {
    return
  }

  try {
    await deleteStage(id)
    toast.success('Стадия удалена')
    await loadStages()
  } catch (error) {
    console.error('Ошибка удаления стадии:', error)
    toast.error('Ошибка удаления стадии')
  }
}
</script>
