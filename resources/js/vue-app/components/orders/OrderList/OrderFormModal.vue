<template>
  <Modal @close="$emit('close')">
    <template #header>
      <h2 class="text-xl font-semibold text-gray-900">
        {{ order ? 'Редактировать заказ' : 'Новый заказ' }}
      </h2>
    </template>

    <form @submit.prevent="handleSubmit" class="overflow-y-auto max-h-[70vh] p-4 space-y-4">
      <!-- Чекбокс создания проекта -->
      <div class="mb-2">
        <label class="inline-flex items-center">
          <input type="checkbox" v-model="createProjectRef" class="mr-2" />
          Создать новый проект
        </label>
      </div>

      <!-- Блок создания проекта -->
      <div v-if="createProjectRef" class="border-b pb-4 mb-4">
        <h3 class="text-lg font-medium text-gray-900 mb-3">Информация о проекте</h3>

        <!-- Название проекта -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Название проекта *</label>
          <UIInput v-model="projectForm.title" placeholder="Введите название проекта" required />
          <div v-if="errors.project_title" class="text-red-600 text-sm mt-1">
            {{ errors.project_title }}
          </div>
        </div>

        <!-- Клиент проекта -->
        <div class="mb-4 flex items-center gap-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Клиент проекта *</label>
          <button
            type="button"
            @click="showCreateClient = true"
            class="ml-2 px-2 py-1 rounded bg-blue-500 text-white text-xs hover:bg-blue-600"
          >
            +
          </button>
        </div>
        <Vue3Select
          v-model="selectedProjectClient"
          :options="clients"
          label="name"
          placeholder="Выберите клиента"
          :clearable="true"
          :searchable="true"
          required
        />
        <div v-if="errors.project_client_id" class="text-red-600 text-sm mt-1">
          {{ errors.project_client_id }}
        </div>
      </div>

      <!-- Модалка создания клиента -->
      <Modal v-if="showCreateClient" @close="showCreateClient = false">
        <template #header>
          <h2 class="text-lg font-semibold">Новый клиент</h2>
        </template>
        <form @submit.prevent="handleCreateClient" class="p-4 space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Имя *</label>
            <UIInput v-model="newClient.name" required placeholder="Имя клиента" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Компания</label>
            <UIInput v-model="newClient.company_name" placeholder="Название компании" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Телефон *</label>
            <UIInput v-model="newClient.contacts[0].value" required placeholder="+993 XX YYYYYY" />
          </div>
          <div v-if="createClientError" class="text-red-600 text-sm">{{ createClientError }}</div>
          <div class="flex gap-2 pt-2">
            <UIButton type="submit" :loading="creatingClient" class="flex-1">Создать</UIButton>
            <UIButton
              type="button"
              variant="secondary"
              @click="showCreateClient = false"
              class="flex-1"
              >Отмена</UIButton
            >
          </div>
        </form>
      </Modal>

      <!-- МАССОВОЕ добавление заказов -->
      <div v-if="createProjectRef">
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
              @update:model-value="
                (val) => {
                  order.product_id = val
                  fillStagesAndAssignees(order)
                }
              "
            />
            <div v-if="errors[`product_id_${idx}`]" class="text-red-600 text-sm mt-1">
              {{ errors[`product_id_${idx}`] }}
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
            <div v-if="errors[`quantity_${idx}`]" class="text-red-600 text-sm mt-1">
              {{ errors[`quantity_${idx}`] }}
            </div>
          </div>
          <!-- Дедлайн -->
          <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Дедлайн</label>
            <flatPickr
              v-model="order.deadline"
              :config="flatpickrConfig"
              placeholder="Выберите дату и время"
              class="w-full text-gray-700 text-base p-2 border border-gray-300 rounded-md flatpickr-uiinput focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
            />
          </div>
          <!-- Цена -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Общая сумма (TMT)</label>
            <UIInput
              v-model="order.price"
              type="number"
              min="0"
              step="0.01"
              placeholder="Введите общую сумму"
            />
          </div>
          <!-- Стадии и назначенные -->
          <div
            v-if="order.product_id"
            class="bg-gray-50 rounded-md p-3 mt-2 border border-gray-200"
          >
            <div class="flex flex-wrap gap-4 items-center mb-2">
              <span class="font-semibold text-xs text-gray-500">Стадии для этого заказа:</span>
              <label class="flex items-center gap-1 text-xs">
                <input
                  type="checkbox"
                  :checked="order.has_design_stage"
                  @change="
                    onStageToggleMass(
                      order,
                      'has_design_stage',
                      'designer_id',
                      getProductDesignerId(order.product_id),
                    )
                  "
                />
                Дизайн
              </label>
              <label class="flex items-center gap-1 text-xs">
                <input
                  type="checkbox"
                  :checked="order.has_print_stage"
                  @change="
                    onStageToggleMass(
                      order,
                      'has_print_stage',
                      'print_operator_id',
                      getProductPrintOperatorId(order.product_id),
                    )
                  "
                />
                Печать
              </label>
              <label class="flex items-center gap-1 text-xs">
                <input
                  type="checkbox"
                  :checked="order.has_engraving_stage"
                  @change="
                    onStageToggleMass(
                      order,
                      'has_engraving_stage',
                      'engraving_operator_id',
                      getProductEngravingOperatorId(order.product_id),
                    )
                  "
                />
                Гравировка
              </label>
              <label class="flex items-center gap-1 text-xs">
                <input
                  type="checkbox"
                  :checked="order.has_workshop_stage"
                  @change="
                    onStageToggleMass(
                      order,
                      'has_workshop_stage',
                      'workshop_worker_id',
                      getProductWorkshopWorkerId(order.product_id),
                    )
                  "
                />
                Цех
              </label>
            </div>
            <div class="flex flex-col gap-2 items-start mb-2">
              <AssignmentManager
                v-if="order.has_design_stage"
                title="Дизайнеры"
                :assignments="order.designers"
                :all-users="allDesigners"
                @update="(val) => order.designers.splice(0, order.designers.length, ...val)"
              />
              <button
                v-if="order.has_design_stage"
                type="button"
                class="text-blue-600 hover:text-blue-800 text-xs"
                @click="addDesigner(order)"
              >
                + Добавить дизайнера
              </button>
              <AssignmentManager
                v-if="order.has_print_stage"
                title="Печатники"
                :assignments="order.print_operators"
                :all-users="allPrintOperators"
                @update="
                  (val) => order.print_operators.splice(0, order.print_operators.length, ...val)
                "
              />
              <button
                v-if="order.has_print_stage"
                type="button"
                class="text-blue-600 hover:text-blue-800 text-xs"
                @click="addPrintOperator(order)"
              >
                + Добавить печатника
              </button>
              <AssignmentManager
                v-if="order.has_engraving_stage"
                title="Гравировщики"
                :assignments="order.engraving_operators"
                :all-users="allEngravingOperators"
                @update="
                  (val) =>
                    order.engraving_operators.splice(0, order.engraving_operators.length, ...val)
                "
              />
              <button
                v-if="order.has_engraving_stage"
                type="button"
                class="text-blue-600 hover:text-blue-800 text-xs"
                @click="addEngravingOperator(order)"
              >
                + Добавить гравировщика
              </button>
              <AssignmentManager
                v-if="order.has_workshop_stage"
                title="Цех"
                :assignments="order.workshop_workers"
                :all-users="allWorkshopWorkers"
                @update="
                  (val) => order.workshop_workers.splice(0, order.workshop_workers.length, ...val)
                "
              />
              <button
                v-if="order.has_workshop_stage"
                type="button"
                class="text-blue-600 hover:text-blue-800 text-xs"
                @click="addWorkshopWorker(order)"
              >
                + Добавить работника цеха
              </button>
            </div>
          </div>
        </div>
        <button type="button" @click="addOrder" class="text-blue-600 hover:text-blue-800 text-sm">
          + Добавить ещё заказ
        </button>
      </div>

      <!-- ОДИНОЧНЫЙ заказ (старый режим) -->
      <template v-if="!createProjectRef">
        <!-- Выбор клиента -->
        <div class="flex items-center gap-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Клиент *</label>
          <button
            type="button"
            @click="showCreateClient = true"
            class="ml-2 px-2 py-1 rounded bg-blue-500 text-white text-xs hover:bg-blue-600"
          >
            +
          </button>
        </div>
        <Vue3Select
          v-model="selectedClient"
          :options="clients"
          label="name"
          placeholder="Выберите клиента"
          :clearable="true"
          :searchable="true"
          required
        />
        <div v-if="errors.client_id" class="text-red-600 text-sm mt-1">
          {{ errors.client_id }}
        </div>

        <!-- Выбор проекта (опционально) -->
        <div v-if="!projectId">
          <label class="block text-sm font-medium text-gray-700 mb-1">Проект (необязательно)</label>
          <Vue3Select
            v-model="form.project_id"
            :options="projects"
            label="title"
            :reduce="reduceProject"
            placeholder="Выберите проект или оставьте пустым"
            :clearable="true"
            :searchable="true"
          />
          <div v-if="errors.project_id" class="text-red-600 text-sm mt-1">
            {{ errors.project_id }}
          </div>
        </div>
        <div v-else>
          <input type="hidden" v-model="form.project_id" />
        </div>

        <!-- Выбор продукта -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Продукт *</label>
          <Vue3Select
            v-model="form.product_id"
            :options="products"
            label="name"
            :reduce="reduceProduct"
            placeholder="Выберите продукт"
            :clearable="true"
            :searchable="true"
            required
          />
          <div v-if="errors.product_id" class="text-red-600 text-sm mt-1">
            {{ errors.product_id }}
          </div>
          <!-- Блок с инфой о продукте для заказа -->
          <div v-if="form.product_id" class="bg-gray-50 rounded-md p-3 mt-2 border border-gray-200">
            <div class="flex flex-wrap gap-4 items-center mb-2">
              <span class="font-semibold text-xs text-gray-500">Стадии для этого заказа:</span>
              <label class="flex items-center gap-1 text-xs">
                <input
                  type="checkbox"
                  :checked="form.has_design_stage"
                  @change="
                    onStageToggle('has_design_stage', 'designer_id', selectedProduct?.designer_id)
                  "
                />
                Дизайн
              </label>
              <label class="flex items-center gap-1 text-xs">
                <input
                  type="checkbox"
                  :checked="form.has_print_stage"
                  @change="
                    onStageToggle(
                      'has_print_stage',
                      'print_operator_id',
                      selectedProduct?.print_operator_id,
                    )
                  "
                />
                Печать
              </label>
              <label class="flex items-center gap-1 text-xs">
                <input
                  type="checkbox"
                  :checked="form.has_engraving_stage"
                  @change="onStageToggle('has_engraving_stage', 'engraving_operator_id', null)"
                />
                Гравировка
              </label>
              <label class="flex items-center gap-1 text-xs">
                <input
                  type="checkbox"
                  :checked="form.has_workshop_stage"
                  @change="
                    onStageToggle(
                      'has_workshop_stage',
                      'workshop_worker_id',
                      selectedProduct?.workshop_worker_id,
                    )
                  "
                />
                Цех
              </label>
            </div>
            <div class="flex flex-col gap-2 items-start mb-2">
              <AssignmentManager
                v-if="form.has_design_stage"
                title="Дизайнеры"
                :assignments="designAssignments"
                :all-users="allDesigners"
                @update="(val) => designAssignments.splice(0, designAssignments.length, ...val)"
              />
              <button
                v-if="form.has_design_stage"
                type="button"
                class="text-blue-600 hover:text-blue-800 text-xs"
                @click="addDesignAssignment"
              >
                + Добавить дизайнера
              </button>
              <AssignmentManager
                v-if="form.has_print_stage"
                title="Печатники"
                :assignments="printAssignments"
                :all-users="allPrintOperators"
                @update="(val) => printAssignments.splice(0, printAssignments.length, ...val)"
              />
              <button
                v-if="form.has_print_stage"
                type="button"
                class="text-blue-600 hover:text-blue-800 text-xs"
                @click="addPrintAssignment"
              >
                + Добавить печатника
              </button>
              <AssignmentManager
                v-if="form.has_engraving_stage"
                title="Гравировщики"
                :assignments="engravingAssignments"
                :all-users="allEngravingOperators"
                @update="
                  (val) => engravingAssignments.splice(0, engravingAssignments.length, ...val)
                "
              />
              <button
                v-if="form.has_engraving_stage"
                type="button"
                class="text-blue-600 hover:text-blue-800 text-xs"
                @click="addEngravingAssignment"
              >
                + Добавить гравировщика
              </button>
              <AssignmentManager
                v-if="form.has_workshop_stage"
                title="Цех"
                :assignments="workshopAssignments"
                :all-users="allWorkshopWorkers"
                @update="(val) => workshopAssignments.splice(0, workshopAssignments.length, ...val)"
              />
              <button
                v-if="form.has_workshop_stage"
                type="button"
                class="text-blue-600 hover:text-blue-800 text-xs"
                @click="addWorkshopAssignment"
              >
                + Добавить работника цеха
              </button>
            </div>
          </div>
        </div>

        <!-- Количество -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Количество *</label>
          <UIInput
            v-model="form.quantity"
            type="number"
            min="1"
            placeholder="Введите количество"
            required
          />
          <div v-if="errors.quantity" class="text-red-600 text-sm mt-1">
            {{ errors.quantity }}
          </div>
        </div>

        <!-- Дедлайн -->
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
          <div v-if="errors.deadline" class="text-red-600 text-sm mt-1">
            {{ errors.deadline }}
          </div>
        </div>

        <!-- Цена -->
        <div v-if="canViewPrices()">
          <label class="block text-sm font-medium text-gray-700 mb-1">Общая сумма (TMT)</label>
          <UIInput
            v-model="form.price"
            type="number"
            min="0"
            step="0.01"
            placeholder="Введите общую сумму"
          />
          <div v-if="errors.price" class="text-red-600 text-sm mt-1">
            {{ errors.price }}
          </div>
        </div>
      </template>

      <div class="flex gap-3 pt-4">
        <UIButton type="submit" :loading="loading" class="flex-1">
          {{
            createProjectRef
              ? 'Создать проект с заказами'
              : order
                ? 'Обновить заказ'
                : 'Создать заказ'
          }}
        </UIButton>
        <UIButton
          v-if="order && !createProjectRef"
          type="button"
          variant="danger"
          @click="handleDelete"
          class="flex-1"
          >Удалить</UIButton
        >
        <UIButton v-else type="button" variant="secondary" @click="$emit('close')" class="flex-1"
          >Отмена</UIButton
        >
      </div>
    </form>
  </Modal>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed, watch, watchEffect } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import UIInput from '@/components/ui/UIInput.vue'
