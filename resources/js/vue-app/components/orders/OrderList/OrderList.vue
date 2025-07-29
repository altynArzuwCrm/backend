<template>
  <div class="order-list flex flex-col">
    <div class="flex items-center justify-between py-2 px-4 bg-white border-b mb-2">
      <div class="flex items-center gap-6 text-gray-700 text-base font-medium">
        <div class="flex items-center gap-1">
          <span class="text-gray-500 font-semibold">Всего:</span>
          <span class="text-blue-600 font-bold">{{ pagination?.total || 0 }}</span>
        </div>
        <div class="flex items-center gap-1">
          <span class="text-gray-500 font-semibold">Страницы:</span>
          <span class="text-blue-600 font-bold">{{ pagination?.last_page || 1 }}</span>
        </div>
      </div>
      <div
        class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-1 shadow-sm border border-gray-200"
      >
        <span class="text-gray-600 font-semibold">На странице:</span>
        <select
          v-model.number="perPage"
          @change="changePerPage"
          class="bg-white border border-gray-300 rounded px-2 py-1 focus:ring-2 focus:ring-blue-400 focus:outline-none text-gray-900 font-semibold"
        >
          <option v-for="n in [10, 20, 50, 100, 200, 500]" :key="n" :value="n">{{ n }}</option>
        </select>
      </div>
      <div class="flex items-center gap-3">
        <!-- ✅ НОВОЕ! Фильтр стадий - будет загружаться динамически -->
        <select
          v-model="selectedStage"
          @change="filterByStage"
          class="w-40 h-10 px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 text-base focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200"
        >
          <option value="">Все стадии</option>
          <!-- TODO: Загрузить стадии из API -->
          <option value="draft">Черновик</option>
          <option value="design">Дизайн</option>
          <option value="print">Печать</option>
          <option value="workshop">Цех</option>
          <option value="final">Финальный</option>
          <option value="completed">Завершен</option>
          <option value="cancelled">Отменен</option>
        </select>
        <select
          v-model="selectedArchive"
          @change="filterByArchive"
          class="w-40 h-10 px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 text-base focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200"
        >
          <option value="">Все заказы</option>
          <option value="active">Активные</option>
          <option value="archived">Архивные</option>
        </select>
        <div v-if="canCreateEdit()" class="flex gap-2">
          <UIButton
            @click="showCreateModal = true"
            variant="primary"
            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg px-6 py-2 text-base transition-colors duration-200 shadow-none border-none"
          >
            Добавить заказ
          </UIButton>
        </div>
      </div>
    </div>
    <div class="flex-1 flex flex-col min-h-0">
      <div class="bg-white border border-gray-200">
        <table class="w-full border-collapse border-gray-300 text-gray-900 text-base">
          <thead class="bg-gray-50 text-gray-900 font-medium">
            <tr ref="columnsHeader">
              <th
                v-for="col in columns"
                :key="col.key"
                @click="col.sortable ? setSort(col.key) : null"
                :class="[
                  'border border-gray-200 px-3 py-2 cursor-pointer hover:bg-gray-100 text-left whitespace-nowrap',
                  col.sortable ? 'select-none' : '',
                  col.key === 'created_at' ? 'w-[170px]' : '',
                ]"
              >
                <div class="flex items-center justify-between">
                  <span>{{ col.label }}</span>
                  <span v-if="col.sortable && sortBy === col.key" class="ml-1 text-blue-600">
                    {{ sortOrder === 'asc' ? '↑' : '↓' }}
                  </span>
                </div>
              </th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="(item, index) in orders"
              :key="item.id"
              :class="[
                'cursor-pointer border-b border-gray-100',
                index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                'hover:bg-blue-50 transition-colors',
              ]"
              style="height: 56px"
              @click="openDetailsModal(item)"
            >
              <td
                v-for="col in columns"
                :key="col.key"
                class="border-r border-gray-200 px-4 py-4 text-base whitespace-nowrap align-middle"
              >
                <template v-if="col.key === 'id'">
                  <span class="font-mono text-gray-600">{{ item.id }}</span>
                </template>
                <template v-else-if="col.key === 'product'">
                  <span
                    class="font-medium text-gray-900 cursor-pointer"
                    @click.stop="openDetailsModal(item)"
                    >{{ item.product?.name || '-' }}</span
                  >
                </template>
                <template v-else-if="col.key === 'client'">
                  <span class="text-gray-700">
                    {{ item.client?.name || `(client_id: ${item.client_id})` || '-' }}
                  </span>
                </template>
                <template v-else-if="col.key === 'quantity'">
                  <span class="text-gray-900">{{ item.quantity }}</span>
                </template>
                <!-- ✅ НОВОЕ! Отображение текущей стадии с динамической цветовой индикацией -->
                <template v-else-if="col.key === 'stage'">
                  <div class="flex flex-col items-center gap-1">
                    <span
                      v-if="item.current_stage_info"
                      class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full text-white cursor-pointer"
                      :style="{ backgroundColor: item.current_stage_info.color }"
                    >
                      <div class="w-1.5 h-1.5 bg-white rounded-full opacity-80"></div>
                      {{ item.current_stage_info.display_name }}
                    </span>
                    <!-- ❌ FALLBACK: Старая система (для обратной совместимости) -->
                    <span
                      v-else
                      :class="getStatusClass(item.stage || item.current_stage || '')"
                      class="inline-flex px-2 py-1 text-xs font-semibold rounded-full cursor-pointer"
                    >
                      {{ getStatusText(item.stage || item.current_stage || '') }}
                    </span>
                    <span
                      v-if="item.is_archived"
                      class="inline-flex px-2 py-1 text-xs font-normal rounded-full bg-gray-100 text-gray-500 border border-gray-200"
                    >
                      Архив
                    </span>
                  </div>
                </template>
                <template v-else-if="col.key === 'deadline'">
                  <span class="text-gray-700">{{ formatDate(item.deadline) }}</span>
                </template>
                <template v-else-if="col.key === 'price'">
                  <span class="text-blue-500 font-semibold">
                    {{ item.price ?? '-' }} <span class="text-sm">TMT</span>
                  </span>
                </template>
                <template v-else-if="col.key === 'created_at'">
                  <span class="text-gray-600">{{ formatDate(item.created_at) }}</span>
                </template>
              </td>
            </tr>

            <tr v-if="loading">
              <td :colspan="columns.length" class="px-3 py-8 text-center text-gray-500 text-base">
                Загрузка заказов...
              </td>
            </tr>
            <tr v-if="!loading && orders.length === 0">
              <td :colspan="columns.length" class="px-3 py-8 text-center text-gray-500 text-base">
                Заказы не найдены
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination
        v-if="!loading && pagination.total > 0"
        :current-page="pagination.current_page"
        :last-page="pagination.last_page"
        @go-to-page="changePage"
        class="mt-1 shrink-0"
      />
    </div>

    <OrderFormModal
      v-if="showCreateModal"
      @close="showCreateModal = false"
      @submit="handleOrderCreated"
    />
    <ProjectFormModal
      v-if="showCreateProjectModal"
      @close="showCreateProjectModal = false"
      @submit="handleProjectCreated"
    />
    <OrderDetailsModal
      v-if="detailsOrderId"
      :order-id="detailsOrderId"
      @close="closeDetailsModal"
      @updated="handleOrderUpdatedFromModal"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import Sortable from 'sortablejs'
