import { ref, reactive } from 'vue'
import { getProjects, createProject, updateProject, deleteProject } from '@/services/api'
import type { Project } from '@/types/project'

export function ProjectController() {
  const projects = ref<Project[]>([])
  const pagination = reactive({
    data: [] as Project[],
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 30,
  })
  const loading = ref(false)
  const error = ref('')
  const sortBy = ref('id')
  const sortOrder = ref<'asc' | 'desc'>('desc')

  async function fetchProjects(
    page = 1,
    search = '',
    sort_by = sortBy.value,
    sort_order = sortOrder.value,
    per_page = pagination.per_page,
  ) {
    loading.value = true
    error.value = ''
    try {
      const res = await getProjects({ page, search, sort_by, sort_order, per_page })
      pagination.data = res.data || []
      pagination.current_page = res.current_page || 1
      pagination.last_page = res.last_page || 1
      pagination.total = res.total || 0
      pagination.per_page = res.per_page || 30
      projects.value = res.data || []
    } catch (e: any) {
      error.value = e.message || 'Ошибка загрузки проектов'
    } finally {
      loading.value = false
    }
  }

  async function create(newProject: Partial<Project>) {
    loading.value = true
    try {
      const created = await createProject(newProject)
      await fetchProjects(pagination.current_page)
      return created
    } finally {
      loading.value = false
    }
  }

  async function update(id: number, updatedProject: Partial<Project>) {
    loading.value = true
    try {
      const updated = await updateProject(id, updatedProject)
      await fetchProjects(pagination.current_page)
      return updated
    } finally {
      loading.value = false
    }
  }

  async function remove(id: number) {
    loading.value = true
    try {
      await deleteProject(id)
      if (pagination.data.length === 1 && pagination.current_page > 1) {
        await fetchProjects(pagination.current_page - 1)
      } else {
        await fetchProjects(pagination.current_page)
      }
    } finally {
      loading.value = false
    }
  }

  return {
    projects,
    pagination,
    loading,
    error,
    sortBy,
    sortOrder,
    fetchProjects,
    create,
    update,
    remove,
  }
}
