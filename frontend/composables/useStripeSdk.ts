import { loadStripe, type Stripe } from '@stripe/stripe-js'

/**
 * Lazily loads Stripe.js exactly once per page load, regardless of how many
 * times the checkout modal opens. Publishable key comes from public runtime
 * config — test vs. live is just which publishable key is configured there
 * (see nuxt.config.ts / NUXT_PUBLIC_STRIPE_PUBLISHABLE_KEY).
 */
let stripePromise: Promise<Stripe> | null = null

export function useStripeSdk() {
  const config = useRuntimeConfig()

  function load(): Promise<Stripe> {
    if (stripePromise) return stripePromise

    const publishableKey = config.public.stripePublishableKey as string
    if (!publishableKey) {
      return Promise.reject(new Error('Stripe ist nicht konfiguriert (fehlender Publishable Key).'))
    }

    stripePromise = loadStripe(publishableKey)
      .then((stripe) => {
        if (!stripe) throw new Error('Stripe konnte nicht geladen werden.')
        return stripe
      })
      .catch((err: unknown) => {
        stripePromise = null // allow a later retry instead of caching the failure forever
        throw err
      })

    return stripePromise
  }

  return { load }
}