import UIButton from '@/components/ui/UIButton.vue'
import Vue3Select from 'vue3-select'
import 'vue3-select/dist/vue3-select.css'
import flatPickr from 'vue-flatpickr-component'
import 'flatpickr/dist/flatpickr.css'
import { Russian } from 'flatpickr/dist/l10n/ru.js'
import type { Order, OrderForm } from '@/types/order'
import type { Product } from '@/types/product'
import type { Project } from '@/types/project'
import type { Client } from '@/types/client'
import { OrderController } from '@/controllers/OrderController'
import {
  getAllProducts,
  getAllProjects,
  getAllClients,
  getByRole,
  createProject,
} from '@/services/api'
import { toast } from '@/stores/toast'
import { useUserController } from '@/controllers/UserController.js'
import { UserRole } from '@/types/user'
import { canViewPrices } from '@/utils/permissions'
import { API_CONFIG } from '@/config/api'
import { handle401Error } from '@/utils/auth'
import AssignmentManager from '@/components/products/ProductList/AssignmentManager.vue'

const props = defineProps<{ order?: Order | null; projectId?: number }>()
const { projectId } = props
const emit = defineEmits(['close', 'submit', 'delete'])

const { createOrder, createProjectWithOrders, remove } = OrderController()
const loading = ref(false)
const loadingData = ref(false)

