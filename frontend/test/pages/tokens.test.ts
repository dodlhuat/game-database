import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, type DOMWrapper } from '@vue/test-utils'
import { useAuthStore } from '~/stores/auth'
import TokensPage from '~/pages/tokens.vue'

const getMock = vi.fn()
mockNuxtImport('useApi', () => {
  return () => ({
    get: getMock,
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    download: vi.fn(),
  })
})

const fetchTokenTransactionsMock = vi.fn()
mockNuxtImport('useLoans', () => {
  return () => ({ fetchTokenTransactions: fetchTokenTransactionsMock })
})

function setMemberUser() {
  const auth = useAuthStore()
  auth.token = 'test-token'
  auth.user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    address: null,
    street: null,
    postal_code: null,
    city: null,
    date_of_birth: null,
    role: 'MEMBER',
    status: 'ACTIVE',
    newsletter_opt_in: false,
    tokens: 5,
    tokens_blocked: 0,
    membership_expires_at: new Date(Date.now() + 86_400_000).toISOString(),
    is_member: true,
    email_verified_at: new Date().toISOString(),
  } as Parameters<typeof auth.setUser>[0]

  return auth
}

let activeWrapper: Awaited<ReturnType<typeof mountSuspended>> | null = null

async function mountTokensPage() {
  activeWrapper = await mountSuspended(TokensPage, {
    global: { stubs: { PaymentCheckoutModal: true, NuxtLink: true } },
  })
  return activeWrapper
}

describe('tokens.vue', () => {
  beforeEach(() => {
    getMock.mockReset()
    fetchTokenTransactionsMock.mockReset()
    fetchTokenTransactionsMock.mockResolvedValue({ data: [], meta: { last_page: 1, total: 0 } })
    getMock.mockResolvedValue({
      data: [
        { amount: 20, price_cents: 50, currency: 'EUR' },
        { amount: 30, price_cents: 100, currency: 'EUR' },
        { amount: 40, price_cents: 150, currency: 'EUR' },
      ],
    })
    setMemberUser()
  })

  afterEach(() => {
    activeWrapper?.unmount()
    activeWrapper = null
  })

  it('loads and renders the packages with formatted prices, marking the middle one featured', async () => {
    const wrapper = await mountTokensPage()
    await flushPromises()

    expect(getMock).toHaveBeenCalledWith('/tokens/packages')
    const cards = wrapper.findAll('.token-card')
    expect(cards).toHaveLength(3)
    expect(cards[1]?.classes()).toContain('token-card--featured')
    expect(cards[0]?.classes()).not.toContain('token-card--featured')

    const prices = cards.map((c: DOMWrapper<Element>) => c.find('.token-card__price').text())
    expect(prices[0]).toContain('0,50')
    expect(prices[1]).toContain('1,00')
    expect(prices[2]).toContain('1,50')
  })

  it('opens the checkout modal for the clicked package', async () => {
    const wrapper = await mountTokensPage()
    await flushPromises()

    const modalStub = wrapper.findComponent({ name: 'PaymentCheckoutModal' })
    expect(modalStub.props('pkg')).toBeNull()

    await wrapper.findAll('.token-card__buy')[0]!.trigger('click')

    expect(modalStub.props('pkg')).toEqual({
      amount: 20,
      price_cents: 50,
      currency: 'EUR',
      featured: false,
    })
  })

  it('shows an error alert when the checkout modal emits error', async () => {
    const wrapper = await mountTokensPage()
    await flushPromises()

    const modalStub = wrapper.findComponent({ name: 'PaymentCheckoutModal' })
    await modalStub.vm.$emit('error', 'Zahlung wurde abgelehnt.')
    await flushPromises()

    expect(wrapper.find('.alert-error').text()).toBe('Zahlung wurde abgelehnt.')
  })

  it('shows the not-a-member state and never fetches packages for a non-member', async () => {
    const auth = useAuthStore()
    auth.user = { ...auth.user, role: 'USER', membership_expires_at: null } as Parameters<
      typeof auth.setUser
    >[0]

    const wrapper = await mountTokensPage()
    await flushPromises()

    expect(wrapper.find('.empty-state').exists()).toBe(true)
    expect(wrapper.findAll('.token-card')).toHaveLength(0)
  })

  it('shows the bonus balance with its expiry date when the user has bonus tokens', async () => {
    const auth = setMemberUser()
    auth.user = {
      ...auth.user!,
      bonus_tokens: 20,
      bonus_lots: [{ remaining: 20, expires_at: '2027-03-15T00:00:00Z' }],
    }

    const wrapper = await mountTokensPage()
    await flushPromises()

    const chip = wrapper.find('.balance-chip--bonus')
    expect(chip.exists()).toBe(true)
    expect(chip.text()).toContain('20')
    expect(chip.text()).toContain('15.03.2027')
  })

  it('hides the bonus chip when there are no bonus tokens', async () => {
    const wrapper = await mountTokensPage()
    await flushPromises()

    expect(wrapper.find('.balance-chip--bonus').exists()).toBe(false)
  })

  it('lists the current prices from the loan settings', async () => {
    setMemberUser()
    getMock.mockImplementation(async (url: string) =>
      url === '/loan-settings'
        ? {
            loan_cost_small: 3,
            loan_cost_medium: 6,
            loan_cost_large: 10,
            package_loan_cost: 4,
            extension_cost: 2,
            max_extensions: 2,
            reservation_cost: 1,
          }
        : { data: [] }
    )

    const wrapper = await mountSuspended(TokensPage, { route: '/tokens' })
    await flushPromises()
    const rows = wrapper.findAll('.price-row').map((r: DOMWrapper<Element>) => r.text())

    expect(rows[0]).toContain('Spiel ausleihen')
    expect(rows[0]).toContain('3 / 6 / 10')
    expect(rows[1]).toContain('Paket ausleihen')
    expect(rows[1]).toContain('4')
    expect(rows[2]).toContain('bis zu 2-mal pro Ausleihe')
    expect(rows[2]).toContain('2')
    expect(rows[3]).toContain('Spiel reservieren')
    expect(rows[3]).toContain('1')
    expect(rows[4]).toContain('Kaution')
  })
})

