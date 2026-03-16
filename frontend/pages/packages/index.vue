<template>
  <div class="packages-page">
    <AppNav />

    <!-- ── Page Header ─────────────────────────────────────────── -->
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <p class="page-hero__eyebrow">AUA</p>
        <h1 class="page-hero__title">Spielepakete</h1>
        <p class="page-hero__sub">Kuratierte Zusammenstellungen für jeden Anlass</p>
      </div>
    </section>

    <!-- ── Content ─────────────────────────────────────────────── -->
    <section class="catalog">
      <div class="catalog__inner">

        <div v-if="loading" class="catalog__state">
          <div class="spinner" />
        </div>

        <div v-else-if="!packages.length" class="catalog__state">
          <p class="catalog__empty-title">Keine Pakete vorhanden</p>
          <p class="catalog__empty-sub">Bald verfügbar.</p>
        </div>

        <div v-else class="package-grid">
          <NuxtLink
            v-for="pkg in packages"
            :key="pkg.id"
            :to="`/packages/${pkg.slug}`"
            class="package-card"
          >
            <div class="package-card__top">
              <span class="package-card__type" :class="pkg.type === 'CURATED' ? 'package-card__type--curated' : 'package-card__type--category'">
                {{ pkg.type === 'CURATED' ? 'Kuratiert' : 'Kategorie' }}
              </span>
              <span class="package-card__icon" aria-hidden="true">⬡</span>
            </div>
            <h2 class="package-card__title">{{ pkg.name }}</h2>
            <p v-if="pkg.description" class="package-card__desc">{{ pkg.description }}</p>
            <div class="package-card__meta">
              <span v-if="pkg.category" class="package-card__cat">{{ pkg.category.name }}</span>
              <span class="package-card__count">{{ pkg.games_count ?? 0 }} Spiele</span>
            </div>
            <span class="package-card__arrow">Details →</span>
          </NuxtLink>
        </div>

      </div>
    </section>

    <!-- ── Footer ──────────────────────────────────────────────── -->
    <footer class="l-footer">
      <div class="l-footer__inner">
        <div class="l-footer__brand">
          <span class="l-footer__hex" aria-hidden="true">⬡</span>
          <span class="l-footer__name">AUA</span>
        </div>
        <nav class="l-footer__nav" aria-label="Footer-Navigation">
          <NuxtLink to="/games" class="l-footer__link">Spielesammlung</NuxtLink>
          <NuxtLink to="/packages" class="l-footer__link">Pakete</NuxtLink>
          <NuxtLink to="/terms" class="l-footer__link">Nutzungsbedingungen</NuxtLink>
          <NuxtLink to="/privacy" class="l-footer__link">Datenschutzerklärung</NuxtLink>
          <NuxtLink to="/cookies" class="l-footer__link">Cookie-Richtlinien</NuxtLink>
        </nav>
        <p class="l-footer__copy">&copy; {{ year }} AUA. Alle Rechte vorbehalten.</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import type { Package } from '~/composables/useGames'

const { fetchPackages } = useGames()

const loading = ref(true)
const packages = ref<Package[]>([])
const year = new Date().getFullYear()

onMounted(async () => {
  try {
    const data = await fetchPackages()
    packages.value = data.data
  } finally {
    loading.value = false
  }
})

useHead({ title: 'Spielepakete — AUA' })
</script>

<style lang="scss" scoped>
$hero-bg:       #0F0E0C;
$amber:         #D4921E;
$nav-height:    64px;

$amber-08:      rgba(212, 146, 30, 0.08);
$amber-14:      rgba(212, 146, 30, 0.14);
$amber-25:      rgba(212, 146, 30, 0.25);
$amber-glow:    rgba(212, 146, 30, 0.18);

$hero-text:     #EEE8DF;
$hero-muted:    rgba(238, 232, 223, 0.55);
$hero-muted-50: rgba(238, 232, 223, 0.50);
$hero-divider:  rgba(238, 232, 223, 0.10);

.packages-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--background);
}

