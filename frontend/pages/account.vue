<template>
  <div class="account-page">
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <NuxtLink to="/dashboard" class="page-hero__back">← Dashboard</NuxtLink>
        <p class="page-hero__eyebrow">{{ $t('account.settings') }}</p>
        <h1 class="page-hero__title">{{ $t('account.my_account') }}</h1>
      </div>
    </section>

    <div class="account-content">
      <div class="account-content__inner">
        <!-- Profil -->
        <section class="account-section">
          <h2 class="account-section__title">{{ $t('account.profile_title') }}</h2>

          <div v-if="profileSuccess" class="alert alert-success">{{ profileSuccess }}</div>
          <div v-if="profileError" class="alert alert-error">{{ profileError }}</div>

          <div class="account-section__fields">
            <UiInput
              v-model="profileForm.name"
              :label="$t('auth.name')"
              :error="profileErrors.name"
              autocomplete="name"
            />
            <UiInput
              v-model="profileForm.email"
              type="email"
              :label="$t('auth.email')"
              :error="profileErrors.email"
              autocomplete="email"
            />
            <UiInput
              v-if="auth.isMember"
              v-model="profileForm.address"
              :label="$t('account.address')"
              :error="profileErrors.address"
              autocomplete="street-address"
              :placeholder="$t('account.address_placeholder')"
            />
            <UiInput
              v-if="auth.isMember"
              v-model="profileForm.phone"
              type="tel"
              :label="$t('account.phone')"
              autocomplete="tel"
              :placeholder="$t('account.phone_placeholder')"
            />
            <UiDatePicker
              v-if="auth.isMember"
              v-model="profileForm.date_of_birth"
              :label="$t('account.date_of_birth')"
              :max-date="new Date()"
            />
          </div>

          <UiButton :loading="savingProfile" @click="saveProfile">{{
            $t('account.profile_save')
          }}</UiButton>
        </section>

        <!-- Newsletter -->
        <section class="account-section">
          <h2 class="account-section__title">{{ $t('account.newsletter_title') }}</h2>
          <UiSwitch
            v-model="newsletterOptIn"
            :label="$t('account.newsletter_subscribe')"
            @change="saveNewsletter"
          />
          <div v-if="newsletterMsg" class="alert alert-success" style="margin-top: 0.75rem">
            {{ newsletterMsg }}
          </div>
        </section>

        <!-- Passwort -->
        <section class="account-section">
          <h2 class="account-section__title">{{ $t('account.password_title') }}</h2>

          <div class="account-section__fields">
            <UiInput
              v-model="pwForm.current_password"
              type="password"
              :label="$t('account.password_current')"
              :error="pwErrors.current_password"
              autocomplete="current-password"
            />
            <UiInput
              v-model="pwForm.new_password"
              type="password"
              :label="$t('account.password_new')"
              :error="pwErrors.new_password"
              autocomplete="new-password"
            />
            <UiInput
              v-model="pwForm.new_password_confirmation"
              type="password"
              :label="$t('account.password_confirm_new')"
              autocomplete="off"
            />
          </div>

          <div v-if="pwError" class="alert alert-error">{{ pwError }}</div>
          <div v-if="pwSuccess" class="alert alert-success">{{ pwSuccess }}</div>

          <UiButton :loading="savingPw" @click="changePassword">{{
            $t('account.password_save')
          }}</UiButton>
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
const { t } = useI18n()

// Profile
const profileForm = reactive({ name: '', email: '', address: '', phone: '', date_of_birth: '' })
const profileErrors = reactive({ name: '', email: '', address: '', phone: '', date_of_birth: '' })
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
  profileForm.phone = auth.user?.phone ?? ''
  profileForm.date_of_birth = auth.user?.date_of_birth ?? ''
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
    if (auth.isMember) {
      payload.address = profileForm.address
      payload.phone = profileForm.phone
      payload.date_of_birth = profileForm.date_of_birth
    }

    const data = await api.patch<{ user: typeof auth.user; message: string }>('/account', payload)
    if (data.user) auth.setUser(data.user)
    profileSuccess.value = t('account.profile_saved')
  } catch (err: unknown) {
    const e = err as { errors?: Record<string, string[]>; message?: string }
    if (e.errors) {
      Object.entries(e.errors).forEach(([k, msgs]) => {
        if (k in profileErrors) (profileErrors as Record<string, string>)[k] = msgs[0] ?? ''
      })
    } else {
      profileError.value = e.message ?? t('common.error.generic')
    }
  } finally {
    savingProfile.value = false
  }
}

async function saveNewsletter() {
  newsletterMsg.value = ''
  try {
    await api.patch('/account', { newsletter_opt_in: newsletterOptIn.value })
    newsletterMsg.value = newsletterOptIn.value
      ? t('account.newsletter_subscribed')
      : t('account.newsletter_unsubscribed')
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
    pwErrors.value.new_password = t('account.password_mismatch')
    return
  }

  savingPw.value = true
  try {
    await api.patch('/account', {
      current_password: pwForm.value.current_password,
      new_password: pwForm.value.new_password,
      new_password_confirmation: pwForm.value.new_password_confirmation,
    })
    pwSuccess.value = t('account.password_changed')
    pwForm.value = { current_password: '', new_password: '', new_password_confirmation: '' }
  } catch (err: unknown) {
    const e = err as { errors?: Record<string, string[]>; message?: string }
    if (e.errors) {
      Object.entries(e.errors).forEach(([k, msgs]) => {
        if (k in pwErrors.value) (pwErrors.value as Record<string, string>)[k] = msgs[0]
      })
    } else {
      pwError.value = e.message ?? t('common.error.generic')
    }
  } finally {
    savingPw.value = false
  }
}
</script>

<style lang="scss" scoped>
$hero-bg: #0f0e0c;
$nav-height: 64px;
$amber-glow: rgba(212, 146, 30, 0.15);
$hero-text: #eee8df;
$hero-muted: rgba(238, 232, 223, 0.72);

// ─── Page shell ───────────────────────────────────────────────────
.account-page {
  min-height: 100vh;
  background: var(--background);
}

// ─── Hero (always dark) ───────────────────────────────────────────
.page-hero {
  position: relative;
  background: $hero-bg;
  padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem;
  overflow: hidden;

  &__backdrop {
    position: absolute;
    inset: 0;
    pointer-events: none;
  }
  &__glow {
    position: absolute;
    width: 400px;
    height: 400px;
    top: -120px;
    left: -60px;
    border-radius: 50%;
    filter: blur(90px);
    background: $amber-glow;
  }
  &__dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
    background-size: 24px 24px;
    mask-image: radial-gradient(ellipse 80% 100% at 30% 50%, black 20%, transparent 100%);
  }

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
    &:hover {
      color: $hero-text;
    }
  }

  &__eyebrow {
    font-size: 0.78rem;
    font-weight: 600;
    color: $amber;
    letter-spacing: 0.02em;
    margin-bottom: 0.4rem;
  }
  &__title {
    font-size: clamp(1.5rem, 3vw, 2.25rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    color: $hero-text;
    margin: 0;
  }
}

// ─── Content (theme-aware) ────────────────────────────────────────
.account-content {
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
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 16px;
  padding: 1.75rem 2rem;

  &__title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--primary-text);
    margin-bottom: 1.25rem;
    letter-spacing: -0.02em;
  }

  &__fields {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.25rem;
  }

  :deep(.button) {
    margin-top: 0.25rem;
  }
}
</style>
