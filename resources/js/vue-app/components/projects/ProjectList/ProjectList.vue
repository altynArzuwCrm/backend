<template>
  <div class="project-list flex flex-col h-full">
    <div class="flex justify-end items-center mb-3">
      <UIButton v-if="canCreateEdit()" @click="showCreateModal = true" variant="primary"
        >Добавить проект</UIButton
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
              v-for="(project, index) in pagination.data"
              :key="project.id"
              :class="[
                'cursor-pointer border-b border-gray-100',
                index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                'hover:bg-blue-50 transition-colors',
              ]"
              @click="openProjectDetails(project)"
              style="height: 44px"
            >
              <template v-for="col in columns" :key="col.key">
                <td
                  :class="[
                    'border-r border-gray-200 px-3 py-2 text-base whitespace-nowrap align-middle',
                    col.key === 'client' ? 'max-w-[220px]' : '',
                  ]"
                >
                  <template v-if="col.key === 'id'">
                    <span class="font-mono text-gray-600">{{ project.id }}</span>
                  </template>
                  <template v-else-if="col.key === 'title'">
                    <span class="font-medium text-gray-900">{{ project.title }}</span>
                  </template>
                  <template v-else-if="col.key === 'client'">
                    <span class="text-gray-700 block truncate">
                      {{
                        getClientNameById(project.orders?.[0]?.client_id) ||
                        (project.client
                          ? `${project.client.name}${project.client.company_name ? ` (${project.client.company_name})` : ''}`
                          : '-')
                      }}
                    </span>
                  </template>
                  <template v-else-if="col.key === 'deadline'">
                    <span class="text-gray-700">{{ formatDate(project.deadline) }}</span>
                  </template>
                  <template v-else-if="col.key === 'total_price'">
                    <span class="text-blue-500 font-semibold">
                      {{ project.total_price ?? '-' }} <span class="text-sm">TMT</span>
                    </span>
                  </template>
                  <template v-else-if="col.key === 'payment_amount'">
                    <span :class="getPaymentClass(project)">
                      {{ project.payment_amount ?? '-' }} <span class="text-sm">TMT</span>
                    </span>
                  </template>
                  <template v-else-if="col.key === 'created_at'">
                    <span class="text-gray-600">{{ formatDate(project.created_at) }}</span>
                  </template>
                  <template v-else>
                    {{ project[col.key] }}
                  </template>
                </td>
              </template>
            </tr>

            <tr v-if="loading">
              <td :colspan="columns.length" class="px-3 py-8 text-center text-gray-500">
                Загрузка проектов...
              </td>
            </tr>
            <tr v-if="error">
              <td :colspan="columns.length" class="px-3 py-8 text-center text-red-500">
                {{ error }}
              </td>
            </tr>
            <tr v-if="!loading && !error && pagination.data.length === 0">
              <td :colspan="columns.length" class="px-3 py-8 text-center text-gray-500">
                {{ props.search ? 'Проекты не найдены' : 'Проекты отсутствуют' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination
        v-if="!loading && !error && pagination.total > 0"
        :current-page="pagination.current_page"
        :last-page="pagination.last_page"
        @go-to-page="goToPage"
        class="mt-1 shrink-0"
      />
    </div>

    <ProjectFormModal
      v-if="showCreateModal"
      :project="null"
      @close="showCreateModal = false"
      @submit="handleCreateProject"
    />

    <ProjectDetailsModal
      v-if="showDetailsModal && selectedProject"
      :project="getSelectedProject()"
      :orders="selectedProjectOrders"
      :comments="selectedProjectComments"
      :assignments="selectedProjectAssignments"
      @close="closeProjectDetails"
      @update-project="onUpdateProject"
      @add-comment="onAddComment"
      @edit-comment="onEditComment"
      @open-order="onOpenOrder"
      @order-created="onOrderCreated"
      @delete-comment="onDeleteComment"
    />

    <OrderDetailsModal v-if="showOrderModal" :order-id="selectedOrderId" @close="closeOrderModal" />
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, nextTick, computed } from 'vue'
import Sortable from 'sortablejs'
import UIButton from '@/components/ui/UIButton.vue'
import Pagination from '@/components/users/UserList/Pagination.vue'
import ProjectFormModal from './ProjectFormModal.vue'
import ProjectDetailsModal from './ProjectDetailsModal.vue'
import OrderDetailsModal from '@/components/orders/OrderList/OrderDetailsModal.vue'
import projectController from '@/controllers/projectControllerInstance'
import type { Project } from '@/types/project'
import { canCreateEdit, canViewPrices } from '@/utils/permissions'

const props = defineProps({
  search: { type: String, default: '' },
})

const {
  projects,
  pagination,
  loading,
  error,
  fetchProjects,
  sortBy,
  sortOrder,
  create,
  update,
  remove,
} = projectController

const defaultColumns = [
  { key: 'id', label: 'ID', sortable: true },
  { key: 'title', label: 'Название', sortable: true },
  { key: 'client', label: 'Клиент', sortable: false },
  { key: 'deadline', label: 'Дедлайн', sortable: true },
]
if (canViewPrices()) {
  defaultColumns.push({ key: 'total_price', label: 'Сумма', sortable: true })
  defaultColumns.push({ key: 'payment_amount', label: 'Оплачено', sortable: true })
}
defaultColumns.push({ key: 'created_at', label: 'Создано', sortable: true })

const SORT_KEY = 'projectList_sortBy'
const ORDER_KEY = 'projectList_sortOrder'
const COLUMNS_KEY = 'projectList_columns'

const savedSortBy = localStorage.getItem(SORT_KEY)
const savedSortOrder = localStorage.getItem(ORDER_KEY)
const savedColumns = localStorage.getItem(COLUMNS_KEY)

const columns = ref(savedColumns ? JSON.parse(savedColumns) : defaultColumns)

if (savedSortBy && sortBy.value !== savedSortBy) sortBy.value = savedSortBy
if (savedSortOrder && sortOrder.value !== savedSortOrder) sortOrder.value = savedSortOrder

const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingProject = ref<Project | null>(null)
const currentPage = ref(1)
const columnsHeader = ref<HTMLElement | null>(null)

const showDetailsModal = ref(false)
function getSelectedProject(): any {
  return selectedProject.value
}

const selectedProject = ref<Project | null>(null)
const selectedProjectOrders = ref<any[]>([])
const selectedProjectComments = ref<any[]>([])
const selectedProjectAssignments = ref([])

const showOrderModal = ref(false)
const selectedOrderId = ref<number | null>(null)

const allClients = ref<any[]>([])

function setSort(key: string, search = '') {
  if (sortBy.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = key
    sortOrder.value = 'asc'
  }
  localStorage.setItem(SORT_KEY, sortBy.value)
  localStorage.setItem(ORDER_KEY, sortOrder.value)
  fetchProjects(1, search, sortBy.value, sortOrder.value, perPage.value)
}

function goToPage(page: number) {
  if (page < 1 || page > pagination.last_page) return
  currentPage.value = page
  fetchProjects(page, props.search, sortBy.value, sortOrder.value, perPage.value)
}

function editProject(project: Project) {
  editingProject.value = project
  showEditModal.value = true
}

async function handleCreateProject(newProject: Project) {
  await create(newProject)
  showCreateModal.value = false
  currentPage.value = 1
}

async function handleUpdateProject(updatedProject: Project) {
  await update(updatedProject.id, updatedProject)
  showEditModal.value = false
  fetchProjects(currentPage.value, props.search, sortBy.value, sortOrder.value, perPage.value)
}

async function handleDeleteProject(projectId: number) {
  await remove(projectId)
  showEditModal.value = false
  editingProject.value = null
  if (pagination?.data?.length === 1 && currentPage.value > 1) {
    currentPage.value--
  }
  await fetchProjects(currentPage.value, props.search, sortBy.value, sortOrder.value, perPage.value)
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

function getPaymentClass(project: Project) {
  if (project.total_price == null || project.payment_amount == null) return 'text-gray-400'
  const percent = project.payment_amount / project.total_price
  if (percent < 0.5) return 'text-red-600 font-semibold'
  if (percent < 0.9) return 'text-orange-500 font-semibold'
  return 'text-green-600 font-semibold'
}

async function openProjectDetails(project: Project) {
  const res = await fetch(`/api/projects/${project.id}`, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
  })
  const freshProject = await res.json()
  selectedProject.value = freshProject
  selectedProjectOrders.value = freshProject.orders || []
  selectedProjectComments.value = await getProjectComments(project.id)

  const assignRes = await fetch('/api/assignments', {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
  })
  const json = assignRes.ok ? await assignRes.json() : { data: [] }
  const allAssignments = Array.isArray(json) ? json : json.data || []
  selectedProjectAssignments.value = allAssignments.filter((a: any) =>
    selectedProjectOrders.value.some((order) => order.id === a.order_id),
  )

  showDetailsModal.value = true
}
function closeProjectDetails() {
  showDetailsModal.value = false
  selectedProject.value = null
}
async function onUpdateProject(updatedProject: any) {
  await fetchProjects(currentPage.value, props.search, sortBy.value, sortOrder.value)
  if (selectedProject.value && updatedProject && updatedProject.id === selectedProject.value.id) {
    selectedProject.value = { ...selectedProject.value, ...updatedProject }
  }
}
async function onAddComment(text: string) {
  if (!selectedProject.value) return
  await addProjectComment(selectedProject.value.id, text)
  selectedProjectComments.value = await getProjectComments(selectedProject.value.id)
}
async function onDeleteComment(commentId: number) {
  if (!selectedProject.value) return

  try {
    console.log('Начинаем удаление комментария:', commentId)
    await deleteProjectComment(commentId)
    console.log('Комментарий успешно удален, обновляем список')
    selectedProjectComments.value = await getProjectComments(selectedProject.value.id)
    console.log('Список комментариев обновлен')
  } catch (error) {
    console.error('Ошибка при удалении комментария:', error)
  }
}
function onEditComment(payload: any) {}
function onOpenOrder(order: any) {
  selectedOrderId.value = order.id
  showOrderModal.value = true
}
function closeOrderModal() {
  showOrderModal.value = false
  selectedOrderId.value = null
}
async function onOrderCreated(order: any) {
  if (!selectedProject.value) return
  const res = await fetch(`/api/projects/${selectedProject.value.id}`, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
  })
  const freshProject = await res.json()
  selectedProject.value = freshProject
  selectedProjectOrders.value = freshProject.orders || []
}

