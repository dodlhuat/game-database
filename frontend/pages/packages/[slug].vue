<template>
  <div class="package-detail">
    <AppNav />

    <div v-if="loading" class="detail-state">
      <div class="spinner" />
    </div>

    <div v-else-if="!pkg" class="detail-state">
      <p class="detail-state__title">{{ $t('pages.package.not_found') }}</p>
      <NuxtLink to="/packages" class="detail-state__back">{{ $t('btn.to_packages') }}</NuxtLink>
    </div>

    <template v-else>
      <!-- ── Hero ──────────────────────────────────────────────── -->
      <section class="page-hero">
        <div class="page-hero__backdrop" aria-hidden="true">
          <div class="page-hero__glow" />
          <div class="page-hero__dots" />
        </div>
        <div class="page-hero__body">
          <NuxtLink to="/packages" class="page-hero__back">{{ $t('btn.to_packages') }}</NuxtLink>
          <div class="page-hero__meta-row">
            <span class="page-hero__type" :class="pkg.type === 'CURATED' ? 'page-hero__type--curated' : 'page-hero__type--category'">
              {{ pkg.type === 'CURATED' ? $t('pages.package.type_curated') : $t('pages.package.type_category') }}
            </span>
            <span v-if="pkg.category" class="page-hero__cat">{{ pkg.category.name }}</span>
          </div>
          <h1 class="page-hero__title">{{ pkg.name }}</h1>
          <p v-if="pkg.description" class="page-hero__desc">{{ pkg.description }}</p>
        </div>
      </section>

      <!-- ── Info ──────────────────────────────────────────────── -->
      <section class="detail">
        <div class="detail__inner">
          <div class="detail__stats">
            <div class="detail__stat">
              <span class="detail__stat-label">{{ $t('pages.package.games_in_package') }}</span>
              <span class="detail__stat-value">{{ pkg.games_count ?? pkg.games?.length ?? 0 }}</span>
            </div>
            <div v-if="pkg.category" class="detail__stat">
              <span class="detail__stat-label">{{ $t('pages.package.type') }}</span>
              <span class="detail__stat-value">{{ pkg.category.name }}</span>
            </div>
            <div class="detail__stat">
              <span class="detail__stat-label">{{ $t('pages.package.type') }}</span>
              <span class="detail__stat-value">{{ pkg.type === 'CURATED' ? $t('pages.package.type_curated') : $t('pages.package.type_category') }}</span>
            </div>
          </div>

          <!-- Spieleliste -->
          <div v-if="pkg.games?.length" class="game-list">
            <h2 class="game-list__title">{{ $t('pages.package.games_included') }}</h2>
            <ul class="game-list__items">
              <li v-for="game in pkg.games" :key="game.id" class="game-list__item">
                <NuxtLink :to="`/games/${game.slug}`" class="game-list__link">
                  {{ game.title }}
                  <span
                    v-if="auth.isLoggedIn && game.available_copies_count !== undefined"
                    class="game-list__avail"
                    :class="game.available_copies_count > 0 ? 'game-list__avail--yes' : 'game-list__avail--no'"
                  >
                    {{ game.available_copies_count > 0 ? $t('pages.package.game_available') : $t('pages.package.game_loaned') }}
                  </span>
                  <span class="game-list__arrow" aria-hidden="true">→</span>
                </NuxtLink>
              </li>
            </ul>
          </div>

          <!-- Borrow Package CTA -->
          <div class="detail__cta">
            <!-- Not logged in -->
            <template v-if="!auth.isLoggedIn">
              <p class="detail__cta-text">{{ $t('pages.package.cta_login') }}</p>
              <NuxtLink to="/login" class="detail__btn">{{ $t('btn.sign_in') }}</NuxtLink>
            </template>

            <!-- Logged in, not a member -->
            <template v-else-if="!auth.isMember">
              <p class="detail__cta-text">{{ $t('pages.package.cta_not_member') }}</p>
              <NuxtLink to="/upgrade" class="detail__btn">{{ $t('btn.become_member') }}</NuxtLink>
            </template>

            <!-- Member, package available -->
            <template v-else-if="pkg.available">
              <div v-if="loanError" class="alert alert-error" style="margin-bottom: 0.75rem">{{ loanError }}</div>
              <button
                v-if="(auth.user?.tokens ?? 0) >= 3"
                class="detail__btn detail__btn--primary"
                :disabled="loaning"
                @click="borrowPackage"
              >
                {{ loaning ? $t('pages.package.borrowing') : $t('pages.package.borrow') }}
              </button>
              <NuxtLink v-else to="/tokens" class="detail__btn">{{ $t('btn.load_tokens') }}</NuxtLink>
            </template>

            <!-- Member, package unavailable -->
            <template v-else>
              <p class="detail__cta-text">{{ $t('pages.package.cta_unavailable') }}</p>
            </template>
          </div>
        </div>
      </section>
    </template>

    <!-- ── Footer ──────────────────────────────────────────────── -->
    <footer class="l-footer">
      <div class="l-footer__inner">
        <div class="l-footer__brand">
          <span class="l-footer__hex" aria-hidden="true">⬡</span>
          <span class="l-footer__name">AUA</span>
        </div>
        <nav class="l-footer__nav" aria-label="Footer-Navigation">
          <NuxtLink to="/games" class="l-footer__link">{{ $t('nav.collection') }}</NuxtLink>
          <NuxtLink to="/packages" class="l-footer__link">{{ $t('nav.packages') }}</NuxtLink>
          <NuxtLink to="/terms" class="l-footer__link">{{ $t('nav.terms') }}</NuxtLink>
          <NuxtLink to="/privacy" class="l-footer__link">{{ $t('nav.privacy') }}</NuxtLink>
          <NuxtLink to="/cookies" class="l-footer__link">{{ $t('nav.cookies') }}</NuxtLink>
        </nav>
        <p class="l-footer__copy">{{ $t('common.copyright', { year }) }}</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import type { Package } from '~/composables/useGames'

