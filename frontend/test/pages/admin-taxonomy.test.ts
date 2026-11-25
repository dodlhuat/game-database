import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, DOMWrapper, type VueWrapper } from '@vue/test-utils'
import TagsPage from '~/pages/admin/tags.vue'
import MechanicsPage from '~/pages/admin/mechanics.vue'

// The form dialog renders via <Teleport to="body">
function dialog() {
  return new DOMWrapper(document.body).find('.modal-overlay')
}

const adm = vi.hoisted(() => ({
  fetchAdminTags: vi.fn(),
  createTag: vi.fn(),
  updateTag: vi.fn(),
  deleteTag: vi.fn(),
  fetchAdminMechanics: vi.fn(),
  createMechanic: vi.fn(),
  updateMechanic: vi.fn(),
  deleteMechanic: vi.fn(),
}))
mockNuxtImport('useAdmin', () => () => adm)

const confirmMock = vi.hoisted(() => vi.fn())
const notifyMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useDialogs', () => {
  return () => ({ confirmDialog: confirmMock, notify: notifyMock })
})

const kinds = [
  {
    name: 'tags',
    page: TagsPage,
    fetch: 'fetchAdminTags',
    create: 'createTag',
    update: 'updateTag',
    remove: 'deleteTag',
  },
  {
    name: 'mechanics',
    page: MechanicsPage,
    fetch: 'fetchAdminMechanics',
    create: 'createMechanic',
    update: 'updateMechanic',
    remove: 'deleteMechanic',
  },
] as const

let wrapper: VueWrapper | null = null

beforeEach(() => {
  Object.values(adm).forEach((fn) => fn.mockReset())
  confirmMock.mockReset()
  notifyMock.mockReset()
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
})

describe.each(kinds)('admin/$name.vue', (k) => {
  async function mountPage() {
    adm[k.fetch].mockResolvedValue({
      data: [{ id: 3, name: 'Kooperativ', slug: 'kooperativ', games_count: 4 }],
    })
    wrapper = await mountSuspended(k.page)
    await flushPromises()
  }

  async function save() {
    await dialog()
      .findAll('button')
      .find((b) => b.text() === 'Speichern')!
      .trigger('click')
    await flushPromises()
  }

  it('creates an entry and reloads the list', async () => {
    adm[k.create].mockResolvedValue({})
    await mountPage()

    await wrapper!.find('.hero-btn').trigger('click')
    await dialog().find('input').setValue('Würfeln')
    await save()

    expect(adm[k.create]).toHaveBeenCalledWith('Würfeln')
    expect(adm[k.fetch]).toHaveBeenCalledTimes(2)
    expect(dialog().exists()).toBe(false)
  })

  it('renames an entry and shows a refused name', async () => {
    adm[k.update].mockRejectedValue({ status: 422, message: 'Dieser Name ist schon vergeben.' })
    await mountPage()

    await wrapper!.find('tbody .action-btn').trigger('click')
    await dialog().find('input').setValue('Würfeln')
    await save()

    expect(adm[k.update]).toHaveBeenCalledWith(3, 'Würfeln')
    expect(dialog().find('.form-error').text()).toBe('Dieser Name ist schon vergeben.')
  })

  it('does not save an empty name', async () => {
    await mountPage()

    await wrapper!.find('.hero-btn').trigger('click')
    await dialog().find('input').setValue('   ')
    await save()

    expect(adm[k.create]).not.toHaveBeenCalled()
  })

  it('names the affected games before deleting', async () => {
    confirmMock.mockResolvedValue(true)
    adm[k.remove].mockResolvedValue({})
    await mountPage()

    await wrapper!.find('tbody .action-btn--danger').trigger('click')
    await flushPromises()

    expect(confirmMock.mock.calls[0]![0].message).toContain('4 Spielen')
    expect(adm[k.remove]).toHaveBeenCalledWith(3)
    expect(notifyMock).toHaveBeenCalledWith('„Kooperativ“ wurde gelöscht.')
  })
})
