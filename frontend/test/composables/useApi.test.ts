import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { defineComponent } from 'vue'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import { useApi } from '~/composables/useApi'
import { useAuthStore } from '~/stores/auth'

// See test/composables/usePayPalSdk.test.ts for why composable-only tests
// need a mounted host component in the "nuxt" vitest environment.
const Host = defineComponent({
  setup(_, { expose }) {
    expose({ api: useApi(), auth: useAuthStore() })

    return () => null
  },
})

async function mountHost() {
  const wrapper = await mountSuspended(Host)

  return wrapper.vm as unknown as {
    api: ReturnType<typeof useApi>
    auth: ReturnType<typeof useAuthStore>
  }
}

function jsonResponse(body: unknown, init: ResponseInit = {}): Response {
  return new Response(JSON.stringify(body), {
    status: 200,
    headers: { 'Content-Type': 'application/json' },
    ...init,
  })
}

let fetchMock: ReturnType<typeof vi.fn>

describe('useApi', () => {
  beforeEach(() => {
    localStorage.clear()
    fetchMock = vi.fn()
    vi.stubGlobal('fetch', fetchMock)
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('get() builds the URL with only the defined query params', async () => {
    fetchMock.mockResolvedValue(jsonResponse({ ok: true }))
    const { api } = await mountHost()

    await api.get('/games', { params: { q: 'catan', page: 2, tag: undefined } })

    const calledUrl = fetchMock.mock.calls[0]![0] as string
    expect(calledUrl).toContain('/games?')
    expect(calledUrl).toContain('q=catan')
    expect(calledUrl).toContain('page=2')
    expect(calledUrl).not.toContain('tag')
  })

  it('adds the Authorization header when a token is stored', async () => {
    localStorage.setItem('auth_token', 'secret-token')
    fetchMock.mockResolvedValue(jsonResponse({}))
    const { api } = await mountHost()

    await api.get('/dashboard')

    const options = fetchMock.mock.calls[0]![1] as RequestInit
    expect((options.headers as Record<string, string>).Authorization).toBe('Bearer secret-token')
  })

  it('omits the Authorization header when no token is stored', async () => {
    fetchMock.mockResolvedValue(jsonResponse({}))
    const { api } = await mountHost()

    await api.get('/games')

    const options = fetchMock.mock.calls[0]![1] as RequestInit
    expect((options.headers as Record<string, string>).Authorization).toBeUndefined()
  })

  it('sends a JSON body with Content-Type for a plain object', async () => {
    fetchMock.mockResolvedValue(jsonResponse({}))
    const { api } = await mountHost()

    await api.post('/loans', { copy_id: 5 })

    const options = fetchMock.mock.calls[0]![1] as RequestInit
    expect((options.headers as Record<string, string>)['Content-Type']).toBe('application/json')
    expect(options.body).toBe(JSON.stringify({ copy_id: 5 }))
  })

  it('sends FormData as-is without a Content-Type header', async () => {
    fetchMock.mockResolvedValue(jsonResponse({}))
    const { api } = await mountHost()

    const fd = new FormData()
    fd.append('file', new Blob(['x']))
    await api.post('/admin/games/import', fd)

    const options = fetchMock.mock.calls[0]![1] as RequestInit
    expect(options.body).toBe(fd)
    expect((options.headers as Record<string, string>)['Content-Type']).toBeUndefined()
  })

  it('returns undefined for a 204 No Content response', async () => {
    fetchMock.mockResolvedValue(new Response(null, { status: 204 }))
    const { api } = await mountHost()

    await expect(api.delete('/favorites/1')).resolves.toBeUndefined()
  })

  it('throws the parsed error body with the status on failure', async () => {
    fetchMock.mockResolvedValue(jsonResponse({ message: 'Nicht gefunden' }, { status: 404 }))
    const { api } = await mountHost()

    await expect(api.get('/games/does-not-exist')).rejects.toMatchObject({
      status: 404,
      message: 'Nicht gefunden',
    })
  })

  it('falls back to a generic message when the error body is not valid JSON', async () => {
    fetchMock.mockResolvedValue(new Response('not json', { status: 500 }))
    const { api } = await mountHost()

    await expect(api.get('/broken')).rejects.toMatchObject({
      status: 500,
      message: 'Unbekannter Fehler',
    })
  })

  it('logs out and redirects to /login on a 401, then still throws', async () => {
    fetchMock.mockResolvedValue(jsonResponse({ message: 'Unauthenticated' }, { status: 401 }))
    const { api, auth } = await mountHost()
    auth.setAuth({ id: 1 } as Parameters<typeof auth.setAuth>[0], 'old-token')

    await expect(api.get('/account')).rejects.toMatchObject({ status: 401 })

    expect(auth.token).toBeNull()
    expect(auth.user).toBeNull()
  })

  it('does not log out on a 401 when skipAuthRedirect is set', async () => {
    fetchMock.mockResolvedValue(jsonResponse({ message: 'Unauthenticated' }, { status: 401 }))
    const { api, auth } = await mountHost()
    auth.setAuth({ id: 1 } as Parameters<typeof auth.setAuth>[0], 'old-token')

    await expect(
      api.post('/auth/logout', undefined, { skipAuthRedirect: true })
    ).rejects.toMatchObject({ status: 401 })

    expect(auth.token).toBe('old-token')
  })

  it('download() triggers a browser download for a successful response', async () => {
    fetchMock.mockResolvedValue(new Response(new Blob(['data']), { status: 200 }))
    const clickSpy = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {})
    const originalCreateObjectURL = URL.createObjectURL
    const originalRevokeObjectURL = URL.revokeObjectURL
    URL.createObjectURL = vi.fn(() => 'blob:mock-url')
    URL.revokeObjectURL = vi.fn()

    const { api } = await mountHost()
    await api.download('/admin/games/export', 'spiele.xlsx')

    expect(URL.createObjectURL).toHaveBeenCalled()
    expect(clickSpy).toHaveBeenCalledTimes(1)
    expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:mock-url')

    clickSpy.mockRestore()
    URL.createObjectURL = originalCreateObjectURL
    URL.revokeObjectURL = originalRevokeObjectURL
  })

  it('download() throws when the response is not ok', async () => {
    fetchMock.mockResolvedValue(new Response(null, { status: 500 }))
    const { api } = await mountHost()

    await expect(api.download('/admin/games/export', 'spiele.xlsx')).rejects.toThrow(
      'Download fehlgeschlagen'
    )
  })
})
