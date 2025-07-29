export interface AuditLog {
  id: number
  user_id: number
  auditable_type: string
  auditable_id: number
  action: AuditAction
  old_values: Record<string, any> | null
  new_values: Record<string, any> | null
  ip_address?: string
  user_agent?: string
  created_at: string
  updated_at: string

  // Relations
  user?: {
    id: number
    name: string
    username: string
    role?: string
  }
  auditable?: {
    id: number
    name?: string
    title?: string
    company_name?: string
  }
}

export enum AuditAction {
  CREATED = 'created',
  UPDATED = 'updated',
  DELETED = 'deleted',
  RESTORED = 'restored',
  FORCE_DELETED = 'force_deleted',
}

export interface AuditLogFilters {
  user_id?: number
  auditable_type?: string
  auditable_id?: number
  action?: AuditAction
  date_from?: string
  date_to?: string
  search?: string
  page?: number
  per_page?: number
}

export interface AuditLogStats {
  total_logs: number
  logs_today: number
  logs_this_week: number
  logs_this_month: number
  actions_count: Record<AuditAction, number>
  models_count: Record<string, number>
  users_count: Record<number, number>
}

export interface AuditLogResponse {
  data: AuditLog[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  pagination?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

// Русские названия для действий
export const AUDIT_ACTION_LABELS: Record<AuditAction, string> = {
  [AuditAction.CREATED]: 'Создание',
  [AuditAction.UPDATED]: 'Обновление',
  [AuditAction.DELETED]: 'Удаление',
  [AuditAction.RESTORED]: 'Восстановление',
  [AuditAction.FORCE_DELETED]: 'Полное удаление',
}

// Русские названия для моделей
export const AUDIT_MODEL_LABELS: Record<string, string> = {
  'App\\Models\\Order': 'Заказ',
  'App\\Models\\Product': 'Товар',
  'App\\Models\\Project': 'Проект',
  'App\\Models\\User': 'Пользователь',
  'App\\Models\\Client': 'Клиент',
  'App\\Models\\ClientContact': 'Контакт клиента',
  'App\\Models\\Comment': 'Комментарий',
  'App\\Models\\OrderAssignment': 'Назначение заказа',
}

// Цвета для действий
export const AUDIT_ACTION_COLORS: Record<AuditAction, string> = {
  [AuditAction.CREATED]: 'text-green-600 bg-green-50',
  [AuditAction.UPDATED]: 'text-blue-600 bg-blue-50',
  [AuditAction.DELETED]: 'text-red-600 bg-red-50',
  [AuditAction.RESTORED]: 'text-yellow-600 bg-yellow-50',
  [AuditAction.FORCE_DELETED]: 'text-red-800 bg-red-100',
}