const products = ref<any[]>([])
const projects = ref<{ id: number; title: string }[]>([])
const clients = ref<{ id: number; name: string }[]>([])

const createProjectRef = ref(false)
const projectForm = reactive({ title: '', client_id: null })
// Расширяю структуру заказа для массового режима
const orders = ref([
  {
    product_id: 0,
    quantity: 1,
    deadline: null,
    price: null,
    has_design_stage: false,
    has_print_stage: false,
    has_engraving_stage: false,
    has_workshop_stage: false,
    designer_id: null,
    print_operator_id: null,
    engraver_id: null,
    workshop_worker_id: null,
    designers: [] as any[],
    print_operators: [] as any[],
    engraving_operators: [] as any[],
    workshop_workers: [] as any[],
    _wasEdited: {},
  },
])

// Объявляю form для одиночного заказа, чтобы не было ошибок в шаблоне
const form = reactive<any>({
  client_id: 0,
  project_id: 0,
  product_id: 0,
  quantity: 1,
  deadline: null,
  price: null,
  has_design_stage: false,
  has_print_stage: false,
  has_engraving_stage: false,
  has_workshop_stage: false,
  designer_id: null,
  print_operator_id: null,
  engraver_id: null,
  workshop_worker_id: null,
})

const errors = reactive<Record<string, string>>({
  project_title: '',
  project_client_id: '',
  product_id_0: '',
  quantity_0: '',
})

const flatpickrConfig = {
  dateFormat: 'Y-m-d H:i',
  altInput: true,
  altFormat: 'd F Y H:i',
  enableTime: true,
  time_24hr: true,
  allowInput: true,
  clickOpens: true,
  locale: Russian,
}

const reduceProduct = (product: { id: number; name: string }) => product.id
const reduceProject = (project: { id: number; title: string }) => project.id
const reduceClient = (client: { id: number; name: string }) => client.id

const { users, fetchUsers, loading: loadingUsers, filters: userFilters } = useUserController()

const lastInitializedProductId = ref<number | null>(null)
const lastSelectedDesignerId = ref<number | null>(null)
const lastSelectedPrintOperatorId = ref<number | null>(null)
const lastSelectedWorkshopWorkerId = ref<number | null>(null)

const showCreateClient = ref(false)
const creatingClient = ref(false)
const createClientError = ref('')
const newClient = reactive({
  name: '',
  company_name: '',
  contacts: [{ type: 'phone', value: '', localId: Date.now() }],
})

const designAssignments = reactive([] as any[])
const printAssignments = reactive([] as any[])
const engravingAssignments = reactive([] as any[])
const workshopAssignments = reactive([] as any[])

const allDesigners = ref([] as any[])
const allPrintOperators = ref([] as any[])
const allEngravingOperators = ref([] as any[])
const allWorkshopWorkers = ref([] as any[])

// Флаги для предотвращения повторного автозаполнения после ручного редактирования
const wasEdited = reactive({
  designer: false,
  print_operator: false,
  engraving_operator: false,
  workshop_worker: false,
})

// Автозаполнение для одиночного заказа
watch(
  () => form.product_id,
  (newProductId) => {
    const prod = products.value.find((p) => p.id === newProductId)
    const assignments = Array.isArray(prod?.assignments) ? prod.assignments : []
    if (!wasEdited.designer) {
      designAssignments.splice(
        0,
        designAssignments.length,
        ...assignments
          .filter((a) => a.role_type === 'designer')
          .map((a) => ({
            ...a,
            user_id: a.user_id || (a.user && a.user.id) || null,
          })),
      )
    }
    if (!wasEdited.print_operator) {
      printAssignments.splice(
        0,
        printAssignments.length,
        ...assignments
          .filter((a) => a.role_type === 'print_operator')
          .map((a) => ({
            ...a,
            user_id: a.user_id || (a.user && a.user.id) || null,
          })),
      )
    }
    if (!wasEdited.engraving_operator) {
      engravingAssignments.splice(
        0,
        engravingAssignments.length,
        ...assignments
          .filter((a) => a.role_type === 'engraving_operator')
          .map((a) => ({
            ...a,
            user_id: a.user_id || (a.user && a.user.id) || null,
          })),
      )
    }
    if (!wasEdited.workshop_worker) {
      workshopAssignments.splice(
        0,
        workshopAssignments.length,
        ...assignments
          .filter((a) => a.role_type === 'workshop_worker')
          .map((a) => ({
            ...a,
            user_id: a.user_id || (a.user && a.user.id) || null,
          })),
      )
    }
  },
  { immediate: true },
)

// AssignmentManager update handlers для сброса флага wasEdited
function updateDesignAssignments(val: any[]) {
  wasEdited.designer = true
  designAssignments.splice(0, designAssignments.length, ...val)
}
function updatePrintAssignments(val: any[]) {
  wasEdited.print_operator = true
  printAssignments.splice(0, printAssignments.length, ...val)
}
function updateEngravingAssignments(val: any[]) {
  wasEdited.engraving_operator = true
  engravingAssignments.splice(0, engravingAssignments.length, ...val)
}
function updateWorkshopAssignments(val: any[]) {
  wasEdited.workshop_worker = true
  workshopAssignments.splice(0, workshopAssignments.length, ...val)
}

