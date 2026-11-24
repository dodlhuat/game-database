import { ref } from 'vue'

export interface RenewalCheckout {
  type: 'RENEWAL'
  price_cents: number
  currency: string
}

/**
 * Renewal flow shared by dashboard, account and upgrade page: loads the
 * price and hands it to <PaymentCheckoutModal>, which sends the member to
 * Mollie. The outcome is shown on /zahlung.
 */
export function useMembershipRenewal() {
  const api = useApi()
  const { t } = useI18n()

  const renewCheckout = ref<RenewalCheckout | null>(null)
  const renewError = ref('')
  const renewLoading = ref(false)

  async function renew() {
    if (renewLoading.value) return
    renewLoading.value = true
    renewError.value = ''
    try {
      const pricing = await api.get<{ fee_cents: number; currency: string }>('/membership/pricing')
      renewCheckout.value = {
        type: 'RENEWAL',
        price_cents: pricing.fee_cents,
        currency: pricing.currency,
      }
    } catch {
      renewError.value = t('common.error.generic')
    } finally {
      renewLoading.value = false
    }
  }

  return { renewCheckout, renewError, renewLoading, renew }
}