describe('tokens.vue — derived figures', () => {
  beforeEach(() => {
    fetchTokenTransactionsMock.mockReset()
    setMemberUser()
  })

  afterEach(() => {
    activeWrapper?.unmount()
    activeWrapper = null
  })

  it('shows the per-token price and the saving against the smallest package', async () => {
    fetchTokenTransactionsMock.mockResolvedValue({ data: [], meta: { last_page: 1, total: 0 } })
    getMock.mockImplementation(async (url: string) =>
      url === '/tokens/packages'
        ? {
            data: [
              { amount: 20, price_cents: 1000, currency: 'EUR' },
              { amount: 40, price_cents: 1850, currency: 'EUR' },
            ],
          }
        : Promise.reject(new Error('no settings'))
    )

    const wrapper = await mountTokensPage()
    await flushPromises()
    const cards = wrapper.findAll('.token-card')

    expect(cards[0]!.find('.token-card__rate').text()).toContain('0,50')
    expect(cards[0]!.find('.token-card__save').exists()).toBe(false)
    expect(cards[1]!.find('.token-card__rate').text()).toContain('0,46')
    expect(cards[1]!.find('.token-card__save').text()).toContain('7 %')
  })

  it('tells how many games of each size the free balance covers', async () => {
    const auth = useAuthStore()
    auth.user = { ...auth.user!, tokens: 20 }
    fetchTokenTransactionsMock.mockResolvedValue({ data: [], meta: { last_page: 1, total: 0 } })
    getMock.mockImplementation(async (url: string) =>
      url === '/loan-settings'
        ? {
            loan_cost_small: 3,
            loan_cost_medium: 6,
            loan_cost_large: 10,
            package_loan_cost: 4,
            extension_cost: 2,
            max_extensions: 2,
            reservation_cost: 1,
          }
        : { data: [] }
    )

    const wrapper = await mountTokensPage()
    await flushPromises()
    const counts = wrapper.findAll('.capacity__count').map((c: DOMWrapper<Element>) => c.text())

    expect(counts).toEqual(['6×', '3×', '2×'])
  })

  it('hides provider payment references in the history', async () => {
    getMock.mockResolvedValue({ data: [] })
    fetchTokenTransactionsMock.mockResolvedValue({
      data: [
        {
          id: 1,
          type: 'PURCHASE',
          amount: 30,
          description: 'Stripe-Kauf (Payment Intent pi_123)',
          created_at: '2026-09-18T10:00:00Z',
        },
        {
          id: 2,
          type: 'BORROW',
          amount: -6,
          description: 'Azul',
          created_at: '2026-08-02T10:00:00Z',
        },
      ],
      meta: { last_page: 1, total: 2 },
    })

    const wrapper = await mountTokensPage()
    await flushPromises()
    const rows = wrapper.findAll('.tx-row')

    expect(wrapper.text()).not.toContain('pi_123')
    expect(rows[0]!.find('.tx-row__detail').exists()).toBe(false)
    expect(rows[1]!.find('.tx-row__detail').text()).toBe('Azul')
    expect(wrapper.findAll('.tx-group__month')).toHaveLength(2)
  })
})
