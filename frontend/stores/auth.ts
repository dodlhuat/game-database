import { defineStore } from 'pinia'

interface User {
  id: number
  name: string
  email: string
  address: string | null
  role: 'USER' | 'MEMBER' | 'ADMIN'
  status: 'PENDING' | 'ACTIVE' | 'REJECTED' | 'SUSPENDED'
  newsletter_opt_in: boolean
  tokens: number
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
    isRegisteredUser: (state) => state.user?.role === 'USER',
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
