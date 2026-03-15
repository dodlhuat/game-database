<template>
  <main class="content">
    <h1>Mein Konto</h1>
    <NuxtLink to="/dashboard">← Dashboard</NuxtLink>

    <!-- Newsletter -->
    <div class="card">
      <div class="header"><h2>Newsletter</h2></div>
      <div class="switch">
        <input v-model="newsletterOptIn" type="checkbox" id="newsletter-switch" @change="saveNewsletter" />
        <label for="newsletter-switch">Newsletter abonnieren</label>
      </div>
      <div v-if="newsletterMsg" class="alert alert-success">{{ newsletterMsg }}</div>
    </div>

    <!-- Passwort ändern -->
    <div class="card">
      <div class="header"><h2>Passwort ändern</h2></div>
      <UiInput
        v-model="pwForm.current_password"
        type="password"
        label="Aktuelles Passwort"
        :error="pwErrors.current_password"
        autocomplete="current-password"
      />
      <UiInput
        v-model="pwForm.new_password"
        type="password"
        label="Neues Passwort"
        :error="pwErrors.new_password"
        autocomplete="new-password"
      />
      <UiInput
        v-model="pwForm.new_password_confirmation"
        type="password"
        label="Neues Passwort wiederholen"
        autocomplete="new-password"
      />
      <div v-if="pwError" class="alert alert-error" role="alert">{{ pwError }}</div>
      <div v-if="pwSuccess" class="alert alert-success">{{ pwSuccess }}</div>
      <UiButton :loading="savingPw" @click="changePassword">Passwort speichern</UiButton>
    </div>
  </main>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

definePageMeta({ middleware: ['auth'] })

const api = useApi()
const auth = useAuthStore()

const newsletterOptIn = ref(false)
const newsletterMsg = ref('')

const pwForm = ref({ current_password: '', new_password: '', new_password_confirmation: '' })
const pwErrors = ref({ current_password: '', new_password: '' })
const pwError = ref('')
const pwSuccess = ref('')
const savingPw = ref(false)

onMounted(() => {
  newsletterOptIn.value = auth.user?.newsletter_opt_in ?? false
})

async function saveNewsletter() {
  newsletterMsg.value = ''
  try {
    await api.patch('/account', { newsletter_opt_in: newsletterOptIn.value })
    newsletterMsg.value = newsletterOptIn.value ? 'Newsletter abonniert.' : 'Newsletter abgemeldet.'
  } catch {
    newsletterOptIn.value = !newsletterOptIn.value
  }
}

async function changePassword() {
  pwErrors.value = { current_password: '', new_password: '' }
  pwError.value = ''
  pwSuccess.value = ''

  if (!pwForm.value.current_password || !pwForm.value.new_password) return
  if (pwForm.value.new_password !== pwForm.value.new_password_confirmation) {
    pwErrors.value.new_password = 'Passwörter stimmen nicht überein.'
    return
  }

  savingPw.value = true
  try {
    await api.patch('/account', {
      current_password: pwForm.value.current_password,
      new_password: pwForm.value.new_password,
      new_password_confirmation: pwForm.value.new_password_confirmation,
    })
    pwSuccess.value = 'Passwort wurde geändert.'
    pwForm.value = { current_password: '', new_password: '', new_password_confirmation: '' }
  } catch (err: unknown) {
    const e = err as { errors?: Record<string, string[]>; message?: string }
    if (e.errors) {
      Object.entries(e.errors).forEach(([k, msgs]) => {
        if (k in pwErrors.value) (pwErrors.value as Record<string, string>)[k] = msgs[0]
      })
    } else {
      pwError.value = e.message ?? 'Ein Fehler ist aufgetreten.'
    }
  } finally {
    savingPw.value = false
  }
}
</script>
