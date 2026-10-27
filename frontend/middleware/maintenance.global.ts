/**
 * Wartungsmodus (NUXT_PUBLIC_MAINTENANCE_MODE=true)
 * Leitet jede Route außer der Startseite dorthin um.
 */
export default defineNuxtRouteMiddleware((to) => {
  if (!useRuntimeConfig().public.maintenanceMode) return
  if (to.path === '/') return

  return navigateTo('/', { replace: true })
})
