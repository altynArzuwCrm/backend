// Конфигурация API для Laravel
export const API_CONFIG = {
  // Базовый URL для Laravel API
  BASE_URL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',

  // Таймаут для запросов (в миллисекундах)
  TIMEOUT: 10000,

  // Заголовки по умолчанию для Laravel
  DEFAULT_HEADERS: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest', // Для Laravel CSRF
  },

  // Настройки для разработки
  DEV: {
    // Использовать имитацию API если реальный недоступен
    USE_MOCK_FALLBACK: true,

    // Задержка для имитации (в миллисекундах)
    MOCK_DELAY: 1000,
  },
}

// Endpoints Laravel API
export const API_ENDPOINTS = {
  AUTH: {
    LOGIN: '/login',
    LOGOUT: '/logout',
    REGISTER: '/register',
    ME: '/me', // Получить текущего пользователя
  },
  USER: {
    PROFILE: '/user/profile',
    UPDATE: '/user/update',
    AVATAR: '/user/avatar',
  },
  // Добавьте другие endpoints по мере необходимости
}

// Коды ошибок Laravel
export const ERROR_CODES = {
  UNAUTHORIZED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  VALIDATION_ERROR: 422,
  SERVER_ERROR: 500,
}

// Сообщения об ошибках
export const ERROR_MESSAGES = {
  NETWORK_ERROR: 'Ошибка сети. Проверьте подключение к интернету.',
  UNAUTHORIZED: 'Необходима авторизация.',
  FORBIDDEN: 'Доступ запрещен.',
  NOT_FOUND: 'Ресурс не найден.',
  SERVER_ERROR: 'Ошибка сервера. Попробуйте позже.',
  VALIDATION_ERROR: 'Ошибка валидации данных.',
  UNKNOWN_ERROR: 'Неизвестная ошибка.',
}
