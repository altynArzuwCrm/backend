import { API_CONFIG } from '@/config/api'
import type { AuditLog, AuditLogFilters, AuditLogResponse, AuditLogStats } from '@/types/audit'

export class AuditController {
  /**
   * Получить список аудит-логов с фильтрацией
   */
  static async getAuditLogs(filters: AuditLogFilters = {}): Promise<AuditLogResponse> {
    const params = new URLSearchParams()

    if (filters.user_id) params.append('user_id', filters.user_id.toString())
    if (filters.auditable_type) params.append('auditable_type', filters.auditable_type)
    if (filters.auditable_id) params.append('auditable_id', filters.auditable_id.toString())
    if (filters.action) params.append('action', filters.action)
    if (filters.date_from) params.append('date_from', filters.date_from)
    if (filters.date_to) params.append('date_to', filters.date_to)
    if (filters.search) params.append('search', filters.search)
    if (filters.page) params.append('page', filters.page.toString())
    if (filters.per_page) params.append('per_page', filters.per_page.toString())

    const response = await fetch(`${API_CONFIG.BASE_URL}/audit-logs?${params.toString()}`, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      },
    })

    if (!response.ok) {
      throw new Error('Ошибка загрузки аудит-логов')
    }

    return await response.json()
  }

  /**
   * Получить статистику аудит-логов
   */
  static async getAuditStats(): Promise<AuditLogStats> {
    const response = await fetch(`${API_CONFIG.BASE_URL}/audit-logs/stats`, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      },
    })

    if (!response.ok) {
      throw new Error('Ошибка загрузки статистики аудит-логов')
    }

    return await response.json()
  }

  /**
   * Получить аудит-логи конкретной сущности
   */
  static async getEntityAuditLogs(
    auditableType: string,
    auditableId: number,
    filters: Omit<AuditLogFilters, 'auditable_type' | 'auditable_id'> = {},
  ): Promise<AuditLogResponse> {
    const params = new URLSearchParams()

    if (filters.user_id) params.append('user_id', filters.user_id.toString())
    if (filters.action) params.append('action', filters.action)
    if (filters.date_from) params.append('date_from', filters.date_from)
    if (filters.date_to) params.append('date_to', filters.date_to)
    if (filters.search) params.append('search', filters.search)
    if (filters.page) params.append('page', filters.page.toString())
    if (filters.per_page) params.append('per_page', filters.per_page.toString())

    const response = await fetch(
      `${API_CONFIG.BASE_URL}/audit-logs/entity?auditable_type=${auditableType}&auditable_id=${auditableId}&${params.toString()}`,
      {
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
        },
      },
    )

    if (!response.ok) {
      throw new Error('Ошибка загрузки логов сущности')
    }

    return await response.json()
  }

  /**
   * Получить детальную информацию о конкретной записи аудит-лога
   */
  static async getAuditLog(id: number): Promise<AuditLog> {
    const response = await fetch(`${API_CONFIG.BASE_URL}/audit-logs/${id}`, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
      },
    })

    if (!response.ok) {
      throw new Error('Ошибка загрузки аудит-лога')
    }

    return await response.json()
  }

  /**
   * Экспорт аудит-логов в CSV
   */
  static async exportToCSV(filters: AuditLogFilters = {}): Promise<Blob> {
    const params = new URLSearchParams()

    if (filters.user_id) params.append('user_id', filters.user_id.toString())
    if (filters.auditable_type) params.append('auditable_type', filters.auditable_type)
    if (filters.auditable_id) params.append('auditable_id', filters.auditable_id.toString())
    if (filters.action) params.append('action', filters.action)
    if (filters.date_from) params.append('date_from', filters.date_from)
    if (filters.date_to) params.append('date_to', filters.date_to)
    if (filters.search) params.append('search', filters.search)

    const response = await fetch(
      `${API_CONFIG.BASE_URL}/audit-logs/export/csv?${params.toString()}`,
      {
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
        },
      },
    )

    if (!response.ok) {
      throw new Error('Ошибка экспорта в CSV')
    }

    return await response.blob()
  }

  /**
   * Экспорт аудит-логов в JSON
   */
  static async exportToJSON(filters: AuditLogFilters = {}): Promise<Blob> {
    const params = new URLSearchParams()

    if (filters.user_id) params.append('user_id', filters.user_id.toString())
    if (filters.auditable_type) params.append('auditable_type', filters.auditable_type)
    if (filters.auditable_id) params.append('auditable_id', filters.auditable_id.toString())
    if (filters.action) params.append('action', filters.action)
    if (filters.date_from) params.append('date_from', filters.date_from)
    if (filters.date_to) params.append('date_to', filters.date_to)
    if (filters.search) params.append('search', filters.search)

    const response = await fetch(
      `${API_CONFIG.BASE_URL}/audit-logs/export/json?${params.toString()}`,
      {
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
        },
      },
    )

    if (!response.ok) {
      throw new Error('Ошибка экспорта в JSON')
    }

    return await response.blob()
  }
}
