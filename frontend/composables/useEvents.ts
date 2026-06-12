import type { ApiEvent } from '~/types/api'

export type { ApiEvent }

export function useEvents() {
  const api = useApi()

  const fetchEvents = async (): Promise<ApiEvent[]> => {
    const res = await api.get<{ data: ApiEvent[] }>('/events')
    return res.data
  }

  const fetchAdminEvents = async (): Promise<ApiEvent[]> => {
    const res = await api.get<{ data: ApiEvent[] }>('/admin/events')
    return res.data
  }

  const createEvent = async (formData: FormData): Promise<ApiEvent> => {
    const res = await api.post<{ data: ApiEvent }>('/admin/events', formData)
    return res.data
  }

  const updateEvent = async (id: number, formData: FormData): Promise<ApiEvent> => {
    const res = await api.post<{ data: ApiEvent }>(`/admin/events/${id}?_method=PUT`, formData)
    return res.data
  }

  const deleteEvent = (id: number) => api.delete(`/admin/events/${id}`)

  return { fetchEvents, fetchAdminEvents, createEvent, updateEvent, deleteEvent }
}
