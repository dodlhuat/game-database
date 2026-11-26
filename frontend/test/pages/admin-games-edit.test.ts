import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, DOMWrapper, type VueWrapper } from '@vue/test-utils'
import AdminGamesPage from '~/pages/admin/games.vue'

function body() {
  return new DOMWrapper(document.body)
}

const adm = vi.hoisted(() => ({
  fetchAdminGames: vi.fn(),
  createGame: vi.fn(),
  updateGame: vi.fn(),
  deleteGame: vi.fn(),
  fetchAdminMechanics: vi.fn(),
  fetchAdminTags: vi.fn(),
  createTag: vi.fn(),
  importGames: vi.fn(),
  exportGames: vi.fn(),
  fetchCopies: vi.fn(),
  createCopy: vi.fn(),
  updateCopy: vi.fn(),
  deleteCopy: vi.fn(),
  uploadGameImages: vi.fn(),
  deleteGameImage: vi.fn(),
}))
mockNuxtImport('useAdmin', () => () => adm)

mockNuxtImport('useGames', () => () => ({ fetchLanguages: vi.fn().mockResolvedValue([]) }))

const getMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useApi', () => () => ({
  get: getMock,
  post: vi.fn(),
  put: vi.fn(),
  patch: vi.fn(),
  delete: vi.fn(),
}))

const confirmMock = vi.hoisted(() => vi.fn())
const notifyMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useDialogs', () => () => ({ confirmDialog: confirmMock, notify: notifyMock }))

const GAME = {
  id: 4,
  title: 'Azul',
  slug: 'azul',
  description: 'Fliesen legen',
  short_description: null,
  min_players: 2,
  max_players: 4,
  min_age: 8,
  duration_min: 30,
  duration_max: 45,
  difficulty: 'EASY',
  year: 2017,
  instagram_url: null,
  deposit_tokens: 2,
  size_category: 'MEDIUM',
  is_active: true,
  tags: [{ id: 1, name: 'Familie' }],
  mechanics: [],
  languages: [],
  cover_image_url: null,
  copies_count: 1,
}

let wrapper: VueWrapper | null = null

async function mountPage() {
  adm.fetchAdminGames.mockResolvedValue({
    data: [{ ...GAME }],
    meta: { current_page: 1, last_page: 1, total: 1 },
  })
  adm.fetchAdminMechanics.mockResolvedValue({ data: [] })
  adm.fetchAdminTags.mockResolvedValue({ data: [] })
  getMock.mockResolvedValue({ data: { ...GAME, images: [] } })
  wrapper = await mountSuspended(AdminGamesPage, {
    global: { stubs: { UiVirtualDropdown: true, UiDatePicker: true, UiRichEditor: true } },
  })
  await flushPromises()
}

function rowButton(text: string) {
  return wrapper!.findAll('tbody .action-btn').find((b) => b.text().includes(text))!
}

const sent = (fd: FormData) => ({
  fields: Object.fromEntries([...fd.entries()].filter(([k]) => !k.endsWith('[]'))),
  tags: fd.getAll('tag_ids[]'),
})

beforeEach(() => {
  Object.values(adm).forEach((fn) => fn.mockReset())
  getMock.mockReset()
  confirmMock.mockReset()
  notifyMock.mockReset()
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
})

