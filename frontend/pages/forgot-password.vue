<template>
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
