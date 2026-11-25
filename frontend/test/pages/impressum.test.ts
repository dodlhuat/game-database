import { describe, it, expect, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import ImpressumPage from '~/pages/impressum.vue'

// AppFooter loads the sponsors for its link
mockNuxtImport('useSponsors', () => () => ({
  activeSponsors: { value: [] },
  fetchSponsors: vi.fn().mockResolvedValue([]),
}))

describe('/impressum', () => {
  it('names owner, seat, register number, board and contact', async () => {
    const w = await mountSuspended(ImpressumPage)
    const text = w.text()

    expect(text).toContain('AUA-Alle.Unsere.Abenteuer')
    expect(text).toContain('Michael Gleiß Gasse 3, 3107 Viehofen')
    expect(text).toContain('1545800206')
    expect(text).toContain('Alexander Hamberger-Windegger, Obmann')
    expect(w.find('a[href="mailto:alle.unsere.abenteuer@gmail.com"]').exists()).toBe(true)
    // no translation key left showing
    expect(text).not.toMatch(/imprint\.[a-z_.]+/)
  })
})
