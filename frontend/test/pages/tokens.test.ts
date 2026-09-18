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
    global: { stubs: { StripeCheckoutModal: true, NuxtLink: true } },
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

    const modalStub = wrapper.findComponent({ name: 'StripeCheckoutModal' })
    expect(modalStub.props('pkg')).toBeNull()

    await wrapper.findAll('.token-card__buy')[0]!.trigger('click')

    expect(modalStub.props('pkg')).toEqual({
      amount: 20,
      price_cents: 50,
      currency: 'EUR',
      featured: false,
    })
  })

  it('shows a success alert, credits the user, and closes the modal on success', async () => {
    const auth = useAuthStore()
    const wrapper = await mountTokensPage()
    await flushPromises()
    fetchTokenTransactionsMock.mockClear()

    await wrapper.findAll('.token-card__buy')[0]!.trigger('click')
    const modalStub = wrapper.findComponent({ name: 'StripeCheckoutModal' })
    await modalStub.vm.$emit('success', {
      message: 'Token wurden gutgeschrieben.',
      user: { ...auth.user, tokens: 99 },
    })
    await flushPromises()

    expect(wrapper.find('.alert-success').text()).toBe('Token wurden gutgeschrieben.')
    expect(auth.user?.tokens).toBe(99)
    expect(fetchTokenTransactionsMock).toHaveBeenCalledWith(1)
    expect(modalStub.props('pkg')).toBeNull()
  })

  it('shows an error alert when the checkout modal emits error', async () => {
    const wrapper = await mountTokensPage()
    await flushPromises()

    const modalStub = wrapper.findComponent({ name: 'StripeCheckoutModal' })
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
})
