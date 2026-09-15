import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import PayPalButtons from '~/components/PayPalButtons.vue'

const loadMock = vi.fn()
mockNuxtImport('usePayPalSdk', () => {
  return () => ({ load: loadMock })
})

const postMock = vi.fn()
mockNuxtImport('useApi', () => {
  return () => ({
    get: vi.fn(),
    post: postMock,
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    download: vi.fn(),
  })
})

describe('PayPalButtons', () => {
  let renderMock: ReturnType<typeof vi.fn>
  let buttonsFactory: ReturnType<typeof vi.fn>

  beforeEach(() => {
    loadMock.mockReset()
    postMock.mockReset()
    renderMock = vi.fn().mockResolvedValue(undefined)
    buttonsFactory = vi.fn().mockReturnValue({ render: renderMock, close: vi.fn() })
    ;(window as unknown as { paypal?: unknown }).paypal = { Buttons: buttonsFactory }
  })

  it('renders the PayPal button once the SDK is loaded', async () => {
    loadMock.mockResolvedValue(undefined)

    await mountSuspended(PayPalButtons, { props: { amount: 20 } })
    await flushPromises()

    expect(buttonsFactory).toHaveBeenCalledTimes(1)
    expect(renderMock).toHaveBeenCalledTimes(1)
  })

  it('createOrder posts the token amount and returns the orderID', async () => {
    loadMock.mockResolvedValue(undefined)

    await mountSuspended(PayPalButtons, { props: { amount: 30 } })
    await flushPromises()

    postMock.mockResolvedValueOnce({ orderID: 'ORDER-1' })
    const options = buttonsFactory.mock.calls[0]![0]
    const orderId = await options.createOrder()

    expect(postMock).toHaveBeenCalledWith('/tokens/checkout', { amount: 30 })
    expect(orderId).toBe('ORDER-1')
  })

  it('onApprove captures the order and emits success with the server payload', async () => {
    loadMock.mockResolvedValue(undefined)

    const wrapper = await mountSuspended(PayPalButtons, { props: { amount: 20 } })
    await flushPromises()

    postMock.mockResolvedValueOnce({ message: 'Token gutgeschrieben', user: { tokens: 40 } })
    const options = buttonsFactory.mock.calls[0]![0]
    await options.onApprove({ orderID: 'ORDER-1' })

    expect(postMock).toHaveBeenCalledWith('/tokens/capture/ORDER-1')
    expect(wrapper.emitted('success')?.[0]?.[0]).toEqual({
      message: 'Token gutgeschrieben',
      user: { tokens: 40 },
    })
  })

  it('emits error when the capture call fails', async () => {
    loadMock.mockResolvedValue(undefined)

    const wrapper = await mountSuspended(PayPalButtons, { props: { amount: 20 } })
    await flushPromises()

    postMock.mockRejectedValueOnce({ message: 'Zahlung wurde abgelehnt.' })
    const options = buttonsFactory.mock.calls[0]![0]
    await options.onApprove({ orderID: 'ORDER-1' })

    expect(wrapper.emitted('error')?.[0]?.[0]).toBe('Zahlung wurde abgelehnt.')
  })

  it('emits error when the SDK fails to load, without rendering a button', async () => {
    loadMock.mockRejectedValue(new Error('PayPal SDK konnte nicht geladen werden.'))

    const wrapper = await mountSuspended(PayPalButtons, { props: { amount: 20 } })
    await flushPromises()

    expect(wrapper.emitted('error')?.[0]?.[0]).toBe('PayPal SDK konnte nicht geladen werden.')
    expect(buttonsFactory).not.toHaveBeenCalled()
  })
})
