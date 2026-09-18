import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, DOMWrapper } from '@vue/test-utils'
import StripeCheckoutModal from '~/components/StripeCheckoutModal.vue'

// <Teleport to="body"> moves the dialog out of the mounted wrapper's own DOM
// subtree, so wrapper.find() can't see it — query document.body instead.
function modal() {
  return new DOMWrapper(document.body)
}

const loadMock = vi.fn()
mockNuxtImport('useStripeSdk', () => {
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

const pkg = { amount: 20, price_cents: 50, currency: 'EUR' }

// Tracked so the Teleported dialog content doesn't leak into the next test.
let activeWrapper: Awaited<ReturnType<typeof mountSuspended>> | null = null

async function mountModal(initialPkg: typeof pkg | null = pkg) {
  activeWrapper = await mountSuspended(StripeCheckoutModal, {
    props: { pkg: initialPkg, onSuccess: () => {}, onError: () => {}, onClose: () => {} },
  })
  await flushPromises()
  return activeWrapper
}

describe('StripeCheckoutModal', () => {
  let mountEl: ReturnType<typeof vi.fn>
  let confirmPayment: ReturnType<typeof vi.fn>
  let elementsFactory: ReturnType<typeof vi.fn>
  let createElement: ReturnType<typeof vi.fn>
  let stripeInstance: { elements: typeof elementsFactory; confirmPayment: typeof confirmPayment }

  beforeEach(() => {
    loadMock.mockReset()
    postMock.mockReset()
    mountEl = vi.fn()
    confirmPayment = vi.fn()
    createElement = vi.fn().mockReturnValue({ mount: mountEl, unmount: vi.fn() })
    elementsFactory = vi.fn().mockReturnValue({ create: createElement })
    stripeInstance = { elements: elementsFactory, confirmPayment }
    loadMock.mockResolvedValue(stripeInstance)
    postMock.mockResolvedValue({ clientSecret: 'pi_123_secret_abc' })
  })

  afterEach(() => {
    activeWrapper?.unmount()
    activeWrapper = null
  })

  it('renders nothing when no package is selected', async () => {
    await mountModal(null)

    expect(modal().find('.dialog').exists()).toBe(false)
    expect(postMock).not.toHaveBeenCalled()
  })

  it('creates a payment intent and mounts the Payment Element for the selected package', async () => {
    await mountModal()

    expect(postMock).toHaveBeenCalledWith('/tokens/checkout', { amount: 20 })
    expect(elementsFactory).toHaveBeenCalledWith({ clientSecret: 'pi_123_secret_abc' })
    expect(createElement).toHaveBeenCalledWith('payment')
    expect(mountEl).toHaveBeenCalledTimes(1)
    expect(modal().find('.dialog__title').text()).toContain('20 Token')
  })

  it('confirms the payment and emits success on submit', async () => {
    const wrapper = await mountModal()
    confirmPayment.mockResolvedValue({ paymentIntent: { id: 'pi_123', status: 'succeeded' } })
    postMock.mockResolvedValueOnce({ message: 'Token gutgeschrieben', user: { tokens: 40 } })

    await modal().find('form').trigger('submit.prevent')
    await flushPromises()

    expect(confirmPayment).toHaveBeenCalledWith(
      expect.objectContaining({ redirect: 'if_required' })
    )
    expect(postMock).toHaveBeenCalledWith('/tokens/confirm/pi_123')
    expect(wrapper.emitted('success')?.[0]?.[0]).toEqual({
      message: 'Token gutgeschrieben',
      user: { tokens: 40 },
    })
  })

  it('shows an inline error and stays open when the card is declined', async () => {
    const wrapper = await mountModal()
    confirmPayment.mockResolvedValue({ error: { message: 'Karte wurde abgelehnt.' } })

    await modal().find('form').trigger('submit.prevent')
    await flushPromises()

    expect(modal().find('.form-error').text()).toBe('Karte wurde abgelehnt.')
    expect(wrapper.emitted('error')).toBeUndefined()
    expect(modal().find('.dialog').exists()).toBe(true)
  })

  it('emits error and closes when preparing the checkout fails', async () => {
    postMock.mockReset()
    postMock.mockRejectedValue({ message: 'Zahlung konnte nicht vorbereitet werden.' })

    const wrapper = await mountModal()

    expect(wrapper.emitted('error')?.[0]?.[0]).toBe('Zahlung konnte nicht vorbereitet werden.')
    expect(wrapper.emitted('close')).toBeTruthy()
  })
})
