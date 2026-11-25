import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, type VueWrapper } from '@vue/test-utils'
import TermsPage from '~/pages/terms.vue'
import PrivacyPage from '~/pages/privacy.vue'
import CookiesPage from '~/pages/cookies.vue'
import LegalDocument from '~/components/LegalDocument.vue'

const getMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useApi', () => {
  return () => ({ get: getMock, post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() })
})

const DOC = `NUTZUNGSBEDINGUNGEN
§1 Geltungsbereich
Diese Bedingungen gelten für alle Ausleihen.
§2 Rückgabe
Spiele kommen dorthin zurück, wo sie abgeholt wurden.
Zuletzt aktualisiert: Dezember 2026`

const pages = [
  { name: 'terms', page: TermsPage, endpoint: '/terms', unavailable: 'nicht verfügbar' },
  { name: 'privacy', page: PrivacyPage, endpoint: '/privacy', unavailable: 'in Kürze verfügbar' },
  { name: 'cookies', page: CookiesPage, endpoint: '/cookies', unavailable: 'in Kürze verfügbar' },
]

let wrapper: VueWrapper | null = null

beforeEach(() => {
  getMock.mockReset()
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
})

describe.each(pages)('/$name', ({ page, endpoint, unavailable }) => {
  it('shows the current version with its date and text', async () => {
    getMock.mockResolvedValue({
      version: '1.3',
      content: DOC,
      published_at: '2026-11-25T00:00:00Z',
    })
    wrapper = await mountSuspended(page)
    await flushPromises()

    expect(getMock).toHaveBeenCalledWith(endpoint)
    expect(wrapper.find('.legal-meta__version').text()).toBe('v1.3')
    expect(wrapper.find('.legal-meta__date').text()).toBe('25. November 2026')
    expect(wrapper.findAll('.legal-doc__h2')).toHaveLength(2)
    expect(wrapper.text()).toContain('wo sie abgeholt wurden')
  })

  it('says the document is not available instead of showing nothing', async () => {
    getMock.mockRejectedValue({ status: 500 })
    wrapper = await mountSuspended(page)
    await flushPromises()

    expect(wrapper.find('.legal-empty').text()).toContain(unavailable)
    expect(wrapper.find('.legal-doc').exists()).toBe(false)
  })
})

describe('LegalDocument', () => {
  const sections = (n: number) =>
    Array.from({ length: n }, (_, i) => `§${i + 1} Abschnitt ${i + 1}\nText ${i + 1}.`).join('\n')

  it('adds a table of contents from four sections on, linking to each', async () => {
    const short = await mountSuspended(LegalDocument, { props: { content: sections(3) } })
    expect(short.find('.legal-doc__toc').exists()).toBe(false)

    const long = await mountSuspended(LegalDocument, { props: { content: sections(4) } })
    const links = long.findAll('.legal-doc__toc-link')
    expect(links).toHaveLength(4)
    const target = links[2]!.attributes('href')!.slice(1)
    expect(long.find(`h2[id="${target}"]`).text()).toContain('Abschnitt 3')
  })
})
