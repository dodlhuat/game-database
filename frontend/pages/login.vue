<template>
  <div class="auth-card">
    <div class="auth-card__eyebrow">{{ $t('auth.member_area') }}</div>
    <h1 class="auth-card__title">{{ $t('auth.login_title') }}</h1>

    <form @submit.prevent="submit">
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
        autocomplete="current-password"
      />
      <NuxtLink to="/forgot-password" class="forgot-link">{{
        $t('auth.forgot_password')
      }}</NuxtLink>

      <div v-if="emailNotVerified" class="alert alert-warning" role="alert">
        {{ $t('auth.email_not_verified') }}
        <button
          type="button"
          class="resend-link"
          :disabled="resendLoading"
          @click="resendVerification"
        >
          {{ resendLoading ? $t('auth.resending') : $t('auth.resend_link') }}
        </button>
        <span v-if="resendSuccess" class="resend-success">{{ $t('auth.email_sent') }}</span>
        <span v-if="resendError" class="resend-error">{{ $t('common.error.generic') }}</span>
      </div>

      <div v-else-if="statusMessage" class="alert alert-error" role="alert">
        {{ statusMessage }}
      </div>

      <UiButton type="submit" :loading="loading">{{ $t('btn.login') }}</UiButton>
    </form>

    <p class="auth-card__footer-text">
      {{ $t('auth.no_account') }} <NuxtLink to="/register">{{ $t('btn.register') }}</NuxtLink>
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'

definePageMeta({ layout: 'auth', middleware: [] })

const { login } = useAuth()
const { t } = useI18n()
const api = useApi()
const route = useRoute()
const loading = ref(false)
const emailNotVerified = ref(false)
const resendLoading = ref(false)
const resendSuccess = ref(false)
const resendError = ref(false)
const lastEmail = ref('')
const statusMessage = ref(
  route.query.reason === 'unauthenticated' ? t('common.error.session_expired') : ''
)

const form = reactive({ email: '', password: '' })
const errors = reactive({ email: '', password: '' })

async function submit() {
  errors.email = ''
  errors.password = ''
  statusMessage.value = ''
  emailNotVerified.value = false
  loading.value = true

  try {
    await login(form)
    await navigateTo('/dashboard')
  } catch (err: unknown) {
    const e = err as {
      status?: number
      reason?: string
      message?: string
      errors?: Record<string, string[]>
    }

    if (e.reason === 'email_not_verified') {
      emailNotVerified.value = true
      lastEmail.value = form.email
    } else if (e.errors) {
      errors.email = e.errors.email?.[0] ?? ''
      errors.password = e.errors.password?.[0] ?? ''
    } else {
      statusMessage.value = e.message ?? t('common.error.generic')
    }
  } finally {
    loading.value = false
  }
}

async function resendVerification() {
  resendLoading.value = true
  resendSuccess.value = false
  resendError.value = false
  try {
    await api.post('/auth/email/resend', { email: form.email })
    resendSuccess.value = true
  } catch {
    resendError.value = true
  } finally {
    resendLoading.value = false
  }
}
</script>

<style lang="scss" scoped>
:deep(.input-wrapper) {
  margin-bottom: 1.25rem;
}

.forgot-link {
  display: block;
  font-size: 0.8rem;
  color: rgba(212, 146, 30, 0.75);
  text-decoration: none;
  text-align: right;
  margin-top: -0.75rem;
  margin-bottom: 1.25rem;
  transition: color 0.2s;

  &:hover {
    color: $amber;
  }
}

.resend-link {
  background: none;
  border: none;
  color: $amber;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  margin-left: 0.5rem;
  text-decoration: underline;

  &:disabled {
    opacity: 0.5;
    cursor: default;
  }
}

.resend-success {
  font-size: 0.8rem;
  color: rgba(238, 232, 223, 0.72);
  margin-left: 0.4rem;
}

.resend-error {
  font-size: 0.8rem;
  color: #e06c6c;
  margin-left: 0.4rem;
}
</style>