// Для массового режима (createProject): автозаполнение для каждого заказа
function autoFillOrderAssignments(order: any) {
  console.log('🎯 autoFillOrderAssignments called for product_id:', order.product_id)
  const prod = products.value.find((p) => p.id === order.product_id)
  const assignments = Array.isArray(prod?.assignments) ? prod.assignments : []
  console.log('📦 Product found:', prod)
  console.log('👥 Product assignments:', assignments)

  if (!order._wasEdited) order._wasEdited = {}
  if (!Array.isArray(order.designers)) order.designers = []
  if (!Array.isArray(order.print_operators)) order.print_operators = []
  if (!Array.isArray(order.engraving_operators)) order.engraving_operators = []
  if (!Array.isArray(order.workshop_workers)) order.workshop_workers = []

  // Устанавливаем стадии согласно продукту, но даем возможность пользователю изменить их
  order.has_design_stage = !!prod?.has_design_stage
  order.has_print_stage = !!prod?.has_print_stage
  order.has_engraving_stage = !!prod?.has_engraving_stage
  order.has_workshop_stage = !!prod?.has_workshop_stage

  // Заполняем назначения для каждой стадии, если она активна и не редактировалась пользователем
  if (order.has_design_stage && !order._wasEdited.designer) {
    const designerAssignments = assignments.filter((a) => a.role_type === 'designer')
    console.log('🎨 Adding designers:', designerAssignments)
    order.designers.splice(
      0,
      order.designers.length,
      ...designerAssignments.map((a) => ({
        ...a,
        user_id: Number(a.user_id || (a.user && a.user.id) || 0) || null,
        user:
          a.user ||
          (a.user_id ? allDesigners.value.find((u) => u.id === Number(a.user_id)) : undefined),
      })),
    )
    console.log('🎨 Designers after assignment:', order.designers)
  } else if (!order.has_design_stage) {
    order.designers.splice(0, order.designers.length)
    console.log('🎨 Cleared designers (stage disabled)')
  }

  if (order.has_print_stage && !order._wasEdited.print_operator) {
    const printAssignments = assignments.filter((a) => a.role_type === 'print_operator')
    console.log('🖨️ Adding print operators:', printAssignments)
    order.print_operators.splice(
      0,
      order.print_operators.length,
      ...printAssignments.map((a) => ({
        ...a,
        user_id: Number(a.user_id || (a.user && a.user.id) || 0) || null,
        user:
          a.user ||
          (a.user_id ? allPrintOperators.value.find((u) => u.id === Number(a.user_id)) : undefined),
      })),
    )
    console.log('🖨️ Print operators after assignment:', order.print_operators)
  } else if (!order.has_print_stage) {
    order.print_operators.splice(0, order.print_operators.length)
    console.log('🖨️ Cleared print operators (stage disabled)')
  }

  if (order.has_engraving_stage && !order._wasEdited.engraving_operator) {
    const engravingAssignments = assignments.filter((a) => a.role_type === 'engraving_operator')
    console.log('⚡ Adding engraving operators:', engravingAssignments)
    order.engraving_operators.splice(
      0,
      order.engraving_operators.length,
      ...engravingAssignments.map((a) => ({
        ...a,
        user_id: Number(a.user_id || (a.user && a.user.id) || 0) || null,
        user:
          a.user ||
          (a.user_id
            ? allEngravingOperators.value.find((u) => u.id === Number(a.user_id))
            : undefined),
      })),
    )
    console.log('⚡ Engraving operators after assignment:', order.engraving_operators)
  } else if (!order.has_engraving_stage) {
    order.engraving_operators.splice(0, order.engraving_operators.length)
    console.log('⚡ Cleared engraving operators (stage disabled)')
  }

  if (order.has_workshop_stage && !order._wasEdited.workshop_worker) {
    const workshopAssignments = assignments.filter((a) => a.role_type === 'workshop_worker')
    console.log('🔧 Adding workshop workers:', workshopAssignments)
    order.workshop_workers.splice(
      0,
      order.workshop_workers.length,
      ...workshopAssignments.map((a) => ({
        ...a,
        user_id: Number(a.user_id || (a.user && a.user.id) || 0) || null,
        user:
          a.user ||
          (a.user_id
            ? allWorkshopWorkers.value.find((u) => u.id === Number(a.user_id))
            : undefined),
      })),
    )
    console.log('🔧 Workshop workers after assignment:', order.workshop_workers)
  } else if (!order.has_workshop_stage) {
    order.workshop_workers.splice(0, order.workshop_workers.length)
    console.log('🔧 Cleared workshop workers (stage disabled)')
  }

  console.log('🏁 autoFillOrderAssignments completed. Final state:', {
    product_id: order.product_id,
    stages: {
      has_design_stage: order.has_design_stage,
      has_print_stage: order.has_print_stage,
      has_engraving_stage: order.has_engraving_stage,
      has_workshop_stage: order.has_workshop_stage,
    },
    assignments: {
      designers: order.designers.length,
      print_operators: order.print_operators.length,
      engraving_operators: order.engraving_operators.length,
      workshop_workers: order.workshop_workers.length,
    },
  })
}

// Удален конфликтующий watcher - используется только один watcher ниже

async function handleCreateClient() {
  createClientError.value = ''

  if (!newClient.name.trim()) {
    createClientError.value = 'Имя клиента обязательно'
    return
  }

  const phoneContact = newClient.contacts.find((c) => c.type === 'phone')
  if (!phoneContact || !phoneContact.value.trim()) {
    createClientError.value = 'Телефон обязателен'
    return
  }

  const phoneRegex = /^\+993[-\s]?\d{2}[-\s]?\d{6}$/
  if (!phoneRegex.test(phoneContact.value)) {
    createClientError.value = 'Телефон должен быть в формате +993 XX YYYYYY'
    return
  }

  creatingClient.value = true
  try {
    // Создаем клиента
    const clientData = {
      name: newClient.name.trim(),
      company_name: newClient.company_name.trim(),
    }

    const response = await (await import('@/services/api')).createClient(clientData)

    // Создаем контакт для клиента
    const phoneContact = newClient.contacts.find((c) => c.type === 'phone')
    if (phoneContact && phoneContact.value.trim()) {
      await (
        await import('@/services/api')
      ).createClientContact(response.id, {
        type: 'phone',
        value: phoneContact.value.trim(),
      })
    }

    // Обновляем список клиентов
    const [clientsData] = await Promise.all([getAllClients()])
    clients.value = (clientsData.data || clientsData).map((c) => ({ id: c.id, name: c.name }))

    // Выбираем нового клиента
    if (createProjectRef.value) {
      projectForm.client_id = response.id
    } else {
      form.client_id = response.id
    }

    showCreateClient.value = false
    // Сбрасываем форму
    newClient.name = ''
    newClient.company_name = ''
    newClient.contacts = [{ type: 'phone', value: '', localId: Date.now() }]
  } catch (e) {
    createClientError.value = 'Ошибка при создании клиента'
  } finally {
    creatingClient.value = false
  }
}

onMounted(async () => {
  loadingData.value = true
  try {
    const [
      productsData,
      projectsData,
      clientsData,
      designers,
      printOperators,
      workshopWorkers,
      engravingOperators,
    ] = await Promise.all([
      getAllProducts(),
      getAllProjects(),
      getAllClients(),
      getByRole('designer'),
      getByRole('print_operator'),
      getByRole('workshop_worker'),
      getByRole('engraving_operator'),
    ])
    products.value = (productsData.data || productsData).map((p) => ({
      id: p.id,
      name: p.name,
      ...p,
    }))
    projects.value = (projectsData.data || projectsData).map((p) => ({
      id: p.id,
      title: p.title,
    }))
    clients.value = (clientsData.data || clientsData).map((c) => ({
      id: c.id,
      name: c.name,
    }))
    allDesigners.value = designers.data || []
    allPrintOperators.value = printOperators.data || []
    allWorkshopWorkers.value = workshopWorkers.data || []
    allEngravingOperators.value = engravingOperators.data || []
    await fetchUsers(1, '', 'id', 'asc', 100)
    // --- Заполняем назначения из order.assignments, если есть ---
    if (props.order && props.order.assignments) {
      designAssignments.splice(
        0,
        designAssignments.length,
        ...props.order.assignments
          .filter((a) => a.role_type === 'designer')
          .map((a) => ({
            ...a,
            user_id: a.user_id || (a.user && a.user.id) || null,
          })),
      )
      printAssignments.splice(
        0,
        printAssignments.length,
        ...props.order.assignments
          .filter((a) => a.role_type === 'print_operator')
          .map((a) => ({
            ...a,
            user_id: a.user_id || (a.user && a.user.id) || null,
          })),
      )
      engravingAssignments.splice(
        0,
        engravingAssignments.length,
        ...props.order.assignments
          .filter((a) => a.role_type === 'engraving_operator')
          .map((a) => ({
            ...a,
            user_id: a.user_id || (a.user && a.user.id) || null,
          })),
      )
      workshopAssignments.splice(
        0,
        workshopAssignments.length,
        ...props.order.assignments
          .filter((a) => a.role_type === 'workshop_worker')
          .map((a) => ({
            ...a,
            user_id: a.user_id || (a.user && a.user.id) || null,
          })),
      )
    }
  } finally {
    loadingData.value = false
  }
})

