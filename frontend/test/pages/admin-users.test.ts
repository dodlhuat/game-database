import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, DOMWrapper } from '@vue/test-utils'
import AdminUsersPage from '~/pages/admin/users.vue'

// <Teleport to="body"> moves modal content out of the mounted wrapper's own
// DOM subtree, so wrapper.find() can't see it — query document.body instead.
function modal() {
  return new DOMWrapper(document.body)
}

const getMock = vi.fn()
const postMock = vi.fn()
const putMock = vi.fn()
const patchMock = vi.fn()
mockNuxtImport('useApi', () => {
  return () => ({
    get: getMock,
    post: postMock,
    put: putMock,
    patch: patchMock,
    delete: vi.fn(),
    download: vi.fn(),
  })
})

const baseUsers = [
  {
    id: 1,
    name: 'Pending Paul',
    email: 'paul@example.com',
    role: 'USER',
    status: 'PENDING',
    created_at: '2026-01-01',
  },
  {
    id: 2,
    name: 'Active Anna',
    email: 'anna@example.com',
    role: 'MEMBER',
    status: 'ACTIVE',
    created_at: '2026-01-01',
  },
]

// The create/edit modals render via <Teleport to="body"> — their DOM nodes
// land in document.body outside the mounted wrapper's own subtree, so they
// survive past the end of a test unless explicitly unmounted. Track and
// clean up the last-mounted wrapper to avoid leaking stale modal content
// (with its filled-in field values) into the next test.
let activeWrapper: Awaited<ReturnType<typeof mountSuspended>> | null = null

async function mountUsersPage() {
  getMock.mockResolvedValue({ data: baseUsers.map((u) => ({ ...u })) })
  activeWrapper = await mountSuspended(AdminUsersPage, {
    global: { stubs: { UiVirtualDropdown: true } },
  })
  await flushPromises()
  return activeWrapper
}

function row(wrapper: Awaited<ReturnType<typeof mountUsersPage>>, i: number) {
  return wrapper.findAll('tbody tr')[i]!
}

// Secondary actions live in the ⋯ menu (basix ContextMenu), which renders
// into document.body once its module has loaded.
async function openMenu(wrapper: Awaited<ReturnType<typeof mountUsersPage>>, i: number) {
  await row(wrapper, i).find('.row-menu-btn').trigger('click')
  await flushPromises()
  // The first open imports the menu module — wait for its items rather than
  // a fixed tick, which lost the race now and then and made this test flaky
  await vi.waitFor(() => {
    if (!modal().findAll('.context-menu-item').length) throw new Error('menu not open yet')
  })
  return modal()
    .findAll('.context-menu-item')
    .map((li) => li)
}
async function menuLabels(wrapper: Awaited<ReturnType<typeof mountUsersPage>>, i: number) {
  return (await openMenu(wrapper, i)).map((li) => li.text())
}
async function clickMenuItem(
  wrapper: Awaited<ReturnType<typeof mountUsersPage>>,
  i: number,
  label: string
) {
  const item = (await openMenu(wrapper, i)).find((li) => li.text() === label)!
  await item.trigger('click')
  await flushPromises()
}

afterEach(() => {
  activeWrapper?.unmount()
  activeWrapper = null
  document.querySelectorAll('.context-menu').forEach((el) => el.remove())
})

describe('admin/users.vue — status actions', () => {
  beforeEach(() => {
    getMock.mockReset()
    postMock.mockReset()
    putMock.mockReset()
    patchMock.mockReset()
  })

  it('loads and renders the user list', async () => {
    const wrapper = await mountUsersPage()

    expect(getMock).toHaveBeenCalledWith('/admin/users', { params: { page: 1 } })
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })

  it('approves a pending user', async () => {
    patchMock.mockResolvedValue({})
    const wrapper = await mountUsersPage()

    const approveBtn = row(wrapper, 0)
      .findAll('button')
      .find((b: DOMWrapper<Element>) => b.text() === wrapper.vm.$t('admin.users.approve'))!
    await approveBtn.trigger('click')
    await flushPromises()

    expect(patchMock).toHaveBeenCalledWith('/admin/users/1/approve')
    expect(row(wrapper, 0).find('.badge').text()).toBe(wrapper.vm.$t('admin.users.status_active'))
  })

  it('rejects a pending user', async () => {
    patchMock.mockResolvedValue({})
    const wrapper = await mountUsersPage()

    await clickMenuItem(wrapper, 0, wrapper.vm.$t('admin.users.reject'))

    expect(patchMock).toHaveBeenCalledWith('/admin/users/1/reject')
    expect(row(wrapper, 0).find('.badge').text()).toBe(wrapper.vm.$t('admin.users.status_rejected'))
  })

  it('suspends an active user', async () => {
    patchMock.mockResolvedValue({})
    const wrapper = await mountUsersPage()

    await clickMenuItem(wrapper, 1, wrapper.vm.$t('admin.users.suspend'))

    expect(patchMock).toHaveBeenCalledWith('/admin/users/2/suspend')
    expect(row(wrapper, 1).find('.badge').text()).toBe(
      wrapper.vm.$t('admin.users.status_suspended')
    )
  })

  it('shows the main action per status and the rest in the row menu', async () => {
    const wrapper = await mountUsersPage()
    const $t = wrapper.vm.$t

    // Pending: approve is the visible button, reject/edit/history in the menu.
    expect(row(wrapper, 0).find('.action-btn').text()).toBe($t('admin.users.approve'))
    const pendingMenu = await menuLabels(wrapper, 0)
    expect(pendingMenu).toEqual([
      $t('admin.actions.edit'),
      $t('admin.actions.token_history'),
      $t('admin.users.reject'),
    ])
    document.querySelectorAll('.context-menu').forEach((el) => el.remove())

    // Active: edit is the visible button, history/suspend in the menu.
    expect(row(wrapper, 1).find('.action-btn').text()).toBe($t('admin.actions.edit'))
    const activeMenu = await menuLabels(wrapper, 1)
    expect(activeMenu).toEqual([$t('admin.actions.token_history'), $t('admin.users.suspend')])
    expect(activeMenu).not.toContain($t('admin.users.approve'))
  })

  it('shows spendable tokens with bonus and blocked broken out', async () => {
    getMock.mockResolvedValue({
      data: [{ ...baseUsers[1], tokens: 10, bonus_tokens: 20, tokens_blocked: 4 }],
    })
    activeWrapper = await mountSuspended(AdminUsersPage, {
      global: { stubs: { UiVirtualDropdown: true } },
    })
    await flushPromises()

    const cell = activeWrapper.find('.user-table__tokens')
    expect(cell.find('.user-table__token-total').text()).toBe('26')
    expect(cell.find('.user-table__sub').text()).toBe('davon 20 Bonus · 4 blockiert')
  })
})

