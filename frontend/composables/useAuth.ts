import { useAuthStore } from '~/stores/auth'

interface RegisterPayload {
  name: string
  email: string
  password: string
  password_confirmation: string
  newsletter_opt_in: boolean
  terms_accepted: boolean
  website: string
  form_loaded_at: number
}

interface LoginPayload {
  email: string
  password: string
}

export function useAuth() {
  const api = useApi()
  const auth = useAuthStore()

  async function register(payload: RegisterPayload) {
    const data = await api.post<{
      message: string
      token: string
      user: Parameters<typeof auth.setAuth>[0]
    }>('/auth/register', payload)
    auth.setAuth(data.user, data.token)
    return data
  }

  async function login(payload: LoginPayload) {
    const data = await api.post<{ token: string; user: Parameters<typeof auth.setAuth>[0] }>(
      '/auth/login',
      payload
    )
    auth.setAuth(data.user, data.token)
    return data
  }

  async function logout() {
    await api.post('/auth/logout', undefined, { skipAuthRedirect: true }).catch(() => {})
    auth.logout()
    await navigateTo('/login')
  }

  async function fetchMe() {
    // A top-level Laravel resource, so the user comes wrapped in `data`
    // (unlike login/register, where it's nested under `user`).
    const { data: user } = await api.get<{ data: Parameters<typeof auth.setAuth>[0] }>('/auth/me')
    // setUser also updates the localStorage copy, so the next app start
    // doesn't flash the stale snapshot again.
    auth.setUser(user)
    return user
  }

  return { register, login, logout, fetchMe }
}