const route = useRoute()
const { fetchPackage } = useGames()
const auth = useAuthStore()
const { t } = useI18n()

const api = useApi()
const loading = ref(true)
const loaning = ref(false)
const loanError = ref('')
const pkg = ref<Package | null>(null)
const year = new Date().getFullYear()

onMounted(async () => {
  try {
    const data = await fetchPackage(route.params.slug as string)
    pkg.value = data.data
  } catch {
    pkg.value = null
  } finally {
    loading.value = false
  }
})

async function borrowPackage() {
  if (!pkg.value) return
  loaning.value = true
  loanError.value = ''
  try {
    await api.post('/package-loans', { package_id: pkg.value.id })
    if (auth.user) auth.setUser({ ...auth.user, tokens: Math.max(0, auth.user.tokens - 3) })
    await navigateTo('/dashboard')
  } catch (err: unknown) {
    const e = err as { message?: string }
    loanError.value = e.message ?? t('common.error.generic')
  } finally {
    loaning.value = false
  }
}

useHead(() => ({
  title: pkg.value ? `${pkg.value.name} — ${t('pages.packages.title')}` : t('pages.package.not_found'),
}))
</script>

<style lang="scss" scoped>
$hero-bg:       #0F0E0C;
$nav-height:    64px;

$amber-08:      rgba(212, 146, 30, 0.08);
$amber-25:      rgba(212, 146, 30, 0.25);
$amber-glow:    rgba(212, 146, 30, 0.20);

$hero-text:     #EEE8DF;
$hero-muted:    rgba(238, 232, 223, 0.72);
$hero-muted-50: rgba(238, 232, 223, 0.65);
$hero-divider:  rgba(238, 232, 223, 0.10);

.package-detail {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--background);
}

