<template>
  <Modal @close="$emit('close')">
    <template #header>
      <h2 class="text-xl font-semibold text-gray-900">Создать проект с заказами</h2>
    </template>

    <form @submit.prevent="handleSubmit" class="overflow-y-auto max-h-[70vh] p-4 space-y-4">
      <!-- Информация о проекте -->
      <div class="border-b pb-4">
        <h3 class="text-lg font-medium text-gray-900 mb-3">Информация о проекте</h3>

        <!-- Название проекта -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Название проекта *</label>
          <UIInput
            v-model="projectForm.title"
            type="text"
            placeholder="Введите название проекта"
            required
          />
          <div v-if="errors.project_title" class="text-red-600 text-sm mt-1">
            {{ errors.project_title }}
          </div>
        </div>

        <!-- Клиент проекта -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Клиент проекта *</label>
          <Vue3Select
            v-model="projectForm.client_id"
            :options="clients"
            label="name"
            :reduce="reduceClient"
            placeholder="Выберите клиента"
            :clearable="true"
            :searchable="true"
            required
          />
          <div v-if="errors.project_client_id" class="text-red-600 text-sm mt-1">
            {{ errors.project_client_id }}
          </div>
        </div>
      </div>

      <!-- Заказы -->
      <div>
        <h3 class="text-lg font-medium text-gray-900 mb-3">Заказы проекта</h3>

        <div
          v-for="(order, idx) in orders"
          :key="idx"
          class="mb-6 p-4 border rounded-lg bg-gray-50"
        >
          <div class="flex justify-between items-center mb-3">
            <h4 class="text-md font-medium text-gray-800">Заказ {{ idx + 1 }}</h4>
            <button
              v-if="orders.length > 1"
              type="button"
              @click="removeOrder(idx)"
              class="text-red-500 hover:text-red-700 text-sm"
            >
              Удалить
            </button>
          </div>

          <!-- Продукт -->
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Продукт *</label>
            <Vue3Select
              v-model="order.product_id"
              :options="products"
              label="name"
              :reduce="reduceProduct"
              placeholder="Выберите продукт"
              :clearable="true"
              :searchable="true"
              required
            />
            <div v-if="getError(`product_id_${idx}`)" class="text-red-600 text-sm mt-1">
              {{ getError(`product_id_${idx}`) }}
            </div>
          </div>

          <!-- Количество -->
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Количество *</label>
            <UIInput
              v-model="order.quantity"
              type="number"
              min="1"
              placeholder="Введите количество"
              required
            />
            <div v-if="getError(`quantity_${idx}`)" class="text-red-600 text-sm mt-1">
              {{ getError(`quantity_${idx}`) }}
            </div>
          </div>

          <!-- Дедлайн -->
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Дедлайн</label>
            <flatPickr
              v-model="order.deadline"
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
          </div>

          <!-- Цена -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Цена (TMT)</label>
            <UIInput
              v-model="order.price"
              type="number"
              min="0"
              step="0.01"
              placeholder="Введите цену"
            />
          </div>
        </div>

        <button type="button" @click="addOrder" class="text-blue-600 hover:text-blue-800 text-sm">
          + Добавить ещё заказ
        </button>
      </div>

      <div class="flex gap-3 pt-4">
        <UIButton type="submit" :loading="loading" class="flex-1"> Создать проект </UIButton>
        <UIButton type="button" variant="secondary" @click="$emit('close')" class="flex-1">
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
import type { Product } from '@/types/product'
import type { Client } from '@/types/client'
import { OrderController } from '@/controllers/OrderController'
import { getAllProducts, getAllClients } from '@/services/api'
import { toast } from '@/stores/toast'

const emit = defineEmits(['close', 'submit'])

const { createProjectWithOrders, fetchOrders } = OrderController()
const loading = ref(false)
const loadingData = ref(false)

const products = ref<{ id: number; name: string }[]>([])
const clients = ref<{ id: number; name: string }[]>([])

const projectForm = reactive({
  title: '',
  client_id: 0,
})

const orders = ref([
  {
    product_id: 0,
    quantity: 1,
    deadline: null,
    price: null,
  },
])

const errors = reactive({
  project_title: '',
  project_client_id: '',
  product_id_0: '',
  quantity_0: '',
})

const reduceProduct = (product: { id: number; name: string }) => product.id
const reduceClient = (client: { id: number; name: string }) => client.id

onMounted(async () => {
  loadingData.value = true

  try {
    const [productsData, clientsData] = await Promise.all([getAllProducts(), getAllClients()])

    products.value = (
      Array.isArray(productsData) ? productsData : (productsData as any).data || []
    ).map((p: Product) => ({
      id: p.id,
      name: p.name,
    }))

    clients.value = (
      Array.isArray(clientsData) ? clientsData : (clientsData as any).data || []
    ).map((c: Client) => ({
      id: c.id,
      name: c.name,
    }))
  } catch (error) {
    console.error('Ошибка загрузки данных:', error)
    toast.show('Ошибка загрузки данных', 'error')
  } finally {
    loadingData.value = false
  }
})

function getError(key: string): string | undefined {
  return (errors as any)[key]
}

function validateForm() {
  // Очищаем ошибки
  Object.keys(errors).forEach((key) => {
    ;(errors as any)[key] = ''
  })

  let valid = true

  // Проверяем проект
  if (!projectForm.title?.trim()) {
    errors.project_title = 'Название проекта обязательно'
    valid = false
  }

  if (!projectForm.client_id || projectForm.client_id <= 0) {
    errors.project_client_id = 'Клиент проекта обязателен'
    valid = false
  }

  // Проверяем каждый заказ
  orders.value.forEach((order, idx) => {
    if (!order.product_id || order.product_id <= 0) {
      ;(errors as any)[`product_id_${idx}`] = 'Продукт обязателен'
      valid = false
    }

    if (!order.quantity || order.quantity <= 0) {
      ;(errors as any)[`quantity_${idx}`] = 'Количество должно быть больше 0'
      valid = false
    }
  })

  return valid
}

async function handleSubmit() {
  if (!validateForm()) {
    return
  }

  loading.value = true
  try {
    const projectData = {
      title: projectForm.title,
      client_id: projectForm.client_id,
      orders: orders.value.map((order) => {
        const orderData: Record<string, unknown> = {
          product_id: order.product_id,
          quantity: order.quantity,
          client_id: projectForm.client_id, // Используем клиента проекта
        }

        if (order.deadline && order.deadline !== null) {
          orderData.deadline = order.deadline
        }
        if (order.price !== undefined && order.price !== null && order.price !== '') {
          orderData.price = order.price
        }

        return orderData
      }),
    }

    console.log('Отправляемые данные проекта:', projectData)
    await createProjectWithOrders(projectData)

    // Обновляем список заказов после создания проекта
    await fetchOrders(1)

    toast.show('Проект успешно создан!')
    emit('submit')
    emit('close')
  } catch (e) {
    console.error('Ошибка при создании проекта:', e)
    const errorMessage = e instanceof Error ? e.message : 'Ошибка при создании проекта'
    toast.show(errorMessage, 'error')
  } finally {
    loading.value = false
  }
}

function addOrder() {
  orders.value.push({
    product_id: 0,
    quantity: 1,
    deadline: null,
    price: null,
  })
}

function removeOrder(idx: number) {
  if (orders.value.length > 1) {
    orders.value.splice(idx, 1)
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
