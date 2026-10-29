import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import authMiddleware from '~/middleware/auth'
import adminMiddleware from '~/middleware/admin'
import { useAuthStore } from '~/stores/auth'
import maintenanceMiddleware from '~/middleware/maintenance.global'

const navigateToMock = vi.hoisted(() => vi.fn())
mockNuxtImport('navigateTo', () => navigateToMock)

// Middleware signatures require a to/from route — unused by either guard,
// so an empty stand-in is enough.
const route = {} as never

describe('auth middleware', () => {
  beforeEach(() => {
    navigateToMock.mockReset()
    localStorage.clear()
  })

  it('redirects to /login when no token is stored', () => {
    authMiddleware(route, route)
    expect(navigateToMock).toHaveBeenCalledWith('/login')
  })

  it('does not redirect when a token is stored', () => {
    localStorage.setItem('auth_token', 'a-token')
    authMiddleware(route, route)
    expect(navigateToMock).not.toHaveBeenCalled()
  })
})

describe('admin middleware', () => {
  beforeEach(() => {
    navigateToMock.mockReset()
    localStorage.clear()
  })

  it('redirects to /login when no user is stored', () => {
    adminMiddleware(route, route)
    expect(navigateToMock).toHaveBeenCalledWith('/login')
  })

  it('redirects to /login when the stored user is not valid JSON', () => {
    localStorage.setItem('auth_user', '{not json')
    adminMiddleware(route, route)
    expect(navigateToMock).toHaveBeenCalledWith('/login')
  })

  it('redirects to /dashboard when the stored user is not an admin', () => {
    localStorage.setItem('auth_user', JSON.stringify({ role: 'MEMBER' }))
    adminMiddleware(route, route)
    expect(navigateToMock).toHaveBeenCalledWith('/dashboard')
  })

  it('does not redirect for an admin user', () => {
    localStorage.setItem('auth_user', JSON.stringify({ role: 'ADMIN' }))
    adminMiddleware(route, route)
    expect(navigateToMock).not.toHaveBeenCalled()
  })
})

describe('maintenance middleware', () => {
  const go = (path: string) => maintenanceMiddleware({ path } as never, route)

  beforeEach(() => {
    navigateToMock.mockReset()
    useAuthStore().user = null
  })
  afterEach(() => {
    useRuntimeConfig().public.maintenanceMode = false
    useAuthStore().user = null
  })

  it('does nothing while maintenance mode is off', () => {
    useRuntimeConfig().public.maintenanceMode = false
    go('/games')
    expect(navigateToMock).not.toHaveBeenCalled()
  })

  it('redirects every other route to the index page when on', () => {
    useRuntimeConfig().public.maintenanceMode = true
    go('/games')
    expect(navigateToMock).toHaveBeenCalledWith('/', { replace: true })
  })

  it('lets the index page through when on', () => {
    useRuntimeConfig().public.maintenanceMode = true
    go('/')
    expect(navigateToMock).not.toHaveBeenCalled()
  })

  it('lets the login page through when on', () => {
    useRuntimeConfig().public.maintenanceMode = true
    go('/login')
    expect(navigateToMock).not.toHaveBeenCalled()
  })

  it('lets a logged-in admin reach every route when on', () => {
    useRuntimeConfig().public.maintenanceMode = true
    useAuthStore().user = { role: 'ADMIN' } as never
    go('/admin/games')
    expect(navigateToMock).not.toHaveBeenCalled()
  })

  it('still redirects a logged-in non-admin when on', () => {
    useRuntimeConfig().public.maintenanceMode = true
    useAuthStore().user = { role: 'MEMBER' } as never
    go('/dashboard')
    expect(navigateToMock).toHaveBeenCalledWith('/', { replace: true })
  })
})