.detail-state {
  flex: 1;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  gap: 1rem;
  padding: calc(#{$nav-height} + 4rem) 1.5rem 4rem;

  &__title { font-size: 1.25rem; font-weight: 700; color: var(--primary-text); padding-bottom: 0; }
  &__back { font-size: 0.9rem; color: $amber; text-decoration: none; &:hover { text-decoration: underline; } }
}

// ─── Hero ─────────────────────────────────────────────────────────
.page-hero {
  position: relative;
  background: $hero-bg;
  padding: calc(#{$nav-height} + 3rem) 1.5rem 3rem;
  overflow: hidden;

  &__backdrop { position: absolute; inset: 0; pointer-events: none; }

  &__glow {
    position: absolute;
    width: 600px; height: 400px;
    top: -100px; right: -100px;
    border-radius: 50%;
    filter: blur(100px);
    background: $amber-glow;
  }

  &__dots {
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px);
    background-size: 24px 24px;
    mask-image: radial-gradient(ellipse 100% 100% at 80% 50%, black 10%, transparent 80%);
  }

  &__body { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; }

  &__back {
    display: inline-block;
    font-size: 0.85rem; font-weight: 500;
    color: $hero-muted; text-decoration: none; margin-bottom: 1.5rem;
    transition: color 0.2s;
    &:hover { color: $hero-text; }
  }

  &__meta-row { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; flex-wrap: wrap; }

  &__type {
    font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
    padding: 0.2rem 0.55rem; border-radius: 999px;
  }
  &__type--curated  { background: $amber-08; color: $amber; border: 1px solid $amber-25; }
  &__type--category { background: rgba(99,102,241,0.1); color: #818cf8; border: 1px solid rgba(99,102,241,0.25); }

  &__cat {
    font-size: 0.72rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase;
    color: $hero-muted;
  }

  &__title {
    font-size: clamp(1.75rem, 4vw, 3rem);
    font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0 0 0.75rem;
  }

  &__desc {
    font-size: 1rem; line-height: 1.7; color: $hero-muted; max-width: 640px; padding-bottom: 0;
  }
}

// ─── Detail ───────────────────────────────────────────────────────
.detail {
  flex: 1;
  padding: 3rem 1.5rem 4rem;

  &__inner { max-width: 700px; margin: 0 auto; display: flex; flex-direction: column; gap: 2rem; }

  &__stats {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1px;
    background: var(--divider);
    border: 1px solid var(--divider);
    border-radius: 12px;
    overflow: hidden;
  }

  &__stat {
    display: flex; flex-direction: column; gap: 0.25rem;
    padding: 1rem 1.25rem;
    background: var(--secondary-background);
  }

  &__stat-label {
    font-size: 0.72rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--secondary-text);
  }

  &__stat-value {
    font-size: 1.05rem; font-weight: 700; color: var(--primary-text); letter-spacing: -0.01em;
  }

  &__cta {
    padding: 1.5rem;
    background: var(--secondary-background);
    border: 1px solid var(--divider);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
  }

  &__cta-text {
    font-size: 0.9rem; color: var(--secondary-text); padding-bottom: 0;
  }

  &__btn {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 0.6rem 1.25rem;
    background: $amber; color: #0F0E0C;
    font-size: 0.875rem; font-weight: 700; font-family: inherit;
    border: none; border-radius: 8px; text-decoration: none; cursor: pointer;
    transition: opacity 0.2s;
    white-space: nowrap;
    &:hover { opacity: 0.88; }
  }
}

// ─── Game List ────────────────────────────────────────────────────
.game-list {
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 12px;
  overflow: hidden;

  &__title {
    font-size: 0.78rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
    color: var(--secondary-text);
    padding: 0.9rem 1.25rem;
    border-bottom: 1px solid var(--divider);
    margin: 0;
  }

  &__items {
    list-style: none;
    margin: 0;
    padding: 0;
  }

  &__item {
    border-bottom: 1px solid var(--divider);
    &:last-child { border-bottom: none; }
  }

  &__link {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.75rem 1.25rem;
    font-size: 0.9rem; font-weight: 500;
    color: var(--primary-text); text-decoration: none;
    transition: background 0.15s, color 0.15s;

    &:hover {
      background: var(--background);
      color: var(--accent-text);
      .game-list__arrow { opacity: 1; }
    }
  }

  &__arrow {
    font-size: 0.8rem;
    color: var(--secondary-text);
    opacity: 0.4;
    transition: opacity 0.15s;
  }

  &__avail {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    padding: 0.1rem 0.4rem;
    border-radius: 999px;
    margin-left: auto;
    flex-shrink: 0;

    &--yes { background: #166534; color: #bbf7d0; }
    &--no  { background: #7c2d12; color: #fed7aa; }
  }
}

// ─── Footer ───────────────────────────────────────────────────────
.l-footer {
  background: $hero-bg; border-top: 1px solid $hero-divider; padding: 2.5rem 1.5rem;
  &__inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; flex-wrap: wrap; gap: 1.5rem; }
  &__brand { display: flex; align-items: center; gap: 0.4rem; flex-shrink: 0; }
  &__hex { font-size: 1.2rem; color: $amber; }
  &__name { font-size: 0.95rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em; }
  &__nav { display: flex; gap: 1.5rem; flex-wrap: wrap; flex: 1; justify-content: center; position: static; transform: none; width: auto; height: auto; @media (max-width: 640px) { justify-content: flex-start; } }
  &__link { font-size: 0.85rem; color: $hero-muted; text-decoration: none; transition: color 0.2s; &:hover { color: $hero-text; } }
  &__copy { font-size: 0.8rem; color: $hero-muted-50; margin-left: auto; padding-bottom: 0; @media (max-width: 640px) { margin-left: 0; width: 100%; } }
}
</style>
