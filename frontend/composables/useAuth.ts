import { useAuthStore } from '~/stores/auth'

interface RegisterPayload {
  name: string
  email: string
  password: string
  password_confirmation: string
  newsletter_opt_in: boolean
  terms_accepted: boolean
}

interface LoginPayload {
  email: string
  password: string
}

export function useAuth() {
  const api = useApi()
  const auth = useAuthStore()

  async function register(payload: RegisterPayload) {
    const data = await api.post<{ message: string; user: unknown }>('/auth/register', payload)
    return data
  }

  async function login(payload: LoginPayload) {
    const data = await api.post<{ token: string; user: Parameters<typeof auth.setAuth>[0] }>('/auth/login', payload)
    auth.setAuth(data.user, data.token)
    return data
  }

  async function logout() {
    await api.post('/auth/logout').catch(() => {})
    auth.logout()
    await navigateTo('/login')
  }

  async function fetchMe() {
    const user = await api.get<Parameters<typeof auth.setAuth>[0]>('/auth/me')
    auth.user = user
    return user
  }

  return { register, login, logout, fetchMe }
}
