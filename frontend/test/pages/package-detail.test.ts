import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { useAuthStore } from '~/stores/auth'
import type { Package } from '~/composables/useGames'
import PackageDetailPage from '~/pages/packages/[slug].vue'

const fetchPackageMock = vi.fn()
mockNuxtImport('useGames', () => {
  return () => ({
    fetchPackage: fetchPackageMock,
    fetchPackages: vi.fn(),
    fetchGame: vi.fn(),
    fetchGames: vi.fn(),
    fetchMechanics: vi.fn(),
    smartSearch: vi.fn(),
  })
})

const navigateToMock = vi.hoisted(() => vi.fn())
mockNuxtImport('navigateTo', () => navigateToMock)

const postMock = vi.fn()
mockNuxtImport('useApi', () => {
  return () => ({
    get: vi.fn(),
    post: postMock,
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    download: vi.fn(),
  })
})

const basePackage: Package = {
  id: 1,
  name: 'Familien-Paket',
  slug: 'familien-paket',
  description: null,
  type: 'CURATED',
  is_active: true,
  games: [],
  available: true,
}

function setUser(overrides: Record<string, unknown>) {
  const auth = useAuthStore()
  auth.token = 'test-token'
  auth.user = {
    id: 1,
    name: 'Test',
    email: 'test@example.com',
    address: null,
    street: null,
    postal_code: null,
    city: null,
    date_of_birth: null,
    role: 'MEMBER',
    status: 'ACTIVE',
    newsletter_opt_in: false,
    tokens: 10,
    tokens_blocked: 0,
    membership_expires_at: new Date(Date.now() + 86_400_000).toISOString(),
    is_member: true,
    email_verified_at: new Date().toISOString(),
    ...overrides,
  } as Parameters<typeof auth.setUser>[0]

  return auth
}

// useAsyncData caches by key (`package-${slug}`) — a fresh slug per mount
// avoids stale cross-test data (see test/pages/game-detail.test.ts).
let slugCounter = 0

async function mountPackagePage(pkg: Partial<Package> = {}) {
  const slug = `pkg-${slugCounter++}`
  fetchPackageMock.mockResolvedValue({ data: { ...basePackage, ...pkg, slug } })
  const wrapper = await mountSuspended(PackageDetailPage, { route: `/packages/${slug}` })
  await flushPromises()
  return wrapper
}

describe('packages/[slug].vue — borrow CTA', () => {
  beforeEach(() => {
    fetchPackageMock.mockReset()
    postMock.mockReset()
    navigateToMock.mockReset()
  })

  it('prompts login when not logged in', async () => {
    const auth = useAuthStore()
    auth.token = null
    auth.user = null

    const wrapper = await mountPackagePage()
    const cta = wrapper.find('.detail__cta')

    expect(cta.text()).toContain(wrapper.vm.$t('btn.sign_in'))
  })

  it('prompts to become a member for a logged-in non-member', async () => {
    setUser({ role: 'USER', membership_expires_at: null })

    const wrapper = await mountPackagePage()
    const cta = wrapper.find('.detail__cta')

    expect(cta.text()).toContain(wrapper.vm.$t('btn.become_member'))
  })

  it('shows a borrow button when the package is available and tokens suffice', async () => {
    setUser({ tokens: 5 })

    const wrapper = await mountPackagePage({ available: true })
    const cta = wrapper.find('.detail__cta')

    expect(cta.text()).toContain(wrapper.vm.$t('pages.package.borrow'))
  })

  it('shows a load-tokens link when tokens are insufficient', async () => {
    setUser({ tokens: 2 })

    const wrapper = await mountPackagePage({ available: true })
    const cta = wrapper.find('.detail__cta')

    expect(cta.text()).toContain(wrapper.vm.$t('btn.load_tokens'))
  })

  it('shows an unavailable message when the package cannot be borrowed', async () => {
    setUser({ tokens: 5 })

    const wrapper = await mountPackagePage({ available: false })
    const cta = wrapper.find('.detail__cta')

    expect(cta.text()).toContain(wrapper.vm.$t('pages.package.cta_unavailable'))
    expect(cta.find('button').exists()).toBe(false)
  })

  it('borrows the package, deducts tokens, and navigates to the dashboard on success', async () => {
    const auth = setUser({ tokens: 5 })
    postMock.mockResolvedValue({})

    const wrapper = await mountPackagePage({ id: 42, available: true })
    await wrapper.find('.detail__cta button').trigger('click')
    await flushPromises()

    expect(postMock).toHaveBeenCalledWith('/package-loans', { package_id: 42 })
    expect(auth.user?.tokens).toBe(2)
    expect(navigateToMock).toHaveBeenCalledWith('/dashboard')
  })

  it('shows an error and keeps the tokens when the borrow request fails', async () => {
    const auth = setUser({ tokens: 5 })
    postMock.mockRejectedValue({ message: 'Kein Slot mehr frei.' })

    const wrapper = await mountPackagePage({ available: true })
    await wrapper.find('.detail__cta button').trigger('click')
    await flushPromises()

    expect(wrapper.find('.alert-error').text()).toBe('Kein Slot mehr frei.')
    expect(auth.user?.tokens).toBe(5)
  })
})
