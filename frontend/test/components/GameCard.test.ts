import { describe, it, expect, afterEach } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import GameCard from '~/components/GameCard.vue'
import type { Game } from '~/composables/useGames'

const DAY = 24 * 60 * 60 * 1000

function makeGame(overrides: Partial<Game> = {}): Game {
  return {
    id: 1,
    title: 'Testspiel',
    slug: 'testspiel',
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
    size_category: 'MEDIUM',
    borrow_cost: 0,
    reservation_cost: 0,
    languages: [],
    year: null,
    instagram_url: null,
    deposit_tokens: 0,
    cover_image_url: null,
    available_copies_count: 0,
    copies_count: 0,
    reviews_count: 0,
    ...overrides,
  }
}

let wrapper: Awaited<ReturnType<typeof mountSuspended>> | null = null

async function mountCard(game: Game) {
  wrapper = await mountSuspended(GameCard, { props: { game } })
  return wrapper
}

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
})

describe('GameCard new badge', () => {
  it('shows the badge for a game added within the last 30 days', async () => {
    const w = await mountCard(
      makeGame({ created_at: new Date(Date.now() - 5 * DAY).toISOString() })
    )
    expect(w.find('.game-card__new').exists()).toBe(true)
    expect(w.find('.game-card__new').text()).toBe('Neu')
  })

  it('hides the badge once the game is older than 30 days', async () => {
    const w = await mountCard(
      makeGame({ created_at: new Date(Date.now() - 31 * DAY).toISOString() })
    )
    expect(w.find('.game-card__new').exists()).toBe(false)
  })

  it('hides the badge when created_at is missing or invalid', async () => {
    let w = await mountCard(makeGame({ created_at: null }))
    expect(w.find('.game-card__new').exists()).toBe(false)
    w.unmount()
    w = await mountCard(makeGame({ created_at: 'not-a-date' }))
    expect(w.find('.game-card__new').exists()).toBe(false)
  })
})
