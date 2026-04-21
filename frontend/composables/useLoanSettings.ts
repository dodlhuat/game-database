export interface LoanSettings {
  start_date: string
  interval_days: number
  grace_days: number
  loan_duration_weeks: number
  max_extensions: number
  loan_cost: number
  condition_very_good_after: number
  condition_good_after: number
  deposit_pct_very_good: number
  deposit_pct_good: number
}

export function useLoanSettings() {
  const api = useApi()

  async function fetchSettings(): Promise<LoanSettings> {
    return api.get<LoanSettings>('/loan-settings')
  }

  function getNextAppointment(settings: LoanSettings): Date {
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    const deadline = new Date(today)
    deadline.setDate(deadline.getDate() + settings.grace_days)

    const startDate = new Date(settings.start_date + 'T00:00:00')

    if (settings.interval_days <= 0 || isNaN(startDate.getTime())) {
      const fallback = new Date(deadline)
      fallback.setDate(fallback.getDate() + 1)
      return fallback
    }

    const msPerDay = 24 * 60 * 60 * 1000
    const daysSinceStart = (deadline.getTime() - startDate.getTime()) / msPerDay
    const n = daysSinceStart < 0 ? 0 : Math.floor(daysSinceStart / settings.interval_days) + 1
    const appointment = new Date(startDate)
    appointment.setDate(appointment.getDate() + n * settings.interval_days)
    return appointment
  }

  function getDueDate(appointment: Date, settings: LoanSettings): Date {
    const due = new Date(appointment)
    due.setDate(due.getDate() + settings.loan_duration_weeks * 7)
    return due
  }

  function formatDate(date: Date): string {
    return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' })
  }

  function toIsoDate(date: Date): string {
    return date.toISOString().slice(0, 10)
  }

  return { fetchSettings, getNextAppointment, getDueDate, formatDate, toIsoDate }
}
