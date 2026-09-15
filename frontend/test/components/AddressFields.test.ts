import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { nextTick } from 'vue'
import AddressFields from '~/components/ui/AddressFields.vue'

const validateAddressMock = vi.fn()
mockNuxtImport('useAddress', () => {
  return () => ({ validateAddress: validateAddressMock })
})

function mountFields(props: Partial<InstanceType<typeof AddressFields>['$props']> = {}) {
  return mountSuspended(AddressFields, {
    props: { street: 'Teststraße 1', postalCode: '1010', city: 'Wien', ...props },
  })
}

function cityInput(wrapper: Awaited<ReturnType<typeof mountFields>>) {
  return wrapper.find('input[placeholder="Wien"]')
}

describe('AddressFields', () => {
  beforeEach(() => {
    validateAddressMock.mockReset()
  })

  it('shows no status initially', async () => {
    const wrapper = await mountFields()
    expect(wrapper.find('.address-fields__status').exists()).toBe(false)
  })

  it('does not call validateAddress when the address is incomplete', async () => {
    const wrapper = await mountFields({ street: '', city: 'Wien', postalCode: '1010' })

    await cityInput(wrapper).trigger('blur')
    await flushPromises()

    expect(validateAddressMock).not.toHaveBeenCalled()
    expect(wrapper.find('.address-fields__status').exists()).toBe(false)
  })

  it('does not call validateAddress when the postal code is not exactly 4 digits', async () => {
    const wrapper = await mountFields({ postalCode: '10' })

    await cityInput(wrapper).trigger('blur')
    await flushPromises()

    expect(validateAddressMock).not.toHaveBeenCalled()
  })

  it('shows checking then verified for a valid, verified address', async () => {
    let resolveValidate!: (value: { verified: boolean }) => void
    validateAddressMock.mockImplementation(
      () => new Promise((resolve) => (resolveValidate = resolve))
    )
    const wrapper = await mountFields()

    await cityInput(wrapper).trigger('blur')
    await nextTick()
    expect(wrapper.find('.address-fields__status--checking').exists()).toBe(true)

    resolveValidate({ verified: true })
    await flushPromises()

    expect(validateAddressMock).toHaveBeenCalledWith('Teststraße 1', '1010', 'Wien')
    expect(wrapper.find('.address-fields__status--verified').exists()).toBe(true)
  })

  it('shows not-found for an address the backend could not verify', async () => {
    validateAddressMock.mockResolvedValue({ verified: false })
    const wrapper = await mountFields()

    await cityInput(wrapper).trigger('blur')
    await flushPromises()

    expect(wrapper.find('.address-fields__status--not-found').exists()).toBe(true)
  })

  it('resets to idle (never shows an error) when the check itself fails', async () => {
    validateAddressMock.mockRejectedValue(new Error('network down'))
    const wrapper = await mountFields()

    await cityInput(wrapper).trigger('blur')
    await flushPromises()

    expect(wrapper.find('.address-fields__status').exists()).toBe(false)
  })

  it('resets the status once the address is edited again', async () => {
    validateAddressMock.mockResolvedValue({ verified: true })
    const wrapper = await mountFields()

    await cityInput(wrapper).trigger('blur')
    await flushPromises()
    expect(wrapper.find('.address-fields__status--verified').exists()).toBe(true)

    await wrapper.setProps({ street: 'Neue Straße 2' })

    expect(wrapper.find('.address-fields__status').exists()).toBe(false)
  })
})
