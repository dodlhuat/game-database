import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import ForgotPasswordPage from '~/pages/forgot-password.vue'
import ResetPasswordPage from '~/pages/reset-password.vue'

const postMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useApi', () => {
  return () => ({ get: vi.fn(), post: postMock, put: vi.fn(), patch: vi.fn(), delete: vi.fn() })
})

let wrapper: Awaited<ReturnType<typeof mountSuspended>> | null = null

beforeEach(() => {
  postMock.mockReset()
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
})

describe('/forgot-password', () => {
  async function request(email: string) {
    wrapper = await mountSuspended(ForgotPasswordPage)
    await wrapper.find('input[type="email"]').setValue(email)
    await wrapper.find('form').trigger('submit')
    await flushPromises()
  }

  it('sends the address and confirms without revealing whether it exists', async () => {
    postMock.mockResolvedValue({ message: 'Falls ein Konto existiert …' })
    await request('mia@example.at')

    expect(postMock).toHaveBeenCalledWith('/auth/forgot-password', { email: 'mia@example.at' })
    expect(wrapper!.find('.auth-card__success').exists()).toBe(true)
    expect(wrapper!.find('form').exists()).toBe(false)
  })

  it('keeps the form and shows the field error when the request is refused', async () => {
    postMock.mockRejectedValue({ errors: { email: ['Zu viele Versuche.'] } })
    await request('mia@example.at')

    expect(wrapper!.text()).toContain('Zu viele Versuche.')
    expect(wrapper!.find('form').exists()).toBe(true)
  })
})

describe('/reset-password', () => {
  async function open(query: string) {
    wrapper = await mountSuspended(ResetPasswordPage, { route: `/reset-password${query}` })
    await flushPromises()
    return wrapper
  }

  async function submit(password: string, confirmation: string) {
    const [pw, confirm] = wrapper!.findAll('input[type="password"]')
    await pw!.setValue(password)
    await confirm!.setValue(confirmation)
    await wrapper!.find('form').trigger('submit')
    await flushPromises()
  }

  it('sends token and address from the mail link with the new password', async () => {
    postMock.mockResolvedValue({})
    await open('?token=abc123&email=mia%40example.at')

    await submit('neues-passwort-123', 'neues-passwort-123')

    expect(postMock).toHaveBeenCalledWith('/auth/reset-password', {
      token: 'abc123',
      email: 'mia@example.at',
      password: 'neues-passwort-123',
      password_confirmation: 'neues-passwort-123',
    })
    expect(wrapper!.find('.auth-card__success').exists()).toBe(true)
  })

  it('catches mismatching passwords before asking the server', async () => {
    await open('?token=abc123&email=mia%40example.at')

    await submit('neues-passwort-123', 'anders-123')

    expect(postMock).not.toHaveBeenCalled()
    expect(wrapper!.text()).toContain('Passwörter stimmen nicht überein.')
  })

  it('offers a new link when the mail link was already used or expired', async () => {
    postMock.mockRejectedValue({
      status: 422,
      message: 'Dieser Link ist ungültig oder bereits abgelaufen.',
    })
    await open('?token=alt&email=mia%40example.at')

    await submit('neues-passwort-123', 'neues-passwort-123')

    expect(wrapper!.find('form').exists()).toBe(false)
    expect(wrapper!.find('a[href="/forgot-password"]').exists()).toBe(true)
  })

  it('keeps the form on a server error so the member can retry', async () => {
    postMock.mockRejectedValue({ status: 500, message: 'Server nicht erreichbar.' })
    await open('?token=abc123&email=mia%40example.at')

    await submit('neues-passwort-123', 'neues-passwort-123')

    expect(wrapper!.find('.alert-error').text()).toBe('Server nicht erreichbar.')
    expect(wrapper!.find('form').exists()).toBe(true)
  })

  it('puts a password rule violation next to the field', async () => {
    postMock.mockRejectedValue({ errors: { password: ['Mindestens 8 Zeichen.'] } })
    await open('?token=abc123&email=mia%40example.at')

    await submit('kurz', 'kurz')

    expect(wrapper!.text()).toContain('Mindestens 8 Zeichen.')
    expect(wrapper!.find('.alert-error').exists()).toBe(false)
  })

  it('offers a new link instead of a form when the link is incomplete', async () => {
    await open('?token=abc123')

    expect(wrapper!.find('form').exists()).toBe(false)
    expect(wrapper!.find('a[href="/forgot-password"]').exists()).toBe(true)
  })
})
