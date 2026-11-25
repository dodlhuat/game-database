import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import AdminPackagesPage from '~/pages/admin/packages.vue'

const fetchAdminPackagesMock = vi.hoisted(() => vi.fn())
const deletePackageMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useAdmin', () => {
  return () => ({
    fetchAdminPackages: fetchAdminPackagesMock,
    createPackage: vi.fn(),
    updatePackage: vi.fn(),
    deletePackage: deletePackageMock,
    fetchAdminGames: vi
      .fn()
      .mockResolvedValue({ data: [], meta: { current_page: 1, last_page: 1, total: 0 } }),
  })
})

const confirmMock = vi.hoisted(() => vi.fn())
const notifyMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useDialogs', () => {
  return () => ({ confirmDialog: confirmMock, notify: notifyMock })
})

const pkg = {
  id: 7,
  name: 'Familienabend',
  slug: 'familienabend',
  description: null,
  type: 'CURATED',
  games_count: 3,
  games: [],
  is_active: true,
}

let wrapper: Awaited<ReturnType<typeof mountSuspended>> | null = null

async function clickDelete() {
  wrapper = await mountSuspended(AdminPackagesPage)
  await flushPromises()
  await wrapper.find('.action-btn--danger').trigger('click')
  await flushPromises()
}

describe('admin/packages.vue — deleting', () => {
  beforeEach(() => {
    fetchAdminPackagesMock.mockReset().mockResolvedValue({ data: [pkg] })
    deletePackageMock.mockReset()
    confirmMock.mockReset()
    notifyMock.mockReset()
  })

  afterEach(() => {
    wrapper?.unmount()
    wrapper = null
  })

  it('asks first and does nothing when cancelled', async () => {
    confirmMock.mockResolvedValue(false)
    await clickDelete()

    expect(confirmMock).toHaveBeenCalledWith(expect.objectContaining({ danger: true }))
    expect(deletePackageMock).not.toHaveBeenCalled()
  })

  it('shows why a borrowed package cannot be deleted', async () => {
    confirmMock.mockResolvedValue(true)
    deletePackageMock.mockRejectedValue({ message: 'Dieses Paket wurde schon ausgeliehen …' })
    await clickDelete()

    expect(deletePackageMock).toHaveBeenCalledWith(7)
    expect(notifyMock).toHaveBeenCalledWith('Dieses Paket wurde schon ausgeliehen …', 'error')
    expect(wrapper!.text()).toContain('Familienabend')
  })

  it('confirms a successful delete', async () => {
    confirmMock.mockResolvedValue(true)
    deletePackageMock.mockResolvedValue({})
    await clickDelete()

    expect(notifyMock).toHaveBeenCalledWith('„Familienabend“ wurde gelöscht.')
  })
})
