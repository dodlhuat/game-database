<template>
  <div class="tokens-page" data-theme="dark">
    <AppNav />

    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <p class="page-hero__eyebrow">Mein Konto</p>
        <h1 class="page-hero__title">Token aufladen</h1>
        <p class="page-hero__sub">Token werden für Ausleihen verwendet. Aktuelles Guthaben: <strong class="highlight">{{ auth.user?.tokens ?? 0 }} Token</strong></p>
      </div>
    </section>

    <div class="tokens-content">
      <div class="tokens-content__inner">

        <div v-if="!auth.isMember" class="no-member">
          <p>Nur Mitglieder können Token kaufen.</p>
          <NuxtLink to="/upgrade">Jetzt Mitglied werden →</NuxtLink>
        </div>

        <template v-else>
          <div v-if="success" class="alert alert-success">{{ success }}</div>
          <div v-if="error" class="alert alert-error">{{ error }}</div>

          <div class="token-grid">
            <div
              v-for="pkg in tokenPackages"
              :key="pkg.amount"
              class="token-card"
              :class="{ 'token-card--featured': pkg.featured }"
            >
              <div v-if="pkg.featured" class="token-card__badge">Beliebt</div>
              <div class="token-card__amount">{{ pkg.amount }}</div>
              <div class="token-card__label">Token</div>
              <UiButton
                :loading="loadingAmount === pkg.amount"
                :disabled="!!loadingAmount"
                @click="addTokens(pkg.amount)"
              >
                Hinzufügen
              </UiButton>
            </div>
          </div>

          <div class="token-info">
            <h3 class="token-info__title">Token-Kosten</h3>
            <ul class="token-info__list">
              <li><span class="token-info__cost">2 Token</span> Spiel ausleihen</li>
              <li><span class="token-info__cost">3 Token</span> Paket ausleihen</li>
              <li><span class="token-info__cost">1 Token</span> Ausleihe verlängern</li>
            </ul>
          </div>
        </template>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

definePageMeta({ middleware: ['auth'] })

const auth = useAuthStore()
const api = useApi()
const loadingAmount = ref<number | null>(null)
const success = ref('')
const error = ref('')

const tokenPackages = [
  { amount: 20, featured: false },
  { amount: 30, featured: true },
  { amount: 40, featured: false },
]

async function addTokens(amount: number) {
  loadingAmount.value = amount
  success.value = ''
  error.value = ''
  try {
    const data = await api.post<{ user: typeof auth.user; message: string }>('/tokens/add', { amount })
    if (data.user) auth.setUser(data.user)
    success.value = data.message
  } catch (err: unknown) {
    const e = err as { message?: string }
    error.value = e.message ?? 'Ein Fehler ist aufgetreten.'
  } finally {
    loadingAmount.value = null
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
$surface-hover: rgba(255, 255, 255, 0.07);
$border: rgba(238, 232, 223, 0.1);
$border-amber: rgba(212, 146, 30, 0.4);

.page-hero { position: relative; background: $hero-bg; padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem; overflow: hidden; &__backdrop { position: absolute; inset: 0; pointer-events: none; } &__glow { position: absolute; width: 400px; height: 400px; top: -120px; right: -60px; border-radius: 50%; filter: blur(90px); background: $amber-glow; } &__dots { position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 24px 24px; mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%); } &__body { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; } &__eyebrow { font-size: 0.78rem; font-weight: 600; color: $amber; letter-spacing: 0.02em; margin-bottom: 0.4rem; } &__title { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0 0 0.5rem; } &__sub { font-size: 0.95rem; color: $hero-muted; margin: 0; } }

.highlight { color: $amber; }

.tokens-content {
  background: $hero-bg;
  min-height: calc(100vh - 10rem);
  padding: 2.5rem 1.5rem 4rem;

  &__inner {
    max-width: 700px;
    margin: 0 auto;
  }
}

.no-member {
  color: $hero-muted;
  a { color: $amber; font-weight: 600; }
}

.token-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 2.5rem;

  @media (max-width: 500px) {
    grid-template-columns: 1fr;
  }
}

.token-card {
  position: relative;
  background: $surface;
  border: 1px solid $border;
  border-radius: 14px;
  padding: 1.75rem 1.25rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  transition: border-color 0.2s;

  &:hover { border-color: rgba(212, 146, 30, 0.25); }

  &--featured {
    border-color: $border-amber;
    background: rgba(212, 146, 30, 0.06);
  }

  &__badge {
    position: absolute;
    top: -0.6rem;
    left: 50%;
    transform: translateX(-50%);
    background: $amber;
    color: #0F0E0C;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.2rem 0.6rem;
    border-radius: 20px;
    letter-spacing: 0.04em;
    white-space: nowrap;
  }

  &__amount {
    font-size: 2.5rem;
    font-weight: 800;
    letter-spacing: -0.04em;
    color: $hero-text;
    line-height: 1;
  }

  &__label {
    font-size: 0.8rem;
    font-weight: 600;
    color: $amber;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-bottom: 0.5rem;
  }
}

.token-info {
  border-top: 1px solid $border;
  padding-top: 1.5rem;

  &__title {
    font-size: 0.875rem;
    font-weight: 700;
    color: $hero-text;
    margin-bottom: 0.75rem;
  }

  &__list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    font-size: 0.875rem;
    color: $hero-muted;
  }

  &__cost {
    display: inline-block;
    min-width: 5rem;
    font-weight: 600;
    color: $amber;
  }
}
</style>
