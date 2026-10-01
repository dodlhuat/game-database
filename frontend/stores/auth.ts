import { defineStore } from 'pinia'

interface User {
  id: number
  name: string
  email: string
  address: string | null
  street: string | null
  postal_code: string | null
  city: string | null
  date_of_birth: string | null
  role: 'USER' | 'SUPPORTER' | 'MEMBER' | 'ADMIN'
  status: 'PENDING' | 'ACTIVE' | 'REJECTED' | 'SUSPENDED'
  newsletter_opt_in: boolean
  tokens: number
  bonus_tokens: number
  bonus_lots?: { remaining: number; expires_at: string }[]
  tokens_blocked: number
  membership_expires_at: string | null
  is_member: boolean
  email_verified_at: string | null
}

interface AuthState {
  user: User | null
  token: string | null
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: null,
  }),

  getters: {
    isLoggedIn: (state) => !!state.token,
    isAdmin: (state) => state.user?.role === 'ADMIN',
    isActive: (state) => state.user?.status === 'ACTIVE',
    isMember: (state) => {
      if (!state.user || state.user.role !== 'MEMBER') return false
      if (!state.user.membership_expires_at) return false
      return new Date(state.user.membership_expires_at) > new Date()
    },
    isSupporter: (state) => {
      if (!state.user || state.user.role !== 'SUPPORTER') return false
      if (!state.user.membership_expires_at) return false
      return new Date(state.user.membership_expires_at) > new Date()
    },
    bonusTokens: (state) => state.user?.bonus_tokens ?? 0,
    // Normal plus bonus tokens
    totalTokens: (state) => (state.user?.tokens ?? 0) + (state.user?.bonus_tokens ?? 0),
    freeTokens(): number {
      return Math.max(0, this.totalTokens - (this.user?.tokens_blocked ?? 0))
    },
    // Earliest expiry of any bonus lot, null if no bonus tokens
    bonusExpiresAt: (state) => state.user?.bonus_lots?.[0]?.expires_at ?? null,
    isRegisteredUser: (state) => state.user?.role === 'USER',
    canBorrow(): boolean {
      return this.isMember || this.isAdmin
    },
  },

  actions: {
    setAuth(user: User, token: string) {
      this.user = user
      this.token = token
      localStorage.setItem('auth_token', token)
      localStorage.setItem('auth_user', JSON.stringify(user))
    },

    setUser(user: User) {
      this.user = user
      localStorage.setItem('auth_user', JSON.stringify(user))
    },

    // Optimistic local spend: bonus tokens go first, like on the server
    spendTokens(amount: number) {
      if (!this.user) return
      const fromBonus = Math.min(this.user.bonus_tokens ?? 0, amount)
      this.setUser({
        ...this.user,
        bonus_tokens: (this.user.bonus_tokens ?? 0) - fromBonus,
        tokens: Math.max(0, this.user.tokens - (amount - fromBonus)),
      })
    },

    logout() {
      this.user = null
      this.token = null
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
    },

    loadFromStorage() {
      const token = localStorage.getItem('auth_token')
      const userRaw = localStorage.getItem('auth_user')
      if (token && userRaw) {
        try {
          this.token = token
          this.user = JSON.parse(userRaw)
        } catch {
          this.logout()
        }
      }
    },
  },
})
