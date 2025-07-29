<template>
  <transition name="modal-fade">
    <div
      class="fixed inset-0 z-50 bg-black bg-opacity-40 flex items-center justify-center"
      v-if="props.project"
      @click="onOverlayClick"
    >
      <transition name="modal-scale">
        <div
          class="relative w-[1100px] max-w-[98vw] h-[90vh] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden"
          @click.stop
        >
          <button
            @click="emit('close')"
            class="absolute top-6 right-6 text-3xl text-gray-400 hover:text-red-500 transition font-bold z-10"
          >
            ✕
          </button>
          <div class="flex-1 flex flex-row h-full min-h-0">
            <!-- Левая часть: детали проекта -->
            <div
              class="w-1/2 bg-gradient-to-br from-blue-200 via-purple-200 to-cyan-200 p-10 flex flex-col gap-8 border-r border-gray-200 min-w-[340px] overflow-y-auto"
            >
              <div>
                <div class="flex items-center gap-4 mb-2">
                  <div class="text-3xl font-extrabold text-gray-900 tracking-tight">
                    Проект #{{ props.project?.id }}
                  </div>
                  <span
                    v-if="props.project"
                    :class="[
                      'inline-block px-4 py-1 rounded-full text-base font-bold shadow',
                      statusBadge(props.project.status || ''),
                    ]"
                  >
                    {{ getStatusText(props.project.status || '') }}
                  </span>
                </div>
                <div class="text-lg text-gray-500 font-medium mb-6">
                  {{ props.project?.title || '' }}
                </div>
                <div
                  class="bg-white rounded-2xl shadow-lg p-8 mb-8 border border-blue-100 flex flex-col gap-4"
                >
                  <div class="text-2xl font-extrabold text-blue-900 mb-2">Детали проекта</div>
                  <div class="flex flex-col gap-2">
                    <div
                      class="grid grid-cols-[140px_1fr_40px] items-center gap-x-2 text-base text-gray-800"
                    >
                      <span class="font-semibold">Название:</span>
                      <EditableField
                        v-if="props.project"
                        :model-value="props.project.title ?? ''"
                        type="text"
                        :required="true"
                        @save="(val) => updateProjectField('title', val)"
                        class="w-64"
                      />
                      <span></span>
                    </div>
                    <div
                      class="grid grid-cols-[140px_1fr_40px] items-center gap-x-2 text-base text-gray-800"
                    >
                      <span class="font-semibold">Клиент:</span>
                      <div>
                        <div v-if="!showClientSelect" class="flex items-center group">
                          <span class="truncate">{{
                            getClientNameById(getClientId(props.orders[0]?.client)) ||
                            (props.project?.client
                              ? `${props.project.client.name}${props.project.client.company_name ? ` (${props.project.client.company_name})` : ''}`
                              : '-')
                          }}</span>
                          <button
                            @click="showClientSelect = true"
                            class="ml-2 p-1 rounded hover:bg-gray-100 opacity-0 group-hover:opacity-100 transition-opacity"
                            title="Изменить клиента"
                          >
                            <svg
                              xmlns="http://www.w3.org/2000/svg"
                              class="h-4 w-4 text-gray-400"
                              fill="none"
                              viewBox="0 0 24 24"
                              stroke="currentColor"
                            >
                              <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a4 4 0 01-1.414.828l-4 1a1 1 0 01-1.263-1.263l1-4a4 4 0 01.828-1.414z"
                              />
                            </svg>
                          </button>
                        </div>
                        <div v-if="showClientSelect" class="flex items-center gap-2 mt-1">
                          <Vue3Select
                            v-model="selectedClientIdProxy"
                            :options="allClients"
                            label="name"
                            :reduce="clientIdReduce"
                            placeholder="Выберите клиента"
                            :clearable="true"
                            :searchable="true"
                            class="w-64"
                          />
                          <button
                            @click="confirmClient"
                            class="p-1 rounded hover:bg-green-100 text-green-500"
                            title="Подтвердить"
                          >
                            <svg
                              xmlns="http://www.w3.org/2000/svg"
                              class="h-4 w-4"
                              fill="none"
                              viewBox="0 0 24 24"
                              stroke="currentColor"
                            >
                              <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                              />
                            </svg>
                          </button>
                          <button
                            @click="cancelClient"
                            class="p-1 rounded hover:bg-red-100 text-red-500"
                            title="Отмена"
                          >
                            <svg
                              xmlns="http://www.w3.org/2000/svg"
                              class="h-4 w-4"
                              fill="none"
                              viewBox="0 0 24 24"
                              stroke="currentColor"
                            >
                              <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                              />
                            </svg>
                          </button>
                        </div>
                      </div>
                      <span></span>
                    </div>
                    <div
                      class="grid grid-cols-[140px_1fr_40px] items-center gap-x-2 text-base text-gray-800"
                    >
                      <span class="font-semibold">Дедлайн:</span>
                      <div>
                        <div v-if="!showDeadlineInput" class="flex items-center group">
                          <span>{{
                            formatDateTime(
                              props.project?.deadline ? String(props.project.deadline) : '',
                            )
                          }}</span>
                          <button
                            @click="startDeadlineEdit"
                            class="ml-2 p-1 rounded hover:bg-gray-100 opacity-0 group-hover:opacity-100 transition-opacity"
                            title="Изменить дедлайн"
                          >
                            <svg
                              xmlns="http://www.w3.org/2000/svg"
                              class="h-4 w-4 text-gray-400"
                              fill="none"
                              viewBox="0 0 24 24"
                              stroke="currentColor"
                            >
                              <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a4 4 0 01-1.414.828l-4 1a1 1 0 01-1.263-1.263l1-4a4 4 0 01.828-1.414z"
                              />
                            </svg>
                          </button>
                        </div>
                        <div v-if="showDeadlineInput" class="flex items-center gap-2 mt-1">
                          <flatPickr
                            v-model="tempDeadline"
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
                            class="w-48 text-gray-700 text-base p-2 border border-gray-300 rounded-md flatpickr-uiinput focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors duration-200"
                          />
                          <button
                            @click="confirmDeadline"
                            class="p-1 rounded hover:bg-green-100 text-green-500"
                            title="Подтвердить"
                          >
                            <svg
                              xmlns="http://www.w3.org/2000/svg"
                              class="h-4 w-4"
                              fill="none"
                              viewBox="0 0 24 24"
                              stroke="currentColor"
                            >
                              <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                              />
                            </svg>
                          </button>
                          <button
                            @click="cancelDeadline"
                            class="p-1 rounded hover:bg-red-100 text-red-500"
                            title="Отмена"
                          >
                            <svg
                              xmlns="http://www.w3.org/2000/svg"
                              class="h-4 w-4"
                              fill="none"
                              viewBox="0 0 24 24"
                              stroke="currentColor"
                            >
                              <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                              />
                            </svg>
                          </button>
                        </div>
                      </div>
                      <span></span>
                    </div>
                    <div
                      v-if="canViewPrices()"
                      class="grid grid-cols-[140px_1fr_40px] items-center gap-x-2 text-base text-gray-800"
                    >
                      <span class="font-semibold">Сумма:</span>
                      <EditableField
                        v-if="props.project"
                        :model-value="props.project.total_price ?? 0"
                        type="number"
                        :min="0"
                        @save="(val) => updateProjectField('total_price', val)"
                        class="w-32"
                      />
                      <span class="ml-1">TMT</span>
                    </div>
                    <div
                      v-if="canViewPrices()"
                      class="grid grid-cols-[140px_1fr_40px] items-center gap-x-2 text-base text-gray-800"
                    >
                      <span class="font-semibold">Оплачено:</span>
                      <EditableField
                        v-if="props.project"
                        :model-value="props.project.payment_amount ?? 0"
                        type="number"
                        :min="0"
                        @save="(val) => updateProjectField('payment_amount', val)"
                        class="w-32"
                      />
                      <span class="ml-1">TMT</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Правая часть: комментарии, связанные заказы и создание заказа -->
            <div class="w-1/2 flex flex-col gap-4 p-10 bg-[#f8fafc] min-w-[340px] overflow-y-auto">
              <!-- Комментарии -->
              <div class="bg-white rounded-xl shadow p-6 mb-4 border border-blue-100 flex flex-col">
                <div class="text-2xl font-extrabold text-blue-900 mb-2">Комментарии</div>
                <div>
                  <ul class="space-y-3">
                    <li
                      v-for="comment in comments"
                      :key="comment.id"
                      class="flex gap-3 items-start group relative"
                    >
                      <div
                        class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-purple-400 flex items-center justify-center text-white font-extrabold text-base shadow"
                      >
                        <img
                          v-if="userImageUrls[comment.user?.name]"
                          :src="userImageUrls[comment.user?.name]"
                          :alt="comment.user?.name"
                          class="w-8 h-8 rounded-full object-cover"
                        />
                        <span v-else>
                          {{ comment.user?.name ? comment.user.name[0] : '?' }}
                        </span>
                      </div>
                      <div
                        class="bg-white rounded-xl p-3 flex-1 shadow-sm border border-blue-100 relative"
                      >
                        <button
                          @click="$emit('delete-comment', comment.id)"
                          title="Удалить"
                          class="absolute top-8 right-2 opacity-0 group-hover:opacity-100 transition-opacity duration-150 w-6 h-6 flex items-center justify-center text-gray-300 hover:text-red-400"
                        >
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 20 20"
                            class="w-4 h-4"
                          >
                            <path
                              stroke="currentColor"
                              stroke-width="1.5"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M7.5 9v3.5m5-3.5V12.5M4.5 6.5h11M8.5 4.5h3a1 1 0 0 1 1 1v1h-5v-1a1 1 0 0 1 1-1Zm-3 2v9a2 2 0 0 0 2 2h5a2 2 0 0 0 2-2v-9"
                            />
                          </svg>
                        </button>
                        <div class="flex items-center gap-2 mb-0.5">
                          <span class="font-bold text-sm text-gray-900">{{
                            comment.user?.name
                          }}</span>
                          <span v-if="comment.user?.roles && comment.user.roles.length">
                            <span
                              v-for="role in comment.user.roles"
                              :key="typeof role === 'string' ? role : role.name"
                              class="text-[10px] rounded px-2 py-0.5 font-semibold mr-1"
                              :class="
                                getRoleBadgeClass(typeof role === 'string' ? role : role.name)
                              "
                            >
                              {{
                                getRoleLabel(
                                  typeof role === 'string' ? role : role.display_name || role.name,
                                )
                              }}
                            </span>
                          </span>
                          <span v-else>
                            <span
                              class="text-[10px] rounded px-2 py-0.5 font-semibold"
                              :class="getRoleBadgeClass(comment.user?.role || '')"
                            >
                              {{ getRoleLabel(comment.user?.role || '') }}
                            </span>
                          </span>
                          <span class="text-[10px] text-gray-400 ml-auto">{{
                            formatDateTime(comment.created_at ? String(comment.created_at) : '')
                          }}</span>
                        </div>
                        <div class="text-sm text-gray-700 leading-snug">{{ comment.text }}</div>
                      </div>
                    </li>
                  </ul>
                </div>
                <!-- Минималистичная форма комментария -->
                <div
                  class="bg-white rounded-xl shadow border border-blue-200 p-3 flex flex-col gap-2 mt-2"
                >
                  <input
                    v-model="newComment"
                    @focus="commentFocused = true"
                    @blur="onCommentBlur"
                    @keydown.enter.prevent="addComment"
                    type="text"
                    class="w-full border-none outline-none text-base text-gray-800 bg-transparent px-2 py-2"
                    placeholder="Добавить комментарий..."
                  />
                  <div
                    v-if="commentFocused || newComment.trim()"
                    class="flex gap-2 mt-1 justify-end"
                  >
                    <button
                      @click="addComment"
                      type="button"
                      class="rounded-full bg-blue-300 hover:bg-blue-400 text-white text-xs font-bold px-4 py-1 shadow transition"
                    >
                      ОТПРАВИТЬ
                    </button>
                    <button
                      @click="cancelComment"
                      type="button"
                      class="rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold px-4 py-1 shadow transition"
                    >
                      ОТМЕНА
                    </button>
                  </div>
                </div>
              </div>
              <!-- Связанные заказы -->
              <div
                class="bg-white border border-blue-100 rounded-2xl shadow-lg p-6 mb-4 flex flex-col gap-4"
              >
                <div class="text-2xl font-extrabold text-blue-900 mb-2">Связанные заказы</div>
                <div v-if="orders.length === 0" class="text-gray-500">Нет связанных заказов</div>
                <div v-else class="flex flex-col gap-3">
                  <div
                    v-for="order in orders"
                    :key="order.id"
                    :class="[
                      'rounded-xl shadow p-4 border border-blue-100 flex flex-col gap-2',
                      order.stage ? orderStatusBadge(order.stage) : 'bg-gray-100',
                    ]"
                  >
                    <div class="flex items-center justify-between">
                      <div>
                        <span class="font-semibold text-blue-700 mr-2">{{ order.id }}</span>
                        <span
                          v-if="order.stage"
                          :class="[
                            'inline-block align-middle px-2 py-0.5 rounded-full text-xs font-semibold mr-2',
                            orderStatusBadge(order.stage),
                          ]"
                        >
                          {{ orderStatusText(order.stage) }}
                        </span>
                        <span class="text-gray-800 align-middle">{{
                          order.product?.name || '—'
                        }}</span>
                      </div>
                      <button
                        v-if="canCreateEdit() || isMyOrder(order)"
                        @click="$emit('open-order', order)"
                        class="text-blue-500 font-semibold hover:underline text-sm"
                      >
                        Открыть
                      </button>
                    </div>

                    <div class="text-xs text-gray-400 mt-1">
                      {{ formatDateTime(order.created_at ? String(order.created_at) : '') }}
                    </div>
                  </div>
                </div>
              </div>
              <!-- Создать заказ -->
              <div
                v-if="canCreateEdit()"
                class="bg-white border border-blue-100 rounded-2xl shadow-lg p-6 flex flex-col gap-4"
              >
                <div class="text-2xl font-extrabold text-blue-900 mb-2">Создать заказ</div>
                <OrderFormModal
                  v-if="showOrderForm"
                  :project-id="props.project.id"
                  @close="showOrderForm = false"
                  @submit="onOrderCreated"
                />
                <button
                  v-else
                  @click="showOrderForm = true"
                  class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded shadow font-bold transition border border-blue-600"
                >
                  Новый заказ
                </button>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import EditableField from '@/components/ui/EditableField.vue'
