import type { ApiPickupLocation } from '~/types/api'

export interface Loan {
  id: number
  copy: {
    id: number
    condition: string
    borrow_count: number
    qr_code: string | null
  }
  game: {
    id: number
    title: string
    slug: string
    cover_image_url: string | null
    deposit_tokens: number
  }
  start_date: string
  due_date: string
  returned_at: string | null
  return_condition: string | null
  deposit_tokens: number
  /** Set when the game is part of a package — it can only go back with the whole package. */
  package_loan_id?: number | null
  package?: { id: number; name: string } | null
  /** Personal pickup: the booked appointment. */
  pickup_event?: {
    id: number
    title: string
    date: string
    time: string | null
    end_time: string | null
  } | null
  /** Where the game is picked up — and has to be returned. */
  pickup_location?: ApiPickupLocation | null
  status: 'ACTIVE' | 'RETURNED' | 'OVERDUE' | 'EXTENDED' | 'CANCELLED'
  is_overdue: boolean
  extensions: {
    id: number
    requested_due_date: string
    status: 'PENDING' | 'APPROVED' | 'REJECTED'
    admin_note: string | null
  }[]
  can_extend?: boolean
  extension_blocked_reason?:
    'not_extendable' | 'max_reached' | 'reserved' | 'awaiting_pickup' | null
  /** Booked for a pickup event, not handed out yet: no extending, returning or damage report. */
  awaiting_pickup?: boolean
  next_extension_cost?: number
  extensions_used?: number
  max_extensions?: number
  extension_earliest_due_date?: string
  extension_latest_due_date?: string
}

export interface TokenTransaction {
  id: number
  loan_id: number | null
  type:
    | 'BORROW'
    | 'DEPOSIT_BLOCK'
    | 'DEPOSIT_RELEASE'
    | 'DEPOSIT_FORFEIT'
    | 'PURCHASE'
    | 'ADMIN_ADJUSTMENT'
    | 'BONUS_GRANT'
    | 'BONUS_EXPIRE'
    | 'REFUND'
    | 'EXTENSION'
    | 'RESERVATION'
  amount: number
  description: string | null
  created_at: string
  loan?: {
    copy?: { game?: { title: string; slug: string } }
  }
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
    tokens_blocked: number
  }
}

export type ReturnPlace = { return_location_id?: number } | { return_event_id?: number }

export interface ReturnOptions {
  locations: ApiPickupLocation[]
  events: {
    id: number
    title: string
    date: string
    time: string | null
    end_time: string | null
    location: ApiPickupLocation | null
  }[]
}

export function useLoans() {
  const api = useApi()

  const fetchDashboard = () => api.get<DashboardData>('/dashboard')

  const fetchLoans = () => api.get<{ data: Loan[] }>('/loans')

  const createLoan = (payload: {
    copy_id: number
    pickup_location_id?: number
    /** Personal pickup event instead of a location — brings place, date and time. */
    pickup_event_id?: number
    start_date: string
    due_date: string
  }) => api.post<{ data: Loan }>('/loans', payload)

  // `place` says where it came back — required for games from a pickup event
  const returnLoan = (loanId: number, returnCondition: string, place: ReturnPlace = {}) =>
    api.post<{ data: Loan }>(`/loans/${loanId}/return`, {
      return_condition: returnCondition,
      ...place,
    })

  const returnPackageLoan = (
    packageLoanId: number,
    returnCondition: string,
    place: ReturnPlace = {}
  ) =>
    api.post(`/package-loans/${packageLoanId}/return`, {
      return_condition: returnCondition,
      ...place,
    })

  /** Where a loan (or a whole package) may be returned. */
  const fetchReturnOptions = (loan: Loan) =>
    api.get<ReturnOptions>(
      loan.package_loan_id
        ? `/package-loans/${loan.package_loan_id}/return-options`
        : `/loans/${loan.id}/return-options`
    )

  const requestExtension = (loanId: number, requestedDueDate: string) =>
    api.post(`/loans/${loanId}/extend`, { requested_due_date: requestedDueDate })

  const addFavorite = (gameId: number) => api.post('/favorites', { game_id: gameId })

  const removeFavorite = (gameId: number) => api.delete(`/favorites/${gameId}`)

  const addReservation = (gameId: number) => api.post('/reservations', { game_id: gameId })

  const removeReservation = (reservationId: number) => api.delete(`/reservations/${reservationId}`)

  const reportDamage = (loanId: number, description: string, photo?: File | null) => {
    const fd = new FormData()
    fd.append('loan_id', String(loanId))
    fd.append('description', description)
    if (photo) fd.append('photo', photo)
    return api.post('/damage-reports', fd)
  }

  const fetchTokenTransactions = (page = 1) =>
    api.get<{
      data: TokenTransaction[]
      meta: { current_page: number; last_page: number; total: number }
    }>(`/token-transactions?page=${page}`)

  return {
    fetchDashboard,
    fetchLoans,
    createLoan,
    returnLoan,
    returnPackageLoan,
    fetchReturnOptions,
    requestExtension,
    addFavorite,
    removeFavorite,
    addReservation,
    removeReservation,
    reportDamage,
    fetchTokenTransactions,
  }
}
