import { describe, it, expect, beforeEach, vi, afterAll } from 'vitest'
import { defineComponent } from 'vue'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import { useRuntimeConfig } from '#imports'
import { usePayPalSdk } from '~/composables/usePayPalSdk'

// NOTE: mockNuxtImport('useRuntimeConfig', ...) is deliberately NOT used
// here — it replaces the composable everywhere, including inside Nuxt's own
// internal bootstrap (router setup reads real runtime config), which
// crashes the "nuxt" test environment entirely. Mutating the real config's
// `public` object at runtime is what runtimeConfig.public is designed for
// anyway, so this is the correct override mechanism, not a workaround.

// @nuxt/test-utils' "nuxt" test environment sets up the Nuxt app/router
// context via a component mount — calling a composable with no mounted
// component crashes its internal setup. This trivial host component is the
// documented way around that for composable-only tests.
const Host = defineComponent({
  setup(_, { expose }) {
    expose(usePayPalSdk())

    return () => null
  },
})

async function mountHost() {
  const wrapper = await mountSuspended(Host)

  return wrapper.vm as unknown as { load: () => Promise<void> }
}

// happy-dom actually attempts to "load" appended <script src> elements and
// fires its own load/error events asynchronously — which races with this
// test manually driving onload/onerror. Intercepting appendChild keeps the
// script element out of the real DOM (never connected, so happy-dom never
// touches it) while still letting the composable's own logic run normally.
let appendedNodes: Node[] = []
const appendChildSpy = vi.spyOn(document.head, 'appendChild').mockImplementation((node: Node) => {
  appendedNodes.push(node)
  return node
})

afterAll(() => appendChildSpy.mockRestore())

function paypalScripts(): HTMLScriptElement[] {
  return appendedNodes.filter(
    (n): n is HTMLScriptElement =>
      n instanceof HTMLScriptElement && n.src.includes('paypal.com/sdk/js')
  )
}

describe('usePayPalSdk', () => {
  beforeEach(() => {
    appendedNodes = []
    const config = useRuntimeConfig()
    config.public.paypalClientId = 'test-client-id'
    config.public.paypalCurrency = 'EUR'
    delete (window as unknown as { paypal?: unknown }).paypal
  })

  it('resolves immediately without injecting a script if window.paypal already exists', async () => {
    ;(window as unknown as { paypal: object }).paypal = {}
    const { load } = await mountHost()

    await expect(load()).resolves.toBeUndefined()
    expect(paypalScripts()).toHaveLength(0)
  })

  it('rejects when no client id is configured, without touching the SDK cache', async () => {
    useRuntimeConfig().public.paypalClientId = ''
    const { load } = await mountHost()

    await expect(load()).rejects.toThrow('PayPal ist nicht konfiguriert')
    expect(paypalScripts()).toHaveLength(0)
  })

  // The module caches its in-flight/resolved SDK promise across calls (by
  // design — see usePayPalSdk.ts), so the script-injection lifecycle is
  // exercised as one continuous scenario rather than isolated tests.
  it('injects the SDK script once, dedupes concurrent calls, and retries after a failure', async () => {
    const { load } = await mountHost()

    const first = load()
    const second = load()
    expect(paypalScripts()).toHaveLength(1)
    const script = paypalScripts()[0]
    expect(script).toBeDefined()
    expect(script!.src).toContain('client-id=test-client-id')
    expect(script!.src).toContain('currency=EUR')

    script!.onerror?.(new Event('error'))
    await expect(first).rejects.toThrow('PayPal SDK konnte nicht geladen werden.')
    await expect(second).rejects.toThrow('PayPal SDK konnte nicht geladen werden.')

    const retry = load()
    expect(paypalScripts()).toHaveLength(2)
    const retryScript = paypalScripts()[1]
    expect(retryScript).toBeDefined()
    retryScript!.onload?.(new Event('load'))
    await expect(retry).resolves.toBeUndefined()
  })
})