describe('admin/games.vue — editing a game', () => {
  it('sends every field, so emptied ones are cleared', async () => {
    adm.updateGame.mockResolvedValue({})
    await mountPage()

    await rowButton('Bearbeiten').trigger('click')
    await flushPromises()
    await body()
      .findAll('.modal-overlay button')
      .find((b) => b.text() === 'Speichern')!
      .trigger('click')
    await flushPromises()

    const [id, fd] = adm.updateGame.mock.calls[0]!
    const { fields, tags } = sent(fd as FormData)
    expect(id).toBe(4)
    expect(fields).toMatchObject({
      title: 'Azul',
      instagram_url: '',
      short_description: '',
      deposit_tokens: '2',
    })
    expect(tags).toEqual(['1'])
    // an empty list still goes along, so the last mechanic can be removed
    expect(fields).toHaveProperty('mechanic_ids', '')
    expect(fields).toHaveProperty('language_ids', '')
  })

  it('keeps the form open with the server reason when saving fails', async () => {
    adm.updateGame.mockRejectedValue({ status: 422, message: 'Dieser Slug ist schon vergeben.' })
    await mountPage()

    await rowButton('Bearbeiten').trigger('click')
    await flushPromises()
    await body()
      .findAll('.modal-overlay button')
      .find((b) => b.text() === 'Speichern')!
      .trigger('click')
    await flushPromises()

    expect(body().find('.modal-overlay .form-error').text()).toBe('Dieser Slug ist schon vergeben.')
  })

  it('deletes a game only after asking', async () => {
    confirmMock.mockResolvedValueOnce(false).mockResolvedValueOnce(true)
    adm.deleteGame.mockResolvedValue({})
    await mountPage()

    await wrapper!.find('tbody .action-btn--danger').trigger('click')
    await flushPromises()
    expect(adm.deleteGame).not.toHaveBeenCalled()

    await wrapper!.find('tbody .action-btn--danger').trigger('click')
    await flushPromises()
    expect(adm.deleteGame).toHaveBeenCalledWith(4)
    expect(notifyMock).toHaveBeenCalledWith(expect.stringContaining('Azul'))
  })
})

describe('admin/games.vue — copies', () => {
  it('adds a copy and updates the count', async () => {
    adm.fetchCopies
      .mockResolvedValueOnce({ data: [{ id: 10, condition: 'GOOD', qr_code: 'QR-10' }] })
      .mockResolvedValueOnce({
        data: [
          { id: 10, condition: 'GOOD', qr_code: 'QR-10' },
          { id: 11, condition: 'NEW', qr_code: 'QR-11' },
        ],
      })
    adm.createCopy.mockResolvedValue({})
    await mountPage()

    await rowButton('Kopien').trigger('click')
    await flushPromises()
    await body()
      .findAll('button')
      .filter((b) => b.text().includes('Kopie hinzufügen'))
      .at(0)!
      .trigger('click')
    await flushPromises()
    await body()
      .findAll('.modal-overlay button')
      .filter((b) => b.text() === 'Speichern')
      .at(-1)!
      .trigger('click')
    await flushPromises()

    expect(adm.createCopy).toHaveBeenCalledWith(expect.objectContaining({ game_id: 4 }))
    expect(adm.fetchCopies).toHaveBeenLastCalledWith({ game_id: 4 })
  })

  it('says so when the copies cannot be loaded', async () => {
    adm.fetchCopies.mockRejectedValue({ message: 'Server nicht erreichbar.' })
    await mountPage()

    await rowButton('Kopien').trigger('click')
    await flushPromises()

    expect(notifyMock).toHaveBeenCalledWith('Server nicht erreichbar.', 'error')
  })
})

describe('admin/games.vue — title search', () => {
  it('searches by title after a short pause, from the first page', async () => {
    await mountPage()
    adm.fetchAdminGames.mockClear()

    const input = wrapper!.find('.title-search__input')
    await input.setValue('az')
    await input.setValue('azul')
    await new Promise((r) => setTimeout(r, 400))
    await flushPromises()

    // one request for the whole word, not one per keystroke
    expect(adm.fetchAdminGames).toHaveBeenCalledTimes(1)
    expect(adm.fetchAdminGames).toHaveBeenCalledWith({ page: 1, search: 'azul' })
    // the field stays while the list reloads
    expect(wrapper!.find('.title-search__input').exists()).toBe(true)
  })

  it('names the search term when nothing matches and lists all again when cleared', async () => {
    await mountPage()
    adm.fetchAdminGames.mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, total: 0 },
    })

    await wrapper!.find('.title-search__input').setValue('Monopoly')
    await new Promise((r) => setTimeout(r, 400))
    await flushPromises()
    expect(wrapper!.find('.dash-empty__text').text()).toContain('„Monopoly“')

    await wrapper!.find('.title-search__input').setValue('')
    await new Promise((r) => setTimeout(r, 400))
    await flushPromises()
    expect(adm.fetchAdminGames).toHaveBeenLastCalledWith({ page: 1 })
  })
})
