export default defineNuxtPlugin(() => {
  const auth = useAuthStore()
  auth.loadFromStorage()

  // The stored user is only a snapshot from the last login — balances,
  // role and membership change server-side (purchases, admin edits, expiry).
  // Refresh it once per app start; the cached copy renders meanwhile. A 401
  // is handled by useApi (logs out), anything else keeps the cached copy.
  if (auth.token) {
    useAuth()
      .fetchMe()
      .catch(() => {})
  }
})
