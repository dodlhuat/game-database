import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, DOMWrapper, type VueWrapper } from '@vue/test-utils'
import AdminUsersPage from '~/pages/admin/users.vue'

// The ⋯ menu (basix ContextMenu) renders into document.body
function body() {
  return new DOMWrapper(document.body)
}

const api = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  patch: vi.fn(),
  delete: vi.fn(),
}))
mockNuxtImport('useApi', () => () => api)

const confirmMock = vi.hoisted(() => vi.fn())
const notifyMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useDialogs', () => () => ({ confirmDialog: confirmMock, notify: notifyMock }))

const user = (id: number, name: string, status: string, extra: Record<string, unknown> = {}) => ({
  id,
  name,
  email: `${name.toLowerCase()}@example.at`,
  role: 'MEMBER',
  status,
  created_at: '2026-01-01',
  tokens: 0,
  ...extra,
})

let wrapper: VueWrapper | null = null

async function mountPage(list: unknown[]) {
  api.get.mockResolvedValue({
    data: list,
    meta: { current_page: 1, last_page: 1, total: list.length },
  })
  wrapper = await mountSuspended(AdminUsersPage)
  await flushPromises()
}

function row(name: string) {
  return wrapper!.findAll('tbody tr').find((r) => r.text().includes(name))!
}

async function menuOf(name: string) {
  await row(name).find('.row-menu-btn').trigger('click')
  await flushPromises()
  await vi.waitFor(() => {
    if (!body().findAll('.context-menu-item').length) throw new Error('menu not open yet')
  })
  return body().findAll('.context-menu-item')
}

async function choose(name: string, label: string) {
  const item = (await menuOf(name)).filter((li) => li.text() === label).at(-1)!
  await item.trigger('click')
  await flushPromises()
}

beforeEach(() => {
  Object.values(api).forEach((fn) => fn.mockReset())
  confirmMock.mockReset()
  notifyMock.mockReset()
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
  document.body.innerHTML = ''
})

describe('admin/users.vue — suspended, rejected and anonymized accounts', () => {
  it('offers unlock and delete for a suspended user', async () => {
    await mountPage([user(1, 'Mia', 'SUSPENDED')])

    const items = (await menuOf('Mia')).map((i) => i.text())
    expect(items).toEqual(expect.arrayContaining(['Entsperren', 'Löschen']))
  })

  it('offers approve and delete but no unlock for a rejected user', async () => {
    await mountPage([user(2, 'Tom', 'REJECTED')])

    const items = (await menuOf('Tom')).map((i) => i.text())
    expect(items).toEqual(expect.arrayContaining(['Freischalten', 'Löschen']))
    expect(items).not.toContain('Entsperren')
  })

  it('approves a rejected user later and shows them active', async () => {
    api.patch.mockResolvedValue({})
    await mountPage([user(2, 'Tom', 'REJECTED')])

    await choose('Tom', 'Freischalten')

    expect(api.patch).toHaveBeenCalledWith('/admin/users/2/approve')
    expect(row('Tom').text()).toContain('Aktiv')
  })

  it('offers neither for an anonymized account', async () => {
    await mountPage([user(1, 'Gelöschtes Konto', 'SUSPENDED', { anonymized_at: '2026-11-01' })])

    expect(row('Gelöschtes Konto').text()).toContain('Anonymisiert')
    const items = (await menuOf('Gelöschtes Konto')).map((i) => i.text())
    expect(items).not.toContain('Entsperren')
    expect(items).not.toContain('Freischalten')
    expect(items).not.toContain('Löschen')
  })

  it('unlocks a user and shows them active', async () => {
    api.patch.mockResolvedValue({})
    await mountPage([user(1, 'Mia', 'SUSPENDED')])

    await choose('Mia', 'Entsperren')

    expect(api.patch).toHaveBeenCalledWith('/admin/users/1/unsuspend')
    expect(row('Mia').text()).toContain('Aktiv')
    expect(notifyMock).toHaveBeenCalledWith(expect.stringContaining('Mia'))
  })

  it('deletes only after asking and takes the user off the list', async () => {
    confirmMock.mockResolvedValueOnce(false).mockResolvedValueOnce(true)
    api.delete.mockResolvedValue({})
    await mountPage([user(1, 'Mia', 'SUSPENDED'), user(2, 'Tom', 'ACTIVE')])

    await choose('Mia', 'Löschen')
    expect(api.delete).not.toHaveBeenCalled()
    expect(confirmMock.mock.calls[0]![0]).toMatchObject({ danger: true })
    expect(confirmMock.mock.calls[0]![0].message).toContain('30 Tage')

    document.body.click()
    await flushPromises()
    await choose('Mia', 'Löschen')
    expect(api.delete).toHaveBeenCalledWith('/admin/users/1')
    expect(wrapper!.text()).not.toContain('Mia')
  })

  it('says why a delete was refused', async () => {
    confirmMock.mockResolvedValue(true)
    api.delete.mockRejectedValue({
      status: 422,
      message: 'Dieses Konto hat noch laufende Ausleihen.',
    })
    await mountPage([user(1, 'Mia', 'SUSPENDED')])

    await choose('Mia', 'Löschen')

    expect(notifyMock).toHaveBeenCalledWith('Dieses Konto hat noch laufende Ausleihen.', 'error')
    expect(wrapper!.text()).toContain('Mia')
  })
})

