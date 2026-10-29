import { useAuthStore } from '~/stores/auth'

/**
 * Wartungsmodus aus Sicht des aktuellen Users: eingeloggte Admins sehen
 * trotz NUXT_PUBLIC_MAINTENANCE_MODE=true die normale Seite.
 */
export function useMaintenance() {
  const auth = useAuthStore()
  const enabled = useRuntimeConfig().public.maintenanceMode
  return computed(() => !!enabled && !auth.isAdmin)
}
