<template>
  <Modal @close="$emit('close')">
    <template #header>
      <h2 class="text-xl font-semibold text-gray-900">
        {{ project ? 'Редактировать проект' : 'Создать проект' }}
      </h2>
    </template>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Название *</label>
        <UIInput
          :model-value="form.title ?? ''"
          @update:model-value="(val) => (form.title = val ? String(val) : '')"
          placeholder="Введите название"
          :error="errors.title"
          required
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Дедлайн</label>
        <flatPickr
          v-model="form.deadline"
          :config="{
            dateFormat: 'Y-m-d H:i',
            altInput: true,
            altFormat: 'd F Y H:i',
            enableTime: true,
            time_24hr: true,
            allowInput: true,
            clickOpens: true,
            locale: Russian,
          }"
          placeholder="Выберите дату и время"
          class="w-full text-gray-700 text-base p-2 border border-gray-300 rounded-md flatpickr-uiinput focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
        />
        <div v-if="errors.deadline" class="text-red-600 text-sm mt-1">{{ errors.deadline }}</div>
      </div>

      <div v-if="canViewPrices()">
        <label class="block text-sm font-medium text-gray-700 mb-1">Сумма к оплате</label>
        <UIInput
          class="text-gray-700"
          type="number"
          :model-value="form.total_price"
          @update:model-value="(val) => (form.total_price = val ? String(val) : '')"
          placeholder="0.00"
          :error="errors.total_price"
          min="0"
        />
      </div>

      <div v-if="canViewPrices()">
        <label class="block text-sm font-medium text-gray-700 mb-1">Оплачено</label>
        <UIInput
          class="text-gray-700"
          type="number"
          :model-value="form.payment_amount"
          @update:model-value="(val) => (form.payment_amount = val ? String(val) : '')"
          placeholder="0.00"
          :error="errors.payment_amount"
          min="0"
        />
      </div>

      <div class="flex gap-3 pt-4">
        <UIButton type="submit" :loading="loading" class="flex-1">
          {{ project ? 'Сохранить' : 'Создать' }}
        </UIButton>
        <UIButton
          v-if="project"
          type="button"
          variant="danger"
          @click="handleDelete"
          class="flex-1"
        >
          Удалить
        </UIButton>
        <UIButton v-else type="button" variant="secondary" @click="$emit('close')" class="flex-1">
          Отмена
        </UIButton>
      </div>
    </form>
  </Modal>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import UIInput from '@/components/ui/UIInput.vue'
import UIButton from '@/components/ui/UIButton.vue'
import Vue3Select from 'vue3-select'
import 'vue3-select/dist/vue3-select.css'
import flatPickr from 'vue-flatpickr-component'
import 'flatpickr/dist/flatpickr.css'
import { Russian } from 'flatpickr/dist/l10n/ru.js'
import type { Project } from '@/types/project'
import type { Client } from '@/types/client'
import projectController from '@/controllers/projectControllerInstance'
import { getAllClients } from '@/services/api'
import { toast } from '@/stores/toast'
import { canViewPrices } from '@/utils/permissions'

const props = defineProps<{ project?: Project | null }>()
const emit = defineEmits(['close', 'submit', 'delete'])

const loading = ref(false)
const loadingClients = ref(false)

const clients = ref<{ id: number; name: string }[]>([])

const form = reactive({
  title: '',
  deadline: null as Date | null,
  total_price: '',
  payment_amount: '',
})

const errors = reactive({
  title: '',
  total_price: '',
  payment_amount: '',
  deadline: '',
})

const reduceClient = (client: { id: number; name: string }) => client.id

onMounted(async () => {
  loadingClients.value = true
  const data = await getAllClients()
  clients.value = data.map((c: Client) => ({
    id: c.id,
    name: c.name,
  }))
  loadingClients.value = false

  if (props.project) {
    Object.assign(form, {
      title: props.project.title || '',
      deadline: props.project.deadline ? new Date(props.project.deadline) : null,
      total_price: props.project.total_price != null ? String(props.project.total_price) : '',
      payment_amount:
        props.project.payment_amount != null ? String(props.project.payment_amount) : '',
    })
  }
})

function validateForm() {
  errors.title = ''
  errors.total_price = ''
  errors.payment_amount = ''
  errors.deadline = ''

  let valid = true

  if (!form.title?.trim()) {
    errors.title = 'Название обязательно'
    valid = false
  }

  if (form.total_price && Number(form.total_price) < 0) {
    errors.total_price = 'Сумма не может быть отрицательной'
    valid = false
  }

  if (form.payment_amount && Number(form.payment_amount) < 0) {
    errors.payment_amount = 'Оплата не может быть отрицательной'
    valid = false
  }

  if (form.deadline && form.deadline instanceof Date && form.deadline < new Date()) {
    errors.deadline = 'Дата не может быть в прошлом'
    valid = false
  }

  return valid
}

async function handleSubmit() {
  if (!validateForm()) return

  loading.value = true
  try {
    const payload = {
      ...form,
      total_price: form.total_price ? Number(form.total_price) : 0,
      payment_amount: form.payment_amount ? Number(form.payment_amount) : 0,
      deadline: form.deadline
        ? (() => {
            const d = new Date(form.deadline)
            if (!isNaN(d.getTime())) {
              const pad = (n: number) => n.toString().padStart(2, '0')
              return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:00`
            }
            return form.deadline
          })()
        : null,
    }

    if (props.project?.id) {
      await projectController.update(props.project.id, payload as Partial<Project>)
      toast.show('Проект успешно обновлён!')
      emit('submit', { id: props.project.id, ...payload })
      emit('close')
    } else {
      const created = await projectController.create(payload as Partial<Project>)
      toast.show('Проект успешно создан!')
      const newId = (created as any)?.id || (created as any)?.data?.id
      if (newId) {
        emit('submit', { id: newId, ...payload })
      }
      emit('close')
    }
  } finally {
    loading.value = false
  }
}

function handleDelete() {
  if (props.project && confirm('Удалить проект?')) {
    const projectId = props.project.id
    projectController.remove(projectId).then(() => {
      toast.show('Проект удалён!')
      emit('delete', projectId)
      emit('close')
    })
  }
}
</script>

<style>
@import '@vueform/multiselect/themes/default.css';
@import 'vue3-select/dist/vue3-select.css';

/* Компактный dropdown для vue3-select */
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
  color: #374151 !important; /* text-gray-700 */
  background: #fff !important;
}

.flatpickr-uiinput .flatpickr-input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 1rem;
  color: #111827;
  background: #fff;
  transition:
    border-color 0.2s,
    box-shadow 0.2s;
}
.flatpickr-uiinput .flatpickr-input:focus {
  outline: none;
  border-color: transparent;
  box-shadow: 0 0 0 2px #3b82f6;
}
:deep(.flatpickr-calendar) {
  left: 60px !important;
}
</style>
