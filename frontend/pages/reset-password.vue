<template>
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
      <NuxtLink to="/login" class="auth-card__success-link">{{
        $t('auth.reset_to_login')
      }}</NuxtLink>
    </div>

    <p v-if="!done" class="auth-card__footer-text">
      <NuxtLink to="/login">← {{ $t('auth.back_to_login') }}</NuxtLink>
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'

definePageMeta({ layout: 'auth', middleware: [] })

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
