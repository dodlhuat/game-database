import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { useAuthStore } from '~/stores/auth'
import type { Game } from '~/composables/useGames'
import GameDetailPage from '~/pages/games/[slug].vue'

// openLoanModal/submitLoan drive real vanilla-JS basix widgets (Modal) via
// direct DOM manipulation — out of scope here (see Phase B precedent for
// DatePicker/RichEditor/etc.); stubbed just so mounting/interacting never
// crashes if that code path is exercised.
// Arrow functions can't be used with `new` — these are real `function`s so
// the mocked classes stay constructible.
vi.mock('@dodlhuat/basix/js/modal', () => ({
  Modal: vi.fn(function MockModal() {
    return { show: vi.fn(), hide: vi.fn() }
  }),
}))
vi.mock('@dodlhuat/basix/js/lightbox', () => ({
  Lightbox: vi.fn(function MockLightbox() {
    return { show: vi.fn() }
  }),
}))

const fetchGameMock = vi.fn()
mockNuxtImport('useGames', () => {
  return () => ({ fetchGame: fetchGameMock, fetchGames: vi.fn(), fetchMechanics: vi.fn() })
})

const addReservationMock = vi.fn()
const createLoanMock = vi.fn()
mockNuxtImport('useLoans', () => {
  return () => ({
    createLoan: createLoanMock,
    addReservation: addReservationMock,
    fetchDashboard: vi.fn(),
    fetchLoans: vi.fn(),
    returnLoan: vi.fn(),
    requestExtension: vi.fn(),
    addFavorite: vi.fn(),
    removeFavorite: vi.fn(),
    removeReservation: vi.fn(),
    reportDamage: vi.fn(),
    fetchTokenTransactions: vi.fn(),
  })
})

const fetchSettingsMock = vi.fn()
mockNuxtImport('useLoanSettings', () => {
  return () => ({
    fetchSettings: fetchSettingsMock,
    getNextAppointment: vi.fn(),
    getDueDate: vi.fn(),
    formatDate: vi.fn(),
    toIsoDate: vi.fn(),
  })
})

