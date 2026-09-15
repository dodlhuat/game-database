import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '~/stores/auth'

function setUser(overrides: Record<string, unknown>) {
  const auth = useAuthStore()
  auth.user = {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    address: null,
    street: null,
    postal_code: null,
    city: null,
    date_of_birth: null,
    role: 'USER',
    status: 'ACTIVE',
    newsletter_opt_in: false,
    tokens: 0,
    tokens_blocked: 0,
    membership_expires_at: null,
    is_member: false,
    email_verified_at: null,
    ...overrides,
  } as Parameters<typeof auth.setUser>[0]

  return auth
}

const future = new Date(Date.now() + 86_400_000).toISOString()
const past = new Date(Date.now() - 86_400_000).toISOString()

describe('auth store — canBorrow', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('is true for an admin, even without an active membership', () => {
    const auth = setUser({ role: 'ADMIN' })
    expect(auth.canBorrow).toBe(true)
  })

  it('is true for a member with a future membership expiry', () => {
    const auth = setUser({ role: 'MEMBER', membership_expires_at: future })
    expect(auth.canBorrow).toBe(true)
  })

  it('is false for a member whose membership already expired', () => {
    const auth = setUser({ role: 'MEMBER', membership_expires_at: past })
    expect(auth.canBorrow).toBe(false)
  })

  it('is false for a plain user', () => {
    const auth = setUser({ role: 'USER' })
    expect(auth.canBorrow).toBe(false)
  })

  it('is false for a supporter (supporter alone does not grant borrowing)', () => {
    const auth = setUser({ role: 'SUPPORTER', membership_expires_at: future })
    expect(auth.canBorrow).toBe(false)
  })

  it('is false when not logged in', () => {
    const auth = useAuthStore()
    expect(auth.canBorrow).toBe(false)
  })
})
