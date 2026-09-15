import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, DOMWrapper } from '@vue/test-utils'
import AdminGamesPage from '~/pages/admin/games.vue'

// The import-result dialog renders via <Teleport to="body"> — outside the
// mounted wrapper's own DOM subtree (see test/pages/admin-users.test.ts).
function modal() {
  return new DOMWrapper(document.body)
}

const fetchAdminGamesMock = vi.fn()
const importGamesMock = vi.fn()
const exportGamesMock = vi.fn()
mockNuxtImport('useAdmin', () => {
  return () => ({
    fetchAdminGames: fetchAdminGamesMock,
    createGame: vi.fn(),
    updateGame: vi.fn(),
    deleteGame: vi.fn(),
    fetchAdminMechanics: vi.fn().mockResolvedValue({ data: [] }),
    fetchAdminTags: vi.fn().mockResolvedValue({ data: [] }),
    createTag: vi.fn(),
    importGames: importGamesMock,
    exportGames: exportGamesMock,
    fetchCopies: vi.fn(),
    createCopy: vi.fn(),
    updateCopy: vi.fn(),
    deleteCopy: vi.fn(),
    uploadGameImages: vi.fn(),
    deleteGameImage: vi.fn(),
  })
})

mockNuxtImport('useGames', () => {
  return () => ({
    fetchGames: vi.fn(),
    fetchGame: vi.fn(),
    fetchMechanics: vi.fn(),
    fetchPackages: vi.fn(),
    fetchPackage: vi.fn(),
    fetchLanguages: vi.fn().mockResolvedValue([]),
    smartSearch: vi.fn(),
  })
})

let activeWrapper: Awaited<ReturnType<typeof mountSuspended>> | null = null

async function mountGamesPage() {
  fetchAdminGamesMock.mockResolvedValue({ data: [] })
  activeWrapper = await mountSuspended(AdminGamesPage, {
    global: { stubs: { UiVirtualDropdown: true, UiDatePicker: true, UiRichEditor: true } },
  })
  await flushPromises()
  return activeWrapper
}

afterEach(() => {
  activeWrapper?.unmount()
  activeWrapper = null
})

function setFileInputFiles(input: HTMLInputElement, files: File[]) {
  Object.defineProperty(input, 'files', { value: files, configurable: true })
}

describe('admin/games.vue — export', () => {
  beforeEach(() => {
    fetchAdminGamesMock.mockReset()
    exportGamesMock.mockReset()
    // happy-dom does not implement window.alert — plain assignment, since
    // there's no existing function for vi.spyOn to wrap.
    window.alert = vi.fn()
  })

  it('exports games and toggles the loading state', async () => {
    let resolveExport!: () => void
    exportGamesMock.mockImplementation(
      () => new Promise<void>((resolve) => (resolveExport = resolve))
    )
    const wrapper = await mountGamesPage()

    const exportBtn = wrapper.find('.hero-btn--secondary')
    const clickPromise = exportBtn.trigger('click')
    await flushPromises()
    expect(exportBtn.text()).toContain(wrapper.vm.$t('btn.exporting'))

    resolveExport()
    await clickPromise
    await flushPromises()

    expect(exportGamesMock).toHaveBeenCalled()
    expect(exportBtn.text()).toContain(wrapper.vm.$t('btn.export'))
  })

  it('alerts with the error message when export fails', async () => {
    exportGamesMock.mockRejectedValue({ message: 'Export fehlgeschlagen.' })
    const wrapper = await mountGamesPage()

    await wrapper.find('.hero-btn--secondary').trigger('click')
    await flushPromises()

    expect(window.alert).toHaveBeenCalledWith('Export fehlgeschlagen.')
  })
})

describe('admin/games.vue — import', () => {
  beforeEach(() => {
    fetchAdminGamesMock.mockReset()
    importGamesMock.mockReset()
  })

  it('imports a selected file and reloads the game list', async () => {
    importGamesMock.mockResolvedValue({ new: 2, updated: 1, total: 3 })
    fetchAdminGamesMock.mockResolvedValue({ data: [] })
    const wrapper = await mountGamesPage()
    fetchAdminGamesMock.mockClear()

    const fileInput = wrapper.find('input[type="file"][accept]').element as HTMLInputElement
    const file = new File(['data'], 'spiele.xlsx')
    setFileInputFiles(fileInput, [file])
    await wrapper.find('input[type="file"][accept]').trigger('change')
    await flushPromises()

    expect(importGamesMock).toHaveBeenCalledWith(file)
    expect(fetchAdminGamesMock).toHaveBeenCalledTimes(1)
    expect(modal().text()).toContain(wrapper.vm.$t('admin.import.title'))
    expect(modal().find('.import-result__value--new').text()).toBe('2')
  })

  it('does nothing when no file is selected', async () => {
    const wrapper = await mountGamesPage()

    const fileInput = wrapper.find('input[type="file"][accept]').element as HTMLInputElement
    setFileInputFiles(fileInput, [])
    await wrapper.find('input[type="file"][accept]').trigger('change')
    await flushPromises()

    expect(importGamesMock).not.toHaveBeenCalled()
  })

  it('shows an error and a zeroed result when the import fails', async () => {
    importGamesMock.mockRejectedValue({ message: 'Ungültige Datei.' })
    const wrapper = await mountGamesPage()

    const fileInput = wrapper.find('input[type="file"][accept]').element as HTMLInputElement
    setFileInputFiles(fileInput, [new File(['bad'], 'bad.xlsx')])
    await wrapper.find('input[type="file"][accept]').trigger('change')
    await flushPromises()

    expect(modal().text()).toContain('Ungültige Datei.')
    expect(modal().find('.import-result__value--new').text()).toBe('0')
  })
})
