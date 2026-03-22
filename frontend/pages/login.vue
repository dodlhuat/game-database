<template>
  <div class="auth-page">
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
        <div class="auth-card__eyebrow">Mitgliederbereich</div>
        <h1 class="auth-card__title">Anmelden</h1>

        <form @submit.prevent="submit">
          <UiInput
            v-model="form.email"
            type="email"
            label="E-Mail"
            :error="errors.email"
            required
            autocomplete="email"
          />
          <UiInput
            v-model="form.password"
            type="password"
            label="Passwort"
            :error="errors.password"
            required
            autocomplete="current-password"
          />

          <div v-if="statusMessage" class="alert alert-error" role="alert">{{ statusMessage }}</div>

          <UiButton type="submit" :loading="loading">Einloggen</UiButton>
        </form>

        <p class="auth-card__footer-text">
          Noch kein Konto? <NuxtLink to="/register">Jetzt registrieren</NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'

definePageMeta({ middleware: [] })

const { login } = useAuth()
const route = useRoute()
const loading = ref(false)
const statusMessage = ref(
  route.query.reason === 'unauthenticated'
    ? 'Deine Sitzung ist abgelaufen. Bitte melde dich erneut an.'
    : ''
)

const form = reactive({ email: '', password: '' })
const errors = reactive({ email: '', password: '' })

async function submit() {
  errors.email = ''
  errors.password = ''
  statusMessage.value = ''
  loading.value = true

  try {
    await login(form)
    await navigateTo('/dashboard')
  } catch (err: unknown) {
    const e = err as { status?: number; message?: string; errors?: Record<string, string[]> }

    if (e.errors) {
      errors.email = e.errors.email?.[0] ?? ''
      errors.password = e.errors.password?.[0] ?? ''
    } else {
      statusMessage.value = e.message ?? 'Ein Fehler ist aufgetreten.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
$bg: #0F0E0C;
$amber: #D4921E;
$amber-glow: rgba(212, 146, 30, 0.18);
$amber-08: rgba(212, 146, 30, 0.08);
$amber-20: rgba(212, 146, 30, 0.20);
$text: #EEE8DF;
$muted: rgba(238, 232, 223, 0.5);

.auth-page {
  position: relative;
  min-height: 100vh;
  background: $bg;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1.25rem;

  &__backdrop {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
  }

  &__glow {
    position: absolute;
    width: 560px;
    height: 560px;
    top: -160px;
    right: -100px;
    border-radius: 50%;
    filter: blur(110px);
    background: $amber-glow;
  }

  &__dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
    background-size: 24px 24px;
    mask-image: radial-gradient(ellipse 80% 80% at 70% 30%, black 20%, transparent 100%);
  }

  &__body {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 420px;
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }

  &__brand {
    display: flex;
    justify-content: center;
  }

  &__brand-link {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    text-decoration: none;
  }

  &__hex {
    font-size: 1.2rem;
    color: $amber;
  }

  &__name {
    font-size: 1rem;
    font-weight: 800;
    color: $text;
    letter-spacing: -0.03em;
  }
}

.auth-card {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(238, 232, 223, 0.1);
  border-radius: 16px;
  padding: 2rem;
  backdrop-filter: blur(8px);

  &__eyebrow {
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: $amber;
    margin-bottom: 0.4rem;
  }

  &__title {
    font-size: 1.6rem;
    font-weight: 800;
    letter-spacing: -0.04em;
    color: $text;
    margin-bottom: 1.75rem;
  }

  &__footer-text {
    font-size: 0.875rem;
    color: $muted;
    text-align: center;
    margin-top: 1.25rem;
    padding-bottom: 0;

    a {
      color: $amber;
      text-decoration: none;
      font-weight: 600;

      &:hover {
        text-decoration: underline;
      }
    }
  }
}
</style>
