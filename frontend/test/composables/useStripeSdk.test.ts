import { describe, it, expect, beforeEach, vi } from 'vitest'
import { defineComponent } from 'vue'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import { useRuntimeConfig } from '#imports'
import { useStripeSdk } from '~/composables/useStripeSdk'

const loadStripeMock = vi.fn()
vi.mock('@stripe/stripe-js', () => ({
  loadStripe: (...args: unknown[]) => loadStripeMock(...args),
}))

// Same reasoning as the former usePayPalSdk test: mutate the real runtime
// config's `public` object rather than mocking useRuntimeConfig globally,
// which would crash Nuxt's own internal bootstrap.
const Host = defineComponent({
  setup(_, { expose }) {
    expose(useStripeSdk())

    return () => null
  },
})

async function mountHost() {
  const wrapper = await mountSuspended(Host)

  return wrapper.vm as unknown as { load: () => Promise<unknown> }
}

describe('useStripeSdk', () => {
  beforeEach(() => {
    loadStripeMock.mockReset()
    const config = useRuntimeConfig()
    config.public.stripePublishableKey = 'pk_test_123'
  })

  it('rejects when no publishable key is configured, without calling loadStripe', async () => {
    useRuntimeConfig().public.stripePublishableKey = ''
    const { load } = await mountHost()

    await expect(load()).rejects.toThrow('Stripe ist nicht konfiguriert')
    expect(loadStripeMock).not.toHaveBeenCalled()
  })

  // The module caches its in-flight/resolved Stripe promise across calls (by
  // design — see useStripeSdk.ts), so the cache/retry lifecycle is exercised
  // as one continuous scenario rather than isolated tests.
  it('loads Stripe.js once, caches it, and retries after a failure', async () => {
    loadStripeMock.mockResolvedValueOnce(null)
    const { load } = await mountHost()

    await expect(load()).rejects.toThrow('Stripe konnte nicht geladen werden.')

    loadStripeMock.mockRejectedValueOnce(new Error('network down'))
    await expect(load()).rejects.toThrow('network down')

    const stripeInstance = { elements: vi.fn() }
    loadStripeMock.mockResolvedValueOnce(stripeInstance)

    const first = await load()
    const second = await load()

    expect(first).toBe(stripeInstance)
    expect(second).toBe(stripeInstance)
    expect(loadStripeMock).toHaveBeenCalledTimes(3)
    expect(loadStripeMock).toHaveBeenCalledWith('pk_test_123')
  })
})
