import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, type VueWrapper } from '@vue/test-utils'
import PackagesIndexPage from '~/pages/packages/index.vue'

const fetchPackagesMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useGames', () => () => ({ fetchPackages: fetchPackagesMock }))

const pkg = (id: number, name: string, extra: Record<string, unknown> = {}) => ({
  id,
  name,
  slug: name.toLowerCase(),
  description: null,
  type: 'CURATED',
  games_count: 3,
  is_available: true,
  ...extra,
})

let wrapper: VueWrapper | null = null

async function mountPage() {
  wrapper = await mountSuspended(PackagesIndexPage)
  await flushPromises()
  return wrapper
}

beforeEach(() => {
  clearNuxtData('packages')
  fetchPackagesMock.mockReset()
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
})

describe('/packages', () => {
  it('lists the packages', async () => {
    fetchPackagesMock.mockResolvedValue({ data: [pkg(1, 'Familienabend'), pkg(2, 'Strategie')] })
    await mountPage()

    expect(wrapper!.findAll('.package-card')).toHaveLength(2)
    expect(wrapper!.text()).toContain('Familienabend')
  })

  it('says when there are no packages', async () => {
    fetchPackagesMock.mockResolvedValue({ data: [] })
    await mountPage()

    expect(wrapper!.find('.catalog__empty-title').exists()).toBe(true)
    expect(wrapper!.find('[role="alert"]').exists()).toBe(false)
  })

  it('tells a failed load apart from an empty list and retries', async () => {
    fetchPackagesMock.mockRejectedValueOnce({ message: 'down' })
    await mountPage()

    const alert = wrapper!.find('[role="alert"]')
    expect(alert.text()).toContain('konnten nicht geladen werden')

    fetchPackagesMock.mockResolvedValue({ data: [pkg(1, 'Familienabend')] })
    await alert.find('button').trigger('click')
    await flushPromises()

    expect(wrapper!.findAll('.package-card')).toHaveLength(1)
  })
})
