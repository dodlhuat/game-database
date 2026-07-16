<template>
  <div class="upgrade-page" data-theme="dark">
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <p class="page-hero__eyebrow">{{ $t('pages.upgrade.membership') }}</p>
        <h1 class="page-hero__title">{{ $t('pages.upgrade.title') }}</h1>
        <p class="page-hero__sub">{{ $t('pages.upgrade.sub') }}</p>
      </div>
    </section>

    <div class="upgrade-content">
      <div class="upgrade-content__inner">
        <div v-if="auth.isMember" class="already-member">
          <div class="already-member__icon">✓</div>
          <h2 class="already-member__title">{{ $t('pages.upgrade.already_member') }}</h2>
          <p class="already-member__text">
            Deine Mitgliedschaft ist gültig bis
            <strong>{{ formatDate(auth.user?.membership_expires_at) }}</strong
            >.
          </p>
          <NuxtLink to="/dashboard" class="btn-secondary">Zum Dashboard</NuxtLink>
        </div>

        <div v-else-if="auth.isSupporter" class="already-member">
          <div class="already-member__icon">
            <span class="icon icon-support" />
          </div>
          <h2 class="already-member__title">{{ $t('pages.upgrade.already_supporter_title') }}</h2>
          <p class="already-member__text">
            {{ $t('pages.upgrade.already_supporter_text') }}
            <strong>{{ formatDate(auth.user?.membership_expires_at) }}</strong
            >.
          </p>

          <div class="activate-full-card">
            <h3 class="activate-full-card__title">{{ $t('pages.upgrade.activate_full_title') }}</h3>
            <p class="activate-full-card__text">{{ $t('pages.upgrade.activate_full_text') }}</p>
            <div v-if="activateError" class="alert alert-error">{{ activateError }}</div>
            <UiButton :loading="activateLoading" @click="activateFull">{{
              $t('pages.upgrade.activate_full_btn')
            }}</UiButton>
            <p class="activate-full-card__note">{{ $t('pages.upgrade.activate_full_note') }}</p>
          </div>
        </div>

        <div v-else class="upgrade-layout">
          <!-- Tier selector -->
          <div class="tier-selector">
            <p class="tier-selector__label">{{ $t('pages.upgrade.select_tier') }}</p>
            <div class="tier-cards">
              <button
                type="button"
                class="tier-card"
                :class="{ 'tier-card--active': tier === 'MEMBER' }"
                @click="tier = 'MEMBER'"
              >
                <span class="tier-card__icon"><span class="icon icon-cases" /></span>
                <span class="tier-card__name">{{ $t('pages.upgrade.tier_member_name') }}</span>
                <span class="tier-card__tagline">{{
                  $t('pages.upgrade.tier_member_tagline')
                }}</span>
              </button>
              <button
                type="button"
                class="tier-card"
                :class="{ 'tier-card--active': tier === 'SUPPORTER' }"
                @click="tier = 'SUPPORTER'"
              >
                <span class="tier-card__icon"><span class="icon icon-support" /></span>
                <span class="tier-card__name">{{ $t('pages.upgrade.tier_supporter_name') }}</span>
                <span class="tier-card__tagline">{{
                  $t('pages.upgrade.tier_supporter_tagline')
                }}</span>
              </button>
            </div>
          </div>

          <div class="upgrade-columns">
            <!-- Benefits -->
            <div class="benefits-panel">
              <h2 class="benefits-panel__title">{{ $t('pages.upgrade.benefits_title') }}</h2>

              <ul class="benefit-list">
                <template v-if="tier === 'MEMBER'">
                  <li class="benefit-list__item">
                    <span class="benefit-list__icon benefit-list__icon--token" aria-hidden="true"
                      >◈</span
                    >
                    <div>{{ $t('pages.upgrade.tier_member_benefit_tokens') }}</div>
                  </li>
                  <li class="benefit-list__item">
                    <span class="benefit-list__icon" aria-hidden="true">
                      <span class="icon icon-article" />
                    </span>
                    <div>{{ $t('pages.upgrade.tier_member_benefit_access') }}</div>
                  </li>
                </template>
                <template v-else>
                  <li class="benefit-list__item">
                    <span class="benefit-list__icon" aria-hidden="true">
                      <span class="icon icon-support" />
                    </span>
                    <div>{{ $t('pages.upgrade.tier_supporter_benefit_support') }}</div>
                  </li>
                  <li class="benefit-list__item">
                    <span class="benefit-list__icon" aria-hidden="true">
                      <span class="icon icon-block" />
                    </span>
                    <div>{{ $t('pages.upgrade.tier_supporter_benefit_no_tokens') }}</div>
                  </li>
                  <li class="benefit-list__item">
                    <span class="benefit-list__icon" aria-hidden="true">
                      <span class="icon icon-sync" />
                    </span>
                    <div>{{ $t('pages.upgrade.tier_supporter_benefit_upgrade') }}</div>
                  </li>
                </template>
                <li class="benefit-list__item">
                  <span class="benefit-list__icon" aria-hidden="true">
                    <span class="icon icon-calendar_today" />
                  </span>
                  <div>{{ $t('pages.upgrade.benefit_events') }}</div>
                </li>
                <li class="benefit-list__item">
                  <span class="benefit-list__icon" aria-hidden="true">
                    <span class="icon icon-mail" />
                  </span>
                  <div>{{ $t('pages.upgrade.benefit_newsletter') }}</div>
                </li>
                <li class="benefit-list__item">
                  <span class="benefit-list__icon" aria-hidden="true">
                    <span class="icon icon-refresh" />
                  </span>
                  <div>{{ $t('pages.upgrade.benefit_duration') }}</div>
                </li>
              </ul>

              <p class="benefits-panel__note">{{ $t('pages.upgrade.payment_note') }}</p>
            </div>

            <!-- Form -->
            <div class="upgrade-form">
              <h2 class="upgrade-form__title">{{ $t('pages.upgrade.form_title') }}</h2>
              <p class="upgrade-form__sub">{{ $t('pages.upgrade.form_sub') }}</p>

              <div v-if="error" class="alert alert-error">{{ error }}</div>

              <UiInput
                v-model="address"
                :label="$t('pages.upgrade.address_label')"
                :error="addressError"
                :placeholder="$t('pages.upgrade.address_placeholder')"
                autocomplete="street-address"
              />

              <UiButton :loading="loading" @click="upgrade">{{
                tier === 'MEMBER'
                  ? $t('pages.upgrade.submit_member')
                  : $t('pages.upgrade.submit_supporter')
              }}</UiButton>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

