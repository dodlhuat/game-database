/**
 * Auth-Middleware
 * Leitet nicht eingeloggte Benutzer zu /login weiter.
 */
export default defineNuxtRouteMiddleware(() => {
  // Nur Client-seitig prüfen (Token liegt im localStorage)
  if (import.meta.server) return

  const token = localStorage.getItem('auth_token')
  if (!token) {
    return navigateTo('/login')
  }
})
