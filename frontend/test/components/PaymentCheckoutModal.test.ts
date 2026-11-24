import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, DOMWrapper } from '@vue/test-utils'
import PaymentCheckoutModal from '~/components/PaymentCheckoutModal.vue'

// <Teleport to="body"> moves the dialog out of the wrapper's subtree
function modal() {
  return new DOMWrapper(document.body)
}

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

const pkg = { amount: 20, price_cents: 1000, currency: 'EUR' }
const assignMock = vi.fn()
let activeWrapper: Awaited<ReturnType<typeof mountSuspended>> | null = null

async function mountModal(props: Record<string, unknown>) {
  activeWrapper = await mountSuspended(PaymentCheckoutModal, {
    props: { onError: () => {}, onClose: () => {}, ...props },
  })
  await flushPromises()
  return activeWrapper
}

beforeEach(() => {
  postMock.mockReset()
  assignMock.mockReset()
  sessionStorage.clear()
  // happy-dom would try to navigate; we only want to see where it would go
  vi.spyOn(window.location, 'assign').mockImplementation(assignMock)
})

afterEach(() => {
  activeWrapper?.unmount()
  activeWrapper = null
  vi.restoreAllMocks()
})

describe('PaymentCheckoutModal', () => {
  it('renders nothing without a package or membership', async () => {
    await mountModal({ pkg: null })
    expect(modal().find('.dialog').exists()).toBe(false)
  })

  it('shows what is paid and how much', async () => {
    await mountModal({ pkg })
    expect(modal().find('.summary__item').text()).toBe('20 Token')
    expect(modal().find('.summary__price').text()).toContain('10,00')
  })

  it('opens the Mollie checkout and remembers where the member started', async () => {
    postMock.mockResolvedValue({ checkoutUrl: 'https://www.mollie.com/checkout/tr_1', id: 7 })
    await mountModal({ pkg })

    await modal().find('.dialog__pay').trigger('click')
    await flushPromises()

    expect(postMock).toHaveBeenCalledWith('/tokens/checkout', { amount: 20 })
    expect(assignMock).toHaveBeenCalledWith('https://www.mollie.com/checkout/tr_1')
    expect(sessionStorage.getItem('payment_return_to')).toBe('/')
  })

  it('sends membership type and address to the membership checkout', async () => {
    postMock.mockResolvedValue({ checkoutUrl: 'https://www.mollie.com/checkout/tr_2', id: 3 })
    await mountModal({
      membership: {
        type: 'MEMBER',
        price_cents: 2400,
        currency: 'EUR',
        address: { street: 'Hauptplatz 1', postal_code: '4020', city: 'Linz' },
      },
    })

    await modal().find('.dialog__pay').trigger('click')
    await flushPromises()

    expect(postMock).toHaveBeenCalledWith('/membership/checkout', {
      type: 'MEMBER',
      street: 'Hauptplatz 1',
      postal_code: '4020',
      city: 'Linz',
    })
  })

  it('stays open with an inline error when the checkout cannot be prepared', async () => {
    postMock.mockRejectedValue({ message: 'Zahlung konnte nicht vorbereitet werden.' })
    await mountModal({ pkg })

    await modal().find('.dialog__pay').trigger('click')
    await flushPromises()

    expect(modal().find('.form-error').text()).toBe('Zahlung konnte nicht vorbereitet werden.')
    expect(assignMock).not.toHaveBeenCalled()
    expect(modal().find('.dialog__pay').attributes('disabled')).toBeUndefined()
  })
})
