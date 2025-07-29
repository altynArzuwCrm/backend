import { UserRole } from '@/types/user'

// Универсальная функция для проверки наличия роли у пользователя
export function hasRole(user: any, roleName: string): boolean {
  return (
    user &&
    user.roles &&
    Array.isArray(user.roles) &&
    user.roles.some((r: any) => r.name === roleName)
  )
}

// Получить текущего пользователя
export function getCurrentUser() {
  const userStr = localStorage.getItem('user')
  return userStr ? JSON.parse(userStr) : null
}

// Получить роль текущего пользователя
export function getCurrentUserRole(): UserRole | null {
  const user = getCurrentUser()
  return user?.role || null
}

// Проверить, является ли пользователь администратором или менеджером
export function isAdminOrManager(): boolean {
  const user = getCurrentUser()
  return hasRole(user, 'admin') || hasRole(user, 'manager')
}

// Проверить, является ли пользователь администратором
export function isAdmin(): boolean {
  const user = getCurrentUser()
  return hasRole(user, 'admin')
}

// Проверить, является ли пользователь менеджером
export function isManager(): boolean {
  const user = getCurrentUser()
  return hasRole(user, 'manager')
}

// Проверить, является ли пользователь сотрудником (не админ/менеджер)
export function isStaff(): boolean {
  const user = getCurrentUser()
  return (
    hasRole(user, 'designer') || hasRole(user, 'print_operator') || hasRole(user, 'workshop_worker')
  )
}

// Проверить, может ли пользователь создавать/редактировать записи
export function canCreateEdit(): boolean {
  return isAdminOrManager()
}

// Проверить, может ли пользователь удалять записи
export function canDelete(): boolean {
  return isAdmin()
}

// Проверить, может ли пользователь видеть всех пользователей
export function canViewAllUsers(): boolean {
  return isAdminOrManager()
}

// Проверить, может ли пользователь видеть всех клиентов
export function canViewAllClients(): boolean {
  return isAdminOrManager()
}

// Проверить, может ли пользователь видеть все заказы
export function canViewAllOrders(): boolean {
  return isAdminOrManager()
}

// Проверить, может ли пользователь видеть все проекты
export function canViewAllProjects(): boolean {
  return isAdminOrManager()
}

// Проверить, может ли пользователь видеть все товары
export function canViewAllProducts(): boolean {
  return isAdminOrManager()
}

// Проверить, может ли пользователь просматривать аудит-логи
export function canViewAuditLogs(): boolean {
  return isAdmin()
}

// Проверить, может ли пользователь просматривать цены
export function canViewPrices(): boolean {
  return isAdminOrManager()
}

// Получить текст для навигации в зависимости от роли
export function getNavigationText(item: string): string {
  const user = getCurrentUser()
  const texts: Record<string, string> = {
    orders: isStaff() ? 'Мои заказы' : 'Заказы',
    projects: isStaff() ? 'Мои проекты' : 'Проекты',
    products: isStaff() ? 'Мои товары' : 'Товары',
  }
  return texts[item] || item
}
