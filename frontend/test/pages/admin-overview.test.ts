import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, type VueWrapper } from '@vue/test-utils'
import AdminIndexPage from '~/pages/admin/index.vue'
import EmailLogsPage from '~/pages/admin/email-logs.vue'
import AdminLoansPage from '~/pages/admin/loans.vue'
import AdminUsersPage from '~/pages/admin/users.vue'

const fetchStatsMock = vi.hoisted(() => vi.fn())
const fetchAdminLoansMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useAdmin', () => () => ({
  fetchStats: fetchStatsMock,
  fetchAdminLoans: fetchAdminLoansMock,
  markOverdue: vi.fn(),
  sendOverdueReminder: vi.fn(),
}))

mockNuxtImport('usePickupLocations', () => () => ({
  fetchActiveLocations: vi.fn().mockResolvedValue([]),
}))

const getMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useApi', () => {
  return () => ({ get: getMock, post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() })
})

const notifyMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useDialogs', () => {
  return () => ({ confirmDialog: vi.fn(), notify: notifyMock })
})

const stats = (overdue: number, pending: number, review: number) => ({
  users: { total: 10, pending, active: 8 },
  loans: { active: 5, overdue, returned_today: 1 },
  copies: { to_review: review, to_review_by_location: [] },
})

const emptyPage = { data: [], meta: { current_page: 1, last_page: 1, total: 0 } }

let wrapper: VueWrapper | null = null

beforeEach(() => {
  fetchStatsMock.mockReset()
  fetchAdminLoansMock.mockReset().mockResolvedValue(emptyPage)
  getMock.mockReset().mockResolvedValue(emptyPage)
  notifyMock.mockReset()
  // the admin middleware reads the login from localStorage
  localStorage.setItem('auth_token', 'test-token')
  localStorage.setItem('auth_user', JSON.stringify({ id: 1, role: 'ADMIN' }))
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
  localStorage.clear()
})

describe('admin start page', () => {
  it('lists only what needs attention, each linking to the filtered list', async () => {
    fetchStatsMock.mockResolvedValue(stats(3, 0, 2))
    wrapper = await mountSuspended(AdminIndexPage)
    await flushPromises()

    const card = wrapper.find('.attention-card')
    expect(card.find('.attention-card__count').text()).toBe('2')
    expect(card.find('a[href="/admin/loans?status=OVERDUE"]').exists()).toBe(true)
    expect(card.find('a[href="/admin/copy-review"]').exists()).toBe(true)
    expect(card.find('a[href="/admin/users?status=PENDING"]').exists()).toBe(false)
  })

  it('shows no attention card when all is done', async () => {
    fetchStatsMock.mockResolvedValue(stats(0, 0, 0))
    wrapper = await mountSuspended(AdminIndexPage)
    await flushPromises()

    expect(wrapper.find('.attention-card').exists()).toBe(false)
  })

  it('the overdue link opens the loan list already filtered', async () => {
    wrapper = await mountSuspended(AdminLoansPage, {
      route: '/admin/loans?status=OVERDUE',
      global: { stubs: { AdminPickupLocationSelect: true } },
    })
    await flushPromises()

    expect(fetchAdminLoansMock).toHaveBeenCalledWith({ page: 1, status: 'OVERDUE' })
  })

  it('the pending link opens the users waiting for approval, with a way back to all', async () => {
    wrapper = await mountSuspended(AdminUsersPage, { route: '/admin/users?status=PENDING' })
    await flushPromises()

    expect(getMock).toHaveBeenCalledWith('/admin/users', { params: { page: 1, status: 'PENDING' } })
    expect(wrapper.find('.dash-section__title').text()).toBe('Warten auf Freischaltung')

    await wrapper.find('.filter-clear').trigger('click')
    await flushPromises()
    expect(getMock).toHaveBeenLastCalledWith('/admin/users', { params: { page: 1 } })
  })
})

describe('admin/email-logs.vue', () => {
  it('offers every mail by name in the filter and shows names in the list', async () => {
    getMock.mockResolvedValue({
      data: [
        {
          id: 1,
          recipient_email: 'mia@example.at',
          template_key: 'pickup_event_reminder',
          subject: 'Morgen: Abholung',
          sent_at: '2026-12-02T08:00:00Z',
          user: null,
        },
      ],
      meta: { current_page: 1, last_page: 1, total: 1 },
    })
    wrapper = await mountSuspended(EmailLogsPage, {
      global: {
        stubs: {
          UiVirtualDropdown: {
            props: ['options'],
            template:
              '<select class="stub-filter"><option v-for="o in options" :key="o.value">{{ o.label }}</option></select>',
          },
        },
      },
    })
    await flushPromises()

    const offered = wrapper.findAll('.stub-filter option').map((o) => o.text())
    expect(offered.length).toBeGreaterThan(20)
    expect(offered).toContain('Abholtermin morgen')
    expect(wrapper.find('.template-tag').text()).toBe('Abholtermin morgen')
  })

  it('starts a new date filter on the first page', async () => {
    getMock.mockResolvedValue({ data: [], meta: { current_page: 3, last_page: 3, total: 60 } })
    wrapper = await mountSuspended(EmailLogsPage, {
      global: { stubs: { UiVirtualDropdown: true } },
    })
    await flushPromises()

    // go to page 2 first
    await wrapper.findAll('.pagination__btn').at(-1)!.trigger('click')
    await flushPromises()
    expect(getMock).toHaveBeenLastCalledWith(
      '/admin/email-logs',
      expect.objectContaining({
        params: expect.objectContaining({ page: 2 }),
      })
    )

    await wrapper.find('input[type="date"]').setValue('2026-12-01')
    await wrapper.find('input[type="date"]').trigger('change')
    await flushPromises()

    expect(getMock).toHaveBeenLastCalledWith('/admin/email-logs', {
      params: { page: 1, template_key: undefined, date_from: '2026-12-01', date_to: undefined },
    })
  })
})
