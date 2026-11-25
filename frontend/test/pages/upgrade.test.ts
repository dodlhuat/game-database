import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import UpgradePage from '~/pages/upgrade.vue'
import { useAuthStore } from '~/stores/auth'

const getMock = vi.hoisted(() => vi.fn())
const postMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useApi', () => {
  return () => ({ get: getMock, post: postMock, put: vi.fn(), patch: vi.fn(), delete: vi.fn() })
})

const navigateToMock = vi.hoisted(() => vi.fn())
mockNuxtImport('navigateTo', () => navigateToMock)

const PRICING = {
  fee_cents: 2400,
  currency: 'EUR',
  bonus_tokens: 20,
  bonus_value_cents: 1000,
  bonus_valid_months: 12,
}

const DAY = 86_400_000

function setUser(role: string, expiresInDays: number | null) {
  const auth = useAuthStore()
  auth.token = 'test-token'
  auth.user = {
    id: 1,
    name: 'Mia',
    email: 'mia@example.at',
    role,
    status: 'ACTIVE',
    tokens: 0,
    tokens_blocked: 0,
    membership_expires_at:
      expiresInDays === null ? null : new Date(Date.now() + expiresInDays * DAY).toISOString(),
  } as unknown as Parameters<typeof auth.setUser>[0]
  return auth
}

// The modal is tested on its own; here it only matters what it is handed
const ModalStub = {
  props: ['membership'],
  template:
    '<div v-if="membership" class="checkout-stub" :data-type="membership.type" :data-price="membership.price_cents" :data-plz="membership.address?.postal_code" />',
}

let wrapper: Awaited<ReturnType<typeof mountSuspended>> | null = null

async function mountPage() {
  wrapper = await mountSuspended(UpgradePage, {
    global: { stubs: { PaymentCheckoutModal: ModalStub } },
  })
  await flushPromises()
  return wrapper
}

function submitButton() {
  return wrapper!.findAll('.upgrade-form button').at(-1)!
}

async function fillAddress(street: string, plz: string, city: string) {
  const inputs = wrapper!.findAll('input')
  await inputs[0]!.setValue(street)
  await inputs[1]!.setValue(plz)
  await inputs[2]!.setValue(city)
}

beforeEach(() => {
  getMock.mockReset().mockResolvedValue(PRICING)
  postMock.mockReset().mockResolvedValue({ verified: true, status: 'verified' })
  navigateToMock.mockReset()
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
})

describe('/upgrade — becoming a member', () => {
  it('hands the chosen tier, config price and address to the checkout', async () => {
    setUser('USER', null)
    await mountPage()

    expect(submitButton().text()).toContain('24,00')
    await wrapper!.findAll('.tier-card')[1]!.trigger('click') // supporter
    await fillAddress('Hauptplatz 1', '4020', 'Linz')
    await submitButton().trigger('click')

    const stub = wrapper!.find('.checkout-stub')
    expect(stub.attributes('data-type')).toBe('SUPPORTER')
    expect(stub.attributes('data-price')).toBe('2400')
    expect(stub.attributes('data-plz')).toBe('4020')
  })

  it('does not open the checkout with an incomplete address', async () => {
    setUser('USER', null)
    await mountPage()

    await fillAddress('Hauptplatz 1', '402', 'Linz') // postal code too short
    await submitButton().trigger('click')

    expect(wrapper!.find('.checkout-stub').exists()).toBe(false)
    expect(wrapper!.find('.alert-error').exists()).toBe(true)
  })

  it('cannot start a checkout while the price is unknown', async () => {
    getMock.mockReset().mockRejectedValue({ message: 'down' })
    setUser('USER', null)
    await mountPage()

    expect(wrapper!.find('.alert-error').exists()).toBe(true)
    expect(submitButton().attributes('disabled')).toBeDefined()
  })
})

describe('/upgrade — existing memberships', () => {
  it('tells an active member there is nothing to buy', async () => {
    setUser('MEMBER', 100)
    await mountPage()

    expect(wrapper!.find('.already-member').exists()).toBe(true)
    expect(wrapper!.find('.tier-card').exists()).toBe(false)
  })

  it('offers an expired member a renewal instead of a new sign-up', async () => {
    setUser('MEMBER', -3)
    await mountPage()

    expect(wrapper!.find('.already-member--expired').exists()).toBe(true)
    expect(wrapper!.find('.tier-card').exists()).toBe(false)

    await wrapper!.find('.already-member--expired button').trigger('click')
    await flushPromises()

    expect(wrapper!.find('.checkout-stub').attributes('data-type')).toBe('RENEWAL')
  })

  it('upgrades a supporter to a full member and stores the new role', async () => {
    const auth = setUser('SUPPORTER', 100)
    postMock.mockResolvedValue({ user: { ...auth.user, role: 'MEMBER' } })
    await mountPage()

    await wrapper!.find('.already-member button').trigger('click')
    await flushPromises()

    expect(postMock).toHaveBeenCalledWith('/membership/activate-full')
    expect(auth.user?.role).toBe('MEMBER')
    expect(navigateToMock).toHaveBeenCalledWith('/dashboard')
  })

  it('keeps a supporter on the page when the upgrade fails', async () => {
    setUser('SUPPORTER', 100)
    postMock.mockRejectedValue({ message: 'Deine Mitgliedschaft ist abgelaufen.' })
    await mountPage()

    await wrapper!.find('.already-member button').trigger('click')
    await flushPromises()

    expect(wrapper!.find('.already-member .alert-error').text()).toBe(
      'Deine Mitgliedschaft ist abgelaufen.'
    )
    expect(navigateToMock).not.toHaveBeenCalled()
  })
})