const selectedProduct = computed(() => {
  return products.value.find((p) => p.id === form.product_id)
})

const selectedProjectClient = computed({
  get: () => {
    if (!projectForm.client_id) return null
    return clients.value.find((c) => c.id === projectForm.client_id) || null
  },
  set: (client) => {
    projectForm.client_id = client ? client.id : null
  },
})

const selectedClient = computed({
  get: () => {
    if (!form.client_id) return null
    return clients.value.find((c) => c.id === form.client_id) || null
  },
  set: (client) => {
    form.client_id = client ? client.id : null
  },
})

const designerOptions = computed(() => users.value.filter((u) => u.role === UserRole.DESIGNER))
const printOperatorOptions = computed(() =>
  users.value.filter((u) => u.role === UserRole.PRINT_OPERATOR),
)
const workshopWorkerOptions = computed(() =>
  users.value.filter((u) => u.role === UserRole.WORKSHOP_WORKER),
)

function updateProductAssignment(role, userId) {
  if (!selectedProduct.value) return
  selectedProduct.value[role] = userId
  // Можно добавить PATCH-запрос к API для обновления продукта, если нужно сохранять сразу
}

function toggleProductStage(stageKey) {
  if (!selectedProduct.value) return
  selectedProduct.value[stageKey] = !selectedProduct.value[stageKey]
  // Можно добавить PATCH-запрос к API для обновления продукта, если нужно сохранять сразу
}

function onStageToggle(stageKey, roleKey, defaultUserId) {
  form[stageKey] = !form[stageKey]
  if (form[stageKey]) {
    // Включили чекбокс — подставить доступных сотрудников
    if (roleKey === 'designer_id' && designAssignments.length === 0) {
      const prod = products.value.find((p) => p.id === form.product_id)
      if (prod && Array.isArray(prod.assignments)) {
        // Подставляем назначенных в продукте
        const productAssignments = prod.assignments.filter((a) => a.role_type === 'designer')
        if (productAssignments.length > 0) {
          designAssignments.push(...productAssignments)
        } else {
          // Если в продукте нет назначений, подставляем всех доступных дизайнеров
          designAssignments.push(
            ...allDesigners.value.map((designer) => ({
              id: Date.now() + Math.random(),
              user_id: designer.id,
              user: designer,
              role_type: 'designer',
              has_design_stage: true,
              has_print_stage: false,
              has_engraving_stage: false,
              has_workshop_stage: false,
            })),
          )
        }
      }
    }
    if (roleKey === 'print_operator_id' && printAssignments.length === 0) {
      const prod = products.value.find((p) => p.id === form.product_id)
      if (prod && Array.isArray(prod.assignments)) {
        const productAssignments = prod.assignments.filter((a) => a.role_type === 'print_operator')
        if (productAssignments.length > 0) {
          printAssignments.push(...productAssignments)
        } else {
          printAssignments.push(
            ...allPrintOperators.value.map((operator) => ({
              id: Date.now() + Math.random(),
              user_id: operator.id,
              user: operator,
              role_type: 'print_operator',
              has_design_stage: false,
              has_print_stage: true,
              has_engraving_stage: false,
              has_workshop_stage: false,
            })),
          )
        }
      }
    }
    if (roleKey === 'engraving_operator_id' && engravingAssignments.length === 0) {
      const prod = products.value.find((p) => p.id === form.product_id)
      if (prod && Array.isArray(prod.assignments)) {
        const productAssignments = prod.assignments.filter(
          (a) => a.role_type === 'engraving_operator',
        )
        if (productAssignments.length > 0) {
          engravingAssignments.push(...productAssignments)
        } else {
          engravingAssignments.push(
            ...allEngravingOperators.value.map((operator) => ({
              id: Date.now() + Math.random(),
              user_id: operator.id,
              user: operator,
              role_type: 'engraving_operator',
              has_design_stage: false,
              has_print_stage: false,
              has_engraving_stage: true,
              has_workshop_stage: false,
            })),
          )
        }
      }
    }
    if (roleKey === 'workshop_worker_id' && workshopAssignments.length === 0) {
      const prod = products.value.find((p) => p.id === form.product_id)
      if (prod && Array.isArray(prod.assignments)) {
        const productAssignments = prod.assignments.filter((a) => a.role_type === 'workshop_worker')
        if (productAssignments.length > 0) {
          workshopAssignments.push(...productAssignments)
        } else {
          workshopAssignments.push(
            ...allWorkshopWorkers.value.map((worker) => ({
              id: Date.now() + Math.random(),
              user_id: worker.id,
              user: worker,
              role_type: 'workshop_worker',
              has_design_stage: false,
              has_print_stage: false,
              has_engraving_stage: false,
              has_workshop_stage: true,
            })),
          )
        }
      }
    }
  } else {
    // Выключили чекбокс — очистить назначения
    if (roleKey === 'designer_id') designAssignments.splice(0)
    if (roleKey === 'print_operator_id') printAssignments.splice(0)
    if (roleKey === 'engraving_operator_id') engravingAssignments.splice(0)
    if (roleKey === 'workshop_worker_id') workshopAssignments.splice(0)
  }
}

function getProductDesignerId(product_id) {
  const prod = products.value.find((p) => p.id === product_id)
  return prod ? prod.designer_id || null : null
}
function getProductPrintOperatorId(product_id) {
  const prod = products.value.find((p) => p.id === product_id)
  return prod ? prod.print_operator_id || null : null
}
function getProductWorkshopWorkerId(product_id) {
  const prod = products.value.find((p) => p.id === product_id)
  return prod ? prod.workshop_worker_id || null : null
}
function getProductEngravingOperatorId(product_id) {
  const prod = products.value.find((p) => p.id === product_id)
  return prod ? prod.engraver_id || null : null
}

