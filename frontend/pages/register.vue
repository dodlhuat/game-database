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
        <h1 class="auth-card__title">Konto erstellen</h1>

        <div v-if="success" class="alert alert-success">
          <p>{{ success }}</p>
          <NuxtLink to="/login">Zum Login</NuxtLink>
        </div>

        <form v-else @submit.prevent="submit">
          <UiInput
            v-model="form.name"
            label="Name"
            :error="errors.name"
            required
            autocomplete="name"
          />
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
            autocomplete="new-password"
          />
          <UiInput
            v-model="form.password_confirmation"
            type="password"
            label="Passwort wiederholen"
            :error="errors.password_confirmation"
            required
            autocomplete="new-password"
          />

          <!-- Honeypot: hidden from real users, bots will fill this -->
          <div class="hp-field" aria-hidden="true">
            <label for="website">Website</label>
            <input id="website" v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off" />
          </div>

          <div class="auth-card__checkboxes">
            <input v-model="form.newsletter_opt_in" type="checkbox" class="styled-checkbox" id="newsletter-opt-in" />
            <label for="newsletter-opt-in">Newsletter abonnieren</label>

            <input v-model="form.terms_accepted" type="checkbox" class="styled-checkbox" id="terms-accepted" required />
            <label for="terms-accepted" style="white-space: pre;">Ich akzeptiere die <NuxtLink to="/terms">Nutzungsbedingungen</NuxtLink></label>
            <p v-if="errors.terms_accepted" class="error-text" role="alert">{{ errors.terms_accepted }}</p>
          </div>

          <div v-if="serverError" class="alert alert-error" role="alert">{{ serverError }}</div>

          <UiButton type="submit" :loading="loading">Registrieren</UiButton>
        </form>

        <p class="auth-card__footer-text">
          Bereits Mitglied? <NuxtLink to="/login">Einloggen</NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'

definePageMeta({ middleware: [] })

const { register } = useAuth()
const loading = ref(false)
const success = ref('')
const serverError = ref('')

const formLoadedAt = Date.now()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  newsletter_opt_in: false,
  terms_accepted: false,
  website: '',
  form_loaded_at: formLoadedAt,
})

const errors = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  terms_accepted: '',
})

async function submit() {
  Object.keys(errors).forEach((k) => ((errors as Record<string, string>)[k] = ''))
  serverError.value = ''
  loading.value = true

  try {
    const data = await register(form)
    success.value = data.message
  } catch (err: unknown) {
    const e = err as { errors?: Record<string, string[]>; message?: string }

    if (e.errors) {
      Object.entries(e.errors).forEach(([key, msgs]) => {
        if (key in errors) (errors as Record<string, string>)[key] = msgs[0] ?? ''
      })
    } else {
      serverError.value = e.message ?? 'Ein Fehler ist aufgetreten.'
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
    max-width: 440px;
    display: flex;
    flex-direction: column;
    gap: 2rem;
    padding: 1.5rem 0;
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

  &__checkboxes {
    margin: 0.5rem 0 1rem;

    // override basix styled-checkbox label color for dark background
    :deep(.styled-checkbox + label) {
      color: $muted;
      font-size: 0.875rem;

      a {
        color: $amber;
        text-decoration: none;
        font-weight: 600;

        &:hover {
          text-decoration: underline;
        }
      }
    }

    :deep(.styled-checkbox + label::before) {
      border-color: rgba(238, 232, 223, 0.25);
    }
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

.error-text {
  color: #f87171;
  font-size: 0.8rem;
  margin: 0.2rem 0 0.5rem;
}

.hp-field {
  position: absolute;
  left: -9999px;
  width: 1px;
  height: 1px;
  overflow: hidden;
  opacity: 0;
  pointer-events: none;
}
</style>
