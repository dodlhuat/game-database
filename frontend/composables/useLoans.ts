export interface Loan {
  id: number
  copy: {
    id: number
    condition: string
    qr_code: string | null
  }
  game: {
    id: number
    title: string
    slug: string
    cover_image_url: string | null
  }
  start_date: string
  due_date: string
  returned_at: string | null
  return_condition: string | null
  status: 'ACTIVE' | 'RETURNED' | 'OVERDUE' | 'EXTENDED'
  is_overdue: boolean
  extensions: {
    id: number
    requested_due_date: string
    status: 'PENDING' | 'APPROVED' | 'REJECTED'
    admin_note: string | null
  }[]
}

export interface Reservation {
  id: number
  position: number
  game?: { title: string; slug: string }
}

export interface DashboardData {
  active_loans: Loan[]
  loan_history: Loan[]
  reservations: Reservation[]
  stats: {
    total_loans: number
    active_loans_count: number
    overdue_count: number
    reservations_count: number
  }
}

export function useLoans() {
  const api = useApi()

  const fetchDashboard = () => api.get<DashboardData>('/dashboard')

  const fetchLoans = () => api.get<{ data: Loan[] }>('/loans')

  const createLoan = (payload: { copy_id: number; start_date: string; due_date: string }) =>
    api.post<{ data: Loan }>('/loans', payload)

  const returnLoan = (loanId: number, returnCondition: string) =>
    api.post<{ data: Loan }>(`/loans/${loanId}/return`, { return_condition: returnCondition })

  const requestExtension = (loanId: number, requestedDueDate: string) =>
    api.post(`/loans/${loanId}/extend`, { requested_due_date: requestedDueDate })

  const addFavorite = (gameId: number) =>
    api.post('/favorites', { game_id: gameId })

  const removeFavorite = (gameId: number) =>
    api.delete(`/favorites/${gameId}`)

  const addReservation = (gameId: number) =>
    api.post('/reservations', { game_id: gameId })

  const removeReservation = (reservationId: number) =>
    api.delete(`/reservations/${reservationId}`)

  const reportDamage = (loanId: number, description: string, photoUrl?: string) =>
    api.post('/damage-reports', { loan_id: loanId, description, photo_url: photoUrl || undefined })

  return {
    fetchDashboard,
    fetchLoans,
    createLoan,
    returnLoan,
    requestExtension,
    addFavorite,
    removeFavorite,
    addReservation,
    removeReservation,
    reportDamage,
  }
}
