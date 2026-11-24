import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import PaymentReturnPage from '~/pages/zahlung.vue'
import { useAuthStore } from '~/stores/auth'

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

const navigateToMock = vi.hoisted(() => vi.fn())
mockNuxtImport('navigateTo', () => navigateToMock)

let activeWrapper: Awaited<ReturnType<typeof mountSuspended>> | null = null

async function visit(query: string) {
  activeWrapper = await mountSuspended(PaymentReturnPage, { route: `/zahlung${query}` })
  await flushPromises()
  return activeWrapper
}

beforeEach(() => {
  getMock.mockReset()
  navigateToMock.mockReset()
  sessionStorage.clear()
  vi.useFakeTimers({ shouldAdvanceTime: true })
})

afterEach(() => {
  activeWrapper?.unmount()
  activeWrapper = null
  vi.useRealTimers()
})

describe('/zahlung — return from Mollie', () => {
  it('shows success, refreshes the user and leads back to where the member started', async () => {
    sessionStorage.setItem('payment_return_to', '/account')
    getMock.mockResolvedValue({
      status: 'paid',
      message: '20 Token wurden deinem Konto gutgeschrieben.',
      user: { id: 1, name: 'Mia', tokens: 25 },
    })

    const wrapper = await visit('?art=tokens&id=5')

    expect(getMock).toHaveBeenCalledWith('/payments/tokens/5')
    expect(wrapper.find('.stage--paid').exists()).toBe(true)
    expect(wrapper.find('.stage__text').text()).toBe('20 Token wurden deinem Konto gutgeschrieben.')
    expect(useAuthStore().user?.tokens).toBe(25)

    await vi.advanceTimersByTimeAsync(4000)
    expect(navigateToMock).toHaveBeenCalledWith('/account')
  })

  it('keeps asking while the payment is pending, then settles', async () => {
    getMock
      .mockResolvedValueOnce({ status: 'pending', message: '', user: null })
      .mockResolvedValueOnce({ status: 'paid', message: 'Bezahlt.', user: null })

    const wrapper = await visit('?art=membership&id=3')
    expect(wrapper.find('.stage--checking').exists()).toBe(true)

    await vi.advanceTimersByTimeAsync(2000)
    await flushPromises()

    expect(getMock).toHaveBeenCalledTimes(2)
    expect(wrapper.find('.stage--paid').exists()).toBe(true)
  })

  it('offers a retry when the payment was cancelled', async () => {
    getMock.mockResolvedValue({ status: 'failed', message: 'Abgebrochen.', user: null })

    const wrapper = await visit('?art=tokens&id=5')

    expect(wrapper.find('.stage--failed').exists()).toBe(true)
    expect(wrapper.find('.stage__cta').text()).toBe('Erneut versuchen')
  })

  it('does not call the API for a broken link', async () => {
    const wrapper = await visit('?art=stripe&id=abc')

    expect(getMock).not.toHaveBeenCalled()
    expect(wrapper.find('.stage--error').exists()).toBe(true)
  })
})