// ─── Hero ─────────────────────────────────────────────────────────
.page-hero {
  position: relative;
  background: $hero-bg;
  padding: calc(#{$nav-height} + 3.5rem) 1.5rem 3.5rem;
  overflow: hidden;

  &__backdrop { position: absolute; inset: 0; pointer-events: none; }

  &__glow {
    position: absolute;
    width: 500px; height: 500px;
    top: -150px; right: -100px;
    border-radius: 50%;
    filter: blur(100px);
    background: $amber-glow;
  }

  &__dots {
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px);
    background-size: 24px 24px;
    mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%);
  }

  &__body { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; }

  &__eyebrow {
    display: inline-block;
    font-size: 0.72rem; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase;
    color: $amber; margin-bottom: 0.75rem;
    padding: 0.25rem 0.65rem;
    border: 1px solid $amber-25; border-radius: 999px; background: $amber-08;
  }

  &__title {
    font-size: clamp(2rem, 5vw, 3.25rem);
    font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0 0 0.5rem;
  }

  &__sub {
    font-size: 1rem; color: $hero-muted; padding-bottom: 0;
  }
}

// ─── Catalog ──────────────────────────────────────────────────────
.catalog {
  flex: 1;
  padding: 2.5rem 1.5rem 4rem;

  &__inner { max-width: 1100px; margin: 0 auto; }

  &__state {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    min-height: 300px; gap: 0.5rem;
  }

  &__empty-title { font-size: 1.1rem; font-weight: 700; color: var(--primary-text); padding-bottom: 0; }
  &__empty-sub   { font-size: 0.9rem; color: var(--secondary-text); padding-bottom: 0; }
}

// ─── Package Grid ─────────────────────────────────────────────────
.package-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.25rem;

  @media (max-width: 900px) { grid-template-columns: repeat(2, 1fr); }
  @media (max-width: 560px) { grid-template-columns: 1fr; }
}

.package-card {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 1.5rem;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 16px;
  text-decoration: none;
  color: var(--primary-text);
  transition: border-color 0.2s, background 0.2s, transform 0.15s;

  &:hover {
    border-color: $amber-25;
    background: $amber-08;
    transform: translateY(-2px);

    .package-card__arrow { color: $amber; opacity: 1; }
  }

  &__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  &__type {
    font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
    padding: 0.2rem 0.55rem; border-radius: 999px;
  }

  &__type--curated  { background: $amber-08; color: $amber; border: 1px solid $amber-25; }
  &__type--category { background: rgba(99,102,241,0.1); color: #818cf8; border: 1px solid rgba(99,102,241,0.25); }

  &__icon {
    font-size: 1.25rem;
    color: $amber;
    opacity: 0.35;
  }

  &__title {
    font-size: 1.2rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: var(--primary-text);
    margin: 0;
  }

  &__desc {
    font-size: 0.875rem;
    line-height: 1.6;
    color: var(--secondary-text);
    padding-bottom: 0;
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
  }

  &__cat {
    font-size: 0.78rem; font-weight: 600;
    color: var(--secondary-text);
    background: var(--background);
    border: 1px solid var(--divider);
    border-radius: 999px;
    padding: 0.15rem 0.5rem;
  }

  &__count {
    font-size: 0.78rem;
    color: var(--secondary-text);
  }

  &__arrow {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--secondary-text);
    opacity: 0.5;
    transition: color 0.2s, opacity 0.2s;
    margin-top: auto;
  }
}

// ─── Footer ───────────────────────────────────────────────────────
.l-footer {
  background: $hero-bg;
  border-top: 1px solid $hero-divider;
  padding: 2.5rem 1.5rem;

  &__inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; flex-wrap: wrap; gap: 1.5rem; }
  &__brand { display: flex; align-items: center; gap: 0.4rem; flex-shrink: 0; }
  &__hex { font-size: 1.2rem; color: $amber; }
  &__name { font-size: 0.95rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em; }
  &__nav { display: flex; gap: 1.5rem; flex-wrap: wrap; flex: 1; justify-content: center; @media (max-width: 640px) { justify-content: flex-start; } }
  &__link { font-size: 0.85rem; color: $hero-muted; text-decoration: none; transition: color 0.2s; &:hover { color: $hero-text; } }
  &__copy { font-size: 0.8rem; color: $hero-muted-50; margin-left: auto; padding-bottom: 0; @media (max-width: 640px) { margin-left: 0; width: 100%; } }
}
</style>