describe('admin/users.vue — deleted users', () => {
  it('switches to the deleted users from page 1 and back', async () => {
    await mountPage([user(1, 'Tom', 'ACTIVE')])
    api.get.mockResolvedValue({
      data: [user(5, 'Mia', 'SUSPENDED', { deleted_at: '2026-11-20T10:00:00Z' })],
      meta: { current_page: 1, last_page: 1, total: 1 },
    })

    await wrapper!.find('.deleted-toggle input').setValue(true)
    await flushPromises()

    expect(api.get).toHaveBeenLastCalledWith('/admin/users', { params: { page: 1, deleted: 1 } })
    expect(wrapper!.find('.dash-section__title').text()).toBe('Gelöschte Konten')
    expect(row('Mia').text()).toContain('Gelöscht am 20.11.2026')
    // the switch stays in place while the list reloads
    expect(wrapper!.find('.deleted-toggle input').exists()).toBe(true)

    await wrapper!.find('.deleted-toggle input').setValue(false)
    await flushPromises()
    expect(api.get).toHaveBeenLastCalledWith('/admin/users', { params: { page: 1 } })
  })

  it('restores a deleted user and says they are still suspended', async () => {
    api.patch.mockResolvedValue({})
    await mountPage([user(1, 'Tom', 'ACTIVE')])
    api.get.mockResolvedValue({
      data: [user(5, 'Mia', 'SUSPENDED', { deleted_at: '2026-11-20T10:00:00Z' })],
      meta: { current_page: 1, last_page: 1, total: 1 },
    })
    await wrapper!.find('.deleted-toggle input').setValue(true)
    await flushPromises()

    await row('Mia')
      .findAll('button')
      .find((b) => b.text() === 'Wiederherstellen')!
      .trigger('click')
    await flushPromises()

    expect(api.patch).toHaveBeenCalledWith('/admin/users/5/restore')
    expect(notifyMock).toHaveBeenCalledWith(expect.stringContaining('noch gesperrt'))
    expect(wrapper!.find('.dash-empty').text()).toContain('Keine gelöschten Konten')
  })

  it('offers no restore for an anonymized deleted user', async () => {
    await mountPage([user(1, 'Tom', 'ACTIVE')])
    api.get.mockResolvedValue({
      data: [
        user(5, 'Gelöschtes Konto', 'SUSPENDED', {
          deleted_at: '2026-10-01',
          anonymized_at: '2026-11-01',
        }),
      ],
      meta: { current_page: 1, last_page: 1, total: 1 },
    })
    await wrapper!.find('.deleted-toggle input').setValue(true)
    await flushPromises()

    expect(row('Gelöschtes Konto').text()).toContain('Anonymisiert')
    expect(row('Gelöschtes Konto').text()).not.toContain('Wiederherstellen')
  })
})
