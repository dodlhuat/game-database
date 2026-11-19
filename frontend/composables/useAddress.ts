export type AddressCheckStatus = 'verified' | 'not_found' | 'unavailable'

export function useAddress() {
  const api = useApi()

  // Best-effort check — the caller must never block on a failed/unverified
  // result, only use it as an inline hint (see components/ui/AddressFields.vue).
  const validateAddress = (street: string, postalCode: string, city: string) =>
    api.post<{ verified: boolean; status: AddressCheckStatus }>('/address/validate', {
      street,
      postal_code: postalCode,
      city,
    })

  return { validateAddress }
}
