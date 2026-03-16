export interface AdminStats {
  users: { total: number; pending: number; active: number }
  loans: { active: number; overdue: number; returned_today: number }
  extensions: { pending: number }
}

export function useAdmin() {
  const api = useApi()

  // Dashboard
  const fetchStats = () => api.get<AdminStats>('/admin/dashboard')

  // Games
  const fetchAdminGames = (params?: Record<string, string | number | boolean>) =>
    api.get<{ data: unknown[]; meta: unknown }>('/admin/games', { params })

  const createGame = (formData: FormData) =>
    api.post('/admin/games', formData)

  const updateGame = (id: number, formData: FormData) =>
    api.post(`/admin/games/${id}?_method=PUT`, formData)

  const deleteGame = (id: number) =>
    api.delete(`/admin/games/${id}`)

  // Tags
  const fetchAdminTags = () =>
    api.get<{ data: { id: number; name: string; slug: string }[] }>('/admin/tags')

  const createTag = (name: string) =>
    api.post<{ data: { id: number; name: string; slug: string } }>('/admin/tags', { name })

  const deleteTag = (id: number) =>
    api.delete(`/admin/tags/${id}`)

  // Categories
  const fetchAdminCategories = () =>
    api.get<{ data: unknown[] }>('/admin/categories')

  const createCategory = (data: unknown) =>
    api.post('/admin/categories', data)

  const updateCategory = (id: number, data: unknown) =>
    api.put(`/admin/categories/${id}`, data)

  const deleteCategory = (id: number) =>
    api.delete(`/admin/categories/${id}`)

  // Copies
  const fetchCopies = (params?: Record<string, string | number>) =>
    api.get<{ data: unknown[]; meta: unknown }>('/admin/copies', { params })

  const createCopy = (data: unknown) =>
    api.post('/admin/copies', data)

  const updateCopy = (id: number, data: unknown) =>
    api.put(`/admin/copies/${id}`, data)

  const deleteCopy = (id: number) =>
    api.delete(`/admin/copies/${id}`)

  // Loans
  const fetchAdminLoans = (params?: Record<string, string | number>) =>
    api.get<{ data: unknown[]; meta: unknown }>('/admin/loans', { params })

  const markOverdue = (id: number) =>
    api.patch(`/admin/loans/${id}/overdue`)

  // Extensions
  const fetchExtensions = (status = 'PENDING') =>
    api.get<{ data: unknown[] }>('/admin/extensions', { params: { status } })

  const approveExtension = (id: number, adminNote?: string) =>
    api.patch(`/admin/extensions/${id}/approve`, { admin_note: adminNote })

  const rejectExtension = (id: number, adminNote?: string) =>
    api.patch(`/admin/extensions/${id}/reject`, { admin_note: adminNote })

  // Newsletters
  const fetchNewsletters = () =>
    api.get<{ data: unknown[] }>('/admin/newsletters')

  const sendNewsletter = (subject: string, body: string) =>
    api.post('/admin/newsletters', { subject, body })

  // Damage Reports
  const fetchDamageReports = (params?: Record<string, string | number>) =>
    api.get<{ data: unknown[] }>('/admin/damage-reports', { params })

  // Packages
  const fetchAdminPackages = () =>
    api.get<{ data: unknown[] }>('/admin/packages')

  const createPackage = (data: unknown) =>
    api.post('/admin/packages', data)

  const updatePackage = (id: number, data: unknown) =>
    api.put(`/admin/packages/${id}`, data)

  const deletePackage = (id: number) =>
    api.delete(`/admin/packages/${id}`)

  return {
    fetchStats,
    fetchAdminGames, createGame, updateGame, deleteGame,
    fetchAdminTags, createTag, deleteTag,
    fetchAdminCategories, createCategory, updateCategory, deleteCategory,
    fetchCopies, createCopy, updateCopy, deleteCopy,
    fetchAdminLoans, markOverdue,
    fetchExtensions, approveExtension, rejectExtension,
    fetchNewsletters, sendNewsletter,
    fetchDamageReports,
    fetchAdminPackages, createPackage, updatePackage, deletePackage,
  }
}
