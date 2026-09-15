import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import authMiddleware from '~/middleware/auth'
import adminMiddleware from '~/middleware/admin'

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
