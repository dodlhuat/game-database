import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, DOMWrapper, type VueWrapper } from '@vue/test-utils'
import AdminSponsorsPage from '~/pages/admin/sponsors.vue'
import type { ApiSponsor } from '~/types/api'

// The form dialog renders via <Teleport to="body">
function dialog() {
  return new DOMWrapper(document.body).find('.modal-overlay')
}

const sp = vi.hoisted(() => ({
  fetchAdminSponsors: vi.fn(),
  createSponsor: vi.fn(),
  updateSponsor: vi.fn(),
  toggleSponsor: vi.fn(),
  reorderSponsors: vi.fn(),
  deleteSponsor: vi.fn(),
}))
mockNuxtImport('useSponsors', () => () => sp)

const confirmMock = vi.hoisted(() => vi.fn())
const notifyMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useDialogs', () => {
  return () => ({ confirmDialog: confirmMock, notify: notifyMock })
})

const sponsor = (id: number, name: string) =>
  ({
    id,
    name,
    url: null,
    logo_url: null,
    logo_on_light: false,
    is_active: true,
  }) as unknown as ApiSponsor

let wrapper: VueWrapper | null = null

async function openCreate(list: ApiSponsor[] = []) {
  sp.fetchAdminSponsors.mockResolvedValue(list)
  wrapper = await mountSuspended(AdminSponsorsPage)
  await flushPromises()
  await wrapper.find('.hero-btn').trigger('click')
}

async function chooseLogo(file: File) {
  const input = dialog().find('input[type="file"]')
  Object.defineProperty(input.element, 'files', { value: [file], configurable: true })
  await input.trigger('change')
}

async function save() {
  await dialog()
    .findAll('button')
    .find((b) => b.text() === 'Speichern')!
    .trigger('click')
  await flushPromises()
}

beforeEach(() => {
  Object.values(sp).forEach((fn) => fn.mockReset())
  confirmMock.mockReset()
  notifyMock.mockReset()
  URL.createObjectURL = vi.fn(() => 'blob:logo')
  URL.revokeObjectURL = vi.fn()
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
})

describe('admin/sponsors.vue', () => {
  it('sends name, trimmed link, plate and logo', async () => {
    sp.createSponsor.mockResolvedValue(sponsor(1, 'Spielwaren Huber'))
    await openCreate()

    await dialog().find('input').setValue('  Spielwaren Huber ')
    await dialog().find('input[type="url"]').setValue(' https://huber.at ')
    await chooseLogo(new File(['x'], 'logo.png', { type: 'image/png' }))
    await save()

    const fd = sp.createSponsor.mock.calls[0]![0] as FormData
    expect(fd.get('name')).toBe('Spielwaren Huber')
    expect(fd.get('url')).toBe('https://huber.at')
    expect(fd.get('is_active')).toBe('1')
    expect((fd.get('logo') as File).name).toBe('logo.png')
    expect(dialog().exists()).toBe(false)
  })

  it('refuses logos in other formats right away', async () => {
    await openCreate()

    await chooseLogo(new File(['x'], 'logo.svg', { type: 'image/svg+xml' }))

    expect(dialog().find('.form-error').text()).toBe('Nur PNG, JPG, WebP oder GIF erlaubt.')
  })

  it('needs a name and a real link', async () => {
    await openCreate()

    await save()
    expect(dialog().find('.form-error').exists()).toBe(true)

    await dialog().find('input').setValue('Huber')
    await dialog().find('input[type="url"]').setValue('huber.at')
    await save()
    expect(dialog().find('.form-error').text()).toContain('http')
    expect(sp.createSponsor).not.toHaveBeenCalled()
  })

  it('says why the server refused, e.g. a logo that is too large', async () => {
    sp.createSponsor.mockRejectedValue({
      status: 422,
      message: 'Das Logo darf höchstens 2 MB groß sein.',
    })
    await openCreate()

    await dialog().find('input').setValue('Huber')
    await save()

    expect(dialog().find('.form-error').text()).toBe('Das Logo darf höchstens 2 MB groß sein.')
  })

  it('says so when the list cannot be loaded', async () => {
    sp.fetchAdminSponsors.mockRejectedValue({ message: 'Server nicht erreichbar.' })
    wrapper = await mountSuspended(AdminSponsorsPage)
    await flushPromises()

    expect(notifyMock).toHaveBeenCalledWith('Server nicht erreichbar.', 'error')
  })
})
