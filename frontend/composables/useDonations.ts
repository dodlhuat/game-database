export function useDonations() {
  const api = useApi()

  const submitDonation = (formData: FormData) =>
    api.post<{ message: string }>('/donations', formData)

  return { submitDonation }
}
