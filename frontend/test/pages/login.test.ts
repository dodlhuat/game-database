import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import LoginPage from '~/pages/login.vue'

const loginMock = vi.fn()
mockNuxtImport('useAuth', () => {
  return () => ({ login: loginMock, register: vi.fn(), logout: vi.fn(), fetchMe: vi.fn() })
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

const navigateToMock = vi.hoisted(() => vi.fn())
mockNuxtImport('navigateTo', () => navigateToMock)

async function mountLoginPage(route = '/login') {
  const wrapper = await mountSuspended(LoginPage, { route })
  await flushPromises()
  return wrapper
}

async function fillAndSubmit(
  wrapper: Awaited<ReturnType<typeof mountLoginPage>>,
  email: string,
  password: string
) {
  const inputs = wrapper.findAll('input')
  await inputs[0]!.setValue(email)
  await inputs[1]!.setValue(password)
  await wrapper.find('form').trigger('submit')
  await flushPromises()
}

describe('login.vue', () => {
  beforeEach(() => {
    loginMock.mockReset()
    postMock.mockReset()
    navigateToMock.mockReset()
  })

  it('shows a session-expired message when redirected with ?reason=unauthenticated', async () => {
    const wrapper = await mountLoginPage('/login?reason=unauthenticated')

    expect(wrapper.find('.alert-error').text()).toBe(wrapper.vm.$t('common.error.session_expired'))
  })

  it('logs in and navigates to the dashboard on success', async () => {
    loginMock.mockResolvedValue({})
    const wrapper = await mountLoginPage()

    await fillAndSubmit(wrapper, 'test@example.com', 'secret')

    expect(loginMock).toHaveBeenCalledWith({ email: 'test@example.com', password: 'secret' })
    expect(navigateToMock).toHaveBeenCalledWith('/dashboard')
  })

  it('shows the resend-verification UI when the email is not verified', async () => {
    loginMock.mockRejectedValue({ reason: 'email_not_verified' })
    const wrapper = await mountLoginPage()

    await fillAndSubmit(wrapper, 'test@example.com', 'secret')

    expect(wrapper.text()).toContain(wrapper.vm.$t('auth.email_not_verified'))
  })

  it('shows field-level errors from the API', async () => {
    loginMock.mockRejectedValue({ errors: { password: ['Falsches Passwort.'] } })
    const wrapper = await mountLoginPage()

    await fillAndSubmit(wrapper, 'test@example.com', 'wrong')

    expect(wrapper.text()).toContain('Falsches Passwort.')
    expect(navigateToMock).not.toHaveBeenCalled()
  })

  it('shows a generic error message for an unrecognized failure', async () => {
    loginMock.mockRejectedValue({ message: 'Server nicht erreichbar.' })
    const wrapper = await mountLoginPage()

    await fillAndSubmit(wrapper, 'test@example.com', 'secret')

    expect(wrapper.find('.alert-error').text()).toBe('Server nicht erreichbar.')
  })

  it('resends the verification email', async () => {
    loginMock.mockRejectedValue({ reason: 'email_not_verified' })
    postMock.mockResolvedValue({})
    const wrapper = await mountLoginPage()
    await fillAndSubmit(wrapper, 'test@example.com', 'secret')

    await wrapper.find('.resend-link').trigger('click')
    await flushPromises()

    expect(postMock).toHaveBeenCalledWith('/auth/email/resend', { email: 'test@example.com' })
    expect(wrapper.find('.resend-success').exists()).toBe(true)
  })
})
