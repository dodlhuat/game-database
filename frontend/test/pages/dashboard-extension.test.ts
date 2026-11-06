import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import DashboardPage from '~/pages/dashboard/index.vue'
import { useAuthStore } from '~/stores/auth'
import type { DashboardData, Loan } from '~/composables/useLoans'

const fetchDashboardMock = vi.fn()
const requestExtensionMock = vi.fn()
mockNuxtImport('useLoans', () => {
  return () => ({
    fetchDashboard: fetchDashboardMock,
    returnLoan: vi.fn(),
    requestExtension: requestExtensionMock,
    removeReservation: vi.fn(),
    reportDamage: vi.fn(),
  })
})

function loan(overrides: Partial<Loan> = {}): Loan {
  return {
    id: 1,
    game: { title: 'Catan', slug: 'catan' },
    start_date: '2026-11-01',
    due_date: '2026-11-29',
    returned_at: null,
    return_condition: null,
    deposit_tokens: 0,
    status: 'ACTIVE',
    is_overdue: false,
    extensions: [],
    can_extend: true,
    extension_blocked_reason: null,
    next_extension_cost: 0,
    ...overrides,
  } as Loan
}

function dashboard(active: Loan[]): DashboardData {
  return {
    active_loans: active,
    loan_history: [],
    reservations: [],
    stats: {
      total_loans: 1,
      active_loans_count: active.length,
      overdue_count: 0,
      reservations_count: 0,
      tokens_blocked: 0,
    },
  }
}

// useAsyncData('dashboard') only runs again once the previous page is unmounted
let mounted: { unmount: () => void } | null = null

async function mountDashboard(active: Loan[]) {
  const auth = useAuthStore()
  auth.token = 'test-token'
  auth.user = {
    id: 1,
    name: 'Test',
    role: 'MEMBER',
    tokens: 5,
    bonus_tokens: 0,
  } as typeof auth.user
  fetchDashboardMock.mockResolvedValue(dashboard(active))
  const wrapper = await mountSuspended(DashboardPage, { route: '/dashboard' })
  await flushPromises()
  mounted = wrapper
  return wrapper
}

const extendButton = (wrapper: Awaited<ReturnType<typeof mountDashboard>>) =>
  wrapper.findAll('.action-btn').find((b) => b.text() === wrapper.vm.$t('btn.extend'))

describe('dashboard — extending a loan', () => {
  beforeEach(() => {
    fetchDashboardMock.mockReset()
    requestExtensionMock.mockReset()
  })

  afterEach(() => {
    mounted?.unmount()
    mounted = null
  })

  it('explains why a reserved game cannot be extended instead of offering the button', async () => {
    const wrapper = await mountDashboard([
      loan({ can_extend: false, extension_blocked_reason: 'reserved' }),
    ])

    expect(extendButton(wrapper)).toBeUndefined()
    expect(wrapper.find('.extension-note').text()).toBe(
      wrapper.vm.$t('dashboard.extension_reserved')
    )
  })

  it('shows when the maximum number of extensions is reached', async () => {
    const wrapper = await mountDashboard([
      loan({ can_extend: false, extension_blocked_reason: 'max_reached' }),
    ])

    expect(extendButton(wrapper)).toBeUndefined()
    expect(wrapper.find('.extension-note').text()).toBe(
      wrapper.vm.$t('dashboard.extension_max_reached')
    )
  })

  it('shows the first extension as free', async () => {
    const wrapper = await mountDashboard([loan()])

    await extendButton(wrapper)!.trigger('click')

    expect(wrapper.find('.extension-cost').text()).toContain(
      wrapper.vm.$t('dashboard.extension_free')
    )
    expect(wrapper.find('.extension-cost__hint').exists()).toBe(false)
  })

  it('shows the price of a paid extension', async () => {
    const wrapper = await mountDashboard([loan({ status: 'EXTENDED', next_extension_cost: 2 })])

    await extendButton(wrapper)!.trigger('click')

    expect(wrapper.find('.extension-cost').text()).toContain('2 Token')
    expect(wrapper.find('.extension-cost__hint').exists()).toBe(true)
  })
})
