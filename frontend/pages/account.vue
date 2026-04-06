<template>
  <div class="account-page" data-theme="dark">
    <AppNav />

    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <NuxtLink to="/dashboard" class="page-hero__back">← Dashboard</NuxtLink>
        <p class="page-hero__eyebrow">Einstellungen</p>
        <h1 class="page-hero__title">Mein Konto</h1>
      </div>
    </section>

    <div class="account-content">
      <div class="account-content__inner">

        <!-- Profil -->
        <section class="account-section">
          <h2 class="account-section__title">Profil</h2>

          <div v-if="profileSuccess" class="alert alert-success">{{ profileSuccess }}</div>
          <div v-if="profileError" class="alert alert-error">{{ profileError }}</div>

          <UiInput v-model="profileForm.name" label="Name" :error="profileErrors.name" autocomplete="name" />
          <UiInput v-model="profileForm.email" type="email" label="E-Mail" :error="profileErrors.email" autocomplete="email" />

          <UiInput
            v-if="auth.isMember"
            v-model="profileForm.address"
            label="Adresse"
            :error="profileErrors.address"
            autocomplete="street-address"
            placeholder="z. B. Musterstraße 1, 1010 Wien"
          />

          <UiButton :loading="savingProfile" @click="saveProfile">Profil speichern</UiButton>
        </section>

        <!-- Newsletter -->
        <section class="account-section">
          <h2 class="account-section__title">Newsletter</h2>
          <div class="account-section__toggle">
            <input v-model="newsletterOptIn" type="checkbox" class="styled-checkbox" id="newsletter-switch" @change="saveNewsletter" />
            <label for="newsletter-switch">Newsletter abonnieren</label>
          </div>
          <div v-if="newsletterMsg" class="alert alert-success">{{ newsletterMsg }}</div>
        </section>

        <!-- Passwort -->
        <section class="account-section">
          <h2 class="account-section__title">Passwort ändern</h2>

          <UiInput v-model="pwForm.current_password" type="password" label="Aktuelles Passwort" :error="pwErrors.current_password" autocomplete="current-password" />
          <UiInput v-model="pwForm.new_password" type="password" label="Neues Passwort" :error="pwErrors.new_password" autocomplete="new-password" />
          <UiInput v-model="pwForm.new_password_confirmation" type="password" label="Neues Passwort wiederholen" autocomplete="off" />

          <div v-if="pwError" class="alert alert-error">{{ pwError }}</div>
          <div v-if="pwSuccess" class="alert alert-success">{{ pwSuccess }}</div>

          <UiButton :loading="savingPw" @click="changePassword">Passwort speichern</UiButton>
        </section>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'

definePageMeta({ middleware: ['auth'] })

const api = useApi()
const auth = useAuthStore()

// Profile
const profileForm = reactive({ name: '', email: '', address: '' })
const profileErrors = reactive({ name: '', email: '', address: '' })
const profileSuccess = ref('')
const profileError = ref('')
const savingProfile = ref(false)

// Newsletter
const newsletterOptIn = ref(false)
const newsletterMsg = ref('')

// Password
const pwForm = ref({ current_password: '', new_password: '', new_password_confirmation: '' })
const pwErrors = ref({ current_password: '', new_password: '' })
const pwError = ref('')
const pwSuccess = ref('')
const savingPw = ref(false)

onMounted(() => {
  profileForm.name = auth.user?.name ?? ''
  profileForm.email = auth.user?.email ?? ''
  profileForm.address = auth.user?.address ?? ''
  newsletterOptIn.value = auth.user?.newsletter_opt_in ?? false
})

async function saveProfile() {
  Object.keys(profileErrors).forEach((k) => ((profileErrors as Record<string, string>)[k] = ''))
  profileSuccess.value = ''
  profileError.value = ''
  savingProfile.value = true

  try {
    const payload: Record<string, string> = {
      name: profileForm.name,
      email: profileForm.email,
    }
    if (auth.isMember) payload.address = profileForm.address

    const data = await api.patch<{ user: typeof auth.user; message: string }>('/account', payload)
    if (data.user) auth.setUser(data.user)
    profileSuccess.value = 'Profil gespeichert.'
  } catch (err: unknown) {
    const e = err as { errors?: Record<string, string[]>; message?: string }
    if (e.errors) {
      Object.entries(e.errors).forEach(([k, msgs]) => {
        if (k in profileErrors) (profileErrors as Record<string, string>)[k] = msgs[0] ?? ''
      })
    } else {
      profileError.value = e.message ?? 'Ein Fehler ist aufgetreten.'
    }
  } finally {
    savingProfile.value = false
  }
}

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

<style lang="scss" scoped>
$hero-bg: #0F0E0C;
$nav-height: 3.5rem;
$amber: #D4921E;
$amber-glow: rgba(212, 146, 30, 0.15);
$hero-text: #EEE8DF;
$hero-muted: rgba(238, 232, 223, 0.55);
$surface: rgba(255, 255, 255, 0.04);
$border: rgba(238, 232, 223, 0.1);

.page-hero {
  position: relative;
  background: $hero-bg;
  padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem;
  overflow: hidden;

  &__backdrop { position: absolute; inset: 0; pointer-events: none; }
  &__glow { position: absolute; width: 400px; height: 400px; top: -120px; left: -60px; border-radius: 50%; filter: blur(90px); background: $amber-glow; }
  &__dots { position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 24px 24px; mask-image: radial-gradient(ellipse 80% 100% at 30% 50%, black 20%, transparent 100%); }

  &__body {
    position: relative;
    z-index: 1;
    max-width: 1100px;
    margin: 0 auto;
  }

  &__back {
    display: inline-block;
    font-size: 0.8rem;
    color: $hero-muted;
    text-decoration: none;
    margin-bottom: 0.75rem;
    transition: color 0.15s;

    &:hover { color: $hero-text; }
  }

  &__eyebrow { font-size: 0.78rem; font-weight: 600; color: $amber; letter-spacing: 0.02em; margin-bottom: 0.4rem; }
  &__title { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0; }
}

.account-content {
  background: $hero-bg;
  min-height: calc(100vh - 10rem);
  padding: 2.5rem 1.5rem 4rem;

  &__inner {
    max-width: 560px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
  }
}

.account-section {
  background: $surface;
  border: 1px solid $border;
  border-radius: 16px;
  padding: 1.75rem 2rem;

  &__title {
    font-size: 0.95rem;
    font-weight: 700;
    color: $hero-text;
    margin-bottom: 1.25rem;
    letter-spacing: -0.02em;
  }

  &__toggle {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;

    :deep(.styled-checkbox + label) {
      color: $hero-muted;
      font-size: 0.9rem;
    }
  }
}
</style>
