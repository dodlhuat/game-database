/**
 * Zentraler API-Wrapper
 * Kümmert sich um Base URL, Auth-Token und Fehlerbehandlung.
 */

interface ApiOptions extends RequestInit {
  params?: Record<string, string | number | boolean | undefined>
}

export function useApi() {
  const config = useRuntimeConfig()
  const baseUrl = config.public.apiBase as string

  function getToken(): string | null {
    return localStorage.getItem('auth_token')
  }

  function buildUrl(path: string, params?: ApiOptions['params']): string {
    const url = new URL(`${baseUrl}${path}`)
    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined) {
          url.searchParams.set(key, String(value))
        }
      })
    }
    return url.toString()
  }

  async function request<T = unknown>(
    path: string,
    options: ApiOptions = {},
  ): Promise<T> {
    const { params, headers: customHeaders, ...fetchOptions } = options

    const token = getToken()
    const isFormData = fetchOptions.body instanceof FormData
    const headers: Record<string, string> = {
      // Bei FormData kein Content-Type setzen — Browser setzt boundary automatisch
      ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
      Accept: 'application/json',
      ...(customHeaders as Record<string, string>),
    }

    if (token) {
      headers['Authorization'] = `Bearer ${token}`
    }

    const response = await fetch(buildUrl(path, params), {
      ...fetchOptions,
      headers,
    })

    if (!response.ok) {
      if (response.status === 401) {
        const auth = useAuthStore()
        auth.logout()
        await navigateTo('/login?reason=unauthenticated')
      }
      const error = await response.json().catch(() => ({ message: 'Unbekannter Fehler' }))
      throw { status: response.status, ...error }
    }

    // 204 No Content
    if (response.status === 204) {
      return undefined as T
    }

    return response.json() as Promise<T>
  }

  return {
    get: <T = unknown>(path: string, options?: ApiOptions) =>
      request<T>(path, { ...options, method: 'GET' }),

    post: <T = unknown>(path: string, body?: unknown, options?: ApiOptions) =>
      request<T>(path, {
        ...options,
        method: 'POST',
        body: body instanceof FormData ? body : JSON.stringify(body),
      }),

    put: <T = unknown>(path: string, body?: unknown, options?: ApiOptions) =>
      request<T>(path, {
        ...options,
        method: 'PUT',
        body: JSON.stringify(body),
      }),

    patch: <T = unknown>(path: string, body?: unknown, options?: ApiOptions) =>
      request<T>(path, {
        ...options,
        method: 'PATCH',
        body: JSON.stringify(body),
      }),

    delete: <T = unknown>(path: string, options?: ApiOptions) =>
      request<T>(path, { ...options, method: 'DELETE' }),
  }
}