function onStageToggleMass(order, stageKey, roleKey, defaultUserId) {
  order[stageKey] = !order[stageKey]
  if (order[stageKey]) {
    // Включили чекбокс — подставить доступных сотрудников
    const prod = products.value.find((p) => p.id === order.product_id)
    if (roleKey === 'designer_id' && order.designers.length === 0) {
      if (prod && Array.isArray(prod.assignments)) {
        const productAssignments = prod.assignments.filter((a) => a.role_type === 'designer')
        if (productAssignments.length > 0) {
          order.designers.push(...productAssignments)
        } else {
          // Если в продукте нет назначений, подставляем всех доступных дизайнеров
          order.designers.push(
            ...allDesigners.value.map((designer) => ({
              id: Date.now() + Math.random(),
              user_id: designer.id,
              user: designer,
              role_type: 'designer',
              has_design_stage: true,
              has_print_stage: false,
              has_engraving_stage: false,
              has_workshop_stage: false,
            })),
          )
        }
      }
    }
    if (roleKey === 'print_operator_id' && order.print_operators.length === 0) {
      if (prod && Array.isArray(prod.assignments)) {
        const productAssignments = prod.assignments.filter((a) => a.role_type === 'print_operator')
        if (productAssignments.length > 0) {
          order.print_operators.push(...productAssignments)
        } else {
          order.print_operators.push(
            ...allPrintOperators.value.map((operator) => ({
              id: Date.now() + Math.random(),
              user_id: operator.id,
              user: operator,
              role_type: 'print_operator',
              has_design_stage: false,
              has_print_stage: true,
              has_engraving_stage: false,
              has_workshop_stage: false,
            })),
          )
        }
      }
    }
    if (roleKey === 'engraving_operator_id' && order.engraving_operators.length === 0) {
      if (prod && Array.isArray(prod.assignments)) {
        const productAssignments = prod.assignments.filter(
          (a) => a.role_type === 'engraving_operator',
        )
        if (productAssignments.length > 0) {
          order.engraving_operators.push(...productAssignments)
        } else {
          order.engraving_operators.push(
            ...allEngravingOperators.value.map((operator) => ({
              id: Date.now() + Math.random(),
              user_id: operator.id,
              user: operator,
              role_type: 'engraving_operator',
              has_design_stage: false,
              has_print_stage: false,
              has_engraving_stage: true,
              has_workshop_stage: false,
            })),
          )
        }
      }
    }
    if (roleKey === 'workshop_worker_id' && order.workshop_workers.length === 0) {
      if (prod && Array.isArray(prod.assignments)) {
        const productAssignments = prod.assignments.filter((a) => a.role_type === 'workshop_worker')
        if (productAssignments.length > 0) {
          order.workshop_workers.push(...productAssignments)
        } else {
          order.workshop_workers.push(
            ...allWorkshopWorkers.value.map((worker) => ({
              id: Date.now() + Math.random(),
              user_id: worker.id,
              user: worker,
              role_type: 'workshop_worker',
              has_design_stage: false,
              has_print_stage: false,
              has_engraving_stage: false,
              has_workshop_stage: true,
            })),
          )
        }
      }
    }
  } else {
    // Выключили чекбокс — очистить назначения
    if (roleKey === 'designer_id') order.designers.splice(0)
    if (roleKey === 'print_operator_id') order.print_operators.splice(0)
    if (roleKey === 'engraving_operator_id') order.engraving_operators.splice(0)
    if (roleKey === 'workshop_worker_id') order.workshop_workers.splice(0)
  }
}

// Следим за изменением селекторов и обновляем lastSelectedXxxId только в одиночном режиме заказа
watchEffect(() => {
  if (!createProjectRef.value) {
    // designer_id
    if (
      typeof form !== 'undefined' &&
      form.designer_id !== undefined &&
      form.designer_id !== null
    ) {
      lastSelectedDesignerId.value = form.designer_id
    }
    // print_operator_id
    if (
      typeof form !== 'undefined' &&
      form.print_operator_id !== undefined &&
      form.print_operator_id !== null
    ) {
      lastSelectedPrintOperatorId.value = form.print_operator_id
    }
    // workshop_worker_id
    if (
      typeof form !== 'undefined' &&
      form.workshop_worker_id !== undefined &&
      form.workshop_worker_id !== null
    ) {
      lastSelectedWorkshopWorkerId.value = form.workshop_worker_id
    }
    // product_id
    if (
      typeof form !== 'undefined' &&
      form.product_id &&
      form.product_id !== lastInitializedProductId.value
    ) {
      const prod = products.value.find((p) => p.id === form.product_id)
      if (prod) {
        form.has_design_stage = !!prod.has_design_stage
        form.has_print_stage = !!prod.has_print_stage
        form.has_engraving_stage = !!prod.has_engraving_stage
        form.has_workshop_stage = !!prod.has_workshop_stage
        form.designer_id = prod.designer_id || null
        form.print_operator_id = prod.print_operator_id || null
        form.engraver_id = prod.engraver_id || null
        form.workshop_worker_id = prod.workshop_worker_id || null
        lastSelectedDesignerId.value = prod.designer_id || null
        lastSelectedPrintOperatorId.value = prod.print_operator_id || null
        lastSelectedWorkshopWorkerId.value = prod.workshop_worker_id || null
      } else {
        form.has_design_stage = false
        form.has_print_stage = false
        form.has_engraving_stage = false
        form.has_workshop_stage = false
        form.designer_id = null
        form.print_operator_id = null
        form.engraver_id = null
        form.workshop_worker_id = null
        lastSelectedDesignerId.value = null
        lastSelectedPrintOperatorId.value = null
        lastSelectedWorkshopWorkerId.value = null
      }
      lastInitializedProductId.value = form.product_id
    }
  }
})

// --- Автозаполнение стадий и сотрудников для каждого заказа ---
const orderProductWatches = []

watch(
  orders,
  (newOrders, oldOrders) => {
    // Очищаем старые watcher'ы
    orderProductWatches.forEach((unwatch) => unwatch && unwatch())
    orderProductWatches.length = 0

    // Для каждого заказа создаём watcher
    newOrders.forEach((order, idx) => {
      const unwatch = watch(
        () => order.product_id,
        (newProductId, oldProductId) => {
          if (newProductId) {
            // Вызываем autoFillOrderAssignments для заполнения назначений
            autoFillOrderAssignments(order)
          }
        },
        { immediate: true },
      )
      orderProductWatches.push(unwatch)
    })
  },
  { immediate: true },
)

function fillStagesAndAssignees(order: any) {
  // Эта функция вызывается при изменении продукта в template
  // Она должна только вызвать autoFillOrderAssignments
  autoFillOrderAssignments(order)
}

function validateForm() {
  // Очищаем ошибки
  Object.keys(errors).forEach((key) => {
    errors[key] = ''
  })

  let valid = true

  // Проверяем обязательные поля
  if (!form.client_id || form.client_id <= 0) {
    errors.client_id = 'Клиент обязателен'
    valid = false
  }

  if (!form.product_id || form.product_id <= 0) {
    errors.product_id = 'Продукт обязателен'
    valid = false
  }

  if (!form.quantity || form.quantity <= 0) {
    errors.quantity = 'Количество должно быть больше 0'
    valid = false
  }

  // Проверяем опциональные поля
  if (form.price !== undefined && form.price !== null && form.price < 0) {
    errors.price = 'Цена не может быть отрицательной'
    valid = false
  }

  if (form.deadline && typeof form.deadline === 'string' && new Date(form.deadline) < new Date()) {
    errors.deadline = 'Дата не может быть в прошлом'
    valid = false
  }

  return valid
}