describe('admin/users.vue — create', () => {
  beforeEach(() => {
    getMock.mockReset()
    postMock.mockReset()
  })

  it('creates a user and prepends it to the list', async () => {
    postMock.mockResolvedValue({
      data: {
        id: 3,
        name: 'Neu',
        email: 'neu@example.com',
        role: 'USER',
        status: 'PENDING',
        created_at: '2026-02-01',
      },
    })
    const wrapper = await mountUsersPage()

    await wrapper.find('.hero-btn').trigger('click')
    const inputs = modal().findAll('.modal-box input')
    await inputs[0]!.setValue('Neu')
    await inputs[1]!.setValue('neu@example.com')
    await inputs[2]!.setValue('password123')
    await modal().find('.modal-box form').trigger('submit')
    await flushPromises()

    expect(postMock).toHaveBeenCalledWith('/admin/users', {
      name: 'Neu',
      email: 'neu@example.com',
      password: 'password123',
      role: 'USER',
      status: 'PENDING',
    })
    expect(wrapper.findAll('tbody tr')).toHaveLength(3)
    expect(modal().find('.modal-box').exists()).toBe(false)
  })

  it('shows an error and keeps the modal open when creation fails', async () => {
    postMock.mockRejectedValue({ data: { message: 'E-Mail bereits vergeben.' } })
    const wrapper = await mountUsersPage()

    await wrapper.find('.hero-btn').trigger('click')
    const inputs = modal().findAll('.modal-box input')
    await inputs[0]!.setValue('Neu')
    await inputs[1]!.setValue('neu@example.com')
    await inputs[2]!.setValue('password123')
    await modal().find('.modal-box form').trigger('submit')
    await flushPromises()

    expect(modal().find('.modal-box').text()).toContain('E-Mail bereits vergeben.')
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
  })
})

describe('admin/users.vue — edit', () => {
  beforeEach(() => {
    getMock.mockReset()
    putMock.mockReset()
  })

  it('prefills the edit form and updates the user in place', async () => {
    putMock.mockResolvedValue({ data: { name: 'Anna Neu' } })
    const wrapper = await mountUsersPage()

    await row(wrapper, 1)
      .findAll('button')
      .find((b: DOMWrapper<Element>) => b.text() === wrapper.vm.$t('admin.actions.edit'))!
      .trigger('click')

    const nameInput = modal().find('.modal-box input[type="text"]')
    expect((nameInput.element as HTMLInputElement).value).toBe('Active Anna')

    await nameInput.setValue('Anna Neu')
    await modal().find('.modal-box form').trigger('submit')
    await flushPromises()

    expect(putMock).toHaveBeenCalledWith('/admin/users/2', {
      name: 'Anna Neu',
      email: 'anna@example.com',
      role: 'MEMBER',
    })
    expect(row(wrapper, 1).text()).toContain('Anna Neu')
  })

  it('only includes the password in the payload when one is entered', async () => {
    putMock.mockResolvedValue({ data: {} })
    const wrapper = await mountUsersPage()

    // For a pending user, edit lives in the row menu.
    await clickMenuItem(wrapper, 0, wrapper.vm.$t('admin.actions.edit'))
    expect(modal().find('.edit-meta').text()).toContain('Registriert am')
    await modal().find('.modal-box form').trigger('submit')
    await flushPromises()

    const payload = putMock.mock.calls[0]![1] as Record<string, unknown>
    expect(payload).not.toHaveProperty('password')
  })
})