import OrderFormModal from './OrderFormModal.vue'
import ProjectFormModal from './ProjectFormModal.vue'
import OrderDetailsModal from './OrderDetailsModal.vue'
import Pagination from '@/components/users/UserList/Pagination.vue'
import UIButton from '@/components/ui/UIButton.vue'
import { OrderController } from '@/controllers/OrderController'
import type { Order } from '@/types/order'
import { canCreateEdit } from '@/utils/permissions'

const { getAll, removeOrder, orders, pagination, loading, fetchOrders } = OrderController()

const SORT_KEY = 'orderList_sortBy'
const ORDER_KEY = 'orderList_sortOrder'
const COLUMNS_KEY = 'orderList_columns'

const savedSortBy = localStorage.getItem(SORT_KEY)
const savedSortOrder = localStorage.getItem(ORDER_KEY)
const savedColumns = localStorage.getItem(COLUMNS_KEY)

const defaultColumns = [
  { key: 'id', label: 'ID', sortable: true },
  { key: 'product', label: 'Товар', sortable: false },
  { key: 'client', label: 'Клиент', sortable: false },
  { key: 'quantity', label: 'Кол-во', sortable: true },
  { key: 'stage', label: 'Статус', sortable: true },
  { key: 'deadline', label: 'Дедлайн', sortable: true },
  { key: 'price', label: 'Цена', sortable: true },
  { key: 'created_at', label: 'Создано', sortable: true },
]

const columns = ref(savedColumns ? JSON.parse(savedColumns) : defaultColumns)

const sortBy = ref(savedSortBy || 'id')
const sortOrder = ref(savedSortOrder || 'asc')
const columnsHeader = ref<HTMLElement | null>(null)

const showCreateModal = ref(false)
const showCreateProjectModal = ref(false)
const showDetailsModal = ref(false)
const detailsOrderId = ref<number | null>(null)

