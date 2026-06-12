<template>
  <div class="auth-card">
    <div class="auth-card__eyebrow">{{ $t('auth.member_area') }}</div>
    <h1 class="auth-card__title">{{ $t('auth.register_title') }}</h1>

    <div v-if="success" class="alert alert-success">
      <p>{{ success }}</p>
      <NuxtLink to="/login">{{ $t('btn.login') }}</NuxtLink>
    </div>

    <form v-else @submit.prevent="submit">
      <UiInput
        v-model="form.name"
        :label="$t('auth.name')"
        :error="errors.name"
        required
        autocomplete="name"
      />
      <UiInput
        v-model="form.email"
        type="email"
        :label="$t('auth.email')"
        :error="errors.email"
        required
        autocomplete="email"
      />
      <UiInput
        v-model="form.password"
        type="password"
        :label="$t('auth.password')"
        :error="errors.password"
        required
        autocomplete="new-password"
      />
      <UiInput
        v-model="form.password_confirmation"
        type="password"
        :label="$t('auth.password_confirm')"
        :error="errors.password_confirmation"
        required
        autocomplete="new-password"
      />

      <!-- Honeypot: hidden from real users, bots will fill this -->
      <div class="hp-field" aria-hidden="true">
        <label for="website">Website</label>
        <input
          id="website"
          v-model="form.website"
          type="text"
          name="website"
          tabindex="-1"
          autocomplete="off"
        />
      </div>

      <div class="auth-card__checkboxes">
        <input
          id="newsletter-opt-in"
          v-model="form.newsletter_opt_in"
          type="checkbox"
          class="styled-checkbox"
        />
        <label for="newsletter-opt-in">{{ $t('auth.newsletter_opt_in') }}</label>

        <input
          id="terms-accepted"
          v-model="form.terms_accepted"
          type="checkbox"
          class="styled-checkbox"
          required
        />
        <label for="terms-accepted"
          >{{ $t('auth.terms_accept') }}
          <NuxtLink to="/terms">{{ $t('nav.terms') }}</NuxtLink></label
        >
        <p v-if="errors.terms_accepted" class="error-text" role="alert">
          {{ errors.terms_accepted }}
        </p>
      </div>

      <div v-if="serverError" class="alert alert-error" role="alert">{{ serverError }}</div>

      <UiButton type="submit" :loading="loading">{{ $t('btn.register') }}</UiButton>
    </form>

    <p class="auth-card__footer-text">
      {{ $t('auth.already_member') }} <NuxtLink to="/login">{{ $t('btn.login') }}</NuxtLink>
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'

definePageMeta({ layout: 'auth', middleware: [] })

const { register } = useAuth()
const { t } = useI18n()
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
      serverError.value = e.message ?? t('common.error.generic')
    }
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
$_muted: rgba(238, 232, 223, 0.65);

.auth-card__checkboxes {
  margin: 0.5rem 0 1rem;

  :deep(.styled-checkbox + label) {
    color: $_muted;
    font-size: 0.875rem;
    white-space: pre-wrap;

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
