import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, type DOMWrapper } from '@vue/test-utils'
import GamesIndexPage from '~/pages/games/index.vue'

// fetchGames gets the page's reactive filter object; keep a snapshot per call
const fetchGamesCalls: Record<string, unknown>[] = []
const langs = vi.hoisted(() => ({ list: [] as { id: number; name: string }[] }))
const notifyMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useDialogs', () => () => ({ confirmDialog: vi.fn(), notify: notifyMock }))

const fetchGamesMock = vi.fn()
const smartSearchMock = vi.fn()
mockNuxtImport('useGames', () => {
  return () => ({
    fetchGames: fetchGamesMock,
    fetchMechanics: () =>
      Promise.resolve({
        data: [
          { id: 3, name: 'Deckbau', slug: 'deckbau' },
          { id: 7, name: 'Arbeiter einsetzen', slug: 'arbeiter-einsetzen' },
        ],
      }),
    fetchLanguages: () => Promise.resolve(langs.list),
    smartSearch: smartSearchMock,
  })
})

let activeWrapper: Awaited<ReturnType<typeof mountSuspended>> | null = null

async function mountPage() {
  activeWrapper = await mountSuspended(GamesIndexPage)
  await flushPromises()
  return activeWrapper
}

function lastFilters() {
  return fetchGamesCalls[fetchGamesCalls.length - 1]!
}

beforeEach(() => {
  langs.list = []
  notifyMock.mockReset()
  fetchGamesCalls.length = 0
  fetchGamesMock.mockReset()
  smartSearchMock.mockReset()
  smartSearchMock.mockResolvedValue({ data: [], meta: { intent: 'FULLTEXT' } })
  fetchGamesMock.mockImplementation((filters: Record<string, unknown>) => {
    fetchGamesCalls.push({ ...filters })
    return Promise.resolve({
      data: [],
      meta: { current_page: 1, last_page: 1, per_page: 24, total: 0 },
    })
  })
})

afterEach(() => {
  activeWrapper?.unmount()
  activeWrapper = null
})

describe('games list — mechanic filter', () => {
  it('sends the mechanic slugs the API filters by, not ids', async () => {
    const wrapper = await mountPage()
    const chips = wrapper.findAll('.filter-section--mechanic .option-chip')
    expect(chips.map((c: DOMWrapper<Element>) => c.text())).toEqual([
      'Deckbau',
      'Arbeiter einsetzen',
    ])

    await chips[0]!.trigger('click')
    await flushPromises()
    expect(lastFilters().mechanic).toBe('deckbau')

    await chips[1]!.trigger('click')
    await flushPromises()
    expect(lastFilters().mechanic).toBe('deckbau,arbeiter-einsetzen')
  })

  it('marks chosen chips and names a single choice in the active filter pill', async () => {
    const wrapper = await mountPage()
    const chip = wrapper.findAll('.filter-section--mechanic .option-chip')[1]!

    await chip.trigger('click')
    await flushPromises()

    expect(chip.classes()).toContain('option-chip--active')
    expect(wrapper.find('.active-pill__text').text()).toBe('Arbeiter einsetzen')
  })

  it('removes a mechanic again on a second click', async () => {
    const wrapper = await mountPage()
    const chip = wrapper.findAll('.filter-section--mechanic .option-chip')[0]!

    await chip.trigger('click')
    await chip.trigger('click')
    await flushPromises()

    expect(lastFilters().mechanic).toBe('')
    expect(chip.classes()).not.toContain('option-chip--active')
  })
})

describe('games list — player count', () => {
  it('sends 6+ as "6 or more"', async () => {
    const wrapper = await mountPage()
    const sixPlus = wrapper
      .findAll('.player-chip')
      .find((c: DOMWrapper<Element>) => c.text() === '6+')!

    await sixPlus.trigger('click')
    await flushPromises()

    expect(lastFilters().players).toBe('6+')
  })
})

