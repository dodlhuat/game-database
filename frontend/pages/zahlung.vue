<template>
  <div class="payment-page">
    <div class="payment-page__glow" aria-hidden="true" />

    <section class="stage" :class="`stage--${state}`" aria-live="polite">
      <!-- The seal: a ring that keeps turning while we check, then settles -->
      <div class="seal" aria-hidden="true">
        <svg class="seal__ring" viewBox="0 0 120 120">
          <circle class="seal__track" cx="60" cy="60" r="52" />
          <circle class="seal__arc" cx="60" cy="60" r="52" />
        </svg>
        <svg v-if="icon" class="icon-svg seal__icon">
          <use :href="`/svg-icons/icons.svg#${icon}`" />
        </svg>
      </div>

      <p class="stage__eyebrow">{{ $t('payment.return_eyebrow') }}</p>
      <h1 class="stage__title">{{ title }}</h1>
      <p class="stage__text">{{ text }}</p>

      <div class="stage__actions">
        <NuxtLink v-if="state === 'paid'" :to="returnTo" class="button button-primary stage__cta">
          {{ $t('payment.continue') }}
        </NuxtLink>
        <NuxtLink
          v-else-if="state === 'failed' || state === 'error'"
          :to="returnTo"
          class="button button-primary stage__cta"
        >
          {{ $t('payment.try_again') }}
        </NuxtLink>
        <NuxtLink
          v-else-if="state === 'slow' || state === 'refunded' || state === 'duplicate'"
          to="/dashboard"
          class="button stage__cta"
        >
          {{ $t('payment.to_dashboard') }}
        </NuxtLink>
      </div>
      <p v-if="state === 'paid'" class="stage__hint">{{ $t('payment.auto_continue') }}</p>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { takePaymentReturn } from '~/utils/paymentReturn'

definePageMeta({ middleware: ['auth'] })

type State = 'checking' | 'paid' | 'slow' | 'failed' | 'refunded' | 'duplicate' | 'error'

const route = useRoute()
const api = useApi()
const auth = useAuthStore()
const { t } = useI18n()

const kind = String(route.query.art ?? '')
const id = Number(route.query.id)

const state = ref<State>('checking')
const message = ref('')
const returnTo = ref(kind === 'tokens' ? '/tokens' : '/dashboard')

// Mollie sends members back before its webhook may have landed: ask a few
// times before telling them it takes longer.
const POLL_MS = 2000
const MAX_TRIES = 15
let tries = 0
let timer: ReturnType<typeof setTimeout> | undefined

const icon = computed(() => {
  switch (state.value) {
    case 'paid':
      return 'check'
    case 'failed':
    case 'error':
      return 'close'
    case 'slow':
      return 'hourglass_bottom'
    case 'refunded':
    case 'duplicate':
      return 'undo'
    default:
      return null
  }
})

const title = computed(() => t(`payment.state_${state.value}_title`))
const text = computed(() => message.value || t(`payment.state_${state.value}_text`))

async function check() {
  tries++
  try {
    const res = await api.get<{ status: string; message: string; user: unknown }>(
      `/payments/${kind}/${id}`
    )
    if (res.user) auth.setUser(res.user as NonNullable<typeof auth.user>)

    if (res.status === 'paid') {
      state.value = 'paid'
      message.value = res.message
      timer = setTimeout(() => navigateTo(returnTo.value), 4000)
    } else if (res.status === 'failed' || res.status === 'refunded' || res.status === 'duplicate') {
      state.value = res.status
      message.value = res.message
    } else if (tries >= MAX_TRIES) {
      state.value = 'slow'
    } else {
      timer = setTimeout(check, POLL_MS)
    }
  } catch (err: unknown) {
    const msg = (err as { message?: unknown })?.message
    state.value = 'error'
    message.value = typeof msg === 'string' && msg ? msg : ''
  }
}

onMounted(() => {
  returnTo.value = takePaymentReturn(returnTo.value)
  if (!['tokens', 'membership'].includes(kind) || !Number.isInteger(id) || id <= 0) {
    state.value = 'error'
    return
  }
  check()
})

