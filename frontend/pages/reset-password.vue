<template>
  <div class="auth-page" data-theme="dark">
    <div class="auth-page__backdrop" aria-hidden="true">
      <div class="auth-page__glow" />
      <div class="auth-page__dots" />
    </div>

    <div class="auth-page__body">
      <div class="auth-page__brand">
        <NuxtLink to="/" class="auth-page__brand-link">
          <span class="auth-page__hex" aria-hidden="true">⬡</span>
          <span class="auth-page__name">AUA</span>
        </NuxtLink>
      </div>

      <div class="auth-card">
        <div class="auth-card__eyebrow">{{ $t('auth.member_area') }}</div>
        <h1 class="auth-card__title">{{ $t('auth.reset_title') }}</h1>

        <div v-if="invalidLink" class="alert alert-error">
          {{ $t('auth.reset_invalid_link') }}
          <br />
          <NuxtLink to="/forgot-password">{{ $t('auth.reset_request_new') }}</NuxtLink>
        </div>

        <template v-else-if="!done">
          <form @submit.prevent="submit">
            <UiInput
              v-model="form.password"
              type="password"
              :label="$t('account.password_new')"
              :error="errors.password"
              required
              autocomplete="new-password"
            />
            <UiInput
              v-model="form.password_confirmation"
              type="password"
              :label="$t('account.password_confirm_new')"
              :error="errors.password_confirmation"
              required
              autocomplete="new-password"
            />

            <div v-if="generalError" class="alert alert-error">{{ generalError }}</div>

            <UiButton type="submit" :loading="loading">{{ $t('auth.reset_submit') }}</UiButton>
          </form>
        </template>

        <div v-else class="auth-card__success">
          <span class="auth-card__success-icon" aria-hidden="true">✓</span>
          <p>{{ $t('auth.reset_success') }}</p>
          <NuxtLink to="/login" class="auth-card__success-link">{{ $t('auth.reset_to_login') }}</NuxtLink>
        </div>

        <p v-if="!done" class="auth-card__footer-text">
          <NuxtLink to="/login">← {{ $t('auth.back_to_login') }}</NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'

definePageMeta({ middleware: [] })

const api = useApi()
const route = useRoute()

const token = ref('')
const emailParam = ref('')
const invalidLink = ref(false)
const loading = ref(false)
const done = ref(false)
const generalError = ref('')

const form = reactive({ password: '', password_confirmation: '' })
const errors = reactive({ password: '', password_confirmation: '' })

onMounted(() => {
  token.value = String(route.query.token ?? '')
  emailParam.value = String(route.query.email ?? '')
  if (!token.value || !emailParam.value) {
    invalidLink.value = true
  }
})

async function submit() {
  errors.password = ''
  errors.password_confirmation = ''
  generalError.value = ''

  if (form.password !== form.password_confirmation) {
    errors.password_confirmation = 'Passwörter stimmen nicht überein.'
    return
  }

  loading.value = true
  try {
    await api.post('/auth/reset-password', {
      token: token.value,
      email: emailParam.value,
      password: form.password,
      password_confirmation: form.password_confirmation,
    })
    done.value = true
  } catch (err: unknown) {
    const e = err as { errors?: Record<string, string[]>; message?: string }
    if (e.errors?.password) {
      errors.password = e.errors.password[0]
    } else {
      generalError.value = e.message ?? 'Fehler beim Zurücksetzen.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
$bg: #0F0E0C;
$amber-glow: rgba(212, 146, 30, 0.18);
$text: #EEE8DF;
$muted: rgba(238, 232, 223, 0.65);

.auth-page {
  position: relative;
  min-height: 100vh;
  background: $bg;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1.25rem;

  &__backdrop { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }

  &__glow {
    position: absolute;
    width: 560px; height: 560px;
    top: -160px; right: -100px;
    border-radius: 50%; filter: blur(110px);
    background: $amber-glow;
  }

  &__dots {
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,0.035) 1px, transparent 1px);
    background-size: 24px 24px;
    mask-image: radial-gradient(ellipse 80% 80% at 70% 30%, black 20%, transparent 100%);
  }

  &__body {
    position: relative; z-index: 1;
    width: 100%; max-width: 420px;
    display: flex; flex-direction: column; gap: 2rem;
  }

  &__brand { display: flex; justify-content: center; }

  &__brand-link {
    display: inline-flex; align-items: center; gap: 0.4rem; text-decoration: none;
  }

  &__hex { font-size: 1.2rem; color: $amber; }

  &__name {
    font-size: 1rem; font-weight: 800; color: $text; letter-spacing: -0.03em;
  }
}

.auth-card {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(238,232,223,0.1);
  border-radius: 16px;
  padding: 2rem;
  backdrop-filter: blur(8px);

  &__eyebrow {
    font-size: 0.72rem; font-weight: 600; letter-spacing: 0.1em;
    text-transform: uppercase; color: $amber; margin-bottom: 0.4rem;
  }

  &__title {
    font-size: 1.6rem; font-weight: 800; letter-spacing: -0.04em;
    color: $text; margin-bottom: 1.75rem;
  }

  &__success {
    display: flex; flex-direction: column; align-items: center;
    gap: 0.75rem; padding: 1rem 0; text-align: center;
  }

  &__success-icon {
    display: flex; align-items: center; justify-content: center;
    width: 48px; height: 48px; border-radius: 50%;
    background: rgba(212,146,30,0.15);
    color: $amber; font-size: 1.4rem; font-weight: 700;
  }

  &__success p {
    font-size: 0.9rem; color: $muted; line-height: 1.55;
  }

  &__success-link {
    display: inline-block; margin-top: 0.25rem;
    font-size: 0.9rem; font-weight: 600; color: $amber; text-decoration: none;
    &:hover { text-decoration: underline; }
  }

  &__footer-text {
    font-size: 0.875rem; color: $muted;
    text-align: center; margin-top: 1.25rem;

    a { color: $amber; text-decoration: none; font-weight: 600;
      &:hover { text-decoration: underline; } }
  }
}
</style>