import OrderFormModal from '@/components/orders/OrderList/OrderFormModal.vue'
import Vue3Select from 'vue3-select'
import flatPickr from 'vue-flatpickr-component'
import 'flatpickr/dist/flatpickr.css'
import { Russian } from 'flatpickr/dist/l10n/ru.js'
import { canCreateEdit, canViewPrices, getCurrentUser } from '@/utils/permissions'
import { getUserImageUrl } from '@/utils/user'

interface Client {
  id: number
  name: string
  company_name?: string // Added company_name to Client interface
}
interface Project {
  id: number
  title: string
  client: Client | null
  deadline: string | null
  budget: number | null
  total_price?: number | null
  payment_amount?: number | null
  status?: string
}
interface Order {
  id: number
  product?: { name: string }
  created_at?: string
  stage?: string
  client?: { name: string } // Added client property to Order interface
}
interface Comment {
  id: number
  text: string
  user?: { name: string; role?: string; roles?: string[] } // Added roles to Comment interface
  created_at: string
}

const emit = defineEmits([
  'close',
  'update-project',
  'add-comment',
  'edit-comment',
  'delete-comment',
  'open-order',
  'order-created',
])

function getClientId(client: any): number | undefined {
  return (client as any)?.id
}

function getUserImageUrlLocal(user: any): string | undefined {
  return (user as any)?.image_url
}