const selectedStage = ref('')
const selectedArchive = ref('')
const selectedAssignmentStatus = ref('')

const allowedPerPage = [10, 20, 50, 100, 200, 500]
const perPage = ref(30)
function validatePerPage(val) {
  if (!allowedPerPage.includes(val)) return 30
  return val
}

function changePerPage() {
  perPage.value = validatePerPage(perPage.value)
  loadOrders(1)
}

watch(perPage, (newVal) => {
  perPage.value = validatePerPage(newVal)
  loadOrders(1)
})

watch(orders, (val) => {
  console.log('orders changed in OrderList', val)
})

function loadOrders(page = 1) {
  const isArchived =
    selectedArchive.value === 'archived'
      ? true
      : selectedArchive.value === 'active'
        ? false
        : undefined

  fetchOrders(
    page,
    sortBy.value,
    sortOrder.value,
    selectedStage.value || undefined,
    isArchived,
    undefined,
    selectedAssignmentStatus.value || undefined,
    perPage.value,
  )
}

function setSort(key: string) {
  // Разрешённые поля для сортировки
  const allowedSortFields = ['id', 'quantity', 'stage', 'deadline', 'price', 'created_at']
  if (!allowedSortFields.includes(key)) return
  if (sortBy.value === key) sortOrder.value = (sortOrder.value as string) === 'asc' ? 'desc' : 'asc'
  else {
    sortBy.value = key
    sortOrder.value = 'asc'
  }
  localStorage.setItem(SORT_KEY, sortBy.value)
  localStorage.setItem(ORDER_KEY, sortOrder.value)
  loadOrders(1)
}

function resetSettings() {
  columns.value = [...defaultColumns]
  localStorage.setItem(COLUMNS_KEY, JSON.stringify(columns.value))
  sortBy.value = 'id'
  sortOrder.value = 'asc'
  localStorage.setItem(SORT_KEY, sortBy.value)
  localStorage.setItem(ORDER_KEY, sortOrder.value)
  loadOrders(1)
}

function changePage(page: number) {
  loadOrders(page)
}

async function deleteOrder(id: number) {
  if (confirm('Удалить заказ?')) {
    try {
      await removeOrder(id)
      // Синглтон контроллер автоматически обновит состояние
    } catch (e) {
      console.error('Ошибка удаления:', e)
    }
  }
}

function handleOrderCreated() {
  showCreateModal.value = false
  loadOrders() // Немедленно обновляем список
}

function handleProjectCreated() {
  showCreateProjectModal.value = false
  loadOrders() // Немедленно обновляем список
}

function getStatusClass(stage: string) {
  return (
    {
      draft: 'bg-gray-100 text-gray-800',
      design: 'bg-blue-100 text-blue-800',
      print: 'bg-yellow-100 text-yellow-800',
      engraving: 'bg-orange-100 text-orange-800',
      workshop: 'bg-purple-100 text-purple-800',
      final: 'bg-green-100 text-green-800',
      completed: 'bg-green-100 text-green-800',
      cancelled: 'bg-red-100 text-red-800',
    }[stage] || 'bg-gray-100 text-gray-800'
  )
}

function getStatusText(stage: string) {
  return (
    {
      draft: 'Черновик',
      design: 'Дизайн',
      print: 'Печать',
      engraving: 'Гравировка',
      workshop: 'Цех',
      final: 'Финальный',
      completed: 'Завершен',
      cancelled: 'Отменен',
    }[stage] || stage
  )
}

function formatDate(date: string) {
  if (!date) return '-'
  return new Date(date).toLocaleString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function filterByStage() {
  loadOrders(1)
}

function filterByArchive() {
  loadOrders(1)
}

function filterByAssignmentStatus() {
  loadOrders(1)
}

function openDetailsModal(order: Order) {
  detailsOrderId.value = order.id
  showDetailsModal.value = true
}

function closeDetailsModal() {
  showDetailsModal.value = false
  detailsOrderId.value = null
}

function handleOrderUpdatedFromModal() {
  loadOrders() // Немедленно обновляем список
}

defineExpose({ loadOrders })

onMounted(async () => {
  await nextTick()
  if (columnsHeader.value) {
    Sortable.create(columnsHeader.value, {
      animation: 150,
      direction: 'horizontal',
      onEnd(evt) {
        const oldIndex = evt.oldIndex
        const newIndex = evt.newIndex
        if (oldIndex === undefined || newIndex === undefined) return
        const moved = columns.value.splice(oldIndex, 1)[0]
        columns.value.splice(newIndex, 0, moved)
        localStorage.setItem(COLUMNS_KEY, JSON.stringify(columns.value))
      },
    })
  }
  loadOrders()
})
</script>
