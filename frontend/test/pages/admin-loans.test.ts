import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, type VueWrapper } from '@vue/test-utils'
import AdminLoansPage from '~/pages/admin/loans.vue'

const fetchAdminLoansMock = vi.hoisted(() => vi.fn())
const markOverdueMock = vi.hoisted(() => vi.fn())
const sendOverdueReminderMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useAdmin', () => {
  return () => ({
    fetchAdminLoans: fetchAdminLoansMock,
    markOverdue: markOverdueMock,
    sendOverdueReminder: sendOverdueReminderMock,
  })
})

mockNuxtImport('usePickupLocations', () => {
  return () => ({ fetchActiveLocations: vi.fn().mockResolvedValue([]) })
})

const notifyMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useDialogs', () => {
  return () => ({ confirmDialog: vi.fn(), notify: notifyMock })
})

function loan(id: number, title: string, status: string, extra: Record<string, unknown> = {}) {
  return {
    id,
    user: { id: 2, name: 'Mia' },
    game: { title },
    start_date: '2026-11-01',
    due_date: '2026-11-29',
    status,
    overdue_reminder_sent_at: null,
    package_loan_id: null,
    pickup_location: null,
    ...extra,
  }
}

const page = (data: unknown[], meta = { current_page: 1, last_page: 1, total: data.length }) => ({
  data,
  meta,
})

let wrapper: VueWrapper | null = null

async function mountPage() {
  wrapper = await mountSuspended(AdminLoansPage, {
    // the location picker is tested on its own
    global: { stubs: { AdminPickupLocationSelect: true } },
  })
  await flushPromises()
  return wrapper
}

function row(title: string) {
  return wrapper!.findAll('tbody tr').find((r) => r.text().includes(title))!
}

beforeEach(() => {
  fetchAdminLoansMock.mockReset()
  markOverdueMock.mockReset()
  sendOverdueReminderMock.mockReset()
  notifyMock.mockReset()
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
})

describe('admin/loans.vue', () => {
  it('filters by status from the first page', async () => {
    fetchAdminLoansMock.mockResolvedValue(page([loan(1, 'Azul', 'ACTIVE')]))
    await mountPage()
    expect(fetchAdminLoansMock).toHaveBeenLastCalledWith({ page: 1 })

    await wrapper!
      .findAll('.filter-tab')
      .find((t) => t.text() === 'Überfällig')!
      .trigger('click')
    await flushPromises()

    expect(fetchAdminLoansMock).toHaveBeenLastCalledWith({ page: 1, status: 'OVERDUE' })
  })

  it('offers "overdue" for running loans and a reminder for overdue ones', async () => {
    fetchAdminLoansMock.mockResolvedValue(
      page([
        loan(1, 'Azul', 'ACTIVE'),
        loan(2, 'Catan', 'OVERDUE', { overdue_reminder_sent_at: '2026-11-20T09:00:00Z' }),
        loan(3, 'Dixit', 'RETURNED'),
      ])
    )
    await mountPage()

    expect(row('Azul').text()).toContain('Als überfällig markieren')
    expect(row('Catan').text()).toContain('Erinnerung senden')
    expect(row('Catan').text()).toContain('Gesendet am 20.11.2026')
    expect(row('Dixit').find('.action-btn').exists()).toBe(false)
  })

  it('marks a loan overdue and reloads', async () => {
    fetchAdminLoansMock.mockResolvedValue(page([loan(1, 'Azul', 'ACTIVE')]))
    markOverdueMock.mockResolvedValue({})
    await mountPage()

    await row('Azul').find('.action-btn--danger').trigger('click')
    await flushPromises()

    expect(markOverdueMock).toHaveBeenCalledWith(1)
    expect(fetchAdminLoansMock).toHaveBeenCalledTimes(2)
  })

  it('confirms a sent reminder', async () => {
    fetchAdminLoansMock.mockResolvedValue(page([loan(2, 'Catan', 'OVERDUE')]))
    sendOverdueReminderMock.mockResolvedValue({})
    await mountPage()

    await row('Catan').find('.action-btn--remind').trigger('click')
    await flushPromises()

    expect(sendOverdueReminderMock).toHaveBeenCalledWith(2)
    expect(notifyMock).toHaveBeenCalledWith('Erinnerung verschickt.')
  })

  it('says so when a reminder cannot be sent', async () => {
    fetchAdminLoansMock.mockResolvedValue(page([loan(2, 'Catan', 'OVERDUE')]))
    sendOverdueReminderMock.mockRejectedValue({})
    await mountPage()

    await row('Catan').find('.action-btn--remind').trigger('click')
    await flushPromises()

    expect(notifyMock).toHaveBeenCalledWith(
      'Die Erinnerung konnte nicht verschickt werden.',
      'error'
    )
    expect(row('Catan').find('.action-btn--remind').attributes('disabled')).toBeUndefined()
  })

  it('pages through the list and keeps the filter', async () => {
    fetchAdminLoansMock.mockResolvedValue(
      page([loan(1, 'Azul', 'OVERDUE')], { current_page: 1, last_page: 2, total: 26 })
    )
    await mountPage()
    await wrapper!
      .findAll('.filter-tab')
      .find((t) => t.text() === 'Überfällig')!
      .trigger('click')
    await flushPromises()

    await wrapper!.find('.admin-pagination button[aria-label="Nächste Seite"]').trigger('click')
    await flushPromises()

    expect(fetchAdminLoansMock).toHaveBeenLastCalledWith({ page: 2, status: 'OVERDUE' })
  })
})
