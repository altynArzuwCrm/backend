<template>
  <Modal @close="$emit('close')">
    <template #header>
      <h2 class="text-xl font-semibold text-gray-900">
        {{ product ? 'Редактировать товар' : 'Создать товар' }}
      </h2>
    </template>

    <form @submit.prevent="handleSubmit" class="space-y-6 pb-4">
      <!-- Название товара -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Название товара <span class="text-red-500">*</span>
        </label>
        <UIInput
          v-model="form.name"
          placeholder="Введите название товара"
          :error="errors.name"
          required
        />
      </div>

      <!-- Этапы производства -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">Этапы производства</label>
        <div class="grid grid-cols-2 gap-3">
          <label
            class="flex items-center p-3 bg-white rounded-lg border border-gray-200 transition-colors cursor-pointer hover:bg-gray-50"
            :class="form.has_design_stage ? 'border-blue-500 bg-blue-50' : ''"
          >
            <input
              type="checkbox"
              v-model="form.has_design_stage"
              class="mr-2 w-4 h-4 text-blue-600"
            />
            <span class="font-medium text-gray-900">Дизайн</span>
          </label>
          <label
            class="flex items-center p-3 bg-white rounded-lg border border-gray-200 transition-colors cursor-pointer hover:bg-gray-50"
            :class="form.has_print_stage ? 'border-blue-500 bg-blue-50' : ''"
          >
            <input
              type="checkbox"
              v-model="form.has_print_stage"
              class="mr-2 w-4 h-4 text-blue-600"
            />
            <span class="font-medium text-gray-900">Печать</span>
          </label>
          <label
            class="flex items-center p-3 bg-white rounded-lg border border-gray-200 transition-colors cursor-pointer hover:bg-gray-50"
            :class="form.has_engraving_stage ? 'border-blue-500 bg-blue-50' : ''"
          >
            <input
              type="checkbox"
              v-model="form.has_engraving_stage"
              class="mr-2 w-4 h-4 text-blue-600"
            />
            <span class="font-medium text-gray-900">Гравировка</span>
          </label>
          <label
            class="flex items-center p-3 bg-white rounded-lg border border-gray-200 transition-colors cursor-pointer hover:bg-gray-50"
            :class="form.has_workshop_stage ? 'border-blue-500 bg-blue-50' : ''"
          >
            <input
              type="checkbox"
              v-model="form.has_workshop_stage"
              class="mr-2 w-4 h-4 text-blue-600"
            />
            <span class="font-medium text-gray-900">Цех</span>
          </label>
        </div>
      </div>
      <!-- Назначения сотрудников -->
      <div v-if="hasAnyStage" class="space-y-4">
        <!-- Дизайнеры -->
        <div v-if="form.has_design_stage">
          <label class="block text-sm font-medium text-gray-700 mb-2">Дизайнеры</label>
          <AssignmentManager
            title=""
            :assignments="form.designers"
            :all-users="allDesigners"
            :errors="errors.designers || []"
            @update="
              (assignments) => {
                form.designers = assignments
              }
            "
          />
          <button
            type="button"
            class="text-blue-600 hover:text-blue-800 text-xs mt-1"
            @click="form.designers.push({ id: Date.now(), user_id: null, user: undefined })"
          >
            + Добавить дизайнера
          </button>
        </div>

        <!-- Печатники -->
        <div v-if="form.has_print_stage">
          <label class="block text-sm font-medium text-gray-700 mb-2">Печатники</label>
          <AssignmentManager
            title=""
            :assignments="form.print_operators"
            :all-users="allPrintOperators"
            :errors="errors.print_operators || []"
            @update="
              (assignments) => {
                form.print_operators = assignments
              }
            "
          />
          <button
            type="button"
            class="text-blue-600 hover:text-blue-800 text-xs mt-1"
            @click="form.print_operators.push({ id: Date.now(), user_id: null, user: undefined })"
          >
            + Добавить печатника
          </button>
        </div>

        <!-- Гравировщики -->
        <div v-if="form.has_engraving_stage">
          <label class="block text-sm font-medium text-gray-700 mb-2">Гравировщик</label>
          <AssignmentManager
            title=""
            :assignments="form.engraving_operators"
            :all-users="allEngravingOperators"
            :errors="errors.engraving_operators || []"
            @update="
              (assignments) => {
                form.engraving_operators = assignments
              }
            "
          />
          <button
            type="button"
            class="text-blue-600 hover:text-blue-800 text-xs mt-1"
            @click="
              form.engraving_operators.push({ id: Date.now(), user_id: null, user: undefined })
            "
          >
            + Добавить гравировщика
          </button>
        </div>

        <!-- Работники цеха -->
        <div v-if="form.has_workshop_stage">
          <label class="block text-sm font-medium text-gray-700 mb-2">Сотрудники цеха</label>
          <AssignmentManager
            title=""
            :assignments="form.workshop_workers"
            :all-users="allWorkshopWorkers"
            :errors="errors.workshop_workers || []"
            @update="
              (assignments) => {
                form.workshop_workers = assignments
              }
            "
          />
          <button
            type="button"
            class="text-blue-600 hover:text-blue-800 text-xs mt-1"
            @click="form.workshop_workers.push({ id: Date.now(), user_id: null, user: undefined })"
          >
            + Добавить работника цеха
          </button>
        </div>
      </div>

      <!-- Кнопки действий -->
      <div class="flex gap-3 pt-4 border-t border-gray-200 mt-6">
        <UIButton type="submit" :loading="loading" class="flex-1" :disabled="!form.name.trim()">
          {{ product ? 'Сохранить' : 'Создать' }}
        </UIButton>

        <UIButton v-if="product" type="button" variant="danger" @click="handleDelete">
          Удалить
        </UIButton>

        <UIButton type="button" variant="secondary" @click="$emit('close')"> Отмена </UIButton>
      </div>
    </form>
  </Modal>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import UIInput from '@/components/ui/UIInput.vue'
