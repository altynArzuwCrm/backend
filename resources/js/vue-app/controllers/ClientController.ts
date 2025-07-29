import { ref, reactive } from 'vue'
import {
  getClients,
  createClient,
  updateClient,
  deleteClient,
  createClientContact,
  updateClientContact,
  deleteClientContact,
} from '@/services/api'
import type { Client } from '@/types/client'

export function ClientController() {
  const clients = ref<Client[]>([])
  const pagination = reactive({
    data: [] as Client[],
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 30,
  })
  const loading = ref(false)
  const error = ref('')
  // Загружаем настройки сортировки из localStorage
  const sortBy = ref(localStorage.getItem('clientList_sortBy') || 'id')
  const sortOrder = ref<'asc' | 'desc'>(
    (localStorage.getItem('clientList_sortOrder') as 'asc' | 'desc') || 'asc',
  )

  async function fetchClients(
    page = 1,
    search = '',
    sort_by = sortBy.value,
    sort_order = sortOrder.value,
    per_page = pagination.per_page,
  ) {
    loading.value = true
    error.value = ''
    try {
      const res = await getClients({ page, search, sort_by, sort_order, per_page })
      console.log('🔍 API Response:', res)

      // Проверяем структуру ответа
      if (res.data && Array.isArray(res.data)) {
        pagination.data = res.data
        pagination.current_page = res.current_page || res.meta?.current_page || 1
        pagination.last_page = res.last_page || res.meta?.last_page || 1
        pagination.total = res.total || res.meta?.total || 0
        pagination.per_page = res.per_page || res.meta?.per_page || per_page || 30
        clients.value = res.data
      } else {
        // Если данные приходят в другом формате
        pagination.data = Array.isArray(res) ? res : []
        pagination.current_page = 1
        pagination.last_page = 1
        pagination.total = Array.isArray(res) ? res.length : 0
        pagination.per_page = per_page || 30
        clients.value = Array.isArray(res) ? res : []
      }

      console.log('🔍 Pagination after update:', pagination)
    } catch (e: any) {
      error.value = e.message || 'Ошибка загрузки клиентов'
      console.error('❌ fetchClients error:', e)
    } finally {
      loading.value = false
    }
  }

  async function fetchAll() {
    const res = await getClients({ all: true })
    return res.data || []
  }

  function setSort(key: string, search = '', per_page = pagination.per_page) {
    if (sortBy.value === key) {
      sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
      sortBy.value = key
      sortOrder.value = 'asc'
    }

    // Сохраняем настройки сортировки в localStorage
    localStorage.setItem('clientList_sortBy', sortBy.value)
    localStorage.setItem('clientList_sortOrder', sortOrder.value)

    fetchClients(1, search, sortBy.value, sortOrder.value, per_page)
  }

  async function create(newClient: Client) {
    loading.value = true
    try {
      const created = await createClient(newClient)
      await fetchClients(
        pagination.current_page,
        '',
        sortBy.value,
        sortOrder.value,
        pagination.per_page,
      )
      return created
    } finally {
      loading.value = false
    }
  }

  async function update(id: number, updatedClient: Client) {
    loading.value = true
    try {
      await updateClient(id, updatedClient)
      await fetchClients(
        pagination.current_page,
        '',
        sortBy.value,
        sortOrder.value,
        pagination.per_page,
      )
    } finally {
      loading.value = false
    }
  }

  async function remove(id: number, page = pagination.current_page) {
    loading.value = true
    try {
      await deleteClient(id)
      await fetchClients(page, '', sortBy.value, sortOrder.value, pagination.per_page)
    } finally {
      loading.value = false
    }
  }

  async function createContact(clientId: number, data: { type: string; value: string }) {
    return await createClientContact(clientId, data)
  }

  async function updateContact(
    clientId: number,
    contactId: number,
    data: { type: string; value: string },
  ) {
    return await updateClientContact(clientId, contactId, data)
  }

  async function removeContact(clientId: number, contactId: number) {
    return await deleteClientContact(clientId, contactId)
  }

  return {
    clients,
    pagination,
    loading,
    error,
    sortBy,
    sortOrder,
    fetchClients,
    setSort,
    create,
    update,
    remove,
    createContact,
    updateContact,
    removeContact,
    fetchAll,
  }
}
