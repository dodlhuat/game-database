import type { ApiSponsor } from '~/types/api'

export function useSponsors() {
  const api = useApi()

  // Shared across the footer and the sponsors page, so the footer link
  // check doesn't refetch on every page it's rendered on.
  const activeSponsors = useState<ApiSponsor[] | null>('active-sponsors', () => null)

  const fetchSponsors = async (force = false): Promise<ApiSponsor[]> => {
    if (activeSponsors.value && !force) return activeSponsors.value
    const res = await api.get<{ data: ApiSponsor[] }>('/sponsors')
    activeSponsors.value = res.data
    return res.data
  }

  // Admin changes alter what the public page/footer should show.
  const invalidate = () => {
    activeSponsors.value = null
  }

  const fetchAdminSponsors = async (): Promise<ApiSponsor[]> => {
    const res = await api.get<{ data: ApiSponsor[] }>('/admin/sponsors')
    return res.data
  }

  const createSponsor = async (formData: FormData): Promise<ApiSponsor> => {
    const res = await api.post<{ data: ApiSponsor }>('/admin/sponsors', formData)
    invalidate()
    return res.data
  }

  const updateSponsor = async (id: number, formData: FormData): Promise<ApiSponsor> => {
    const res = await api.post<{ data: ApiSponsor }>(`/admin/sponsors/${id}?_method=PUT`, formData)
    invalidate()
    return res.data
  }

  const toggleSponsor = async (id: number): Promise<ApiSponsor> => {
    const res = await api.patch<{ data: ApiSponsor }>(`/admin/sponsors/${id}/toggle`)
    invalidate()
    return res.data
  }

  const reorderSponsors = async (ids: number[]) => {
    await api.put('/admin/sponsors/order', { ids })
    invalidate()
  }

  const deleteSponsor = async (id: number) => {
    await api.delete(`/admin/sponsors/${id}`)
    invalidate()
  }

  return {
    activeSponsors,
    fetchSponsors,
    fetchAdminSponsors,
    createSponsor,
    updateSponsor,
    toggleSponsor,
    reorderSponsors,
    deleteSponsor,
  }
}
