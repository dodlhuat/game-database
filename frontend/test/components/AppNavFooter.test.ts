import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import AppNav from '~/components/AppNav.vue'
import AppFooter from '~/components/AppFooter.vue'
import { useAuthStore } from '~/stores/auth'

const logoutMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useAuth', () => () => ({ logout: logoutMock }))

const sponsors = vi.hoisted(() => ({
  activeSponsors: { value: [] as unknown[] },
  fetchSponsors: vi.fn(),
}))
mockNuxtImport('useSponsors', () => () => sponsors)

function login(name: string | null) {
  const auth = useAuthStore()
  auth.token = name ? 'test-token' : null
  auth.user = name
    ? ({ id: 1, name, role: 'MEMBER', status: 'ACTIVE' } as unknown as typeof auth.user)
    : null
}

const hrefs = (w: {
  findAll: (s: string) => { attributes: (a: string) => string | undefined }[]
}) => w.findAll('a').map((a) => a.attributes('href'))

beforeEach(() => {
  logoutMock.mockReset()
  sponsors.fetchSponsors.mockReset().mockResolvedValue([])
  sponsors.activeSponsors.value = []
  localStorage.clear()
  document.documentElement.removeAttribute('data-theme')
  window.matchMedia ??= (() => ({ matches: false })) as unknown as typeof window.matchMedia
})

afterEach(() => {
  login(null)
})

describe('AppNav', () => {
  it('offers guests login and sign-up, no member pages', async () => {
    login(null)
    const nav = await mountSuspended(AppNav)

    expect(hrefs(nav)).toEqual(
      expect.arrayContaining(['/games', '/packages', '/login', '/register'])
    )
    expect(hrefs(nav)).not.toContain('/dashboard')
    expect(hrefs(nav)).not.toContain('/events')
  })

  it('greets a member by first name and logs out', async () => {
    login('Mia Muster')
    const nav = await mountSuspended(AppNav)

    expect(hrefs(nav)).toEqual(expect.arrayContaining(['/events', '/dashboard', '/account']))
    expect(nav.find('a[href="/account"]').text()).toContain('Mia')
    expect(nav.find('a[href="/account"]').text()).not.toContain('Muster')

    await nav
      .findAll('button')
      .find((b) => b.text() === 'Abmelden')!
      .trigger('click')
    expect(logoutMock).toHaveBeenCalled()
  })

  it('remembers the chosen theme', async () => {
    localStorage.setItem('theme', 'light')
    const nav = await mountSuspended(AppNav)
    expect(document.documentElement.getAttribute('data-theme')).toBe('light')

    await nav.find('.l-nav__theme-btn').trigger('click')

    expect(document.documentElement.getAttribute('data-theme')).toBe('dark')
    expect(localStorage.getItem('theme')).toBe('dark')
  })
})

describe('AppFooter', () => {
  it('always links the legal documents', async () => {
    const footer = await mountSuspended(AppFooter)

    expect(hrefs(footer)).toEqual(
      expect.arrayContaining(['/terms', '/privacy', '/cookies', '/impressum'])
    )
  })

  it('shows the sponsors link only once there is someone to thank', async () => {
    const without = await mountSuspended(AppFooter)
    await flushPromises()
    expect(hrefs(without)).not.toContain('/sponsors')

    sponsors.activeSponsors.value = [{ id: 1, name: 'Huber' }]
    const withSponsor = await mountSuspended(AppFooter)
    expect(hrefs(withSponsor)).toContain('/sponsors')
  })
})
