<template>
  <div class="games-page">

    <AppNav />

    <!-- ── Page Header ─────────────────────────────────────────── -->
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <p class="page-hero__eyebrow">AUA</p>
        <h1 class="page-hero__title">Spielesammlung</h1>
        <p v-if="!loading && meta.total" class="page-hero__count">
          {{ meta.total }} Spiele entdecken
        </p>
      </div>
    </section>

    <!-- ── Filter Bar ──────────────────────────────────────────── -->
    <div class="filter-bar">
      <div class="filter-bar__inner">
        <UiInput
          v-model="filters.search"
          placeholder="Suche nach Spielen…"
          class="filter-bar__search"
        />

        <select v-model="filters.category" class="filter-select" @change="resetPage">
          <option value="">Alle Kategorien</option>
          <option v-for="cat in categories" :key="cat.slug" :value="cat.slug">
            {{ cat.name }} ({{ cat.games_count }})
          </option>
        </select>

        <select v-model="filters.difficulty" class="filter-select" @change="resetPage">
          <option value="">Alle Schwierigkeiten</option>
          <option value="EASY">Leicht</option>
          <option value="MEDIUM">Mittel</option>
          <option value="HARD">Schwer</option>
          <option value="EXPERT">Experte</option>
        </select>

        <select v-model="filters.sort" class="filter-select" @change="resetPage">
          <option value="title">A – Z</option>
          <option value="created_at">Neueste zuerst</option>
        </select>

        <label class="filter-toggle">
          <input v-model="filters.available" type="checkbox" @change="resetPage" />
          <span>Nur verfügbar</span>
        </label>
      </div>
    </div>

    <!-- ── Catalog ─────────────────────────────────────────────── -->
    <section class="catalog">
      <div class="catalog__inner">

        <div v-if="loading" class="catalog__state">
          <div class="spinner" />
        </div>

        <div v-else-if="!games.length" class="catalog__state">
          <p class="catalog__empty-title">Keine Spiele gefunden</p>
          <p class="catalog__empty-sub">Versuche andere Filter oder Suchbegriffe.</p>
        </div>

        <template v-else>
          <div class="game-grid">
            <GameCard v-for="game in games" :key="game.id" :game="game" />
          </div>

          <div v-if="meta.last_page > 1" class="pagination">
            <button
              class="pagination__btn"
              :disabled="filters.page === 1"
              @click="changePage(filters.page! - 1)"
            >
              ← Zurück
            </button>
            <span class="pagination__info">
              Seite {{ filters.page }} von {{ meta.last_page }}
            </span>
            <button
              class="pagination__btn"
              :disabled="filters.page === meta.last_page"
              @click="changePage(filters.page! + 1)"
            >
              Weiter →
            </button>
          </div>
        </template>

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
import { ref, reactive, watch, onMounted } from 'vue'
import type { Game } from '~/composables/useGames'

const { fetchGames, fetchCategories } = useGames()

const loading = ref(false)
const games = ref<Game[]>([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 24, total: 0 })
const categories = ref<{ slug: string; name: string; games_count: number }[]>([])
const year = new Date().getFullYear()

const filters = reactive({
  search: '',
  category: '',
  difficulty: '',
  available: false,
  sort: 'title',
  page: 1,
})

async function load() {
  loading.value = true
  try {
    const data = await fetchGames(filters)
    games.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
  }
}

function resetPage() {
  filters.page = 1
  load()
}

function changePage(page: number) {
  filters.page = page
  load()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

let searchTimer: ReturnType<typeof setTimeout>
watch(() => filters.search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(resetPage, 300)
})

onMounted(async () => {
  const [, cats] = await Promise.all([load(), fetchCategories()])
  categories.value = cats.data
})
</script>

<style lang="scss" scoped>
$hero-bg:       #0F0E0C;
$amber:         #D4921E;
$nav-height:    64px;