// --- Новый хелпер для подготовки order_assignments ---
function prepareOrderAssignments(assignmentsByRole: any) {
  console.log('prepareOrderAssignments вход:', JSON.parse(JSON.stringify(assignmentsByRole)))
  const result = []
  if (Array.isArray(assignmentsByRole.designAssignments)) {
    console.log(
      'designAssignments внутри prepareOrderAssignments:',
      assignmentsByRole.designAssignments,
    )
    assignmentsByRole.designAssignments.forEach((a) => {
      console.log('designAssignment user_id:', a.user_id)
      if (a.user_id)
        result.push({
          ...a,
          role_type: 'designer',
          has_design_stage: true,
          has_print_stage: false,
          has_engraving_stage: false,
          has_workshop_stage: false,
        })
    })
  }
  if (Array.isArray(assignmentsByRole.printAssignments)) {
    console.log(
      'printAssignments внутри prepareOrderAssignments:',
      assignmentsByRole.printAssignments,
    )
    assignmentsByRole.printAssignments.forEach((a) => {
      console.log('printAssignment user_id:', a.user_id)
      if (a.user_id)
        result.push({
          ...a,
          role_type: 'print_operator',
          has_design_stage: false,
          has_print_stage: true,
          has_engraving_stage: false,
          has_workshop_stage: false,
        })
    })
  }
  if (Array.isArray(assignmentsByRole.engravingAssignments)) {
    console.log(
      'engravingAssignments внутри prepareOrderAssignments:',
      assignmentsByRole.engravingAssignments,
    )
    assignmentsByRole.engravingAssignments.forEach((a) => {
      console.log('engravingAssignment user_id:', a.user_id)
      if (a.user_id)
        result.push({
          ...a,
          role_type: 'engraving_operator',
          has_design_stage: false,
          has_print_stage: false,
          has_engraving_stage: true,
          has_workshop_stage: false,
        })
    })
  }
  if (Array.isArray(assignmentsByRole.workshopAssignments)) {
    console.log(
      'workshopAssignments внутри prepareOrderAssignments:',
      assignmentsByRole.workshopAssignments,
    )
    assignmentsByRole.workshopAssignments.forEach((a) => {
      console.log('workshopAssignment user_id:', a.user_id)
      if (a.user_id)
        result.push({
          ...a,
          role_type: 'workshop_worker',
          has_design_stage: false,
          has_print_stage: false,
          has_engraving_stage: false,
          has_workshop_stage: true,
        })
    })
  }
  console.log('📋 prepareOrderAssignments result:', result)
  return result
}

// --- Новый хелпер для bulk-assign только для одной стадии и роли ---
function prepareSingleStageAssignments(assignments: any[], roleType: string, stageKey: string) {
  // assignments: массив исполнителей (например, printAssignments)
  // roleType: 'print_operator', 'designer', ...
  // stageKey: 'has_print_stage', 'has_design_stage', ...
  return assignments
    .filter((a) => a.user_id)
    .map((a) => ({
      user_id: a.user_id,
      role_type: roleType,
      has_design_stage: false,
      has_print_stage: false,
      has_engraving_stage: false,
      has_workshop_stage: false,
      [stageKey]: true,
    }))
}
// --- Пример использования для стадии print ---
// const assignments = prepareSingleStageAssignments(printAssignments.value, 'print_operator', 'has_print_stage');
// await bulkAssignOrderAssignments(orderId, assignments);

// --- Новый API-хелпер для bulk-assign назначений ---
async function bulkAssignOrderAssignments(orderId: number, assignments: any[]) {
  console.log('bulkAssignOrderAssignments called:', orderId, assignments)
  try {
    const response = await fetch(`${API_CONFIG.BASE_URL}/orders/${orderId}/bulk-assign`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      },
      body: JSON.stringify({ assignments }),
    })

    if (!response.ok) {
      // Специальная обработка 401 ошибок
      if (response.status === 401) {
        handle401Error('Сессия истекла при отправке назначений. Необходимо войти в систему заново.')
        throw new Error('Сессия истекла. Необходимо войти в систему заново.')
      }
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const result = await response.json()
    console.log('bulkAssignOrderAssignments result:', result)
    return result
  } catch (error) {
    console.error('Error in bulkAssignOrderAssignments:', error)
    throw error
  }
}

async function handleSubmit() {
  loading.value = true
  try {
    if (createProjectRef.value) {
      // Создание проекта с заказами
      if (!projectForm.title || !projectForm.title.trim()) {
        toast.show('Введите название проекта', 'error')
        return
      }

      if (!projectForm.client_id || !selectedProjectClient.value) {
        toast.show('Выберите клиента для проекта', 'error')
        return
      }

      // Валидация заказов
      for (let i = 0; i < orders.value.length; i++) {
        const order = orders.value[i]
        if (!order.product_id) {
          toast.show(`Выберите продукт для заказа ${i + 1}`, 'error')
          return
        }
        if (!order.quantity || order.quantity <= 0) {
          toast.show(`Укажите количество для заказа ${i + 1}`, 'error')
          return
        }
      }

      // 1. Создаём проект без заказов
      const createdProject = await createProject({
        title: projectForm.title.trim(),
        client_id: projectForm.client_id,
      })
      console.log('createdProject:', createdProject)
      const projectId = createdProject.id || createdProject.data?.id
      if (!projectId) {
        toast.show('Ошибка при создании проекта', 'error')
        return
      }
      // 2. Для каждого заказа создаём отдельный заказ с project_id
      for (let i = 0; i < orders.value.length; i++) {
        const order = orders.value[i]
        // Валидация заказа
        if (!order.product_id) {
          toast.show(`Выберите продукт для заказа ${i + 1}`, 'error')
          return
        }
        if (!order.quantity || order.quantity <= 0) {
          toast.show(`Укажите количество для заказа ${i + 1}`, 'error')
          return
        }
        const orderData = {
          client_id: projectForm.client_id,
          product_id: order.product_id,
          quantity: order.quantity,
          deadline: order.deadline,
          price: order.price,
          has_design_stage: order.has_design_stage,
          has_print_stage: order.has_print_stage,
          has_workshop_stage: order.has_workshop_stage,
          has_engraving_stage: order.has_engraving_stage,
          project_id: projectId,
        }
        const createdOrder = await createOrder(orderData)
        const orderId = createdOrder?.data?.id || createdOrder?.id
        // 3. Назначения для каждого заказа
        const assignments = prepareOrderAssignments({
          designAssignments: order.designers,
          printAssignments: order.print_operators,
          engravingAssignments: order.engraving_operators,
          workshopAssignments: order.workshop_workers,
        })
        console.log(
          'Assignments перед bulkAssign (mass):',
          orderId,
          JSON.parse(JSON.stringify(assignments)),
        )
        console.log('🔍 Order data for assignments check:', {
          orderId,
          assignmentsLength: assignments.length,
          orderDesigners: order.designers,
          orderPrintOperators: order.print_operators,
          orderEngravingOperators: order.engraving_operators,
          orderWorkshopWorkers: order.workshop_workers,
        })
        if (orderId && assignments.length > 0) {
          console.log('✅ Sending bulk assign request...')
          await bulkAssignOrderAssignments(orderId, assignments)
          console.log('✅ Bulk assign completed for order', orderId)
        } else {
          console.log('❌ Skipping bulk assign:', {
            orderId,
            assignmentsLength: assignments.length,
          })
        }
      }
      toast.show('Проект и заказы с назначениями успешно созданы!')
    } else {
      // Одиночный заказ
      // Очищаем назначения для выключенных стадий
      if (!form.has_design_stage) designAssignments.splice(0)
      if (!form.has_print_stage) printAssignments.splice(0)
      if (!form.has_engraving_stage) engravingAssignments.splice(0)
      if (!form.has_workshop_stage) workshopAssignments.splice(0)
      if (!validateForm()) return
      const orderData: Record<string, unknown> = {
        client_id: form.client_id,
        product_id: form.product_id,
        quantity: form.quantity,
        has_design_stage: form.has_design_stage,
        has_print_stage: form.has_print_stage,
        has_workshop_stage: form.has_workshop_stage,
        has_engraving_stage: form.has_engraving_stage,
        deadline: form.deadline,
        price: form.price,
      }
      // Добавляем project_id только если выбран валидный проект
      if (form.project_id && form.project_id > 0) {
        orderData.project_id = form.project_id
      }

      const createdOrder = await createOrder(orderData)
      const orderId = createdOrder?.data?.id || createdOrder?.id
      const assignments = prepareOrderAssignments({
        designAssignments: designAssignments,
        printAssignments: printAssignments,
        engravingAssignments: engravingAssignments,
        workshopAssignments: workshopAssignments,
      })
      console.log(
        'Assignments перед bulkAssign (single):',
        orderId,
        JSON.parse(JSON.stringify(assignments)),
      )
      if (orderId && assignments.length > 0) {
        await bulkAssignOrderAssignments(orderId, assignments)
      }
      toast.show('Заказ и назначения успешно созданы!')
    }

    emit('submit')
    emit('close')
  } catch (e) {
    console.error('Ошибка при создании:', e)
    toast.show('Ошибка при создании заказа или назначений', 'error')
  } finally {
    loading.value = false
  }
}