definePageMeta({ middleware: ['auth'] })

const auth = useAuthStore()
const api = useApi()
const loading = ref(false)
const error = ref('')
const address = ref('')
const addressError = ref('')
const tier = ref<'MEMBER' | 'SUPPORTER'>('MEMBER')
const activateLoading = ref(false)
const activateError = ref('')

// Admins cannot become members
if (auth.isAdmin) {
  await navigateTo('/dashboard')
}

function formatDate(dateStr: string | null | undefined): string {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('de-AT', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

async function upgrade() {
  addressError.value = ''
  error.value = ''

  if (!address.value.trim()) {
    addressError.value = 'Bitte gib deine Adresse ein.'
    return
  }

  loading.value = true
  try {
    const endpoint =
      tier.value === 'MEMBER' ? '/membership/upgrade' : '/membership/upgrade-supporter'
    const data = await api.post<{ user: typeof auth.user }>(endpoint, {
      address: address.value,
    })
    if (data.user) auth.setUser(data.user)
    await navigateTo('/dashboard')
  } catch (err: unknown) {
    const e = err as { errors?: Record<string, string[]>; message?: string }
    if (e.errors?.address) {
      addressError.value = e.errors.address[0] ?? ''
    } else {
      error.value = e.message ?? 'Ein Fehler ist aufgetreten.'
    }
  } finally {
    loading.value = false
  }
}

async function activateFull() {
  activateError.value = ''
  activateLoading.value = true
  try {
    const data = await api.post<{ user: typeof auth.user }>('/membership/activate-full')
    if (data.user) auth.setUser(data.user)
    await navigateTo('/dashboard')
  } catch (err: unknown) {
    const e = err as { message?: string }
    activateError.value = e.message ?? 'Ein Fehler ist aufgetreten.'
  } finally {
    activateLoading.value = false
  }
}
</script>

<style lang="scss" scoped>
$hero-bg: #0f0e0c;
$nav-height: 3.5rem;
$amber-glow: rgba(212, 146, 30, 0.15);
$hero-text: #eee8df;
$hero-muted: rgba(238, 232, 223, 0.72);
$surface: rgba(255, 255, 255, 0.04);
$border: rgba(238, 232, 223, 0.1);

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
    right: -60px;
    border-radius: 50%;
    filter: blur(90px);
    background: $amber-glow;
  }
  &__dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
    background-size: 24px 24px;
    mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%);
  }
  &__body {
    position: relative;
    z-index: 1;
    max-width: 1100px;
    margin: 0 auto;
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
    margin: 0 0 0.5rem;
  }
  &__sub {
    font-size: 0.95rem;
    color: $hero-muted;
    margin: 0;
  }
}

