import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, DOMWrapper } from '@vue/test-utils'
import CancelMembershipModal from '~/components/CancelMembershipModal.vue'

// The dialog is teleported to <body>, so query the document instead of the wrapper.
function modal() {
  return new DOMWrapper(document.body)
}

const getMock = vi.fn()
const postMock = vi.fn()
mockNuxtImport('useApi', () => {
  return () => ({
    get: getMock,
    post: postMock,
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    download: vi.fn(),
  })
})

const refundPreview = {
  tokens: 60,
  bonus_tokens: 20,
  refund_tokens: 60,
  gross_cents: 2840,
  fee_cents: 200,
  refund_cents: 2640,
  non_refundable_tokens: 0,
  blockers: [] as string[],
}

let activeWrapper: Awaited<ReturnType<typeof mountSuspended>> | null = null

async function mountModal() {
  activeWrapper = await mountSuspended(CancelMembershipModal, {
    props: { open: true, onClose: () => {}, onCancelled: () => {} },
  })
  await flushPromises()
  return activeWrapper
}

describe('CancelMembershipModal', () => {
  beforeEach(() => {
    getMock.mockReset()
    postMock.mockReset()
    getMock.mockResolvedValue(refundPreview)
  })

  afterEach(() => {
    activeWrapper?.unmount()
    activeWrapper = null
  })

  it('loads the preview and shows refund and forfeited bonus tokens', async () => {
    await mountModal()

    expect(getMock).toHaveBeenCalledWith('/membership/cancel/preview')
    const text = modal().find('.cancel__summary').text()
    expect(text).toContain('60 Token')
    expect(text).toContain('26,40')
    expect(text).toContain('20 geschenkte Bonus-Token verfallen')
  })

  it('shows the blockers instead of the form when cancelling is not possible', async () => {
    getMock.mockResolvedValue({ ...refundPreview, blockers: ['open_loans'] })
    await mountModal()

    expect(modal().find('.cancel__blocked').text()).toContain('ausgeliehene Spiele')
    expect(modal().find('form').exists()).toBe(false)
  })

  it('keeps the submit button disabled until the confirmation is checked', async () => {
    await mountModal()
    const submit = modal().find('button[type="submit"]')

    expect(submit.attributes('disabled')).toBeDefined()

    await modal().find('#cancel-confirm').setValue(true)
    expect(modal().find('button[type="submit"]').attributes('disabled')).toBeUndefined()
  })

  it('sends account, reason and confirmation and emits cancelled with the new user', async () => {
    const wrapper = await mountModal()
    postMock.mockResolvedValue({ refund_cents: 2640, refund_tokens: 60, user: { role: 'USER' } })

    const inputs = modal().findAll('input:not([type="checkbox"])')
    await inputs[0]!.setValue('Max Muster')
    await inputs[1]!.setValue('AT61 1904 3002 3457 3201')
    await modal().find('#cancel-reason').setValue('Zieht um')
    await modal().find('#cancel-confirm').setValue(true)
    await modal().find('form').trigger('submit.prevent')
    await flushPromises()

    expect(postMock).toHaveBeenCalledWith('/membership/cancel', {
      confirm: true,
      reason: 'Zieht um',
      account_holder: 'Max Muster',
      iban: 'AT61 1904 3002 3457 3201',
    })
    expect(modal().find('.cancel__done').exists()).toBe(true)

    const finish = modal().find('.cancel__done button')
    await finish.trigger('click')
    expect(wrapper.emitted('cancelled')?.[0]?.[0]).toEqual({ role: 'USER' })
  })

  it('does not ask for bank details when there is nothing to refund', async () => {
    getMock.mockResolvedValue({
      ...refundPreview,
      refund_tokens: 0,
      gross_cents: 0,
      fee_cents: 0,
      refund_cents: 0,
    })
    await mountModal()

    expect(modal().findAll('input:not([type="checkbox"])').length).toBe(0)
    expect(modal().find('.cancel__summary').text()).toContain('keine Token')
  })

  it('shows server validation errors next to the fields', async () => {
    await mountModal()
    postMock.mockRejectedValue({ errors: { iban: ['Die IBAN ist ungültig.'] } })

    await modal().find('#cancel-confirm').setValue(true)
    await modal().find('form').trigger('submit.prevent')
    await flushPromises()

    expect(modal().find('.error-text').text()).toBe('Die IBAN ist ungültig.')
    expect(modal().find('.cancel__done').exists()).toBe(false)
  })
})
