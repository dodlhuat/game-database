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
        <h1 class="auth-card__title">{{ $t('auth.forgot_title') }}</h1>
        <p class="auth-card__subtitle">{{ $t('auth.forgot_subtitle') }}</p>

        <template v-if="!sent">
          <form @submit.prevent="submit">
            <UiInput
              v-model="email"
              type="email"
              :label="$t('auth.email')"
              :error="error"
              required
              autocomplete="email"
            />
            <UiButton type="submit" :loading="loading">{{ $t('auth.forgot_submit') }}</UiButton>
          </form>
        </template>

        <div v-else class="auth-card__success">
          <span class="auth-card__success-icon" aria-hidden="true">✓</span>
          <p>{{ $t('auth.forgot_sent') }}</p>
        </div>

        <p class="auth-card__footer-text">
          <NuxtLink to="/login">← {{ $t('auth.back_to_login') }}</NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

definePageMeta({ layout: 'auth', middleware: [] })

const api = useApi()
const email = ref('')
const error = ref('')
const loading = ref(false)
const sent = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await api.post('/auth/forgot-password', { email: email.value })
    sent.value = true
  } catch (err: unknown) {
    const e = err as { errors?: Record<string, string[]>; message?: string }
    error.value = e.errors?.email?.[0] ?? e.message ?? 'Fehler beim Senden.'
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
    color: $text; margin-bottom: 0.5rem;
  }

  &__subtitle {
    font-size: 0.875rem; color: $muted; margin-bottom: 1.5rem; line-height: 1.55;
  }

  &__success {
    display: flex; flex-direction: column; align-items: center;
    gap: 0.75rem; padding: 1rem 0;
    text-align: center;
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

  &__footer-text {
    font-size: 0.875rem; color: $muted;
    text-align: center; margin-top: 1.25rem;

    a { color: $amber; text-decoration: none; font-weight: 600;
      &:hover { text-decoration: underline; } }
  }
}
</style>
