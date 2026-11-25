import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, type VueWrapper } from '@vue/test-utils'
import NewslettersPage from '~/pages/admin/newsletters.vue'
import DamageReportsPage from '~/pages/admin/damage-reports.vue'
import PackageLoansPage from '~/pages/admin/package-loans.vue'

const adm = vi.hoisted(() => ({
  fetchNewsletters: vi.fn(),
  sendNewsletter: vi.fn(),
  fetchDamageReports: vi.fn(),
}))
mockNuxtImport('useAdmin', () => () => adm)

const getMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useApi', () => {
  return () => ({ get: getMock, post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() })
})

mockNuxtImport('usePickupLocations', () => () => ({
  fetchActiveLocations: vi.fn().mockResolvedValue([]),
}))

const confirmMock = vi.hoisted(() => vi.fn())
const notifyMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useDialogs', () => {
  return () => ({ confirmDialog: confirmMock, notify: notifyMock })
})

// A plain textarea in place of the rich editor
const stubs = {
  UiRichEditor: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template:
      '<textarea class="stub-editor" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
  },
  AdminPickupLocationSelect: true,
}

let wrapper: VueWrapper | null = null

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

describe('admin/newsletters.vue', () => {
  async function compose() {
    adm.fetchNewsletters.mockResolvedValue({ data: [] })
    wrapper = await mountSuspended(NewslettersPage, { global: { stubs } })
    await flushPromises()
    await wrapper.find('input').setValue('Spieleabend im Dezember')
    await wrapper.find('.stub-editor').setValue('<p>Wir sehen uns!</p>')
  }

  async function send() {
    await wrapper!.find('.hero-btn').trigger('click')
    await flushPromises()
  }

  it('asks before sending to everyone, and sends nothing when declined', async () => {
    confirmMock.mockResolvedValue(false)
    await compose()

    await send()

    expect(confirmMock.mock.calls[0]![0].message).toContain('Spieleabend im Dezember')
    expect(adm.sendNewsletter).not.toHaveBeenCalled()
  })

  it('sends after confirming, clears the form and confirms it', async () => {
    confirmMock.mockResolvedValue(true)
    adm.sendNewsletter.mockResolvedValue({})
    await compose()

    await send()

    expect(adm.sendNewsletter).toHaveBeenCalledWith(
      'Spieleabend im Dezember',
      '<p>Wir sehen uns!</p>'
    )
    expect(wrapper!.find('.success-banner').exists()).toBe(true)
    expect((wrapper!.find('input').element as HTMLInputElement).value).toBe('')
  })

  it('keeps the text when sending fails', async () => {
    confirmMock.mockResolvedValue(true)
    adm.sendNewsletter.mockRejectedValue({ message: 'Mailserver nicht erreichbar.' })
    await compose()

    await send()

    expect(notifyMock).toHaveBeenCalledWith('Mailserver nicht erreichbar.', 'error')
    expect((wrapper!.find('input').element as HTMLInputElement).value).toBe(
      'Spieleabend im Dezember'
    )
  })

  it('cannot be sent without subject and text', async () => {
    adm.fetchNewsletters.mockResolvedValue({ data: [] })
    wrapper = await mountSuspended(NewslettersPage, { global: { stubs } })
    await flushPromises()

    expect(wrapper.find('.hero-btn').attributes('disabled')).toBeDefined()
  })
})

describe('admin/damage-reports.vue', () => {
  it('lists reports with member, game and a photo link that opens separately', async () => {
    adm.fetchDamageReports.mockResolvedValue({
      data: [
        {
          id: 1,
          description: 'Karte eingerissen',
          photo_url: 'https://site.example/storage/damage-photos/a.jpg',
          user: { name: 'Mia' },
          loan: { copy: { game: { title: 'Azul' } } },
          created_at: '2026-12-01T10:00:00Z',
        },
        {
          id: 2,
          description: 'Ecke gestaucht',
          photo_url: null,
          user: { name: 'Tom' },
          loan: null,
          created_at: '2026-12-02T10:00:00Z',
        },
      ],
      meta: { current_page: 1, last_page: 1, total: 2 },
    })
    wrapper = await mountSuspended(DamageReportsPage)
    await flushPromises()

    const rows = wrapper.findAll('tbody tr')
    expect(rows[0]!.text()).toContain('Azul')
    expect(rows[0]!.find('.photo-link').attributes('target')).toBe('_blank')
    expect(rows[1]!.find('.photo-link').exists()).toBe(false)
  })
})

describe('admin/package-loans.vue', () => {
  it('filters by tab from the first page', async () => {
    getMock.mockResolvedValue({ data: [], meta: { current_page: 1, last_page: 1, total: 0 } })
    wrapper = await mountSuspended(PackageLoansPage, { global: { stubs } })
    await flushPromises()
    expect(getMock).toHaveBeenLastCalledWith('/admin/package-loans', {
      params: { page: 1, status: undefined },
    })

    await wrapper
      .findAll('.status-tabs__btn')
      .find((t) => t.text() === 'Überfällig')!
      .trigger('click')
    await flushPromises()

    expect(getMock).toHaveBeenLastCalledWith('/admin/package-loans', {
      params: { page: 1, status: 'OVERDUE' },
    })
  })

  it('marks overdue packages', async () => {
    getMock.mockResolvedValue({
      data: [
        {
          id: 1,
          package: { id: 1, name: 'Familienabend', slug: 'familienabend' },
          user: { id: 2, name: 'Mia', email: 'mia@example.at' },
          start_date: '2026-11-01',
          due_date: '2026-11-29',
          returned_at: null,
          status: 'ACTIVE',
          is_overdue: true,
          pickup_location: null,
        },
      ],
      meta: { current_page: 1, last_page: 1, total: 1 },
    })
    wrapper = await mountSuspended(PackageLoansPage, { global: { stubs } })
    await flushPromises()

    expect(wrapper.find('tbody .text-warn').exists()).toBe(true)
  })
})