async function getProjectComments(projectId: number) {
  const res = await fetch(`/api/comments?project_id=${projectId}`, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
  })
  if (!res.ok) throw new Error('Ошибка загрузки комментариев')
  return await res.json()
}
async function addProjectComment(projectId: number, text: string) {
  const res = await fetch('/api/comments', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
    body: JSON.stringify({ text, project_id: projectId }),
  })
  if (!res.ok) throw new Error('Ошибка при добавлении комментария')
  return await res.json()
}
async function deleteProjectComment(commentId: number) {
  console.log('Удаление комментария с ID:', commentId)
  const res = await fetch(`/api/comments/${commentId}`, {
    method: 'DELETE',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
  })
  console.log('Статус ответа:', res.status)
  console.log('Заголовки ответа:', res.headers)

  if (!res.ok) {
    let errorMessage = 'Ошибка при удалении комментария'
    try {
      const errorData = await res.json()
      console.log('Данные ошибки:', errorData)
      errorMessage = errorData.message || errorMessage
    } catch (e) {
      console.log('Не удалось прочитать JSON ошибки:', e)
    }
    throw new Error(errorMessage)
  }

  try {
    const responseData = await res.json()
    console.log('Успешный ответ:', responseData)
    return responseData
  } catch (e) {
    console.log('Ответ не содержит JSON, но статус успешный')
    return { success: true }
  }
}

function getClientNameById(clientId: number | undefined) {
  if (!clientId) return '-'
  const client = (allClients.value as any[]).find((c) => c.id === clientId)
  if (!client) return '-'
  return client.company_name ? `${client.name} (${client.company_name})` : client.name
}

watch(
  () => props.search,
  (newVal) => {
    currentPage.value = 1
    fetchProjects(1, newVal, sortBy.value, sortOrder.value, perPage.value)
  },
)

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
        // Сохраняем порядок колонок
        localStorage.setItem(COLUMNS_KEY, JSON.stringify(columns.value))
      },
    })
  }
  fetchProjects(currentPage.value, props.search, sortBy.value, sortOrder.value, perPage.value)
  try {
    const res = await fetch('/api/clients/all', {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      },
    })
    const data = await res.json()
    allClients.value = Array.isArray(data) ? data : ([] as any[])
  } catch (e) {
    allClients.value = []
  }
})
</script>
