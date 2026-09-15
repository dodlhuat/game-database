import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import RegisterPage from '~/pages/register.vue'

const registerMock = vi.fn()
mockNuxtImport('useAuth', () => {
  return () => ({ register: registerMock, login: vi.fn(), logout: vi.fn(), fetchMe: vi.fn() })
})

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

async function mountRegisterPage() {
  const wrapper = await mountSuspended(RegisterPage)
  await flushPromises()
  return wrapper
}

async function fillRequiredFields(wrapper: Awaited<ReturnType<typeof mountRegisterPage>>) {
  const inputs = wrapper.findAll('input[type="text"], input[type="email"], input[type="password"]')
  await inputs[0]!.setValue('Test User')
  await inputs[1]!.setValue('test@example.com')
  await inputs[2]!.setValue('supersecret')
  await inputs[3]!.setValue('supersecret')
  await wrapper.find('#terms-accepted').setValue(true)
}

describe('register.vue — submit', () => {
  beforeEach(() => {
    registerMock.mockReset()
    getMock.mockReset()
    // happy-dom does not implement <dialog> — stub just enough for openTerms/closeTerms.
    HTMLDialogElement.prototype.showModal = vi.fn(function (this: HTMLDialogElement) {
      this.setAttribute('open', '')
    })
    HTMLDialogElement.prototype.close = vi.fn(function (this: HTMLDialogElement) {
      this.removeAttribute('open')
    })
  })

  it('shows a success message and hides the form on success', async () => {
    registerMock.mockResolvedValue({ message: 'Bitte bestätige deine E-Mail.' })
    const wrapper = await mountRegisterPage()

    await fillRequiredFields(wrapper)
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.find('.alert-success').text()).toContain('Bitte bestätige deine E-Mail.')
    expect(wrapper.find('form').exists()).toBe(false)
  })

  it('shows field-level validation errors from the API', async () => {
    registerMock.mockRejectedValue({
      errors: { terms_accepted: ['Du musst den AGB zustimmen.'] },
    })
    const wrapper = await mountRegisterPage()

    await fillRequiredFields(wrapper)
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Du musst den AGB zustimmen.')
  })

  it('shows a generic server error for an unrecognized failure', async () => {
    registerMock.mockRejectedValue({ message: 'Serverfehler.' })
    const wrapper = await mountRegisterPage()

    await fillRequiredFields(wrapper)
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.find('.alert-error').text()).toBe('Serverfehler.')
  })

  it('sends the filled-in form data to register()', async () => {
    registerMock.mockResolvedValue({ message: 'ok' })
    const wrapper = await mountRegisterPage()

    await fillRequiredFields(wrapper)
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(registerMock).toHaveBeenCalledWith(
      expect.objectContaining({
        name: 'Test User',
        email: 'test@example.com',
        password: 'supersecret',
        password_confirmation: 'supersecret',
        terms_accepted: true,
        website: '',
      })
    )
  })
})

describe('register.vue — terms modal', () => {
  beforeEach(() => {
    getMock.mockReset()
    HTMLDialogElement.prototype.showModal = vi.fn(function (this: HTMLDialogElement) {
      this.setAttribute('open', '')
    })
    HTMLDialogElement.prototype.close = vi.fn(function (this: HTMLDialogElement) {
      this.removeAttribute('open')
    })
  })

  it('fetches and displays the terms content on first open', async () => {
    getMock.mockResolvedValue({ content: 'Erster Absatz.\n\nZweiter Absatz.' })
    const wrapper = await mountRegisterPage()

    await wrapper.find('.terms-link').trigger('click')
    await flushPromises()

    expect(getMock).toHaveBeenCalledWith('/terms')
    const paragraphs = wrapper.findAll('.terms-modal__para')
    expect(paragraphs).toHaveLength(2)
    expect(paragraphs[0]!.text()).toBe('Erster Absatz.')
  })

  it('does not re-fetch the terms content on a second open', async () => {
    getMock.mockResolvedValue({ content: 'Text.' })
    const wrapper = await mountRegisterPage()

    await wrapper.find('.terms-link').trigger('click')
    await flushPromises()
    await wrapper.find('.terms-link').trigger('click')
    await flushPromises()

    expect(getMock).toHaveBeenCalledTimes(1)
  })

  it('shows an error message when fetching the terms fails', async () => {
    getMock.mockRejectedValue(new Error('down'))
    const wrapper = await mountRegisterPage()

    await wrapper.find('.terms-link').trigger('click')
    await flushPromises()

    expect(wrapper.find('.terms-modal__error').exists()).toBe(true)
  })
})