describe('games list — search with filters', () => {
  it('passes the chosen filters along with the search text', async () => {
    vi.useFakeTimers({ shouldAdvanceTime: true })
    const wrapper = await mountPage()

    await wrapper.findAll('.filter-section--mechanic .option-chip')[0]!.trigger('click')
    await wrapper.find('.hero-search__input').setValue('Abenteuer')
    await vi.advanceTimersByTimeAsync(400)
    await flushPromises()

    const [q, filters] = smartSearchMock.mock.calls.at(-1)!
    expect(q).toBe('Abenteuer')
    expect(filters.mechanic).toBe('deckbau')
    vi.useRealTimers()
  })
})

describe('games list — sorting, paging and clearing', () => {
  /** Opens the filter panel and picks a difficulty chip. */
  async function chooseDifficulty(wrapper: Awaited<ReturnType<typeof mountPage>>) {
    await wrapper.find('.filter-toggle').trigger('click')
    await wrapper
      .findAll('.option-chip')
      .find((c: DOMWrapper<Element>) => c.text() === 'Leicht')!
      .trigger('click')
    await flushPromises()
  }

  it('sorts by newest and back to A–Z, starting on the first page', async () => {
    const wrapper = await mountPage()

    await wrapper.findAll('.sort-btn')[1]!.trigger('click')
    await flushPromises()
    expect(lastFilters()).toMatchObject({ sort: 'created_at', page: 1 })

    await wrapper.findAll('.sort-btn')[0]!.trigger('click')
    await flushPromises()
    expect(lastFilters().sort).toBe('title')
  })

  it('pages forward keeping the filters', async () => {
    fetchGamesMock.mockImplementation((filters: Record<string, unknown>) => {
      fetchGamesCalls.push({ ...filters })
      return Promise.resolve({
        data: [
          {
            id: 1,
            title: 'Azul',
            slug: 'azul',
            tags: [],
            mechanics: [],
            languages: [],
            available_copies_count: 1,
          },
        ],
        meta: { current_page: filters.page, last_page: 3, per_page: 24, total: 60 },
      })
    })
    window.scrollTo = vi.fn() as unknown as typeof window.scrollTo
    const wrapper = await mountPage()
    await chooseDifficulty(wrapper)
    await wrapper.find('.filter-toggle').trigger('click') // close the panel

    await wrapper.findAll('.pagination__btn').at(-1)!.trigger('click')
    await flushPromises()

    expect(lastFilters()).toMatchObject({ page: 2, difficulty: 'EASY' })
  })

  it('removes one filter with its pill and all of them with the clear button', async () => {
    langs.list = [{ id: 4, name: 'Deutsch' }]
    const wrapper = await mountPage()
    await chooseDifficulty(wrapper)
    await wrapper
      .findAll('.option-chip')
      .find((c: DOMWrapper<Element>) => c.text() === 'Deutsch')!
      .trigger('click')
    await flushPromises()
    expect(lastFilters()).toMatchObject({ difficulty: 'EASY', language: 4 })

    await wrapper.find('.filter-toggle').trigger('click') // pills show with the panel closed
    await wrapper.find('.active-pill').trigger('click')
    await flushPromises()
    expect(lastFilters()).toMatchObject({ difficulty: '', language: 4 })

    await wrapper.find('.clear-all-btn').trigger('click')
    await flushPromises()
    expect(lastFilters()).toMatchObject({ difficulty: '', language: '', available: false, page: 1 })
  })

  it('picking the same language twice removes it again', async () => {
    langs.list = [{ id: 4, name: 'Deutsch' }]
    const wrapper = await mountPage()
    await wrapper.find('.filter-toggle').trigger('click')
    const chip = () =>
      wrapper.findAll('.option-chip').find((c: DOMWrapper<Element>) => c.text() === 'Deutsch')!

    await chip().trigger('click')
    await flushPromises()
    expect(lastFilters().language).toBe(4)
    await chip().trigger('click')
    await flushPromises()
    expect(lastFilters().language).toBe('')
  })

  it('says so when the games cannot be loaded', async () => {
    fetchGamesMock.mockRejectedValue({ message: 'Server nicht erreichbar.' })
    await mountPage()

    expect(notifyMock).toHaveBeenCalledWith('Server nicht erreichbar.', 'error')
  })
})
