<template>
  <div class="auth-card auth-card--centered">
    <template v-if="state === 'done'">
      <div class="auth-card__icon">✓</div>
      <h1 class="auth-card__title">{{ $t('newsletter_unsubscribe.done_title') }}</h1>
      <p class="auth-card__text">{{ $t('newsletter_unsubscribe.done_text') }}</p>
      <UiButton @click="navigateTo('/')">{{ $t('newsletter_unsubscribe.home') }}</UiButton>
    </template>

    <template v-else-if="state === 'invalid'">
      <div class="auth-card__icon auth-card__icon--error">✕</div>
      <h1 class="auth-card__title">{{ $t('newsletter_unsubscribe.invalid_title') }}</h1>
      <p class="auth-card__text">{{ $t('newsletter_unsubscribe.invalid_text') }}</p>
      <UiButton @click="navigateTo('/login')">{{ $t('btn.login') }}</UiButton>
    </template>

    <template v-else>
      <div class="auth-card__icon auth-card__icon--muted">✉</div>
      <h1 class="auth-card__title">{{ $t('newsletter_unsubscribe.title') }}</h1>
      <p class="auth-card__text">{{ $t('newsletter_unsubscribe.text') }}</p>
      <p v-if="error" class="unsubscribe-error" role="alert">{{ error }}</p>
      <UiButton :disabled="state === 'sending'" @click="unsubscribe">
        {{
          state === 'sending'
            ? $t('newsletter_unsubscribe.sending')
            : $t('newsletter_unsubscribe.confirm')
        }}
      </UiButton>
    </template>
  </div>
</template>

<script setup lang="ts">
/**
 * Target of the footer link in every newsletter. It asks before unsubscribing
 * on purpose: link scanners and mail previews open links on their own, and a
 * GET that unsubscribes would sign people out without them ever clicking.
 */
definePageMeta({ layout: 'auth', middleware: [] })

const route = useRoute()
const api = useApi()
const { t } = useI18n()

const user = String(route.query.user ?? '')
const signature = String(route.query.signature ?? '')
const state = ref<'ask' | 'sending' | 'done' | 'invalid'>(
  /^\d+$/.test(user) && signature ? 'ask' : 'invalid'
)
const error = ref('')

async function unsubscribe() {
  state.value = 'sending'
  error.value = ''
  try {
    await api.post(`/newsletter/unsubscribe/${user}`, undefined, { params: { signature } })
    state.value = 'done'
  } catch (err: unknown) {
    const e = err as { status?: number; message?: string }
    if (e.status === 403) {
      state.value = 'invalid'
      return
    }
    error.value = e.message ?? t('common.error.try_later')
    state.value = 'ask'
  }
}
</script>

<style lang="scss" scoped>
.auth-card--centered {
  text-align: center;
}

.unsubscribe-error {
  margin: -0.5rem 0 1rem;
  font-size: 0.85rem;
  color: rgba(220, 90, 90, 0.95);
}
</style>
