import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import AdminCancellationsPage from '~/pages/admin/cancellations.vue'

const getMock = vi.hoisted(() => vi.fn())
const patchMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useApi', () => {
  return () => ({ get: getMock, post: vi.fn(), put: vi.fn(), patch: patchMock, delete: vi.fn() })
})

const notifyMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useDialogs', () => {
  return () => ({ confirmDialog: vi.fn(), notify: notifyMock })
})

const duplicate = {
  id: 4,
  user: { id: 2, name: 'Mia', email: 'mia@example.at' },
  type: 'MEMBER',
  price_cents: 2400,
  provider_payment_id: 'tr_2',
  paid_at: '2026-11-25T10:00:00Z',
}

let duplicates: unknown[] = []
let wrapper: Awaited<ReturnType<typeof mountSuspended>> | null = null

describe('admin/cancellations.vue — double payments', () => {
  beforeEach(() => {
    getMock.mockReset()
    patchMock.mockReset()
    notifyMock.mockReset()
    getMock.mockImplementation((path: string) =>
      Promise.resolve(
        path === '/admin/duplicate-payments'
          ? { data: duplicates }
          : { data: [], meta: { current_page: 1, last_page: 1, total: 0 } }
      )
    )
  })

  afterEach(() => {
    wrapper?.unmount()
    wrapper = null
  })

  it('stays out of the way while nothing is to be refunded', async () => {
    duplicates = []
    wrapper = await mountSuspended(AdminCancellationsPage)
    await flushPromises()

    expect(wrapper.find('.dash-section--alert').exists()).toBe(false)
  })

  it('lists a payment to refund and drops it once marked', async () => {
    duplicates = [duplicate]
    patchMock.mockImplementation(() => {
      duplicates = []
      return Promise.resolve({})
    })
    wrapper = await mountSuspended(AdminCancellationsPage)
    await flushPromises()

    const section = wrapper.find('.dash-section--alert')
    expect(section.text()).toContain('Mia')
    expect(section.text()).toContain('tr_2')
    expect(section.text()).toContain('24,00')

    await section.find('button').trigger('click')
    await flushPromises()

    expect(patchMock).toHaveBeenCalledWith('/admin/duplicate-payments/4/refunded')
    expect(notifyMock).toHaveBeenCalledWith('Als erstattet markiert.')
    expect(wrapper.find('.dash-section--alert').exists()).toBe(false)
  })
})