function handleDelete() {
  if (props.order && confirm('Удалить этот заказ?')) {
    remove(props.order.id)
      .then(() => {
        toast.show('Заказ удалён!')
        emit('delete', props.order!.id)
        emit('close')
      })
      .catch((error) => {
        console.error('Ошибка удаления:', error)
        toast.show('Ошибка при удалении заказа', 'error')
      })
  }
}

function addOrder() {
  const newOrder = reactive({
    product_id: 0,
    quantity: 1,
    deadline: null,
    price: null,
    has_design_stage: false,
    has_print_stage: false,
    has_workshop_stage: false,
    has_engraving_stage: false,
    designer_id: null,
    print_operator_id: null,
    workshop_worker_id: null,
    engraver_id: null, // Added engraver_id for engraving stage
    designers: reactive([] as any[]),
    print_operators: reactive([] as any[]),
    engraving_operators: reactive([] as any[]),
    workshop_workers: reactive([] as any[]),
    _wasEdited: reactive({}),
  })
  orders.value.push(newOrder)

  // Watcher будет автоматически создан в основном watch(orders, ...)
  // Если product_id уже выбран, автозаполнение произойдет автоматически через watcher
}

function removeOrder(index: number) {
  if (orders.value.length > 1) {
    orders.value.splice(index, 1)
  }
}

function addDesigner(order: any) {
  order.designers.push({
    id: Date.now(),
    user_id: null,
    user: undefined,
    has_design_stage: order.has_design_stage,
    has_print_stage: order.has_print_stage,
    has_engraving_stage: order.has_engraving_stage,
    has_workshop_stage: order.has_workshop_stage,
  })
}
function addPrintOperator(order: any) {
  order.print_operators.push({
    id: Date.now(),
    user_id: null,
    user: undefined,
    has_design_stage: order.has_design_stage,
    has_print_stage: order.has_print_stage,
    has_engraving_stage: order.has_engraving_stage,
    has_workshop_stage: order.has_workshop_stage,
  })
}
function addEngravingOperator(order: any) {
  order.engraving_operators.push({
    id: Date.now(),
    user_id: null,
    user: undefined,
    has_design_stage: order.has_design_stage,
    has_print_stage: order.has_print_stage,
    has_engraving_stage: order.has_engraving_stage,
    has_workshop_stage: order.has_workshop_stage,
  })
}
function addWorkshopWorker(order: any) {
  order.workshop_workers.push({
    id: Date.now(),
    user_id: null,
    user: undefined,
    has_design_stage: order.has_design_stage,
    has_print_stage: order.has_print_stage,
    has_engraving_stage: order.has_engraving_stage,
    has_workshop_stage: order.has_workshop_stage,
  })
}

// При выборе продукта инициализируем стадии и назначенных в форме
watch(
  () => form.product_id,
  (newProductId) => {
    const prod = products.value.find((p) => p.id === newProductId)
    const assignments = Array.isArray(prod?.assignments) ? prod.assignments : []
    designAssignments.splice(
      0,
      designAssignments.length,
      ...assignments
        .filter((a) => a.role_type === 'designer')
        .map((a) => ({
          ...a,
          user_id: a.user_id || (a.user && a.user.id) || null,
        })),
    )
    printAssignments.splice(
      0,
      printAssignments.length,
      ...assignments
        .filter((a) => a.role_type === 'print_operator')
        .map((a) => ({
          ...a,
          user_id: a.user_id || (a.user && a.user.id) || null,
        })),
    )
    engravingAssignments.splice(
      0,
      engravingAssignments.length,
      ...assignments
        .filter((a) => a.role_type === 'engraving_operator')
        .map((a) => ({
          ...a,
          user_id: a.user_id || (a.user && a.user.id) || null,
        })),
    )
    workshopAssignments.splice(
      0,
      workshopAssignments.length,
      ...assignments
        .filter((a) => a.role_type === 'workshop_worker')
        .map((a) => ({
          ...a,
          user_id: a.user_id || (a.user && a.user.id) || null,
        })),
    )
  },
  { immediate: true },
)

function addDesignAssignment() {
  designAssignments.push({
    id: Date.now(),
    user_id: null,
    user: undefined,
    has_design_stage: form.has_design_stage,
    has_print_stage: form.has_print_stage,
    has_engraving_stage: form.has_engraving_stage,
    has_workshop_stage: form.has_workshop_stage,
  })
}
function addPrintAssignment() {
  printAssignments.push({
    id: Date.now(),
    user_id: null,
    user: undefined,
    has_design_stage: form.has_design_stage,
    has_print_stage: form.has_print_stage,
    has_engraving_stage: form.has_engraving_stage,
    has_workshop_stage: form.has_workshop_stage,
  })
}
function addEngravingAssignment() {
  engravingAssignments.push({
    id: Date.now(),
    user_id: null,
    user: undefined,
    has_design_stage: form.has_design_stage,
    has_print_stage: form.has_print_stage,
    has_engraving_stage: form.has_engraving_stage,
    has_workshop_stage: form.has_workshop_stage,
  })
}
function addWorkshopAssignment() {
  workshopAssignments.push({
    id: Date.now(),
    user_id: null,
    user: undefined,
    has_design_stage: form.has_design_stage,
    has_print_stage: form.has_print_stage,
    has_engraving_stage: form.has_engraving_stage,
    has_workshop_stage: form.has_workshop_stage,
  })
}

watchEffect(() => {
  console.log('users.value:', users.value)
  console.log(
    'Дизайнеры:',
    users.value.filter((u) => u.role === 'designer'),
  )
  console.log(
    'Печатники:',
    users.value.filter((u) => u.role === 'print_operator'),
  )
  console.log(
    'Гравировщики:',
    users.value.filter((u) => u.role === 'engraving_operator'),
  )
  console.log(
    'Цех:',
    users.value.filter((u) => u.role === 'workshop_worker'),
  )
})
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
