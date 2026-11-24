import type { ApiPickupLocation } from '~/types/api'

export type PickupLocationPayload = {
  name: string
  address: string | null
  opening_hours: string | null
  notes: string | null
  map_url: string | null
  is_active: boolean
  /** Restrict the location to these users; empty means open for everyone. */
  user_ids: number[]
}

/** Who a location is restricted to (admin list only). */
export type PickupLocationUser = {
  id: number
  name: string
  email: string
  /** False for users who no longer have an active membership. */
  can_borrow: boolean
}

/** Cache keys: cleared on login/logout so a new user never sees the last one's places. */
export const PICKUP_LOCATION_STATE_KEYS = ['active-pickup-locations', 'user-pickup-locations']

export function usePickupLocations() {
  const api = useApi()

  // Shared by the game and package pages, so the borrow dialog opens
  // without a fresh request every time.
  const activeLocations = useState<ApiPickupLocation[] | null>(
    'active-pickup-locations',
    () => null
  )

  const fetchActiveLocations = async (force = false): Promise<ApiPickupLocation[]> => {
    if (activeLocations.value && !force) return activeLocations.value
    const res = await api.get<{ data: ApiPickupLocation[] }>('/pickup-locations')
    activeLocations.value = res.data
    return res.data
  }

  // Admin move menu: the active locations open to one borrower, cached per user.
  const userLocations = useState<Record<number, ApiPickupLocation[]>>(
    'user-pickup-locations',
    () => ({})
  )

  const fetchUserLocations = async (userId: number): Promise<ApiPickupLocation[]> => {
    // clearNuxtState may leave the state undefined until it is set again
    const cached = userLocations.value?.[userId]
    if (cached) return cached
    const res = await api.get<{ data: ApiPickupLocation[] }>(
      `/admin/users/${userId}/pickup-locations`
    )
    userLocations.value = { ...(userLocations.value ?? {}), [userId]: res.data }
    return res.data
  }

  // Admin changes alter what the borrow dialog and the move menu should offer.
  const invalidate = () => {
    activeLocations.value = null
    userLocations.value = {}
  }

  const fetchAdminLocations = async (): Promise<ApiPickupLocation[]> => {
    const res = await api.get<{ data: ApiPickupLocation[] }>('/admin/pickup-locations')
    return res.data
  }

  const createLocation = async (payload: PickupLocationPayload): Promise<ApiPickupLocation> => {
    const res = await api.post<{ data: ApiPickupLocation }>('/admin/pickup-locations', payload)
    invalidate()
    return res.data
  }

  const updateLocation = async (
    id: number,
    payload: PickupLocationPayload
  ): Promise<ApiPickupLocation> => {
    const res = await api.put<{ data: ApiPickupLocation }>(`/admin/pickup-locations/${id}`, payload)
    invalidate()
    return res.data
  }

  const toggleLocation = async (id: number): Promise<ApiPickupLocation> => {
    const res = await api.patch<{ data: ApiPickupLocation }>(`/admin/pickup-locations/${id}/toggle`)
    invalidate()
    return res.data
  }

  const reorderLocations = async (ids: number[]) => {
    await api.put('/admin/pickup-locations/order', { ids })
    invalidate()
  }

  const deleteLocation = async (id: number) => {
    await api.delete(`/admin/pickup-locations/${id}`)
    invalidate()
  }

  return {
    activeLocations,
    fetchActiveLocations,
    fetchAdminLocations,
    fetchUserLocations,
    createLocation,
    updateLocation,
    toggleLocation,
    reorderLocations,
    deleteLocation,
  }
}
