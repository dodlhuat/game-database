import { useAuthStore } from '~/stores/auth'

/**
 * Wartungsmodus (NUXT_PUBLIC_MAINTENANCE_MODE=true)
 * Leitet jede Route außer der Startseite und dem Login dorthin um.
 * Eingeloggte Admins sind ausgenommen, damit sie weiter Daten pflegen können.
 */
export default defineNuxtRouteMiddleware((to) => {
  if (!useRuntimeConfig().public.maintenanceMode) return
  if (to.path === '/' || to.path === '/login') return
  if (useAuthStore().isAdmin) return

  return navigateTo('/', { replace: true })
})
