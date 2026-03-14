/**
 * Admin-Middleware
 * Leitet weiter wenn der eingeloggte User kein Admin ist.
 * Setzt auth.ts voraus (muss davor laufen).
 */
export default defineNuxtRouteMiddleware(() => {
  if (import.meta.server) return

  const userRaw = localStorage.getItem('auth_user')
  if (!userRaw) {
    return navigateTo('/login')
  }

  try {
    const user = JSON.parse(userRaw)
    if (user?.role !== 'ADMIN') {
      return navigateTo('/dashboard')
    }
  } catch {
    return navigateTo('/login')
  }
})
