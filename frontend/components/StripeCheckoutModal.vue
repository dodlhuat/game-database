<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="item" class="modal-overlay" @click.self="close">
        <div class="dialog">
          <div class="dialog__header">
            <h3 class="dialog__title">{{ item.title }} &middot; {{ formattedPrice }}</h3>
            <button class="dialog__close" aria-label="Schließen" @click="close">
              <svg class="icon-svg" aria-hidden="true">
                <use href="/svg-icons/icons.svg#close" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="onSubmit">
            <div class="dialog__body">
              <div v-if="preparing" class="stripe-form__spinner"><div class="spinner" /></div>
              <div v-show="!preparing" ref="elementContainer" class="stripe-form__element" />
              <p v-if="formError" class="form-error">{{ formError }}</p>
            </div>

            <div class="dialog__actions">
              <button
                type="submit"
                class="button button-primary stripe-form__submit"
                :disabled="submitting || preparing"
              >
                {{ submitting ? 'Wird verarbeitet …' : `${formattedPrice} bezahlen` }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { Stripe, StripeElements, StripePaymentElement } from '@stripe/stripe-js'

interface TokenPackage {
  amount: number
  price_cents: number
  currency: string
}

interface MembershipCheckout {
  type: 'MEMBER' | 'SUPPORTER' | 'RENEWAL'
  price_cents: number
  currency: string
  address?: { street: string; postal_code: string; city: string }
}

const props = withDefaults(
  defineProps<{ pkg?: TokenPackage | null; membership?: MembershipCheckout | null }>(),
  { pkg: null, membership: null }
)

interface CheckoutItem {
  title: string
  price_cents: number
  currency: string
  checkoutPath: string
  checkoutBody: Record<string, unknown>
  confirmPath: (paymentIntentId: string) => string
}

const membershipTitles = {
  MEMBER: 'Vollmitgliedschaft',
  SUPPORTER: 'Außerordentliche Mitgliedschaft',
  RENEWAL: 'Mitgliedschaft verlängern',
}

const item = computed<CheckoutItem | null>(() => {
  if (props.pkg) {
    return {
      title: `${props.pkg.amount} Token`,
      price_cents: props.pkg.price_cents,
      currency: props.pkg.currency,
      checkoutPath: '/tokens/checkout',
      checkoutBody: { amount: props.pkg.amount },
      confirmPath: (id) => `/tokens/confirm/${id}`,
    }
  }
  if (props.membership) {
    return {
      title: membershipTitles[props.membership.type],
      price_cents: props.membership.price_cents,
      currency: props.membership.currency,
      checkoutPath: '/membership/checkout',
      checkoutBody: { type: props.membership.type, ...props.membership.address },
      confirmPath: (id) => `/membership/confirm/${id}`,
    }
  }
  return null
})

const emit = defineEmits<{
  success: [payload: { message: string; user: unknown }]
  error: [message: string]
  close: []
}>()

const api = useApi()
const { load } = useStripeSdk()

const elementContainer = ref<HTMLElement | null>(null)
const preparing = ref(true)
const submitting = ref(false)
const formError = ref('')

let stripe: Stripe | null = null
let elements: StripeElements | null = null
let paymentElement: StripePaymentElement | null = null
let unmountedElement: StripePaymentElement | null = null

// Mounting the Payment Element needs both the container's DOM ref and the
// element itself — whichever becomes available last (order isn't guaranteed
// once preparing flips to false and the template re-renders) triggers it.
watch(elementContainer, (container) => {
  if (container && unmountedElement) {
    unmountedElement.mount(container)
    unmountedElement = null
  }
})

const formattedPrice = computed(() =>
  item.value
    ? new Intl.NumberFormat('de-AT', { style: 'currency', currency: item.value.currency }).format(
        item.value.price_cents / 100
      )
    : ''
)

function close() {
  emit('close')
}

function teardown() {
  paymentElement?.unmount()
  paymentElement = null
  unmountedElement = null
  elements = null
  formError.value = ''
}

async function init(current: CheckoutItem) {
  preparing.value = true
  teardown()
  try {
    const [stripeSdk, data] = await Promise.all([
      load(),
      api.post<{ clientSecret: string }>(current.checkoutPath, current.checkoutBody),
    ])

    stripe = stripeSdk
    elements = stripe.elements({ clientSecret: data.clientSecret })
    paymentElement = elements.create('payment')

    if (elementContainer.value) {
      paymentElement.mount(elementContainer.value)
    } else {
      // Ref not bound yet — the watcher above mounts it once it is.
      unmountedElement = paymentElement
    }
  } catch (err: unknown) {
    const e = err as { message?: string }
    emit('error', e.message ?? 'Zahlung konnte nicht vorbereitet werden.')
    close()
  } finally {
    preparing.value = false
  }
}

async function onSubmit() {
  if (!stripe || !elements) return
  submitting.value = true
  formError.value = ''
  try {
    const { error, paymentIntent } = await stripe.confirmPayment({
      elements,
      redirect: 'if_required',
    })

    if (error) {
      formError.value = error.message ?? 'Zahlung wurde abgelehnt.'
      return
    }
    if (paymentIntent?.status !== 'succeeded') {
      formError.value = 'Zahlung konnte nicht abgeschlossen werden.'
      return
    }

    const result = await api.post<{ message: string; user: unknown }>(
      item.value!.confirmPath(paymentIntent.id)
    )
    emit('success', result)
  } catch (err: unknown) {
    const e = err as { message?: string }
    formError.value = e.message ?? 'Zahlung konnte nicht abgeschlossen werden.'
  } finally {
    submitting.value = false
  }
}

watch(
  item,
  (current) => {
    if (current) init(current)
    else teardown()
  },
  { immediate: true }
)
</script>

<style lang="scss" scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 200;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}
.dialog {
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 16px;
  padding: 1.75rem;
  width: 100%;
  max-width: 420px;
  max-height: 100%;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
  // The <form> wraps body + actions, so it needs to participate in the
  // column layout and shrink instead of forcing the dialog to overflow.
  form {
    display: flex;
    flex-direction: column;
    min-height: 0;
  }
  &__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1.25rem;
    flex-shrink: 0;
  }
  &__title {
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--primary-text);
  }
  &__close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: transparent;
    border: none;
    border-radius: 6px;
    color: var(--secondary-text);
    cursor: pointer;
    transition:
      background 0.15s,
      color 0.15s;
    .icon-svg {
      width: 1rem;
      height: 1rem;
    }
    &:hover {
      background: var(--background);
      color: var(--primary-text);
    }
  }
  &__body {
    overflow-y: auto;
    // flex: 1 1 auto with min-height: 0 lets this shrink and scroll instead
    // of the dialog growing past the viewport — the Payment Element can get
    // tall once several payment methods (card, Klarna, Bancontact, …) are
    // enabled.
    flex: 1 1 auto;
    min-height: 0;
  }
  &__actions {
    flex-shrink: 0;
    padding-top: 1.25rem;
  }
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
  .dialog {
    transition:
      opacity 0.2s ease,
      transform 0.2s ease;
  }
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  .dialog {
    opacity: 0;
    transform: translateY(8px) scale(0.98);
  }
}

.stripe-form {
  &__spinner {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 8rem;
  }
  &__element {
    margin-bottom: 1.25rem;
  }
  &__submit {
    width: 100%;
  }
}

.form-error {
  color: var(--error-text, #f87171);
  font-size: 0.85rem;
  margin: -0.5rem 0 1rem;
}
</style>
