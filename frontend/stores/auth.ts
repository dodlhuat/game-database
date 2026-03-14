import { defineStore } from 'pinia'

interface User {
  id: number
  name: string
  email: string
  role: 'MEMBER' | 'ADMIN'
  status: 'PENDING' | 'ACTIVE' | 'REJECTED' | 'SUSPENDED'
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
  },

  actions: {
    setAuth(user: User, token: string) {
      this.user = user
      this.token = token
      localStorage.setItem('auth_token', token)
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