const props = defineProps<{
  project: Project
  orders: Order[]
  comments: Comment[]
  assignments: { order_id: number; user_id: number }[]
}>()

const showOrderForm = ref(false)
const newComment = ref('')
const commentFocused = ref(false)

const allClients = ref<Client[]>([])
const selectedClientId = ref<number | null>(null)
const tempDeadline = ref<string | null>(null)
const showClientSelect = ref(false)
const showDeadlineInput = ref(false)

// Прокси для v-model, чтобы не было null
const selectedClientIdProxy = computed({
  get: () => selectedClientId.value ?? '',
  set: (val: string | number) => {
    selectedClientId.value = typeof val === 'string' ? (val ? Number(val) : null) : val
  },
})

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const clientIdReduce = (client: any) => client.id

onMounted(async () => {
  // Загрузить всех клиентов для селекта
  const res = await fetch('/api/clients/all', {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
  })
  const data = await res.json()
  allClients.value = Array.isArray(data) ? data : []
  if (props.project?.client?.id) selectedClientId.value = props.project.client.id
  if (props.project?.deadline) tempDeadline.value = props.project.deadline
})

watch(
  () => props.project,
  (newProject) => {
    if (newProject?.client?.id) selectedClientId.value = newProject.client.id
    if (newProject?.deadline) tempDeadline.value = newProject.deadline
  },
  { immediate: true },
)

