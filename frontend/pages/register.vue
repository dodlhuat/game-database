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
        <label for="terms-accepted">
          {{ $t('auth.terms_accept') }}
          <a href="#" class="terms-link" @click.prevent="openTerms">{{ $t('nav.terms') }}</a>
        </label>
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

  <dialog ref="termsDialog" class="terms-modal" @click.self="closeTerms">
    <div class="terms-modal__inner">
      <div class="terms-modal__header">
        <h2 class="terms-modal__title">{{ $t('nav.terms') }}</h2>
        <button
          type="button"
          class="terms-modal__close"
          :aria-label="$t('btn.close')"
          @click="closeTerms"
        >
          <span class="icon icon-close" aria-hidden="true" />
        </button>
      </div>
      <div class="terms-modal__body">
        <p v-if="termsLoading" class="terms-modal__loading">…</p>
        <p v-else-if="termsError" class="terms-modal__error">{{ termsError }}</p>
        <template v-else>
          <p v-for="(para, i) in termsParagraphs" :key="i" class="terms-modal__para">{{ para }}</p>
        </template>
      </div>
      <div class="terms-modal__footer">
        <UiButton type="button" @click="closeTerms">{{ $t('btn.close') }}</UiButton>
      </div>
    </div>
  </dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'

definePageMeta({ layout: 'auth', middleware: [] })

const { register } = useAuth()
const api = useApi()
const { t } = useI18n()
const loading = ref(false)
const success = ref('')
const serverError = ref('')

const termsDialog = ref<HTMLDialogElement | null>(null)
const termsContent = ref('')
const termsLoading = ref(false)
const termsError = ref('')
const termsParagraphs = computed(() => termsContent.value.split('\n\n').filter(Boolean))

async function openTerms() {
  termsDialog.value?.showModal()
  if (termsContent.value) return
  termsLoading.value = true
  termsError.value = ''
  try {
    const data = await api.get<{ content: string }>('/terms')
    termsContent.value = data.content
  } catch {
    termsError.value = t('common.error.generic')
  } finally {
    termsLoading.value = false
  }
}

function closeTerms() {
  termsDialog.value?.close()
}

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

.terms-link {
  color: $amber;
  font-weight: 600;
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }
}

.terms-modal {
  position: fixed;
  inset: 0;
  width: 100%;
  max-width: 100%;
  height: 100%;
  max-height: 100%;
  margin: 0;
  padding: 0;
  border: none;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;

  &:not([open]) {
    display: none;
  }

  &__inner {
    background: #1a1917;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 1rem;
    width: min(560px, calc(100vw - 2rem));
    max-height: calc(100vh - 4rem);
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
    flex-shrink: 0;
  }

  &__title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--primary-text);
    margin: 0;
  }

  &__close {
    background: none;
    border: none;
    cursor: pointer;
    color: rgba(238, 232, 223, 0.5);
    padding: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.15s;

    &:hover {
      color: var(--primary-text);
    }
  }

  &__body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
  }

  &__para {
    font-size: 0.85rem;
    line-height: 1.7;
    color: rgba(238, 232, 223, 0.75);
    margin-bottom: 1rem;
    white-space: pre-line;

    &:last-child {
      margin-bottom: 0;
    }
  }

  &__loading,
  &__error {
    font-size: 0.875rem;
    color: rgba(238, 232, 223, 0.5);
    text-align: center;
    padding: 2rem 0;
  }

  &__error {
    color: #f87171;
  }

  &__footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.07);
    display: flex;
    justify-content: flex-end;
    flex-shrink: 0;
  }
}
</style>
