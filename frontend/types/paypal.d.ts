// Ambient declaration for the PayPal JS SDK (Smart Buttons), loaded at
// runtime via a dynamically injected <script> tag — see
// composables/usePayPalSdk.ts. Only the surface this app actually uses.

export interface PayPalButtonsInstance {
  render: (container: HTMLElement) => Promise<void>
  close?: () => Promise<void>
}

export interface PayPalButtonsOptions {
  style?: {
    layout?: 'vertical' | 'horizontal'
    color?: 'gold' | 'blue' | 'silver' | 'white' | 'black'
    shape?: 'rect' | 'pill'
    label?: 'paypal' | 'checkout' | 'pay' | 'buynow'
    height?: number
    tagline?: boolean
  }
  createOrder: () => Promise<string>
  onApprove: (data: { orderID: string; payerID?: string }) => Promise<void> | void
  onError?: (err: unknown) => void
  onCancel?: () => void
}

export interface PayPalNamespace {
  Buttons: (options: PayPalButtonsOptions) => PayPalButtonsInstance
}

declare global {
  interface Window {
    paypal?: PayPalNamespace
  }
}

export {}
