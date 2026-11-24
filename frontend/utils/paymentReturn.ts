const KEY = 'payment_return_to'

/** Where the member started a payment, so the return page can lead back there. */
export function rememberPaymentReturn(path: string) {
  try {
    sessionStorage.setItem(KEY, path)
  } catch {
    // blocked storage — the return page falls back to a sensible default
  }
}

/** The remembered start page, read once. Only same-site paths are accepted. */
export function takePaymentReturn(fallback: string): string {
  try {
    const path = sessionStorage.getItem(KEY)
    sessionStorage.removeItem(KEY)
    if (path && path.startsWith('/') && !path.startsWith('//')) return path
  } catch {
    // ignore
  }
  return fallback
}
