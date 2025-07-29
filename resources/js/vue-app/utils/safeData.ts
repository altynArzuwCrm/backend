/**
 * Утилиты для безопасной работы с данными
 * Помогают избежать ошибок "Attempt to read property on null"
 */

/**
 * Безопасно получает значение из объекта по пути
 * @param obj - объект для поиска
 * @param path - путь к свойству (например, 'client.name')
 * @param defaultValue - значение по умолчанию
 */
export function safeGet(obj: any, path: string, defaultValue: any = null): any {
  if (!obj || typeof obj !== 'object') {
    return defaultValue
  }

  const keys = path.split('.')
  let result = obj

  for (const key of keys) {
    if (result === null || result === undefined || typeof result !== 'object') {
      return defaultValue
    }
    result = result[key]
  }

  return result !== undefined ? result : defaultValue
}

/**
 * Безопасно получает имя клиента
 * @param client - объект клиента
 */
export function safeGetClientName(client: any): string {
  if (!client) return '-'
  return client.name || client.company_name || '-'
}

/**
 * Безопасно получает ID клиента
 * @param client - объект клиента
 */
export function safeGetClientId(client: any): number | null {
  if (!client || typeof client.id !== 'number') {
    return null
  }
  return client.id
}

/**
 * Безопасно обрабатывает массив данных с проверкой на null
 * @param data - массив данных
 * @param processor - функция обработки каждого элемента
 */
export function safeProcessArray<T, R>(
  data: T[] | null | undefined,
  processor: (item: T) => R,
): R[] {
  if (!Array.isArray(data)) {
    return []
  }

  return data.filter((item) => item !== null && item !== undefined).map(processor)
}

/**
 * Безопасно обрабатывает ответ API
 * @param response - ответ от API
 * @param processor - функция обработки данных
 */
export function safeProcessApiResponse<T, R>(response: any, processor: (data: T) => R): R[] {
  if (!response || !Array.isArray(response)) {
    return []
  }

  return safeProcessArray(response, processor)
}

/**
 * Безопасно выполняет API запрос с обработкой ошибок
 * @param url - URL для запроса
 * @param options - опции запроса
 */
export async function safeApiRequest<T>(url: string, options: RequestInit = {}): Promise<T | null> {
  try {
    const token = localStorage.getItem('auth_token')
    const response = await fetch(url, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
        ...options.headers,
      },
      ...options,
    })

    if (!response.ok) {
      console.error(`API Error: ${response.status} ${response.statusText}`)
      return null
    }

    return await response.json()
  } catch (error) {
    console.error('API Request Error:', error)
    return null
  }
}

/**
 * Безопасно получает данные активности с защитой от null объектов
 * @param data - данные активности
 */
export function safeProcessActivityData(data: any[]): any[] {
  if (!Array.isArray(data)) {
    return []
  }

  return data.map((activity) => ({
    id: activity.id || 0,
    title: activity.title || 'Неизвестное действие',
    time: activity.time || 'Неизвестное время',
    icon: activity.icon || 'DocumentIcon',
    iconBg: activity.iconBg || 'bg-gray-500 bg-opacity-20',
    // Безопасная обработка client
    client: activity.client
      ? {
          id: activity.client.id || 0,
          name: activity.client.name || 'Неизвестный клиент',
          company_name: activity.client.company_name || null,
        }
      : null,
    // Безопасная обработка других полей
    user: activity.user
      ? {
          id: activity.user.id || 0,
          name: activity.user.name || 'Неизвестный пользователь',
          username: activity.user.username || '',
        }
      : null,
    // Копируем остальные поля
    ...activity,
  }))
}

/**
 * Безопасно форматирует дату
 * @param date - дата в любом формате
 * @param format - формат даты (по умолчанию 'ru-RU')
 */
export function safeFormatDate(date: any, format: string = 'ru-RU'): string {
  if (!date) return '-'

  try {
    const d = new Date(date)
    if (isNaN(d.getTime())) return '-'

    return d.toLocaleString(format, {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return '-'
  }
}

/**
 * Безопасно получает значение из объекта с типизацией
 * @param obj - объект
 * @param key - ключ
 * @param defaultValue - значение по умолчанию
 */
export function safeGetValue<T>(obj: any, key: string, defaultValue: T): T {
  if (!obj || typeof obj !== 'object') {
    return defaultValue
  }

  const value = obj[key]
  return value !== undefined ? value : defaultValue
}

/**
 * Проверяет, является ли объект валидным
 * @param obj - объект для проверки
 */
export function isValidObject(obj: any): boolean {
  return obj !== null && obj !== undefined && typeof obj === 'object'
}

/**
 * Создает безопасную копию объекта с проверкой на null
 * @param obj - исходный объект
 */
export function safeClone<T>(obj: T): T | null {
  if (!isValidObject(obj)) {
    return null
  }

  try {
    return JSON.parse(JSON.stringify(obj))
  } catch {
    return null
  }
}
