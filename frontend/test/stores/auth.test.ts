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

describe('auth store — bonus tokens', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('adds bonus tokens to the total and subtracts blocked ones for free tokens', () => {
    const auth = setUser({ tokens: 10, bonus_tokens: 5, tokens_blocked: 4 })
    expect(auth.bonusTokens).toBe(5)
    expect(auth.totalTokens).toBe(15)
    expect(auth.freeTokens).toBe(11)
  })

  it('treats a missing bonus_tokens field as zero', () => {
    const auth = setUser({ tokens: 3 })
    expect(auth.totalTokens).toBe(3)
  })

  it('exposes the earliest bonus expiry', () => {
    const auth = setUser({
      bonus_tokens: 5,
      bonus_lots: [
        { remaining: 2, expires_at: future },
        { remaining: 3, expires_at: '2099-01-01T00:00:00Z' },
      ],
    })
    expect(auth.bonusExpiresAt).toBe(future)
  })

  it('spends bonus tokens before normal tokens', () => {
    const auth = setUser({ tokens: 10, bonus_tokens: 3 })
    auth.spendTokens(5)
    expect(auth.user?.bonus_tokens).toBe(0)
    expect(auth.user?.tokens).toBe(8)
  })

  it('spends only bonus tokens when they cover the cost', () => {
    const auth = setUser({ tokens: 10, bonus_tokens: 4 })
    auth.spendTokens(3)
    expect(auth.user?.bonus_tokens).toBe(1)
    expect(auth.user?.tokens).toBe(10)
  })
})
