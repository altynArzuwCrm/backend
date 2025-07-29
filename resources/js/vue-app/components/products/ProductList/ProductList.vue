<template>
  <div class="product-list flex flex-col h-full">
    <div class="flex justify-end items-center mb-3">
      <UIButton v-if="canCreateEdit()" @click="showCreateModal = true" variant="primary"
        >Добавить товар</UIButton
      >
    </div>

    <div class="flex-1 flex flex-col min-h-0">
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
      </div>
      <div class="bg-white border border-gray-200">
        <table class="w-full border-collapse border-gray-300 text-gray-900 text-base">
          <thead class="bg-gray-50 text-gray-900 font-medium">
            <tr ref="columnsHeader">
              <th
                v-for="col in columns"
                :key="col.key"
                @click="col.sortable ? setSort(col.key, props.search) : null"
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
              v-for="(product, index) in pagination.data"
              :key="product.id"
              :class="[
                'cursor-pointer border-b border-gray-100',
                index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                'hover:bg-blue-50 transition-colors',
              ]"
              @click="editProduct(product)"
              style="height: 44px"
            >
              <td
                v-for="col in columns"
                :key="col.key"
                class="border-r border-gray-200 px-3 py-2 text-base whitespace-nowrap align-middle"
              >
                <template v-if="col.key === 'id'">
                  <span class="font-mono text-gray-600">{{ product.id }}</span>
                </template>
                <template v-else-if="col.key === 'name'">
                  <span class="font-medium text-gray-900">{{ product.name }}</span>
                </template>
                <!-- ✅ НОВОЕ! Отображение доступных стадий -->
                <template v-else-if="col.key === 'available_stages'">
                  <div class="flex flex-wrap gap-1">
                    <span
                      v-for="stage in product.available_stages"
                      :key="stage.id"
                      class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium text-white"
                      :style="{ backgroundColor: stage.color }"
                    >
                      <div
                        v-if="stage.pivot && stage.pivot.is_default"
                        class="w-1.5 h-1.5 bg-white rounded-full"
                        title="Стадия по умолчанию"
                      ></div>
                      {{ stage.display_name }}
                    </span>
                    <!-- Показываем, если нет доступных стадий -->
                    <span
                      v-if="!product.available_stages || product.available_stages.length === 0"
                      class="text-gray-400 text-xs italic"
                    >
                      Стадии не настроены
                    </span>
                  </div>
                </template>
                <template v-else-if="col.key === 'designer'">
                  <AssignmentDisplay
                    :assignments="
                      product.assignments
                        ? product.assignments.filter((a) => a.role_type === 'designer')
                        : []
                    "
                  />
                </template>
                <template v-else-if="col.key === 'print_operator'">
                  <AssignmentDisplay
                    :assignments="
                      product.assignments
                        ? product.assignments.filter((a) => a.role_type === 'print_operator')
                        : []
                    "
                  />
                </template>
                <template v-else-if="col.key === 'engraving_operator'">
                  <AssignmentDisplay
                    :assignments="
                      product.assignments
                        ? product.assignments.filter((a) => a.role_type === 'engraving_operator')
                        : []
                    "
                  />
                </template>
                <template v-else-if="col.key === 'workshop_worker'">
                  <AssignmentDisplay
                    :assignments="
                      product.assignments
                        ? product.assignments.filter((a) => a.role_type === 'workshop_worker')
                        : []
                    "
                  />
                </template>
                <template v-else-if="col.key === 'created_at'">
                  <span class="text-gray-600">{{ formatDate(product.created_at) }}</span>
                </template>
              </td>
            </tr>

            <tr v-if="loading">
              <td :colspan="columns.length" class="px-3 py-8 text-center text-gray-500">
                Загрузка товаров...
              </td>
            </tr>
            <tr v-if="error">
              <td :colspan="columns.length" class="px-3 py-8 text-center text-red-500">
                {{ error }}
              </td>
            </tr>
            <tr v-if="!loading && !error && pagination.data.length === 0">
              <td :colspan="columns.length" class="px-3 py-8 text-center text-gray-500">
                {{ props.search ? 'Товары не найдены' : 'Товары отсутствуют' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination
        v-if="
          !loading &&
          !error &&
          pagination &&
          typeof pagination.last_page !== 'undefined' &&
          pagination.total > 0
        "
        :current-page="pagination.current_page"
        :last-page="pagination.last_page"
        @go-to-page="goToPage"
        class="mt-1 shrink-0"
      />
    </div>

    <ProductFormModal
      v-if="showCreateModal"
      :product="null"
      @close="showCreateModal = false"
      @submit="handleCreateProduct"
    />

    <ProductFormModal
      v-if="showEditModal"
      :product="editingProduct"
      @close="showEditModal = false"
      @submit="handleUpdateProduct"
      @delete="handleDeleteProduct"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, nextTick } from 'vue'
import Sortable from 'sortablejs'
import UIButton from '@/components/ui/UIButton.vue'
import Pagination from '@/components/users/UserList/Pagination.vue'
import ProductFormModal from './ProductFormModal.vue'
import AssignmentDisplay from './AssignmentDisplay.vue'
import productController from '@/controllers/productControllerInstance'
import type { Product, ProductForm } from '@/types/product'
import { canCreateEdit } from '@/utils/permissions'
import { toast } from '@/stores/toast'

const props = defineProps({
  search: { type: String, default: '' },
})

const SORT_KEY = 'productList_sortBy'
const ORDER_KEY = 'productList_sortOrder'
const COLUMNS_KEY = 'productList_columns'

const savedSortBy = localStorage.getItem(SORT_KEY)
const savedSortOrder = localStorage.getItem(ORDER_KEY)
const savedColumns = localStorage.getItem(COLUMNS_KEY)

const defaultColumns = [
  { key: 'id', label: 'ID', sortable: true },
  { key: 'name', label: 'Название', sortable: true },
  { key: 'available_stages', label: 'Стадии', sortable: false }, // ✅ НОВАЯ КОЛОНКА
  { key: 'designer', label: 'Дизайнер', sortable: false },
  { key: 'print_operator', label: 'Печатник', sortable: false },
  { key: 'engraving_operator', label: 'Гравировщик', sortable: false },
  { key: 'workshop_worker', label: 'Работник цеха', sortable: false },
  { key: 'created_at', label: 'Создано', sortable: true },
]

const columns = ref(savedColumns ? JSON.parse(savedColumns) : defaultColumns)

const { pagination, loading, error, fetchProducts, sortBy, sortOrder, update, remove } =
  productController

if (savedSortBy && sortBy.value !== savedSortBy) sortBy.value = savedSortBy
if (savedSortOrder && sortOrder.value !== savedSortOrder) sortOrder.value = savedSortOrder

const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingProduct = ref<Product | null>(null)
const currentPage = ref(1)
const columnsHeader = ref<HTMLElement | null>(null)

const allowedPerPage = [10, 20, 50, 100, 200, 500]
const perPage = ref(30)
function validatePerPage(val) {
  if (!allowedPerPage.includes(val)) return 30
  return val
}
function changePerPage() {
  perPage.value = validatePerPage(perPage.value)
  goToPage(1)
}
watch(perPage, (newVal) => {
  perPage.value = validatePerPage(newVal)
  goToPage(1)
})

function setSort(key: string, search = '') {
  if (sortBy.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = key
    sortOrder.value = 'asc'
  }
  localStorage.setItem(SORT_KEY, sortBy.value)
  localStorage.setItem(ORDER_KEY, sortOrder.value)
  fetchProducts(1, search, sortBy.value, sortOrder.value, perPage.value)
}

function resetSettings() {
  columns.value = [...defaultColumns]
  localStorage.setItem(COLUMNS_KEY, JSON.stringify(columns.value))
  sortBy.value = 'id'
  sortOrder.value = 'asc'
  localStorage.setItem(SORT_KEY, sortBy.value)
  localStorage.setItem(ORDER_KEY, sortOrder.value)
  currentPage.value = 1
  fetchProducts(1, props.search, sortBy.value, sortOrder.value, perPage.value)
}

function goToPage(page: number) {
  if (!pagination || typeof pagination.last_page === 'undefined') return
  if (page < 1 || page > pagination.last_page) return
  currentPage.value = page
  fetchProducts(page, props.search, sortBy.value, sortOrder.value, perPage.value)
}

function editProduct(product: Product) {
  if (!canCreateEdit()) return

  console.log('Editing product:', {
    id: product.id,
    name: product.name,
    designers: product.designers,
    print_operators: product.print_operators,
    engraving_operators: product.engraving_operators,
    workshop_workers: product.workshop_workers,
  })

  editingProduct.value = product
  showEditModal.value = true
}

async function handleCreateProduct(newProduct: Product) {
  showCreateModal.value = false
  currentPage.value = 1
  fetchProducts(currentPage.value, props.search, sortBy.value, sortOrder.value, perPage.value)
}

async function handleUpdateProduct(updatedProduct: Product) {
  console.log('handleUpdateProduct called with:', updatedProduct)

  // Преобразуем Product в ProductForm для API
  const productForm: ProductForm = {
    name: updatedProduct.name,
    designer_id: updatedProduct.designer_id,
    print_operator_id: updatedProduct.print_operator_id,
    workshop_worker_id: updatedProduct.workshop_worker_id,
    has_design_stage: updatedProduct.has_design_stage,
    has_print_stage: updatedProduct.has_print_stage,
    has_engraving_stage: updatedProduct.has_engraving_stage,
    has_workshop_stage: updatedProduct.has_workshop_stage,
    designers: updatedProduct.designers || [],
    print_operators: updatedProduct.print_operators || [],
    engraving_operators: updatedProduct.engraving_operators || [],
    workshop_workers: updatedProduct.workshop_workers || [],
  }

  console.log('Sending to API:', productForm)

  await update(updatedProduct.id, productForm)
  showEditModal.value = false

  console.log('Product updated, fetching products again...')
  fetchProducts(currentPage.value, props.search, sortBy.value, sortOrder.value, perPage.value)
}

async function handleDeleteProduct(productId: number) {
  try {
    await remove(productId)
    showEditModal.value = false
    editingProduct.value = null
    if (pagination?.data?.length === 1 && currentPage.value > 1) {
      currentPage.value--
    }
  } catch (e: any) {
    // Если ошибка 404 — просто закрыть модалку и обновить список
    if (e.message && e.message.includes('Ошибка удаления товара')) {
      toast.show('Товар уже был удалён')
      showEditModal.value = false
      editingProduct.value = null
      // Обновить список, чтобы убрать "мертвый" товар
      await fetchProducts(
        currentPage.value,
        props.search,
        sortBy.value,
        sortOrder.value,
        perPage.value,
      )
    } else {
      toast.show('Ошибка при удалении товара')
    }
  }
}

function formatDate(date: string | null | undefined) {
  if (!date) return '-'
  const d = new Date(date)
  return d.toLocaleString('ru-RU', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

watch(
  () => props.search,
  (newVal) => {
    currentPage.value = 1
    fetchProducts(1, newVal, sortBy.value, sortOrder.value, perPage.value)
  },
)

onMounted(async () => {
  // Принудительно сбрасываем productList_columns в localStorage, чтобы обновить колонки
  localStorage.removeItem('productList_columns')
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
  fetchProducts(currentPage.value, props.search, sortBy.value, sortOrder.value, perPage.value)
})
</script>
