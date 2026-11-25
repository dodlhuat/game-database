import { useAuthStore } from '~/stores/auth'

/**
 * Seiten, die im Wartungsmodus erreichbar bleiben: Startseite (zeigt die
 * Wartungsansicht), Login sowie Passwort vergessen/zurücksetzen, damit sich
 * ein Admin auch ohne bekanntes Passwort wieder Zugang verschaffen kann.
 * Die Newsletter-Abmeldung und das Impressum müssen immer erreichbar sein.
 */
const ALLOWED_PATHS = new Set([
  '/',
  '/login',
  '/forgot-password',
  '/reset-password',
  '/newsletter/abmelden',
  '/impressum',
])

/**
 * Wartungsmodus (NUXT_PUBLIC_MAINTENANCE_MODE=true)
 * Leitet jede Route außer ALLOWED_PATHS auf die Startseite um.
 * Eingeloggte Admins sind ausgenommen, damit sie weiter Daten pflegen können.
 */
export default defineNuxtRouteMiddleware((to) => {
  if (!useRuntimeConfig().public.maintenanceMode) return
  if (ALLOWED_PATHS.has(to.path)) return
  if (useAuthStore().isAdmin) return

  return navigateTo('/', { replace: true })
})
