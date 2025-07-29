export async function getUserImageUrl(user: any): Promise<string> {
  if (!user) return ''
  if (user.image_url) return user.image_url
  if (user.avatar) return user.avatar
  if (user.image && user.image.startsWith('http')) return user.image
  if (user.image) {
    // BASE_URL должен быть определён в API_CONFIG
    try {
      const { API_CONFIG } = await import('@/config/api')
      return `${API_CONFIG.BASE_URL.replace('/api', '')}/storage/${user.image}`
    } catch {
      return ''
    }
  }
  return ''
}
