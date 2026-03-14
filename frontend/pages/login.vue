<template>
  <main>
    <h1>Anmelden</h1>

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

      <p v-if="statusMessage" role="alert">{{ statusMessage }}</p>

      <UiButton type="submit" :loading="loading">Einloggen</UiButton>
    </form>

    <p>Noch kein Konto? <NuxtLink to="/register">Jetzt registrieren</NuxtLink></p>
  </main>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'

definePageMeta({ middleware: [] })

const { login } = useAuth()
const loading = ref(false)
const statusMessage = ref('')

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
