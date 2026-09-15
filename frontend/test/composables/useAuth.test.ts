import { describe, it, expect, beforeEach, vi } from 'vitest'
import { defineComponent } from 'vue'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { useAuth } from '~/composables/useAuth'
import { useAuthStore } from '~/stores/auth'

const postMock = vi.fn()
const getMock = vi.fn()
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

const navigateToMock = vi.hoisted(() => vi.fn())
mockNuxtImport('navigateTo', () => navigateToMock)

const Host = defineComponent({
  setup(_, { expose }) {
    expose({ ...useAuth(), auth: useAuthStore() })

    return () => null
  },
})

async function mountHost() {
  const wrapper = await mountSuspended(Host)

  return wrapper.vm as unknown as ReturnType<typeof useAuth> & {
    auth: ReturnType<typeof useAuthStore>
  }
}

const fakeUser = {
  id: 1,
  name: 'Test User',
  email: 'test@example.com',
  address: null,
  street: null,
  postal_code: null,
  city: null,
  date_of_birth: null,
  role: 'MEMBER',
  status: 'ACTIVE',
  newsletter_opt_in: false,
  tokens: 0,
  tokens_blocked: 0,
  membership_expires_at: null,
  is_member: true,
  email_verified_at: null,
}

describe('useAuth', () => {
  beforeEach(() => {
    postMock.mockReset()
    getMock.mockReset()
    navigateToMock.mockReset()
  })

  it('register() posts to /auth/register and stores the returned session', async () => {
    postMock.mockResolvedValue({ message: 'ok', token: 'tok-1', user: fakeUser })
    const host = await mountHost()

    await host.register({
      name: 'Test',
      email: 'test@example.com',
      password: 'pw',
      password_confirmation: 'pw',
      newsletter_opt_in: false,
      terms_accepted: true,
      website: '',
      form_loaded_at: Date.now(),
    })

    expect(postMock).toHaveBeenCalledWith(
      '/auth/register',
      expect.objectContaining({ name: 'Test' })
    )
    expect(host.auth.token).toBe('tok-1')
    expect(host.auth.user).toEqual(fakeUser)
  })

  it('login() posts to /auth/login and stores the returned session', async () => {
    postMock.mockResolvedValue({ token: 'tok-2', user: fakeUser })
    const host = await mountHost()

    await host.login({ email: 'test@example.com', password: 'pw' })

    expect(postMock).toHaveBeenCalledWith('/auth/login', {
      email: 'test@example.com',
      password: 'pw',
    })
    expect(host.auth.token).toBe('tok-2')
  })

  it('logout() clears the session and navigates to /login even if the server call fails', async () => {
    postMock.mockRejectedValue(new Error('network error'))
    const host = await mountHost()
    host.auth.setAuth(fakeUser as Parameters<typeof host.auth.setAuth>[0], 'tok-3')

    await host.logout()

    expect(postMock).toHaveBeenCalledWith('/auth/logout', undefined, { skipAuthRedirect: true })
    expect(host.auth.token).toBeNull()
    expect(host.auth.user).toBeNull()
    expect(navigateToMock).toHaveBeenCalledWith('/login')
  })

  it('fetchMe() loads the current user into the store', async () => {
    getMock.mockResolvedValue(fakeUser)
    const host = await mountHost()

    const result = await host.fetchMe()

    expect(getMock).toHaveBeenCalledWith('/auth/me')
    expect(result).toEqual(fakeUser)
    expect(host.auth.user).toEqual(fakeUser)
  })
})
