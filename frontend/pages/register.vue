<template>
  <main>
    <h1>Registrieren</h1>

    <div v-if="success">
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

      <label>
        <input v-model="form.newsletter_opt_in" type="checkbox" />
        Newsletter abonnieren
      </label>

      <label>
        <input v-model="form.terms_accepted" type="checkbox" required />
        Ich akzeptiere die <NuxtLink to="/terms">Nutzungsbedingungen</NuxtLink>
      </label>
      <p v-if="errors.terms_accepted" role="alert">{{ errors.terms_accepted }}</p>

      <p v-if="serverError" role="alert">{{ serverError }}</p>

      <UiButton type="submit" :loading="loading">Registrieren</UiButton>
    </form>

    <p>Bereits Mitglied? <NuxtLink to="/login">Einloggen</NuxtLink></p>
  </main>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'

definePageMeta({ middleware: [] })

const { register } = useAuth()
const loading = ref(false)
const success = ref('')
const serverError = ref('')

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  newsletter_opt_in: false,
  terms_accepted: false,
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
        if (key in errors) (errors as Record<string, string>)[key] = msgs[0]
      })
    } else {
      serverError.value = e.message ?? 'Ein Fehler ist aufgetreten.'
    }
  } finally {
    loading.value = false
  }
}
</script>
