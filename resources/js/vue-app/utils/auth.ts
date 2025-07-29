// Authentication utility functions

/**
 * Check if user is authenticated
 */
export function isAuthenticated(): boolean {
  return !!localStorage.getItem('auth_token')
}

/**
 * Get current user from localStorage
 */
export function getCurrentUser() {
  const userStr = localStorage.getItem('user')
  return userStr ? JSON.parse(userStr) : null
}

/**
 * Clear authentication data
 */
export function clearAuth(): void {
  localStorage.removeItem('auth_token')
  localStorage.removeItem('user')
}

/**
 * Set authentication data
 */
export function setAuth(token: string, user: any): void {
  localStorage.setItem('auth_token', token)
  localStorage.setItem('user', JSON.stringify(user))
}

/**
 * Handle 401 Unauthorized errors - clear auth and redirect to login
 */
export function handle401Error(message?: string): void {
  console.log('🚨 401 Unauthorized - clearing auth and redirecting to login')
  clearAuth()

  // Show message if provided
  if (message) {
    console.warn('Auth error:', message)

    // Try to show toast notification if available
    try {
      // Import toast dynamically to avoid circular dependencies
      import('../stores/toast')
        .then(({ toast }) => {
          toast.show(message, 'error')
        })
        .catch(() => {
          // Fallback to alert if toast is not available
          alert(message)
        })
    } catch {
      // Fallback to alert if dynamic import fails
      alert(message)
    }
  }

  // Redirect to login page after a short delay to allow toast to show
  if (typeof window !== 'undefined') {
    const currentPath = window.location.pathname
    if (currentPath !== '/login') {
      setTimeout(() => {
        window.location.href = '/login'
      }, 1000) // 1 second delay to show the toast
    }
  }
}
