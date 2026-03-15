<template>
  <div class="game-detail">

    <AppNav />

    <!-- ── Loading ─────────────────────────────────────────────── -->
    <div v-if="loading" class="detail-state">
      <div class="spinner" />
    </div>

    <!-- ── Not Found ───────────────────────────────────────────── -->
    <div v-else-if="!game" class="detail-state">
      <p class="detail-state__title">Spiel nicht gefunden</p>
      <NuxtLink to="/games" class="button button-primary">Zur Spielesammlung</NuxtLink>
    </div>

    <!-- ── Content ─────────────────────────────────────────────── -->
    <template v-else>

      <!-- Page header -->
      <section class="page-hero">
        <div class="page-hero__backdrop" aria-hidden="true">
          <div class="page-hero__glow" />
          <div class="page-hero__dots" />
        </div>
        <div class="page-hero__body">
          <NuxtLink to="/games" class="page-hero__back">← Zur Spielesammlung</NuxtLink>
          <div class="page-hero__meta-row">
            <p v-if="game.category" class="page-hero__eyebrow">{{ game.category.name }}</p>
            <span
              class="page-hero__badge"
              :class="game.available_copies_count > 0 ? 'page-hero__badge--avail' : 'page-hero__badge--out'"
            >
              {{ game.available_copies_count > 0
                ? `${game.available_copies_count} Kopie(n) verfügbar`
                : 'Aktuell ausgeliehen' }}
            </span>
          </div>
          <h1 class="page-hero__title">{{ game.title }}</h1>
        </div>
      </section>

      <!-- Detail card -->
      <section class="detail">
        <div class="detail__inner">

          <div v-if="game.cover_image_url" class="detail__cover">
            <img :src="game.cover_image_url" :alt="game.title" class="detail__cover-img" />
          </div>

          <div class="detail__info">

            <p v-if="game.short_description" class="detail__short-desc">
              {{ game.short_description }}
            </p>

            <div v-if="hasMeta" class="detail__stats">
              <div v-if="game.min_players" class="detail__stat">
                <span class="detail__stat-label">Spieler</span>
                <span class="detail__stat-value">
                  {{ game.min_players }}{{ game.max_players ? `–${game.max_players}` : '+' }}
                </span>
              </div>
              <div v-if="game.min_age" class="detail__stat">
                <span class="detail__stat-label">Alter</span>
                <span class="detail__stat-value">ab {{ game.min_age }} J.</span>
              </div>
              <div v-if="game.duration_min" class="detail__stat">
                <span class="detail__stat-label">Spielzeit</span>
                <span class="detail__stat-value">
                  {{ game.duration_min }}{{ game.duration_max ? `–${game.duration_max}` : '' }} Min.
                </span>
              </div>
              <div v-if="game.difficulty" class="detail__stat">
                <span class="detail__stat-label">Schwierigkeit</span>
                <span class="detail__stat-value">{{ difficultyLabel(game.difficulty!) }}</span>
              </div>
              <div v-if="game.language" class="detail__stat">
                <span class="detail__stat-label">Sprache</span>
                <span class="detail__stat-value">{{ game.language }}</span>
              </div>
              <div v-if="game.year" class="detail__stat">
                <span class="detail__stat-label">Jahr</span>
                <span class="detail__stat-value">{{ game.year }}</span>
              </div>
            </div>

            <div v-if="game.tags?.length" class="detail__tags">
              <span v-for="tag in game.tags" :key="tag.id" class="detail__tag">{{ tag.name }}</span>
            </div>

            <p v-if="game.description" class="detail__desc">{{ game.description }}</p>

          </div>
        </div>
      </section>

    </template>

    <!-- ── Footer ──────────────────────────────────────────────── -->
    <footer class="l-footer">
      <div class="l-footer__inner">
        <div class="l-footer__brand">
          <span class="l-footer__hex" aria-hidden="true">⬡</span>
          <span class="l-footer__name">Spielothek</span>
        </div>
        <nav class="l-footer__nav" aria-label="Footer-Navigation">
          <NuxtLink to="/games" class="l-footer__link">Spielesammlung</NuxtLink>
          <NuxtLink to="/terms" class="l-footer__link">Nutzungsbedingungen</NuxtLink>
          <NuxtLink to="/privacy" class="l-footer__link">Datenschutzerklärung</NuxtLink>
          <NuxtLink to="/cookies" class="l-footer__link">Cookie-Richtlinien</NuxtLink>
        </nav>
        <p class="l-footer__copy">&copy; {{ year }} Spielothek. Alle Rechte vorbehalten.</p>
      </div>
    </footer>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { Game } from '~/composables/useGames'

const route = useRoute()
const { fetchGame } = useGames()

const loading = ref(true)
const game = ref<Game | null>(null)
const year = new Date().getFullYear()

const DIFFICULTY: Record<string, string> = {
  EASY: 'Leicht', MEDIUM: 'Mittel', HARD: 'Schwer', EXPERT: 'Experte',
}

function difficultyLabel(d: string) {
  return DIFFICULTY[d] ?? d
}

const hasMeta = computed(() =>
  game.value && (
    game.value.min_players || game.value.min_age ||
    game.value.duration_min || game.value.difficulty ||
    game.value.language || game.value.year
  )
)

