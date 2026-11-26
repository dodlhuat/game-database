import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, type VueWrapper } from '@vue/test-utils'
import SponsorsPage from '~/pages/sponsors.vue'
import AboutPage from '~/pages/about.vue'
import EmailVerifiedPage from '~/pages/email-verified.vue'

const fetchSponsorsMock = vi.hoisted(() => vi.fn())
// AppFooter uses the same composable for its sponsors link
const activeSponsors = vi.hoisted(() => ({ value: [] as unknown[] }))
mockNuxtImport('useSponsors', () => () => ({ fetchSponsors: fetchSponsorsMock, activeSponsors }))

let wrapper: VueWrapper | null = null

beforeEach(() => {
  fetchSponsorsMock.mockReset().mockResolvedValue([])
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
})

describe('/sponsors', () => {
  const sponsor = (id: number, name: string, url: string | null, logo: string | null) => ({
    id,
    name,
    url,
    logo_url: logo,
    logo_on_light: false,
    is_active: true,
  })

  it('shows logos on the wall and the rest by name, freshly loaded', async () => {
    fetchSponsorsMock.mockResolvedValue([
      sponsor(1, 'Huber', 'https://huber.at', '/storage/sponsors/huber.png'),
      sponsor(2, 'Maier', null, null),
      sponsor(3, 'Gruber', 'https://gruber.at', null),
    ])
    wrapper = await mountSuspended(SponsorsPage)
    await flushPromises()

    expect(fetchSponsorsMock).toHaveBeenCalledWith(true)
    expect(wrapper.findAll('.logo-wall img')).toHaveLength(1)
    expect(wrapper.find('.credits').text()).toContain('Maier')
    expect(wrapper.find('.credits').text()).toContain('Gruber')
  })

  it('opens sponsor sites in a new tab, marked as sponsored', async () => {
    fetchSponsorsMock.mockResolvedValue([
      sponsor(1, 'Huber', 'https://huber.at', '/storage/sponsors/huber.png'),
      sponsor(2, 'Maier', null, null),
    ])
    wrapper = await mountSuspended(SponsorsPage)
    await flushPromises()

    const links = wrapper.findAll('a[href^="https://"]')
    expect(links).toHaveLength(1)
    expect(links[0]!.attributes('target')).toBe('_blank')
    expect(links[0]!.attributes('rel')).toBe('noopener noreferrer sponsored')
    // no link for a sponsor without a website
    expect(wrapper.findAll('a').some((a) => a.text().includes('Maier'))).toBe(false)
  })

  it('shows the empty state when there are none or loading fails', async () => {
    fetchSponsorsMock.mockRejectedValue({ status: 500 })
    wrapper = await mountSuspended(SponsorsPage)
    await flushPromises()

    expect(wrapper.find('.sp-state--empty').exists()).toBe(true)
  })
})

describe('/about', () => {
  it('has a text for every fact and activity', async () => {
    wrapper = await mountSuspended(AboutPage)

    // a missing translation would show its key, e.g. "about.facts.seat"
    expect(wrapper.text()).not.toMatch(/about\.[a-z_.]+/)
    expect(wrapper.findAll('.about-facts__row')).toHaveLength(4)
  })

  it('shows the team photo with a description and the caption', async () => {
    wrapper = await mountSuspended(AboutPage)

    const img = wrapper.find('.team-photo img')
    expect(img.attributes('src')).toBe('/images/about/team-640.webp')
    expect(img.attributes('alt')).toContain('an einem Holztisch')
    // fixed size, so nothing jumps while it loads
    expect(img.attributes('width')).toBe('640')
    expect(img.attributes('height')).toBe('800')
    expect(wrapper.find('.team-photo figcaption').text()).toBe('Das Team hinter AUA')
  })
})

describe('/email-verified', () => {
  it.each([
    ['?success=1', 'Einloggen', false],
    ['?already=1', 'Einloggen', false],
    ['?error=invalid_link', 'Einloggen', true],
    ['', 'Einloggen', true],
  ])('%s shows the matching state', async (query, button, isError) => {
    wrapper = await mountSuspended(EmailVerifiedPage, { route: `/email-verified${query}` })

    expect(wrapper.find('.auth-card__icon--error').exists()).toBe(isError)
    expect(wrapper.find('button').text()).toBe(button)
  })
})
