export interface LoanSettings {
  start_date: string
  interval_days: number
  grace_days: number
  loan_duration_weeks: number
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
    let n = 0

    while (true) {
      const appointment = new Date(startDate)
      appointment.setDate(appointment.getDate() + n * settings.interval_days)
      if (appointment > deadline) {
        return appointment
      }
      n++
    }
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