import UIButton from '@/components/ui/UIButton.vue'
import Vue3Select from 'vue3-select'
import 'vue3-select/dist/vue3-select.css'
import AssignmentManager from './AssignmentManager.vue'
import type { Product, ProductForm, ProductAssignment } from '@/types/product'
import type { Designer } from '@/types/designer'

interface User {
  id: number
  name: string
}
import productController from '@/controllers/productControllerInstance'
import { getByRole } from '../../../services/api'
import { toast } from '@/stores/toast'

// --- API helpers for product_assignments ---
async function fetchProductAssignments(productId) {
  const res = await fetch(`/api/products/${productId}/assignments`, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
  })
  if (!res.ok) return null
  return await res.json()
}
async function bulkSaveProductAssignments(productId, assignmentsByRole) {
  // assignmentsByRole: { designer: [...], print_operator: [...], ... }
  const assignments = []
  for (const role_type of Object.keys(assignmentsByRole)) {
    for (const a of assignmentsByRole[role_type]) {
      assignments.push({
        user_id: a.user_id,
        role_type,
        is_active: a.is_active !== false,
      })
    }
  }
  return fetch(`/api/products/${productId}/assignments/bulk`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
    body: JSON.stringify({ assignments }),
  })
}
async function updateProductAssignment(productId, assignmentId, data) {
  return fetch(`/api/products/${productId}/assignments/${assignmentId}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
    body: JSON.stringify(data),
  })
}
async function deleteProductAssignment(productId, assignmentId) {
  return fetch(`/api/products/${productId}/assignments/${assignmentId}`, {
    method: 'DELETE',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
  })
}
// --- END API helpers ---

const props = defineProps<{ product?: Product | null }>()
const emit = defineEmits(['close', 'submit', 'delete'])

const { update, remove, create } = productController

const loading = ref(false)
const allDesigners = ref<Designer[]>([])
const allPrintOperators = ref<User[]>([])
const allWorkshopWorkers = ref<User[]>([])
const allEngravingOperators = ref<User[]>([])
const errors = reactive({
  name: '',
  designer_id: '',
  print_operator_id: '',
  workshop_worker_id: '',
  designers: [] as string[],
  print_operators: [] as string[],
  engraving_operators: [] as string[],
  workshop_workers: [] as string[],
})

function reduceUser(u: any) {
  return u.id
}

const hasAnyStage = computed(() => {
  return (
    form.has_design_stage ||
    form.has_print_stage ||
    form.has_engraving_stage ||
    form.has_workshop_stage
  )
})

const form = reactive({
  name: '',
  designer_id: null as number | null,
  print_operator_id: null as number | null,
  workshop_worker_id: null as number | null,
  has_design_stage: false,
  has_print_stage: false,
  has_engraving_stage: false,
  has_workshop_stage: false,
  designers: [] as ProductAssignment[],
  print_operators: [] as ProductAssignment[],
  engraving_operators: [] as ProductAssignment[],
  workshop_workers: [] as ProductAssignment[],
})

onMounted(async () => {
  try {
    const [designers, printOperators, workshopWorkers, engravingOperators] = await Promise.all([
      getByRole('designer'),
      getByRole('print_operator'),
      getByRole('workshop_worker'),
      getByRole('engraving_operator'),
    ])
    allDesigners.value = designers.data as Designer[]
    allPrintOperators.value = printOperators.data as User[]
    allWorkshopWorkers.value = workshopWorkers.data as User[]
    allEngravingOperators.value = engravingOperators.data as User[]
  } catch (error) {
    allDesigners.value = []
    allPrintOperators.value = []
    allWorkshopWorkers.value = []
    allEngravingOperators.value = []
  }

  if (props.product) {
    Object.assign(form, {
      name: props.product.name || '',
      designer_id: props.product.designer_id || null,
      print_operator_id: props.product.print_operator_id || null,
      workshop_worker_id: props.product.workshop_worker_id || null,
      has_design_stage: props.product.has_design_stage || false,
      has_print_stage: props.product.has_print_stage || false,
      has_engraving_stage: props.product.has_engraving_stage || false,
      has_workshop_stage: props.product.has_workshop_stage || false,
      designers: [],
      print_operators: [],
      engraving_operators: [],
      workshop_workers: [],
    })
    if (props.product.assignments) {
      form.designers = props.product.assignments
        .filter((a) => a.role_type === 'designer')
        .map((a) => ({ ...a, user_id: Number(a.user_id || (a.user ? a.user.id : null)) }))
      form.print_operators = props.product.assignments
        .filter((a) => a.role_type === 'print_operator')
        .map((a) => ({ ...a, user_id: Number(a.user_id || (a.user ? a.user.id : null)) }))
      form.engraving_operators = props.product.assignments
        .filter((a) => a.role_type === 'engraving_operator')
        .map((a) => ({ ...a, user_id: Number(a.user_id || (a.user ? a.user.id : null)) }))
      form.workshop_workers = props.product.assignments
        .filter((a) => a.role_type === 'workshop_worker')
        .map((a) => ({ ...a, user_id: Number(a.user_id || (a.user ? a.user.id : null)) }))
      // --- Добавляем назначенных сотрудников в allUsers, если их нет ---
      const addIfMissing = (arr, user, fallbackId) => {
        const id = Number(user ? user.id : fallbackId)
        if (!user && fallbackId) {
          const found = (props.product.assignments || []).find(
            (a) => a.user && Number(a.user.id) === id,
          )
          if (found && found.user && !arr.some((u) => Number(u.id) === id)) {
            arr.push({ id, name: found.user.name })
          }
          return
        }
        if (!user) return
        if (!arr.some((u) => Number(u.id) === id)) arr.push({ id, name: user.name })
      }
      form.designers.forEach((a) => addIfMissing(allDesigners.value, a.user, a.user_id))
      form.print_operators.forEach((a) => addIfMissing(allPrintOperators.value, a.user, a.user_id))
      form.engraving_operators.forEach((a) =>
        addIfMissing(allEngravingOperators.value, a.user, a.user_id),
      )
      form.workshop_workers.forEach((a) =>
        addIfMissing(allWorkshopWorkers.value, a.user, a.user_id),
      )
    } else {
      // --- Загрузка product_assignments (fallback) ---
      const assignmentsData = await fetchProductAssignments(props.product.id)
      if (assignmentsData && assignmentsData.assignments) {
        form.designers = (assignmentsData.assignments.designer || []).map((a) => ({ ...a }))
        form.print_operators = (assignmentsData.assignments.print_operator || []).map((a) => ({
          ...a,
        }))
        form.engraving_operators = (assignmentsData.assignments.engraving_operator || []).map(
          (a) => ({ ...a }),
        )
        form.workshop_workers = (assignmentsData.assignments.workshop_worker || []).map((a) => ({
          ...a,
        }))
      }
    }
  }
})

function validateForm() {
  errors.name = ''
  errors.designer_id = ''
  errors.print_operator_id = ''
  errors.workshop_worker_id = ''
  errors.designers = []
  errors.print_operators = []
  errors.engraving_operators = []
  errors.workshop_workers = []

  let valid = true
  if (!form.name.trim()) {
    errors.name = 'Название обязательно'
    valid = false
  }

  // Валидация множественных назначений
  if (form.has_design_stage && form.designers.length === 0) {
    errors.designers = ['Необходимо назначить хотя бы одного дизайнера']
    valid = false
  } else if (form.has_design_stage && form.designers.some((d) => !d.user_id || d.user_id <= 0)) {
    errors.designers = ['Все дизайнеры должны быть выбраны']
    valid = false
  }

  if (form.has_print_stage && form.print_operators.length === 0) {
    errors.print_operators = ['Необходимо назначить хотя бы одного печатника']
    valid = false
  } else if (
    form.has_print_stage &&
    form.print_operators.some((p) => !p.user_id || p.user_id <= 0)
  ) {
    errors.print_operators = ['Все печатники должны быть выбраны']
    valid = false
  }

  if (form.has_engraving_stage && form.engraving_operators.length === 0) {
    errors.engraving_operators = ['Необходимо назначить хотя бы одного гравировщика']
    valid = false
  } else if (
    form.has_engraving_stage &&
    form.engraving_operators.some((e) => !e.user_id || e.user_id <= 0)
  ) {
    errors.engraving_operators = ['Все гравировщики должны быть выбраны']
    valid = false
  }

  if (form.has_workshop_stage && form.workshop_workers.length === 0) {
    errors.workshop_workers = ['Необходимо назначить хотя бы одного сотрудника цеха']
    valid = false
  } else if (
    form.has_workshop_stage &&
    form.workshop_workers.some((w) => !w.user_id || w.user_id <= 0)
  ) {
    errors.workshop_workers = ['Все сотрудники цеха должны быть выбраны']
    valid = false
  }

  return valid
}

function prepareAssignmentsForApi() {
  // Возвращает assignmentsByRole для bulk API
  return {
    designer: form.has_design_stage ? form.designers : [],
    print_operator: form.has_print_stage ? form.print_operators : [],
    engraving_operator: form.has_engraving_stage ? form.engraving_operators : [],
    workshop_worker: form.has_workshop_stage ? form.workshop_workers : [],
  }
}

async function handleSubmit() {
  if (!validateForm()) return
  loading.value = true
  try {
    if (props.product?.id) {
      await update(props.product.id, {
        ...form,
        designers: undefined,
        print_operators: undefined,
        engraving_operators: undefined,
        workshop_workers: undefined,
      })
      await bulkSaveProductAssignments(props.product.id, prepareAssignmentsForApi())
      toast.show('Товар и назначения успешно обновлены!')
      emit('submit', { id: props.product.id, ...form })
      emit('close')
    } else {
      const created = await create({
        ...form,
        designers: undefined,
        print_operators: undefined,
        engraving_operators: undefined,
        workshop_workers: undefined,
      })
      console.log('Ответ create:', created)
      let newId = undefined
      if (created?.data?.id) newId = created.data.id
      else if (created?.id) newId = created.id
      else if (created?.data?.product?.id) newId = created.data.product.id
      if (newId) {
        await bulkSaveProductAssignments(newId, prepareAssignmentsForApi())
        toast.show('Товар и назначения успешно созданы!')
        emit('submit', { id: newId, ...form })
        emit('close')
      } else {
        toast.show('Ошибка: не удалось получить id нового товара', 'error')
      }
    }
  } finally {
    loading.value = false
  }
}

function handleDelete() {
  if (props.product) {
    const productId = props.product.id
    remove(productId)
      .then(() => {
        toast.show('Товар удалён!')
        emit('delete', productId)
        emit('close')
      })
      .catch((e) => {
        if (e.message && e.message.includes('Ошибка удаления товара')) {
          toast.show('Товар уже был удалён')
          emit('delete', productId)
          emit('close')
        } else {
          toast.show('Ошибка при удалении товара')
        }
      })
  }
}
</script>

<style>
@import 'vue3-select/dist/vue3-select.css';

.vs__dropdown-menu {
  max-height: 110px !important;
  overflow-y: auto !important;
  padding: 0 !important;
}

.vs__dropdown-menu .vs__dropdown-option,
.vs__dropdown-menu .vs__dropdown-option--selected {
  min-height: 24px !important;
  padding: 2px 10px !important;
  font-size: 15px !important;
  line-height: 1.2 !important;
  color: #374151 !important;
  background: #fff !important;
}
</style>