$amber-08:      rgba(212, 146, 30, 0.08);
$amber-25:      rgba(212, 146, 30, 0.25);
$amber-glow:    rgba(212, 146, 30, 0.18);

$hero-text:     #EEE8DF;
$hero-muted:    rgba(238, 232, 223, 0.55);
$hero-muted-50: rgba(238, 232, 223, 0.50);
$hero-divider:  rgba(238, 232, 223, 0.10);

// ─── Page shell ───────────────────────────────────────────────────
.games-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--background);
}

// ─── Page Header ──────────────────────────────────────────────────
.page-hero {
  position: relative;
  background: $hero-bg;
  padding: calc(#{$nav-height} + 3.5rem) 1.5rem 3.5rem;
  overflow: hidden;

  &__backdrop { position: absolute; inset: 0; pointer-events: none; }

  &__glow {
    position: absolute;
    width: 500px;
    height: 500px;
    top: -150px;
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
    mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%);
  }

  &__body {
    position: relative;
    z-index: 1;
    max-width: 1100px;
    margin: 0 auto;
  }

  &__eyebrow {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: $amber;
    margin-bottom: 0.75rem;
    padding: 0.25rem 0.65rem;
    border: 1px solid $amber-25;
    border-radius: 999px;
    background: $amber-08;
  }

  &__title {
    font-size: clamp(2rem, 5vw, 3.25rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    color: $hero-text;
    margin: 0 0 0.5rem;
  }

  &__count {
    font-size: 1rem;
    color: $hero-muted;
    padding-bottom: 0;
  }
}

// ─── Filter Bar ───────────────────────────────────────────────────
.filter-bar {
  background: var(--secondary-background);
  border-bottom: 1px solid var(--divider);
  padding: 0.9rem 1.5rem;
  position: sticky;
  top: $nav-height;
  z-index: 50;

  &__inner {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
  }

  &__search {
    flex: 1;
    min-width: 180px;
  }
}

.filter-select {
  height: 38px;
  padding: 0 0.75rem;
  border: 1px solid var(--divider);
  border-radius: 8px;
  background: var(--background);
  color: var(--primary-text);
  font-size: 0.875rem;
  font-family: inherit;
  cursor: pointer;
  transition: border-color 0.2s;

  &:focus {
    outline: none;
    border-color: var(--accent-color);
  }
}

.filter-toggle {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.875rem;
  color: var(--secondary-text);
  cursor: pointer;
  white-space: nowrap;
  user-select: none;

  input[type="checkbox"] {
    accent-color: var(--accent-color);
    width: 15px;
    height: 15px;
    cursor: pointer;
  }
}

// ─── Catalog ──────────────────────────────────────────────────────
.catalog {
  flex: 1;
  padding: 2.5rem 1.5rem 4rem;

  &__inner {
    max-width: 1100px;
    margin: 0 auto;
  }

  &__state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    gap: 0.5rem;
  }

  &__empty-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary-text);
    padding-bottom: 0;
  }

  &__empty-sub {
    font-size: 0.9rem;
    color: var(--secondary-text);
    padding-bottom: 0;
  }
}

.game-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.25rem;

  @media (max-width: 1100px) { grid-template-columns: repeat(3, 1fr); }
  @media (max-width: 750px)  { grid-template-columns: repeat(2, 1fr); }
  @media (max-width: 480px)  { grid-template-columns: 1fr; }
}

// ─── Pagination ────────────────────────────────────────────────────
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 3rem;

  &__btn {
    padding: 0.5rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    font-family: inherit;
    background: var(--secondary-background);
    color: var(--primary-text);
    border: 1px solid var(--divider);
    border-radius: 8px;
    cursor: pointer;
    transition: border-color 0.2s, color 0.2s;

    &:hover:not(:disabled) {
      border-color: var(--accent-color);
      color: var(--accent-text);
    }

    &:disabled {
      opacity: 0.35;
      cursor: not-allowed;
    }
  }

  &__info {
    font-size: 0.875rem;
    color: var(--secondary-text);
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
