<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="item" class="modal-overlay" @click.self="close">
        <div class="dialog" role="dialog" aria-modal="true" :aria-label="item.title">
          <div class="dialog__header">
            <h3 class="dialog__title">{{ $t('payment.checkout_title') }}</h3>
            <button class="dialog__close" :aria-label="$t('btn.close')" @click="close">
              <svg class="icon-svg" aria-hidden="true">
                <use href="/svg-icons/icons.svg#close" />
              </svg>
            </button>
          </div>

          <!-- What is being paid: one line, one number -->
          <div class="summary">
            <span class="summary__item">{{ item.title }}</span>
            <span class="summary__price">{{ formattedPrice }}</span>
          </div>

          <p class="dialog__note">
            <svg class="icon-svg" aria-hidden="true">
              <use href="/svg-icons/icons.svg#lock" />
            </svg>
            {{ $t('payment.redirect_note') }}
          </p>

          <p v-if="formError" class="form-error" role="alert">{{ formError }}</p>

          <!-- A membership payment of another kind is still open at Mollie -->
          <button
            v-if="openCheckoutUrl"
            type="button"
            class="button button-primary dialog__pay"
            @click="resumeOpen"
          >
            {{ $t('payment.resume_open') }}
          </button>
          <button
            v-else
            type="button"
            class="button button-primary dialog__pay"
            :disabled="redirecting"
            @click="pay"
          >
            {{
              redirecting ? $t('payment.redirecting') : $t('payment.pay', { price: formattedPrice })
            }}
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { rememberPaymentReturn } from '~/utils/paymentReturn'

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

/**
 * Last step before paying: shows what is paid and hands over to the Mollie
 * checkout. The outcome is shown on /zahlung, where Mollie sends the member
 * back.
 */
const props = withDefaults(
  defineProps<{ pkg?: TokenPackage | null; membership?: MembershipCheckout | null }>(),
  { pkg: null, membership: null }
)

const emit = defineEmits<{ error: [message: string]; close: [] }>()

const api = useApi()
const route = useRoute()
const { t } = useI18n()

const redirecting = ref(false)
const formError = ref('')
const openCheckoutUrl = ref<string | null>(null)

const item = computed(() => {
  if (props.pkg) {
    return {
      title: t('payment.item_tokens', { n: props.pkg.amount }),
      price_cents: props.pkg.price_cents,
      currency: props.pkg.currency,
      path: '/tokens/checkout',
      body: { amount: props.pkg.amount } as Record<string, unknown>,
    }
  }
  if (props.membership) {
    return {
      title: t(`payment.item_${props.membership.type.toLowerCase()}`),
      price_cents: props.membership.price_cents,
      currency: props.membership.currency,
      path: '/membership/checkout',
      body: { type: props.membership.type, ...props.membership.address } as Record<string, unknown>,
    }
  }
  return null
})

watch(item, () => {
  redirecting.value = false
  formError.value = ''
  openCheckoutUrl.value = null
})

const formattedPrice = computed(() =>
  item.value
    ? new Intl.NumberFormat('de-AT', { style: 'currency', currency: item.value.currency }).format(
        item.value.price_cents / 100
      )
    : ''
)

function close() {
  if (!redirecting.value) emit('close')
}

async function pay() {
  if (!item.value) return
  redirecting.value = true
  formError.value = ''
  try {
    const res = await api.post<{ checkoutUrl: string }>(item.value.path, item.value.body)
    // Back on /zahlung, "Weiter" leads to where the member started
    rememberPaymentReturn(route.fullPath)
    window.location.assign(res.checkoutUrl)
  } catch (err: unknown) {
    const { message, checkoutUrl } = (err ?? {}) as { message?: unknown; checkoutUrl?: unknown }
    formError.value = typeof message === 'string' && message ? message : t('payment.prepare_failed')
    openCheckoutUrl.value = typeof checkoutUrl === 'string' && checkoutUrl ? checkoutUrl : null
    redirecting.value = false
  }
}

function resumeOpen() {
  if (!openCheckoutUrl.value) return
  rememberPaymentReturn(route.fullPath)
  window.location.assign(openCheckoutUrl.value)
}
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
  width: 100%;
  max-width: 420px;
  padding: 1.6rem;
  border-radius: 16px;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);

  &__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1.1rem;
  }
  &__title {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--secondary-text);
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
    .icon-svg {
      width: 1rem;
      height: 1rem;
    }
    &:hover {
      background: var(--background);
      color: var(--primary-text);
    }
  }
  &__note {
    display: flex;
    align-items: flex-start;
    gap: 0.45rem;
    margin: 1rem 0 1.25rem;
    padding: 0;
    font-size: 0.8rem;
    line-height: 1.5;
    color: var(--secondary-text);
    .icon-svg {
      flex: none;
      width: 0.95rem;
      height: 0.95rem;
      margin-top: 0.15rem;
      color: $amber;
    }
  }
  &__pay {
    width: 100%;
    margin: 0;
  }
}

// The receipt line: item left, amount large on the right
.summary {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.1rem;
  border-radius: 12px;
  background: color-mix(in srgb, #{$amber} 8%, var(--background));
  border: 1px solid color-mix(in srgb, #{$amber} 30%, transparent);

  &__item {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--primary-text);
  }
  &__price {
    font-size: 1.6rem;
    font-weight: 800;
    letter-spacing: -0.04em;
    font-variant-numeric: tabular-nums;
    color: var(--primary-text);
  }
}

.form-error {
  margin: 0 0 1rem;
  font-size: 0.85rem;
  color: var(--error-text);
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
</style>