onMounted(async () => {
  try {
    const data = await fetchGame(route.params.slug as string)
    game.value = data.data
  } catch {
    game.value = null
  } finally {
    loading.value = false
  }
})

useHead(() => ({
  title: game.value ? `${game.value.title} — Spielesammlung` : 'Spiel nicht gefunden',
}))
</script>

<style lang="scss" scoped>
$hero-bg:       #0F0E0C;
$amber:         #D4921E;
$nav-height:    64px;

$amber-08:      rgba(212, 146, 30, 0.08);
$amber-25:      rgba(212, 146, 30, 0.25);
$amber-glow:    rgba(212, 146, 30, 0.20);

$hero-text:     #EEE8DF;
$hero-muted:    rgba(238, 232, 223, 0.55);
$hero-muted-50: rgba(238, 232, 223, 0.50);
$hero-divider:  rgba(238, 232, 223, 0.10);

// ─── Page shell ───────────────────────────────────────────────────
.game-detail {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--background);
}

// ─── State (loading / 404) ────────────────────────────────────────
.detail-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: calc(#{$nav-height} + 4rem) 1.5rem 4rem;

  &__title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-text);
    padding-bottom: 0;
  }
}

// ─── Page Header ──────────────────────────────────────────────────
.page-hero {
  position: relative;
  background: $hero-bg;
  padding: calc(#{$nav-height} + 3rem) 1.5rem 3rem;
  overflow: hidden;

  &__backdrop { position: absolute; inset: 0; pointer-events: none; }

  &__glow {
    position: absolute;
    width: 600px;
    height: 400px;
    top: -100px;
    right: -100px;
    border-radius: 50%;
    filter: blur(100px);
    background: $amber-glow;
  }

  &__dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
    background-size: 24px 24px;
    mask-image: radial-gradient(ellipse 100% 100% at 80% 50%, black 10%, transparent 80%);
  }

  &__body {
    position: relative;
    z-index: 1;
    max-width: 1100px;
    margin: 0 auto;
  }

  &__back {
    display: inline-block;
    font-size: 0.85rem;
    font-weight: 500;
    color: $hero-muted;
    text-decoration: none;
    margin-bottom: 1.5rem;
    transition: color 0.2s;
    &:hover { color: $hero-text; }
  }

  &__meta-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.6rem;
    flex-wrap: wrap;
  }

  &__eyebrow {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: $amber;
    padding: 0.2rem 0.6rem;
    border: 1px solid $amber-25;
    border-radius: 999px;
    background: $amber-08;
    padding-bottom: 0.2rem;
  }

  &__badge {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;

    &--avail {
      background: var(--success-muted);
      color: var(--success);
    }

    &--out {
      background: var(--accent-color-muted);
      color: var(--accent-text);
    }
  }

  &__title {
    font-size: clamp(1.75rem, 4vw, 3rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    color: $hero-text;
    margin: 0;
  }
}

// ─── Detail Content ───────────────────────────────────────────────
.detail {
  flex: 1;
  padding: 3rem 1.5rem 4rem;

  &__inner {
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 3rem;
    align-items: start;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
      gap: 2rem;
    }
  }

  &__cover {
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--divider);
    position: sticky;
    top: calc(#{$nav-height} + 1.5rem);
  }

  &__cover-img {
    width: 100%;
    display: block;
  }

  &__info { display: flex; flex-direction: column; gap: 1.5rem; }

  &__short-desc {
    font-size: 1.05rem;
    line-height: 1.65;
    color: var(--primary-text);
    font-weight: 500;
    padding-bottom: 0;
  }

  // Stats grid
  &__stats {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1px;
    background: var(--divider);
    border: 1px solid var(--divider);
    border-radius: 12px;
    overflow: hidden;
  }

  &__stat {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 1rem 1.25rem;
    background: var(--secondary-background);
  }

  &__stat-label {
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--secondary-text);
  }

  &__stat-value {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--primary-text);
    letter-spacing: -0.01em;
  }

  // Tags
  &__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
  }

  &__tag {
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--secondary-text);
    background: var(--secondary-background);
    border: 1px solid var(--divider);
    border-radius: 999px;
    padding: 0.25rem 0.65rem;
  }

  // Description
  &__desc {
    font-size: 1rem;
    line-height: 1.75;
    color: var(--secondary-text);
    padding-bottom: 0;
  }
}

// ─── Footer ───────────────────────────────────────────────────────
.l-footer {
  background: $hero-bg;
  border-top: 1px solid $hero-divider;
  padding: 2.5rem 1.5rem;

  &__inner {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
  }

  &__brand { display: flex; align-items: center; gap: 0.4rem; flex-shrink: 0; }

  &__hex { font-size: 1.2rem; color: $amber; }

  &__name {
    font-size: 0.95rem;
    font-weight: 700;
    color: $hero-text;
    letter-spacing: -0.02em;
  }

  &__nav {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    flex: 1;
    justify-content: center;
    @media (max-width: 640px) { justify-content: flex-start; }
  }

  &__link {
    font-size: 0.85rem;
    color: $hero-muted;
    text-decoration: none;
    transition: color 0.2s;
    &:hover { color: $hero-text; }
  }

  &__copy {
    font-size: 0.8rem;
    color: $hero-muted-50;
    margin-left: auto;
    padding-bottom: 0;
    @media (max-width: 640px) { margin-left: 0; width: 100%; }
  }
}
</style>
