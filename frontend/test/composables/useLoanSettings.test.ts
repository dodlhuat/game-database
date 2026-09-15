import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { defineComponent } from 'vue'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import { useLoanSettings, type LoanSettings } from '~/composables/useLoanSettings'

const Host = defineComponent({
  setup(_, { expose }) {
    expose(useLoanSettings())

    return () => null
  },
})

async function mountHost() {
  const wrapper = await mountSuspended(Host)

  return wrapper.vm as unknown as ReturnType<typeof useLoanSettings>
}

// Local-calendar-date comparison — the composable parses/produces dates via
// local midnight (new Date('YYYY-MM-DD' + 'T00:00:00')), so asserting via
// toISOString() (UTC) would shift by a day depending on the machine's
// timezone. This matches what the function actually operates on.
function localIso(date: Date): string {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

const baseSettings: LoanSettings = {
  start_date: '2026-01-01',
  interval_days: 7,
  grace_days: 2,
  loan_duration_weeks: 4,
  max_extensions: 2,
  loan_cost: 2,
  condition_very_good_after: 5,
  condition_good_after: 10,
  deposit_pct_very_good: 0,
  deposit_pct_good: 0,
}

describe('useLoanSettings', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2026-01-01T12:00:00'))
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('picks the next interval slot after today + grace days', async () => {
    const { getNextAppointment } = await mountHost()

    // today=Jan 1, grace=2 -> deadline Jan 3; start Jan 1, interval 7
    // -> 1st slot on/after the deadline is Jan 8
    const appointment = getNextAppointment(baseSettings)
    expect(localIso(appointment)).toBe('2026-01-08')
  })

  it('returns the start date itself when today is still before it', async () => {
    const { getNextAppointment } = await mountHost()

    const appointment = getNextAppointment({ ...baseSettings, start_date: '2026-06-01' })
    expect(localIso(appointment)).toBe('2026-06-01')
  })

  it('falls back to deadline + 1 day when interval_days is not positive', async () => {
    const { getNextAppointment } = await mountHost()

    const appointment = getNextAppointment({ ...baseSettings, interval_days: 0 })
    // deadline = Jan 3 (grace_days=2) -> fallback = Jan 4
    expect(localIso(appointment)).toBe('2026-01-04')
  })

  it('falls back to deadline + 1 day when start_date is not a valid date', async () => {
    const { getNextAppointment } = await mountHost()

    const appointment = getNextAppointment({ ...baseSettings, start_date: 'not-a-date' })
    expect(localIso(appointment)).toBe('2026-01-04')
  })

  it('computes the due date as the appointment plus the loan duration in weeks', async () => {
    const { getDueDate } = await mountHost()

    const due = getDueDate(new Date('2026-01-08T00:00:00'), {
      ...baseSettings,
      loan_duration_weeks: 4,
    })
    expect(localIso(due)).toBe('2026-02-05')
  })

  it('formats a date as DD.MM.YYYY', async () => {
    const { formatDate } = await mountHost()

    expect(formatDate(new Date('2026-01-08T00:00:00'))).toBe('08.01.2026')
  })

  it('converts a date to an ISO yyyy-mm-dd string', async () => {
    const { toIsoDate } = await mountHost()

    // toIsoDate itself goes through toISOString() (UTC) — assert against the
    // same conversion here rather than a local date to stay timezone-safe.
    const date = new Date('2026-01-08T00:00:00')
    expect(toIsoDate(date)).toBe(date.toISOString().slice(0, 10))
  })
})
