import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import UnsubscribePage from '~/pages/newsletter/abmelden.vue'

const postMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useApi', () => {
  return () => ({ get: vi.fn(), post: postMock, put: vi.fn(), patch: vi.fn(), delete: vi.fn() })
})

async function open(query: string) {
  const wrapper = await mountSuspended(UnsubscribePage, { route: `/newsletter/abmelden${query}` })
  await flushPromises()
  return wrapper
}

describe('newsletter/abmelden.vue', () => {
  // braces matter: a hook that returns a function gets it called as teardown,
  // and mockReset() returns the mock itself
  beforeEach(() => {
    postMock.mockReset()
  })

  it('asks first — opening the link alone unsubscribes nobody', async () => {
    const wrapper = await open('?user=5&signature=abc')

    expect(wrapper.text()).toContain('Vom Newsletter abmelden?')
    expect(postMock).not.toHaveBeenCalled()
  })

  it('posts the signature on confirm and says it worked', async () => {
    postMock.mockResolvedValue({})
    const wrapper = await open('?user=5&signature=abc')

    await wrapper.find('button').trigger('click')
    await flushPromises()

    expect(postMock).toHaveBeenCalledWith('/newsletter/unsubscribe/5', undefined, {
      params: { signature: 'abc' },
    })
    expect(wrapper.text()).toContain('Abgemeldet')
  })

  it('points to the account settings when the link is broken', async () => {
    postMock.mockRejectedValue({ status: 403, reason: 'invalid_link' })
    const wrapper = await open('?user=5&signature=abc')

    await wrapper.find('button').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Link ungültig')
  })

  it('treats a link without signature as broken right away', async () => {
    const wrapper = await open('?user=5')

    expect(wrapper.text()).toContain('Link ungültig')
  })

  it('keeps the button on a network error so the member can retry', async () => {
    postMock.mockRejectedValue({ status: 500, message: 'Server nicht erreichbar.' })
    const wrapper = await open('?user=5&signature=abc')

    await wrapper.find('button').trigger('click')
    await flushPromises()

    expect(wrapper.find('[role="alert"]').text()).toBe('Server nicht erreichbar.')
    expect(wrapper.find('button').exists()).toBe(true)
  })
})