onBeforeUnmount(() => clearTimeout(timer))

useHead(() => ({ title: t('payment.return_eyebrow') }))
</script>

<style lang="scss" scoped>
$nav-height: 64px;
$ease-out: cubic-bezier(0.16, 1, 0.3, 1);

.payment-page {
  position: relative;
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: calc(#{$nav-height} + 2rem) 1.5rem 3rem;
  overflow: hidden;
  background: var(--background);

  &__glow {
    position: absolute;
    width: 520px;
    height: 520px;
    top: 10%;
    left: 50%;
    translate: -50% 0;
    border-radius: 50%;
    filter: blur(110px);
    background: color-mix(in srgb, #{$amber} 14%, transparent);
    pointer-events: none;
  }
}

.stage {
  position: relative;
  max-width: 440px;
  text-align: center;
  animation: rise 0.6s $ease-out both;

  &__eyebrow {
    margin: 1.5rem 0 0.4rem;
    padding: 0;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: $amber;
  }
  &__title {
    margin: 0;
    font-size: clamp(1.6rem, 6vw, 2.2rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    line-height: 1.1;
    color: var(--primary-text);
  }
  &__text {
    margin: 0.75rem auto 0;
    padding: 0;
    max-width: 34ch;
    font-size: 0.95rem;
    line-height: 1.55;
    color: var(--secondary-text);
  }
  &__actions {
    margin-top: 1.75rem;
    min-height: 2.75rem;
  }
  &__cta {
    min-width: 12rem;
  }
  &__hint {
    margin: 0.75rem 0 0;
    padding: 0;
    font-size: 0.75rem;
    color: var(--secondary-text);
  }
}

// ── The seal ──────────────────────────────────────────────────────
.seal {
  position: relative;
  width: 112px;
  height: 112px;
  margin: 0 auto;
  display: grid;
  place-items: center;

  &__ring {
    position: absolute;
    inset: 0;
    rotate: -90deg;
  }
  &__track,
  &__arc {
    fill: none;
    stroke-width: 6;
  }
  &__track {
    stroke: color-mix(in srgb, var(--primary-text) 8%, transparent);
  }
  &__arc {
    stroke: $amber;
    stroke-linecap: round;
    stroke-dasharray: 327;
    stroke-dashoffset: 245;
    transform-origin: 60px 60px;
    transition:
      stroke-dashoffset 0.7s $ease-out,
      stroke 0.3s ease;
  }
  &__icon {
    width: 2.6rem;
    height: 2.6rem;
    color: $amber;
    animation: pop 0.45s $ease-out both;
  }
}

.stage--checking .seal__arc {
  animation: spin 1.1s linear infinite;
}

// Settled: the ring closes around the outcome
.stage--paid,
.stage--failed,
.stage--error,
.stage--refunded,
.stage--duplicate,
.stage--slow {
  .seal__arc {
    stroke-dashoffset: 0;
  }
}
.stage--failed,
.stage--error {
  .seal__arc {
    stroke: var(--error-text);
  }
  .seal__icon {
    color: var(--error-text);
  }
}
.stage--slow .seal__arc {
  stroke-dashoffset: 110;
}
.stage--paid .seal {
  animation: glow 1.2s ease-out 0.3s both;
  border-radius: 50%;
}

@keyframes spin {
  to {
    rotate: 360deg;
  }
}
@keyframes pop {
  from {
    opacity: 0;
    scale: 0.5;
  }
}
@keyframes glow {
  0% {
    box-shadow: 0 0 0 0 color-mix(in srgb, #{$amber} 45%, transparent);
  }
  100% {
    box-shadow: 0 0 0 28px transparent;
  }
}
@keyframes rise {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
}

@media (prefers-reduced-motion: reduce) {
  .stage,
  .seal__icon,
  .stage--paid .seal {
    animation: none;
  }
  .stage--checking .seal__arc {
    animation-duration: 3s;
  }
}
</style>
