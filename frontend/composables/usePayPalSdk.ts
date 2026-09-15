/**
 * Lazily loads the PayPal JS SDK (Smart Buttons) exactly once per page load,
 * regardless of how many PayPalButtons components mount. Client-ID/currency
 * come from public runtime config — sandbox vs. live is just which client-id
 * is configured there (see nuxt.config.ts / NUXT_PUBLIC_PAYPAL_CLIENT_ID).
 */
let sdkPromise: Promise<void> | null = null

export function usePayPalSdk() {
  const config = useRuntimeConfig()

  function load(): Promise<void> {
    if (window.paypal) return Promise.resolve()

    const clientId = config.public.paypalClientId as string
    if (!clientId) {
      return Promise.reject(new Error('PayPal ist nicht konfiguriert (fehlende Client-ID).'))
    }

    if (sdkPromise) return sdkPromise

    sdkPromise = new Promise<void>((resolve, reject) => {
      const currency = (config.public.paypalCurrency as string) || 'EUR'
      const script = document.createElement('script')
      script.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(clientId)}&currency=${encodeURIComponent(currency)}&intent=capture`
      script.onload = () => resolve()
      script.onerror = () => {
        sdkPromise = null // allow a later retry instead of caching the failure forever
        reject(new Error('PayPal SDK konnte nicht geladen werden.'))
      }
      document.head.appendChild(script)
    })

    return sdkPromise
  }

  return { load }
}