function onOverlayClick(e: MouseEvent) {
  if (e.target === e.currentTarget) emit('close')
}

type ProjectField = keyof Project | 'client_id' | 'total_price' | 'payment_amount'
async function updateProjectField(field: ProjectField, value: unknown) {
  if (!props.project) return
  // PATCH-запрос на /api/projects/{id}
  const payload: Record<string, unknown> = {}
  payload[field] = value
  const res = await fetch(`/api/projects/${props.project.id}`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
    body: JSON.stringify(payload),
  })
  if (res.ok) {
    // После успешного PATCH — получить свежий проект с сервера
    const fresh = await fetch(`/api/projects/${props.project.id}`, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      },
    })
    if (fresh.ok) {
      const freshProject = await fresh.json()
      emit('update-project', freshProject)
    }
  }
}

function formatDateTime(date: string | null | undefined) {
  if (!date) return '-'
  const d = new Date(String(date))
  return d.toLocaleString('ru-RU', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function addComment() {
  if (!newComment.value.trim()) return
  emit('add-comment', newComment.value)
  newComment.value = ''
  commentFocused.value = false
}

function onCommentBlur() {
  setTimeout(() => {
    if (!newComment.value.trim()) commentFocused.value = false
  }, 100)
}
function cancelComment() {
  newComment.value = ''
  commentFocused.value = false
}

function onOrderCreated(order: Order) {
  emit('order-created', order)
  showOrderForm.value = false
}

function getStatusText(status: string) {
  const map: Record<string, string> = {
    active: 'Активен',
    completed: 'Завершён',
    paused: 'Пауза',
    cancelled: 'Отменён',
  }
  return map[status] || status
}

function statusBadge(status: string) {
  const map: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    completed: 'bg-blue-100 text-blue-800',
    paused: 'bg-yellow-100 text-yellow-800',
    cancelled: 'bg-red-100 text-red-800',
  }
  return map[status] || 'bg-gray-100 text-gray-800'
}

function startDeadlineEdit() {
  if (!props.project) return
  tempDeadline.value = props.project.deadline || ''
  showDeadlineInput.value = true
}
async function confirmDeadline() {
  await updateProjectField('deadline', tempDeadline.value)
  showDeadlineInput.value = false
}
function cancelDeadline() {
  showDeadlineInput.value = false
  tempDeadline.value = props.project.deadline || null
}
async function confirmClient() {
  if (selectedClientId.value !== null && selectedClientId.value !== undefined) {
    await updateProjectField('client_id', selectedClientId.value)
  }
  showClientSelect.value = false
}
function cancelClient() {
  showClientSelect.value = false
  selectedClientId.value = props.project?.client?.id || null
}

function orderStatusBadge(status: string) {
  switch (status) {
    case 'draft':
      return 'bg-gray-100 text-gray-700'
    case 'design':
      return 'bg-blue-100 text-blue-700'
    case 'print':
      return 'bg-yellow-100 text-yellow-700'
    case 'engraving':
      return 'bg-orange-100 text-orange-700'
    case 'workshop':
      return 'bg-purple-100 text-purple-700'
    case 'final':
      return 'bg-green-100 text-green-700'
    case 'completed':
      return 'bg-emerald-200 text-emerald-800'
    case 'cancelled':
      return 'bg-red-100 text-red-700'
    default:
      return 'bg-gray-100 text-gray-700'
  }
}
function orderStatusText(status: string) {
  return (
    {
      draft: 'Черновик',
      design: 'Дизайн',
      print: 'Печать',
      engraving: 'Гравировка',
      workshop: 'Цех',
      final: 'Финальный',
      completed: 'Завершён',
      cancelled: 'Отменён',
    }[status] || status
  )
}

// Проверяем, является ли заказ заказом текущего пользователя
function isMyOrder(order: Order): boolean {
  const currentUser = getCurrentUser()
  if (!currentUser) return false
  if (!props.assignments) return false
  return props.assignments.some((a) => a.order_id === order.id && a.user_id === currentUser.id)
}

// Добавляем функцию для поиска имени клиента по client_id
function getClientNameById(clientId: number | undefined) {
  if (!clientId) return '-'
  const client = allClients.value.find((c) => c.id === clientId)
  if (!client) return '-'
  return client.company_name ? `${client.name} (${client.company_name})` : client.name
}

function getRoleBadgeClass(role: string) {
  switch (role) {
    case 'admin':
      return 'bg-red-100 text-red-800'
    case 'manager':
      return 'bg-purple-100 text-purple-800'
    case 'designer':
      return 'bg-blue-100 text-blue-800'
    case 'print_worker':
      return 'bg-yellow-100 text-yellow-800'
    case 'engraver':
      return 'bg-orange-100 text-orange-800'
    case 'workshop_worker':
      return 'bg-green-100 text-green-800'
    case 'client':
      return 'bg-gray-100 text-gray-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

function getRoleLabel(role: string) {
  switch (role) {
    case 'admin':
      return 'Администратор'
    case 'manager':
      return 'Менеджер'
    case 'designer':
      return 'Дизайнер'
    case 'print_worker':
      return 'Печатник'
    case 'engraver':
      return 'Гравер'
    case 'workshop_worker':
      return 'Цехник'
    case 'client':
      return 'Клиент'
    default:
      return role
  }
}

// Исправление getUserImageUrl (ожидает Promise)
// Используем v-if="userImageUrls[comment.user.name]" и асинхронно загружаем аватарки
const userImageUrls = ref<Record<string, string>>({})
async function loadUserImageUrl(user: any) {
  if (!user || !user.name) return
  if (!userImageUrls.value[user.name]) {
    try {
      const url = await getUserImageUrl(user)
      userImageUrls.value[user.name] = url
    } catch {
      userImageUrls.value[user.name] = ''
    }
  }
}
watch(
  () => props.comments,
  (newComments) => {
    newComments.forEach((c) => loadUserImageUrl(c.user))
  },
  { immediate: true, deep: true },
)
</script>
