<template>
  <Modal @close="$emit('close')" title="Массовые назначения">
    <div class="space-y-6">
      <!-- Режим работы -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2"> Режим назначения </label>
        <div class="flex gap-4">
          <label class="flex items-center">
            <input
              v-model="mode"
              type="radio"
              value="single"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
            />
            <span class="ml-2 text-sm text-gray-700">Одиночный заказ</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="mode"
              type="radio"
              value="multiple"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
            />
            <span class="ml-2 text-sm text-gray-700">Несколько заказов</span>
          </label>
        </div>
      </div>

      <!-- Выбор заказов -->
      <div v-if="mode === 'multiple'">
        <label class="block text-sm font-medium text-gray-700 mb-2"> Выберите заказы </label>
        <div class="max-h-40 overflow-y-auto border rounded-md p-2">
          <label
            v-for="order in availableOrders"
            :key="order.id"
            class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded"
          >
            <input
              type="checkbox"
              :value="order.id"
              v-model="selectedOrderIds"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <span class="text-sm text-gray-900">
              Заказ #{{ order.id }} - {{ order.client?.name || 'Без клиента' }}
            </span>
            <span
              v-if="order.current_stage_info"
              class="inline-flex px-2 py-1 text-xs font-semibold rounded-full text-white"
              :style="{ backgroundColor: order.current_stage_info.color }"
            >
              {{ order.current_stage_info.display_name }}
            </span>
          </label>
        </div>
      </div>

      <!-- Назначения -->
      <div>
        <div class="flex justify-between items-center mb-2">
          <label class="block text-sm font-medium text-gray-700"> Назначения </label>
          <button
            type="button"
            @click="addAssignment"
            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
          >
            + Добавить назначение
          </button>
        </div>

        <div class="space-y-4">
          <div
            v-for="(assignment, index) in assignments"
            :key="index"
            class="border rounded-lg p-4 bg-gray-50"
          >
            <div class="flex justify-between items-start mb-3">
              <h4 class="text-sm font-medium text-gray-900">Назначение #{{ index + 1 }}</h4>
              <button
                @click="removeAssignment(index)"
                class="text-red-600 hover:text-red-800 text-sm"
              >
                Удалить
              </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Пользователь -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1"> Пользователь </label>
                <Vue3Select
                  v-model="assignment.user_id"
                  :options="availableUsers"
                  option-label="name"
                  option-value="id"
                  placeholder="Выберите пользователя"
                  class="w-full"
                />
              </div>

              <!-- Роль -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1"> Роль </label>
                <Vue3Select
                  v-model="assignment.role_type"
                  :options="availableRoles"
                  option-label="display_name"
                  option-value="name"
                  placeholder="Выберите роль"
                  class="w-full"
                />
              </div>

              <!-- Стадии -->
              <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-700 mb-2">
                  Назначенные стадии
                </label>
                <div class="space-y-2">
                  <label
                    v-for="stage in availableStages"
                    :key="stage.id"
                    class="flex items-center gap-2 p-2 bg-white rounded border hover:bg-gray-50"
                  >
                    <input
                      type="checkbox"
                      :value="stage.id"
                      v-model="assignment.assigned_stages"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    />
                    <div
                      class="w-3 h-3 rounded-full"
                      :style="{ backgroundColor: stage.color }"
                    ></div>
                    <span class="text-sm text-gray-900">{{ stage.display_name }}</span>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Кнопки -->
      <div class="flex justify-end gap-3 pt-4 border-t">
        <button
          type="button"
          @click="$emit('close')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
        >
          Отмена
        </button>
        <button
          @click="handleSubmit"
          :disabled="!isValid || isSubmitting"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ isSubmitting ? 'Сохранение...' : 'Сохранить назначения' }}
        </button>
      </div>
    </div>
  </Modal>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Vue3Select from 'vue3-select'
import { useToast } from '@/stores/toast'
import { getAllStages, getAllRoles, getAllUsers } from '@/services/api'

interface Props {
  orderId?: number
  availableOrders?: Array<{
    id: number
    client?: { name: string }
    current_stage_info?: { display_name: string; color: string }
  }>
}

interface Assignment {
  user_id: number | null
  role_type: string | null
  assigned_stages: number[]
}

interface User {
  id: number
  name: string
}

interface Role {
  id: number
  name: string
  display_name: string
}

interface Stage {
  id: number
  name: string
  display_name: string
  color: string
}

const props = withDefaults(defineProps<Props>(), {
  availableOrders: () => [],
})

const emit = defineEmits<{
  close: []
  success: [result: any]
}>()

const toast = useToast()

const mode = ref<'single' | 'multiple'>('single')
const selectedOrderIds = ref<number[]>([])
const assignments = ref<Assignment[]>([
  {
    user_id: null,
    role_type: null,
    assigned_stages: [],
  },
])

const availableUsers = ref<User[]>([])
const availableRoles = ref<Role[]>([])
const availableStages = ref<Stage[]>([])
const isSubmitting = ref(false)

// Вычисляемые свойства
const isValid = computed(() => {
  if (mode.value === 'multiple' && selectedOrderIds.value.length === 0) {
    return false
  }

  return assignments.value.every(
    (assignment) =>
      assignment.user_id && assignment.role_type && assignment.assigned_stages.length > 0,
  )
})

// Методы
onMounted(async () => {
  await loadData()

  // Если передан orderId, устанавливаем режим "одиночный"
  if (props.orderId) {
    mode.value = 'single'
  }
})

async function loadData() {
  try {
    const [stagesResponse, rolesResponse, usersResponse] = await Promise.all([
      getAllStages(),
      getAllRoles(),
      getAllUsers(),
    ])

    availableStages.value = stagesResponse.data || []
    availableRoles.value = rolesResponse.data || []
    availableUsers.value = usersResponse.data || []
  } catch (error) {
    console.error('Ошибка загрузки данных:', error)
    toast.show('Ошибка загрузки данных', 'error')
  }
}

function addAssignment() {
  assignments.value.push({
    user_id: null,
    role_type: null,
    assigned_stages: [],
  })
}

function removeAssignment(index: number) {
  if (assignments.value.length > 1) {
    assignments.value.splice(index, 1)
  }
}

async function handleSubmit() {
  if (!isValid.value) {
    toast.show('Пожалуйста, заполните все обязательные поля', 'error')
    return
  }

  isSubmitting.value = true

  try {
    let result

    if (mode.value === 'single' && props.orderId) {
      // Массовое назначение на один заказ
      result = await fetch(`/api/orders/${props.orderId}/bulk-assign`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ assignments: assignments.value }),
      })
    } else if (mode.value === 'multiple') {
      // Массовое назначение на разные заказы
      const bulkAssignments = selectedOrderIds.value.flatMap((orderId) =>
        assignments.value.map((assignment) => ({
          order_id: orderId,
          ...assignment,
        })),
      )

      result = await fetch('/api/assignments/bulk-assign', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ assignments: bulkAssignments }),
      })
    }

    const response = await result.json()

    if (response.errors && response.errors.length > 0) {
      toast.show(`Ошибки: ${response.errors.join(', ')}`, 'error')
    } else {
      toast.show('Назначения успешно созданы', 'success')
      emit('success', response)
      emit('close')
    }
  } catch (error) {
    console.error('Ошибка сохранения назначений:', error)
    toast.show('Ошибка сохранения назначений', 'error')
  } finally {
    isSubmitting.value = false
  }
}
</script>