const baseGame: Game = {
  id: 1,
  title: 'Catan',
  slug: 'catan',
  description: null,
  short_description: null,
  tags: [],
  mechanics: [],
  min_players: null,
  max_players: null,
  min_age: null,
  duration_min: null,
  duration_max: null,
  difficulty: null,
  languages: [],
  year: null,
  instagram_url: null,
  deposit_tokens: 0,
  size_category: 'MEDIUM',
  borrow_cost: 6,
  reservation_cost: 1,
  cover_image_url: null,
  available_copies_count: 1,
  copies_count: 1,
  reviews_count: 0,
  already_borrowed: false,
  copies: [{ id: 1, condition: 'NEW', is_available: true }],
  images: [],
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

// useAsyncData caches by key (`game-${slug}`) across mounts within the same
// Nuxt test app instance — a fresh slug per call avoids stale cross-test data.
let slugCounter = 0

async function mountGamePage(game: Partial<Game> = {}) {
  const slug = `catan-${slugCounter++}`
  fetchGameMock.mockResolvedValue({ data: { ...baseGame, ...game, slug } })
  const wrapper = await mountSuspended(GameDetailPage, { route: `/games/${slug}` })
  await flushPromises()
  return wrapper
}

describe('games/[slug].vue — borrow/reserve CTA', () => {
  beforeEach(() => {
    fetchGameMock.mockReset()
    addReservationMock.mockReset()
    createLoanMock.mockReset()
    fetchSettingsMock.mockReset()
    fetchSettingsMock.mockResolvedValue({ loan_cost_medium: 6 })
  })

  it('shows a login link when not logged in', async () => {
    const auth = useAuthStore()
    auth.token = null
    auth.user = null

    const wrapper = await mountGamePage()
    const actions = wrapper.find('.gd-actions')

    expect(actions.text()).toContain(wrapper.vm.$t('pages.game.login_to_borrow'))
    expect(actions.findAll('button')).toHaveLength(0)
  })

  it('shows the membership-required link for an active non-member', async () => {
    setUser({ role: 'USER', membership_expires_at: null })

    const wrapper = await mountGamePage()
    const actions = wrapper.find('.gd-actions')

    expect(actions.text()).toContain(wrapper.vm.$t('pages.game.membership_required'))
  })

  it('shows the already-borrowed badge for a member who already has this game', async () => {
    setUser({})

    const wrapper = await mountGamePage({ already_borrowed: true })
    const actions = wrapper.find('.gd-actions')

    expect(actions.text()).toContain(wrapper.vm.$t('pages.game.already_borrowed'))
  })

  it('shows a borrow button when copies are available and tokens suffice', async () => {
    setUser({ tokens: 10 })

    const wrapper = await mountGamePage({ available_copies_count: 1, deposit_tokens: 0 })
    const actions = wrapper.find('.gd-actions')

    expect(actions.text()).toContain(wrapper.vm.$t('btn.borrow_game', { cost: 6 }))
  })

  it('shows a load-tokens link when copies are available but tokens are insufficient', async () => {
    setUser({ tokens: 0 })

    const wrapper = await mountGamePage({ available_copies_count: 1, deposit_tokens: 0 })
    const actions = wrapper.find('.gd-actions')

    expect(actions.text()).toContain(wrapper.vm.$t('btn.load_tokens'))
    expect(actions.text()).not.toContain(wrapper.vm.$t('btn.borrow_game', { cost: 6 }))
  })

  it('uses the price of the game category and ignores tokens blocked as deposit', async () => {
    setUser({ tokens: 12, tokens_blocked: 5 })

    const large = await mountGamePage({ size_category: 'LARGE', borrow_cost: 10 })
    expect(large.find('.gd-actions').text()).toContain(large.vm.$t('btn.load_tokens'))

    const small = await mountGamePage({ size_category: 'SMALL', borrow_cost: 3 })
    expect(small.find('.gd-actions').text()).toContain(small.vm.$t('btn.borrow_game', { cost: 3 }))
    expect(small.text()).toContain('Klein · 3 Token')
  })

  it('shows the reservation fee, charges it and confirms the reservation', async () => {
    const auth = setUser({ tokens: 10 })
    addReservationMock.mockResolvedValue({})

    const wrapper = await mountGamePage({ available_copies_count: 0 })
    const actions = wrapper.find('.gd-actions')
    expect(actions.text()).toContain(wrapper.vm.$t('btn.reserve_game', { cost: 1 }))
    expect(actions.text()).toContain(wrapper.vm.$t('pages.game.reserve_fee_note', { cost: 1 }))

    await actions.find('button').trigger('click')
    await flushPromises()

    expect(addReservationMock).toHaveBeenCalledWith(baseGame.id)
    expect(auth.user?.tokens).toBe(9)
    expect(wrapper.find('.gd-reserve-status--success').text()).toBe(
      wrapper.vm.$t('pages.game.reserve_success_paid', { cost: 1 })
    )
  })

  it('tells admins what members pay for a reservation', async () => {
    setUser({ role: 'ADMIN', tokens: 0 })

    const wrapper = await mountGamePage({ available_copies_count: 0 })
    const actions = wrapper.find('.gd-actions')

    expect(actions.text()).toContain(wrapper.vm.$t('btn.reserve'))
    expect(actions.text()).toContain(
      wrapper.vm.$t('pages.game.reserve_fee_note_admin', { cost: 1 })
    )
  })

  it('asks to load tokens when the reservation fee is not covered', async () => {
    setUser({ tokens: 1, tokens_blocked: 1 })

    const wrapper = await mountGamePage({ available_copies_count: 0 })
    const actions = wrapper.find('.gd-actions')

    expect(actions.text()).toContain(wrapper.vm.$t('btn.load_tokens'))
    expect(actions.findAll('button')).toHaveLength(0)
  })

  it('shows an error status when reserving fails', async () => {
    setUser({})
    addReservationMock.mockRejectedValue({ message: 'Schon reserviert.' })

    const wrapper = await mountGamePage({ available_copies_count: 0 })
    const actions = wrapper.find('.gd-actions')

    await actions.find('button').trigger('click')
    await flushPromises()

    expect(wrapper.find('.gd-reserve-status--error').text()).toBe('Schon reserviert.')
  })
})
