import { ref, reactive } from 'vue'
import { API_CONFIG } from '../config/api'
import { handle401Error } from '../utils/auth'
import type { Order, OrderForm, OrderUpdateForm, StageUpdateForm } from '../types/order'

// Создаем синглтон экземпляр
const orders = ref<Order[]>([])
const pagination = reactive({
  data: [] as Order[],
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 30,
})
const loading = ref(false)
const error = ref('')
const sortBy = ref('id')
const sortOrder = ref<'asc' | 'desc'>('desc')

// Вспомогательная функция для обработки ответов с проверкой 401
const handleResponse = async (response: Response) => {
  if (response.status === 401) {
    handle401Error('Сессия истекла. Необходимо войти в систему заново.')
    throw new Error('Сессия истекла. Необходимо войти в систему заново.')
  }

  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`)
  }

  return response.json()
}

const fetchOrders = async (
  page = 1,
  sort_by = sortBy.value,
  sort_order = sortOrder.value,
  stage?: string,
  is_archived?: boolean,
  search?: string,
  assignment_status?: string,
  per_page = pagination.per_page,
) => {
  loading.value = true
  error.value = ''
  try {
    const params = {
      page,
      sort_by,
      sort_order,
      stage,
      is_archived,
      search,
      assignment_status,
      per_page,
    }
    const res = await getAll(params)
    pagination.data = res.data || []
    pagination.current_page = res.current_page || 1
    pagination.last_page = res.last_page || 1
    pagination.total = res.total || 0
    pagination.per_page = res.per_page || 30
    orders.value = res.data || []
    console.log('orders updated', orders.value)
  } catch (e: unknown) {
    const errorMessage = e instanceof Error ? e.message : 'Ошибка загрузки заказов'
    error.value = errorMessage
  } finally {
    loading.value = false
  }
}

const fetchAllOrdersForKanban = async (assignment_status?: string) => {
  loading.value = true
  error.value = ''
  try {
    const params: any = {
      page: 1,
      sort_by: 'id',
      sort_order: 'desc',
      per_page: 1000, // Большое количество для загрузки всех заказов
    }
    if (assignment_status) params.assignment_status = assignment_status
    const res = await getAll(params)
    orders.value = res.data || []
    console.log('all orders for kanban updated', orders.value.length)
  } catch (e: unknown) {
    const errorMessage = e instanceof Error ? e.message : 'Ошибка загрузки заказов'
    error.value = errorMessage
  } finally {
    loading.value = false
  }
}

const createOrder = async (data: OrderForm) => {
  loading.value = true
  try {
    const result = await create(data)
    await fetchOrders(pagination.current_page)
    return result // Возвращаем результат создания заказа (например, { id: ... })
  } finally {
    loading.value = false
  }
}

const updateOrder = async (id: number, data: OrderUpdateForm) => {
  loading.value = true
  try {
    await update(id, data)
    await fetchOrders(pagination.current_page)
  } finally {
    loading.value = false
  }
}

const removeOrder = async (id: number) => {
  loading.value = true
  try {
    await remove(id)
    if (pagination.data.length === 1 && pagination.current_page > 1) {
      await fetchOrders(pagination.current_page - 1)
    } else {
      await fetchOrders(pagination.current_page)
    }
  } finally {
    loading.value = false
  }
}

const getAll = async (params?: {
  project_id?: number
  stage?: string
  page?: number
  sort_by?: string
  sort_order?: string
  is_archived?: boolean
  per_page?: number
  search?: string
  assignment_status?: string
}) => {
  const queryParams = new URLSearchParams()
  if (params?.project_id) queryParams.append('project_id', params.project_id.toString())
  if (params?.stage) queryParams.append('stage', params.stage)
  if (params?.page) queryParams.append('page', params.page.toString())
  if (params?.sort_by) queryParams.append('sort_by', params.sort_by)
  if (params?.sort_order) queryParams.append('sort_order', params.sort_order)
  if (params?.is_archived !== undefined)
    queryParams.append('is_archived', params.is_archived.toString())
  if (params?.per_page) queryParams.append('per_page', params.per_page.toString())
  if (params?.search) queryParams.append('search', params.search)
  if (params?.assignment_status) queryParams.append('assignment_status', params.assignment_status)

  const query = queryParams.toString() ? `?${queryParams.toString()}` : ''
  const url = `${API_CONFIG.BASE_URL}/orders${query}`

  const response = await fetch(url, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      'Cache-Control': 'no-cache',
      Pragma: 'no-cache',
    },
  })

  if (!response.ok) throw new Error('Ошибка загрузки заказов')

  const result = await response.json()
  return result
}

const getById = async (id: number) => {
  const response = await fetch(`${API_CONFIG.BASE_URL}/orders/${id}`, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
  })

  return await handleResponse(response)
}

const create = async (data: OrderForm) => {
  const response = await fetch(`${API_CONFIG.BASE_URL}/orders`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
    body: JSON.stringify(data),
  })

  if (!response.ok) {
    const errorText = await response.text()
    console.error('❌ Create error:', {
      status: response.status,
      statusText: response.statusText,
      error: errorText,
      data: data,
    })

    // Пытаемся извлечь сообщение об ошибке из JSON
    let errorMessage = `Ошибка создания заказа: ${response.status} ${response.statusText}`
    try {
      const errorData = JSON.parse(errorText)
      if (errorData.message) {
        errorMessage = errorData.message
      } else if (errorData.errors) {
        // Если есть ошибки валидации, показываем их
        const validationErrors = Object.values(errorData.errors).flat()
        errorMessage = `Ошибки валидации: ${validationErrors.join(', ')}`
      }
    } catch {
      // Если не удалось распарсить JSON, используем оригинальный текст
      errorMessage = errorText
    }

    throw new Error(errorMessage)
  }
  return await response.json()
}

const update = async (id: number, data: OrderUpdateForm) => {
  const response = await fetch(`${API_CONFIG.BASE_URL}/orders/${id}`, {
    method: 'PUT',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      'Cache-Control': 'no-cache',
      Pragma: 'no-cache',
    },
    body: JSON.stringify(data),
  })

  if (!response.ok) {
    const errorText = await response.text()
    console.error('❌ Update error:', {
      status: response.status,
      statusText: response.statusText,
      error: errorText,
    })
    throw new Error(`Ошибка обновления заказа: ${response.status} ${response.statusText}`)
  }

  const result = await response.json()
  return result
}

const updateStage = async (id: number, data: StageUpdateForm) => {
  const response = await fetch(`${API_CONFIG.BASE_URL}/orders/${id}/stage`, {
    method: 'PUT',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      'Cache-Control': 'no-cache',
      Pragma: 'no-cache',
    },
    body: JSON.stringify(data),
  })

  if (!response.ok) {
    const errorText = await response.text()
    console.error('❌ Stage update error:', {
      status: response.status,
      statusText: response.statusText,
      error: errorText,
    })

    // Пытаемся извлечь сообщение об ошибке из JSON
    let errorMessage = `Ошибка обновления статуса: ${response.status} ${response.statusText}`
    try {
      const errorData = JSON.parse(errorText)
      if (errorData.message) {
        errorMessage = errorData.message
      }
    } catch {
      // Если не удалось распарсить JSON, используем оригинальный текст
      errorMessage = errorText
    }

    throw new Error(errorMessage)
  }

  const result = await response.json()
  return result
}

const moveToNextStage = async (id: number) => {
  const response = await fetch(`${API_CONFIG.BASE_URL}/orders/${id}/next-stage`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
  })

  if (!response.ok) throw new Error('Ошибка перехода к следующей стадии')
  return await response.json()
}

const remove = async (id: number) => {
  const response = await fetch(`${API_CONFIG.BASE_URL}/orders/${id}`, {
    method: 'DELETE',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
  })

  if (!response.ok) {
    const errorText = await response.text()
    console.error('❌ Delete error:', {
      status: response.status,
      statusText: response.statusText,
      error: errorText,
    })
    throw new Error(`Ошибка удаления заказа: ${response.status} ${response.statusText}`)
  }
  return await response.json()
}

const createProjectWithOrders = async (data: {
  title: string
  client_id: number
  orders: unknown[]
}) => {
  const response = await fetch(`${API_CONFIG.BASE_URL}/projects`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
    },
    body: JSON.stringify(data),
  })
  if (!response.ok) {
    const errorText = await response.text()
    console.error('❌ Create project error:', {
      status: response.status,
      statusText: response.statusText,
      error: errorText,
      data: data,
    })

    // Пытаемся извлечь сообщение об ошибке из JSON
    let errorMessage = `Ошибка создания проекта с заказами: ${response.status} ${response.statusText}`
    try {
      const errorData = JSON.parse(errorText)
      if (errorData.message) {
        errorMessage = errorData.message
      } else if (errorData.errors) {
        // Если есть ошибки валидации, показываем их
        const validationErrors = Object.values(errorData.errors).flat()
        errorMessage = `Ошибки валидации: ${validationErrors.join(', ')}`
      }
    } catch {
      // Если не удалось распарсить JSON, используем оригинальный текст
      errorMessage = errorText
    }

    throw new Error(errorMessage)
  }
  return await response.json()
}

// --- SINGLETON STATE ---
const controller = {
  orders,
  pagination,
  loading,
  error,
  sortBy,
  sortOrder,
  fetchOrders,
  fetchAllOrdersForKanban,
  createOrder,
  updateOrder,
  removeOrder,
  getAll,
  getById,
  create,
  update,
  updateStage,
  moveToNextStage,
  remove,
  createProjectWithOrders,
}

export function OrderController() {
  return controller
}