.upgrade-content {
  background: $hero-bg;
  min-height: calc(100vh - 10rem);
  padding: 2.5rem 1.5rem 4rem;

  &__inner {
    max-width: 900px;
    margin: 0 auto;
  }
}

.already-member {
  text-align: center;
  padding: 3rem 2rem;
  background: $surface;
  border: 1px solid $border;
  border-radius: 16px;
  max-width: 480px;
  margin: 0 auto;

  &__icon {
    font-size: 3rem;
    color: $amber;
    margin-bottom: 1rem;
  }
  &__title {
    font-size: 1.5rem;
    font-weight: 700;
    color: $hero-text;
    margin-bottom: 0.5rem;
  }
  &__text {
    color: $hero-muted;
    margin-bottom: 1.5rem;
  }
}

.activate-full-card {
  background: rgba(212, 146, 30, 0.06);
  border: 1px solid rgba(212, 146, 30, 0.25);
  border-radius: 12px;
  padding: 1.5rem;
  text-align: left;

  &__title {
    font-size: 1rem;
    font-weight: 700;
    color: $hero-text;
    margin: 0 0 0.4rem;
  }
  &__text {
    font-size: 0.875rem;
    color: $hero-muted;
    margin: 0 0 1rem;
  }
  &__note {
    font-size: 0.78rem;
    color: rgba(238, 232, 223, 0.5);
    margin: 0.75rem 0 0;
  }
}

.upgrade-layout {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.tier-selector {
  &__label {
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    color: rgba(238, 232, 223, 0.5);
    text-transform: uppercase;
    margin: 0 0 0.75rem;
  }
}

.tier-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;

  @media (max-width: 680px) {
    grid-template-columns: 1fr;
  }
}

.tier-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.35rem;
  text-align: left;
  background: $surface;
  border: 1px solid $border;
  border-radius: 14px;
  padding: 1.25rem 1.35rem;
  cursor: pointer;
  font: inherit;
  color: inherit;
  transition:
    border-color 0.18s,
    background 0.18s,
    transform 0.18s;

  &:hover {
    border-color: rgba(212, 146, 30, 0.4);
    transform: translateY(-2px);
  }

  &__icon {
    color: $amber;
    font-size: 1.15rem;
    margin-bottom: 0.15rem;
  }
  &__name {
    font-size: 0.95rem;
    font-weight: 700;
    color: $hero-text;
  }
  &__tagline {
    font-size: 0.8rem;
    color: $hero-muted;
  }

  &--active {
    background: rgba(212, 146, 30, 0.08);
    border-color: $amber;

    .tier-card__icon {
      color: $amber;
    }
  }
}

.upgrade-columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  align-items: start;

  @media (max-width: 680px) {
    grid-template-columns: 1fr;
  }
}

.benefits-panel {
  background: $surface;
  border: 1px solid $border;
  border-radius: 16px;
  padding: 2rem;

  &__title {
    font-size: 1rem;
    font-weight: 700;
    color: $hero-text;
    margin-bottom: 1.5rem;
    letter-spacing: -0.02em;
  }

  &__note {
    font-size: 0.78rem;
    color: rgba(238, 232, 223, 0.35);
    margin-top: 1.5rem;
    text-align: center;
  }
}

.benefit-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 1rem;

  &__item {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    color: $hero-muted;
    font-size: 0.9rem;
    line-height: 1.5;

    strong {
      color: $hero-text;
    }
  }

  &__icon {
    color: $amber;
    font-size: 1rem;
    margin-top: 0.1rem;
    flex-shrink: 0;
    width: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;

    &--token {
      font-size: 0.85rem;
    }

    .icon {
      font-size: 1rem;
    }
  }
}

.upgrade-form {
  background: $surface;
  border: 1px solid $border;
  border-radius: 16px;
  padding: 2rem;

  &__title {
    font-size: 1rem;
    font-weight: 700;
    color: $hero-text;
    margin-bottom: 0.3rem;
    letter-spacing: -0.02em;
  }

  &__sub {
    font-size: 0.85rem;
    color: $hero-muted;
    margin-bottom: 1.5rem;
  }

  :deep(.input-wrapper) {
    margin-bottom: 1.25rem;
  }
}

.btn-secondary {
  display: inline-block;
  padding: 0.6rem 1.25rem;
  border: 1px solid $border;
  border-radius: 8px;
  color: $hero-text;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  transition: border-color 0.2s;

  &:hover {
    border-color: $amber;
  }
}
</style>
