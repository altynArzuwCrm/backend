import { ref, reactive } from 'vue'
import {
  getUsers,
  getUser,
  createUser,
  updateUser,
  deleteUser,
  toggleUserActive,
  getUsersByRole,
} from '@/services/api'
import type { User, UserRole, CreateUserData, UpdateUserData } from '@/types/user'
import axios from 'axios'

export function useUserController() {
  const users = ref<User[]>([])
  const pagination = reactive({
    data: [] as User[],
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 30,
  })
  const loading = ref(false)
  const error = ref('')

  // Загружаем настройки сортировки из localStorage
  const sortBy = ref(localStorage.getItem('userList_sortBy') || 'id')
  const sortOrder = ref<'asc' | 'desc'>(
    (localStorage.getItem('userList_sortOrder') as 'asc' | 'desc') || 'asc',
  )

  async function fetchUsers(
    page = 1,
    search = '',
    sort_by = sortBy.value,
    sort_order = sortOrder.value,
    per_page = 30,
    role = '',
    is_active = null,
  ) {
    loading.value = true
    error.value = ''
    try {
      const res = await getUsers({
        page,
        search,
        sort_by,
        sort_order,
        per_page,
        role,
        is_active,
      })

      console.log('🔍 Users API Response:', res)

      // Проверяем структуру ответа
      if (res.data && Array.isArray(res.data)) {
        pagination.data = res.data
        pagination.current_page = res.pagination?.current_page || res.meta?.current_page || 1
        pagination.last_page = res.pagination?.last_page || res.meta?.last_page || 1
        pagination.total = res.pagination?.total || res.meta?.total || 0
        pagination.per_page = res.pagination?.per_page || res.meta?.per_page || 30
        users.value = res.data
      } else {
        // Если данные приходят в другом формате
        pagination.data = Array.isArray(res) ? res : []
        pagination.current_page = 1
        pagination.last_page = 1
        pagination.total = Array.isArray(res) ? res.length : 0
        pagination.per_page = 30
        users.value = Array.isArray(res) ? res : []
      }

      console.log('🔍 Users Pagination after update:', pagination)
    } catch (e: any) {
      error.value = e.message || 'Ошибка загрузки пользователей'
      console.error('❌ fetchUsers error:', e)
    } finally {
      loading.value = false
    }
  }

  async function fetchUser(id: number) {
    loading.value = true
    try {
      const user = await getUser(id)
      return user
    } catch (e: any) {
      error.value = e.message || 'Ошибка загрузки пользователя'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function fetchUsersByRole(role: string) {
    loading.value = true
    try {
      const res = await getUsersByRole(role)
      return res.data || []
    } catch (e: any) {
      error.value = e.message || 'Ошибка загрузки пользователей по роли'
      throw e
    } finally {
      loading.value = false
    }
  }

  function setSort(key: string, search = '') {
    if (sortBy.value === key) {
      sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
      sortBy.value = key
      sortOrder.value = 'asc'
    }

    // Сохраняем настройки сортировки в localStorage
    localStorage.setItem('userList_sortBy', sortBy.value)
    localStorage.setItem('userList_sortOrder', sortOrder.value)

    fetchUsers(1, search, sortBy.value, sortOrder.value)
  }

  async function create(userData: any) {
    console.log('UserController.create called with:', userData)
    console.log('userData.image type:', typeof userData.image)
    console.log('userData.image instanceof File:', userData.image instanceof File)
    console.log('userData.image:', userData.image)

    loading.value = true
    try {
      const created = await createUser(userData)
      // Удалён отдельный PATCH-запрос на /users/{id}/roles
      await fetchUsers(pagination.current_page)
      return created
    } finally {
      loading.value = false
    }
  }

  async function update(id: number, userData: any) {
    console.log('UserController.update called with id:', id, 'data:', userData)
    console.log('userData.image type:', typeof userData.image)
    console.log('userData.image instanceof File:', userData.image instanceof File)
    console.log('userData.image:', userData.image)

    loading.value = true
    try {
      const updated = await updateUser(id, userData)
      // Удалён отдельный PATCH-запрос на /users/{id}/roles
      await fetchUsers(pagination.current_page)
      return updated
    } finally {
      loading.value = false
    }
  }

  async function remove(id: number, page = pagination.current_page) {
    loading.value = true
    try {
      await deleteUser(id)
      await fetchUsers(page)
    } finally {
      loading.value = false
    }
  }

  async function toggleActive(id: number) {
    loading.value = true
    try {
      const result = await toggleUserActive(id)
      await fetchUsers(pagination.current_page)
      return result
    } finally {
      loading.value = false
    }
  }

  // Функция для получения метки роли
  function getRoleLabel(role: string): string {
    const labels: Record<string, string> = {
      admin: 'Администратор',
      manager: 'Менеджер',
      designer: 'Дизайнер',
      print_operator: 'Печатник',
      workshop_worker: 'Работник цеха',
    }
    return labels[role] || role
  }

  // Функция для получения класса бейджа роли
  function getRoleBadgeClass(role: string): string {
    const classes: Record<string, string> = {
      admin: 'bg-red-100 text-red-800',
      manager: 'bg-blue-100 text-blue-800',
      designer: 'bg-green-100 text-green-800',
      print_operator: 'bg-yellow-100 text-yellow-800',
      workshop_worker: 'bg-purple-100 text-purple-800',
    }
    return classes[role] || 'bg-gray-100 text-gray-800'
  }

  return {
    users,
    pagination,
    loading,
    error,
    sortBy,
    sortOrder,
    fetchUsers,
    fetchUser,
    fetchUsersByRole,
    setSort,
    create,
    update,
    remove,
    toggleActive,
    getRoleLabel,
    getRoleBadgeClass,
  }
}
