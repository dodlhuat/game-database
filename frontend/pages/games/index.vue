<template>
  <div class="games-page">

    <AppNav />

    <!-- ── Page Header ─────────────────────────────────────────── -->
    <section class="page-hero" data-theme="dark">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <p class="page-hero__eyebrow">AUA</p>
        <h1 class="page-hero__title">{{ $t('pages.games.title') }}</h1>
        <p v-if="!loading && meta.total" class="page-hero__count">
          {{ $t('pages.games.count', { n: meta.total }) }}
        </p>

        <!-- ── Search ── -->
        <div class="hero-search" :class="{ 'hero-search--active': searchFocused }">
          <span class="icon icon-search hero-search__icon" aria-hidden="true" />
          <input
            v-model="filters.search"
            type="text"
            class="hero-search__input"
            :placeholder="$t('pages.games.search_placeholder')"
            @focus="searchFocused = true"
            @blur="searchFocused = false"
          />
          <Transition name="fade">
            <button
              v-if="filters.search"
              class="hero-search__clear"
              :aria-label="$t('pages.games.search_clear')"
              @click="filters.search = ''"
            >
              <span class="icon icon-close" aria-hidden="true" />
            </button>
          </Transition>
        </div>
      </div>
    </section>

    <!-- ── Filter Strip ────────────────────────────────────────── -->
    <div class="filter-strip" :class="{ 'filter-strip--filtered': hasActiveFilters }">
      <div class="filter-strip__scroll">

        <div
          ref="catChipRef"
          class="chip-wrap"
          :class="{ 'chip-wrap--active': !!filters.category, 'chip-wrap--open': catPopoverOpen }"
          style="--i:0"
          @click="toggleCatPopover"
        >
          <span class="chip-label">{{ categoryLabel }}</span>
          <span class="icon icon-expand_more chip-chevron" aria-hidden="true" />
        </div>

        <div class="chip-wrap" :class="{ 'chip-wrap--active': !!filters.difficulty }" style="--i:1">
          <select v-model="filters.difficulty" class="chip-select" @change="resetPage">
            <option value="">{{ $t('pages.games.filter_difficulty') }}</option>
            <option value="EASY">{{ $t('pages.games.filter_difficulty_easy') }}</option>
            <option value="MEDIUM">{{ $t('pages.games.filter_difficulty_medium') }}</option>
            <option value="HARD">{{ $t('pages.games.filter_difficulty_hard') }}</option>
            <option value="EXPERT">{{ $t('pages.games.filter_difficulty_expert') }}</option>
          </select>
          <span class="chip-label">{{ difficultyLabel }}</span>
          <span class="icon icon-expand_more chip-chevron" aria-hidden="true" />
        </div>

        <div class="chip-wrap" :class="{ 'chip-wrap--active': !!filters.players }" style="--i:2">
          <select v-model="filters.players" class="chip-select" @change="resetPage">
            <option value="">{{ $t('pages.games.filter_players') }}</option>
            <option value="1">{{ $t('pages.games.filter_players_count', { n: 1 }) }}</option>
            <option value="2">{{ $t('pages.games.filter_players_count', { n: 2 }) }}</option>
            <option value="3">{{ $t('pages.games.filter_players_count', { n: 3 }) }}</option>
            <option value="4">{{ $t('pages.games.filter_players_count', { n: 4 }) }}</option>
            <option value="5">{{ $t('pages.games.filter_players_count', { n: 5 }) }}</option>
            <option value="6">{{ $t('pages.games.filter_players_count_plus', { n: 6 }) }}</option>
          </select>
          <span class="chip-label">{{ playersLabel }}</span>
          <span class="icon icon-expand_more chip-chevron" aria-hidden="true" />
        </div>

        <div class="chip-wrap" :class="{ 'chip-wrap--active': !!filters.duration }" style="--i:3">
          <select v-model="filters.duration" class="chip-select" @change="resetPage">
            <option value="">{{ $t('pages.games.filter_duration') }}</option>
            <option value="short">{{ $t('pages.games.filter_duration_short') }}</option>
            <option value="medium">{{ $t('pages.games.filter_duration_medium') }}</option>
            <option value="long">{{ $t('pages.games.filter_duration_long') }}</option>
          </select>
          <span class="chip-label">{{ durationLabel }}</span>
          <span class="icon icon-expand_more chip-chevron" aria-hidden="true" />
        </div>

        <div class="chip-wrap" :class="{ 'chip-wrap--active': !!filters.min_age }" style="--i:4">
          <select v-model="filters.min_age" class="chip-select" @change="resetPage">
            <option value="">{{ $t('pages.games.filter_age') }}</option>
            <option value="6">{{ $t('pages.games.filter_age_from', { n: 6 }) }}</option>
            <option value="8">{{ $t('pages.games.filter_age_from', { n: 8 }) }}</option>
            <option value="10">{{ $t('pages.games.filter_age_from', { n: 10 }) }}</option>
            <option value="12">{{ $t('pages.games.filter_age_from', { n: 12 }) }}</option>
            <option value="14">{{ $t('pages.games.filter_age_from', { n: 14 }) }}</option>
            <option value="16">{{ $t('pages.games.filter_age_from', { n: 16 }) }}</option>
            <option value="18">{{ $t('pages.games.filter_age_from', { n: 18 }) }}</option>
          </select>
          <span class="chip-label">{{ ageLabel }}</span>
          <span class="icon icon-expand_more chip-chevron" aria-hidden="true" />
        </div>

        <div class="chip-wrap" :class="{ 'chip-wrap--active': !!filters.language }" style="--i:5">
          <select v-model="filters.language" class="chip-select" @change="resetPage">
            <option value="">{{ $t('pages.games.filter_language') }}</option>
            <option value="DE">{{ $t('pages.games.filter_language_de') }}</option>
            <option value="EN">{{ $t('pages.games.filter_language_en') }}</option>
            <option value="DE/EN">{{ $t('pages.games.filter_language_de_en') }}</option>
          </select>
          <span class="chip-label">{{ languageLabel }}</span>
          <span class="icon icon-expand_more chip-chevron" aria-hidden="true" />
        </div>

        <button
          class="chip-btn"
          :class="{ 'chip-btn--active': filters.available }"
          style="--i:6"
          @click="filters.available = !filters.available; resetPage()"
        >
          <span class="icon icon-check_circle chip-btn__icon" aria-hidden="true" />
          {{ $t('pages.games.filter_available') }}
        </button>

        <div class="chip-wrap chip-wrap--sort" style="--i:7">
          <select v-model="filters.sort" class="chip-select" @change="resetPage">
            <option value="title">{{ $t('pages.games.sort_az') }}</option>
            <option value="created_at">{{ $t('pages.games.sort_newest') }}</option>
          </select>
          <span class="chip-label">{{ sortLabel }}</span>
          <span class="icon icon-expand_more chip-chevron" aria-hidden="true" />
        </div>

      </div>

      <Transition name="slide-clear">
        <button v-if="hasActiveFilters" class="filter-clear" @click="clearFilters" :aria-label="$t('pages.games.filter_clear')">
          <span class="filter-clear__badge">{{ activeFilterCount }}</span>
          <span class="icon icon-close" aria-hidden="true" />
        </button>
      </Transition>
    </div>

    <!-- ── Catalog ─────────────────────────────────────────────── -->
    <section class="catalog">
      <div class="catalog__inner">

        <div v-if="loading" class="catalog__state">
          <div class="spinner" />
        </div>

        <div v-else-if="!games.length" class="catalog__state">
          <p class="catalog__empty-title">{{ $t('pages.games.empty_title') }}</p>
          <p class="catalog__empty-sub">{{ $t('pages.games.empty_sub') }}</p>
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
              {{ $t('pages.games.pagination_prev') }}
            </button>
            <span class="pagination__info">
              {{ $t('pages.games.pagination_info', { page: filters.page, total: meta.last_page }) }}
            </span>
            <button
              class="pagination__btn"
              :disabled="filters.page === meta.last_page"
              @click="changePage(filters.page! + 1)"
            >
              {{ $t('pages.games.pagination_next') }}
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

  <!-- ── Category popover (teleported to body for z-index / overflow) ── -->
  <Teleport to="body">
    <Transition name="cat-pop">
      <div
        v-if="catPopoverOpen"
        ref="catPopoverElRef"
        class="cat-popover"
        :style="catPopoverStyle"
      >
        <div ref="catPickerEl" />
      </div>
    </Transition>
  </Teleport>

</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import type { Game } from '~/composables/useGames'
import { GroupPicker } from '@dodlhuat/basix/js/group-picker'

const { fetchGames, fetchCategories } = useGames()
const { t } = useI18n()

type CategoryItem = { id: number; slug: string; name: string; games_count: number; children: CategoryItem[] }

const loading = ref(false)
const games = ref<Game[]>([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 24, total: 0 })
const categories = ref<CategoryItem[]>([])
const searchFocused = ref(false)
const year = new Date().getFullYear()

// ── Category popover ───────────────────────────────────────────────
const catChipRef = ref<HTMLElement | null>(null)
const catPickerEl = ref<HTMLElement | null>(null)
const catPopoverElRef = ref<HTMLElement | null>(null)
const catPopoverOpen = ref(false)
const catPopoverPos = ref({ top: 0, left: 0, width: 300 })
let catPicker: InstanceType<typeof GroupPicker> | null = null
let isInitializingPicker = false

const catPopoverStyle = computed(() => ({
  top: `${catPopoverPos.value.top}px`,
  left: `${catPopoverPos.value.left}px`,
  width: `${catPopoverPos.value.width}px`,
}))

function toggleCatPopover() {
  catPopoverOpen.value ? closeCatPopover() : openCatPopover()
}

function openCatPopover() {
  const rect = catChipRef.value?.getBoundingClientRect()
  if (!rect) return
  const w = 300
  const left = Math.min(rect.left, window.innerWidth - w - 12)
  catPopoverPos.value = { top: rect.bottom + 6, left: Math.max(8, left), width: w }
  catPopoverOpen.value = true
  nextTick(() => initCatPicker())
}

function closeCatPopover() {
  catPopoverOpen.value = false
  catPicker?.destroy()
  catPicker = null
}

function initCatPicker() {
  if (!catPickerEl.value) return
  catPicker?.destroy()

  const data = categories.value.map(cat => {
    const subs = (cat.children ?? []).map(c => ({ id: c.slug, label: c.name }))
    return subs.length
      ? { id: cat.slug, label: cat.name, subgroups: subs }
      : { id: cat.slug, label: cat.name }
  })

  catPicker = new GroupPicker(catPickerEl.value, data, {
    searchPlaceholder: t('pages.games.category_search'),
    selectAllLabel: t('pages.games.category_select_all'),
    deselectLabel: t('pages.games.category_deselect'),
    emptyLabel: t('pages.games.category_empty'),
    onSelectionChange: (sel: { parentGroups: string[]; subgroups: { groupId: string; subgroupId: string }[] }) => {
      if (isInitializingPicker) return
      const slugs = [
        ...sel.parentGroups,
        ...sel.subgroups.map(s => s.subgroupId),
      ]
      filters.category = slugs.join(',')
      resetPage()
    },
  })

  // Restore current selection without triggering the handler
  if (filters.category) {
    isInitializingPicker = true
    const slugs = filters.category.split(',').filter(Boolean)
    const parentGroups: string[] = []
    const subgroups: { groupId: string; subgroupId: string }[] = []
    for (const slug of slugs) {
      if (categories.value.some(c => c.slug === slug)) {
        parentGroups.push(slug)
      } else {
        for (const parent of categories.value) {
          if ((parent.children ?? []).some(c => c.slug === slug)) {
            subgroups.push({ groupId: parent.slug, subgroupId: slug })
            break
          }
        }
      }
    }
    catPicker.setSelection({ parentGroups, subgroups })
    isInitializingPicker = false
  }
}

function onOutsideClick(e: PointerEvent) {
  if (!catPopoverOpen.value) return
  const target = e.target as Element
  if (!catChipRef.value?.contains(target) && !catPopoverElRef.value?.contains(target)) {
    closeCatPopover()
  }
}

function onEscapeKey(e: KeyboardEvent) {
  if (e.key === 'Escape' && catPopoverOpen.value) closeCatPopover()
}

const filters = reactive({
  search: '',
  category: '',
  difficulty: '',
  players: '',
  duration: '' as '' | 'short' | 'medium' | 'long',
  language: '',
  min_age: '',
  available: false,
  sort: 'title',
  page: 1,
})

// ── Chip labels ────────────────────────────────────────────────────
const categoryLabel = computed(() => {
  if (!filters.category) return t('pages.games.filter_category')
  const slugs = filters.category.split(',').filter(Boolean)
  if (slugs.length === 0) return t('pages.games.filter_category')
  if (slugs.length > 1) return `${slugs.length} ${t('pages.games.filter_category')}`
  const slug = slugs[0]
  for (const cat of categories.value) {
    if (cat.slug === slug) return cat.name
    const child = (cat.children ?? []).find(c => c.slug === slug)
    if (child) return child.name
  }
  return t('pages.games.filter_category')
})
const difficultyMap: Record<string, () => string> = {
  EASY: () => t('pages.games.filter_difficulty_easy'),
  MEDIUM: () => t('pages.games.filter_difficulty_medium'),
  HARD: () => t('pages.games.filter_difficulty_hard'),
  EXPERT: () => t('pages.games.filter_difficulty_expert'),
}
const difficultyLabel = computed(() => filters.difficulty ? difficultyMap[filters.difficulty]?.() ?? filters.difficulty : t('pages.games.filter_difficulty'))
const playersLabel = computed(() => filters.players
  ? (filters.players === '6'
    ? t('pages.games.players_label_plus', { n: filters.players })
    : t('pages.games.players_label', { n: filters.players }))
  : t('pages.games.filter_players'))
const durationMap: Record<string, () => string> = {
  short: () => t('pages.games.filter_duration_short'),
  medium: () => t('pages.games.filter_duration_medium'),
  long: () => t('pages.games.filter_duration_long'),
}
const durationLabel = computed(() => filters.duration ? durationMap[filters.duration]?.() ?? filters.duration : t('pages.games.filter_duration'))
const ageLabel = computed(() => filters.min_age ? t('pages.games.age_label', { n: filters.min_age }) : t('pages.games.filter_age'))
const langMap: Record<string, () => string> = {
  DE: () => t('pages.games.filter_language_de'),
  EN: () => t('pages.games.filter_language_en'),
  'DE/EN': () => t('pages.games.filter_language_de_en'),
}
const languageLabel = computed(() => filters.language ? langMap[filters.language]?.() ?? filters.language : t('pages.games.filter_language'))
const sortLabel = computed(() => filters.sort === 'created_at' ? t('pages.games.sort_newest') : t('pages.games.sort_az'))

// ── Filter state ───────────────────────────────────────────────────
const hasActiveFilters = computed(() =>
  !!(filters.search || filters.category || filters.difficulty ||
    filters.players || filters.duration || filters.language ||
    filters.min_age || filters.available)
)

const activeFilterCount = computed(() =>
  [filters.search, filters.category, filters.difficulty, filters.players,
   filters.duration, filters.language, filters.min_age, filters.available]
    .filter(Boolean).length
)

function clearFilters() {
  filters.search = ''
  filters.category = ''
  filters.difficulty = ''
  filters.players = ''
  filters.duration = ''
  filters.language = ''
  filters.min_age = ''
  filters.available = false
  resetPage()
}

// ── Data loading ───────────────────────────────────────────────────
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
  document.addEventListener('pointerdown', onOutsideClick, { capture: true })
  document.addEventListener('keydown', onEscapeKey)
  const [, cats] = await Promise.all([load(), fetchCategories()])
  categories.value = cats.data
})

onUnmounted(() => {
  document.removeEventListener('pointerdown', onOutsideClick, { capture: true })
  document.removeEventListener('keydown', onEscapeKey)
  catPicker?.destroy()
})
</script>

<style lang="scss" scoped>
$hero-bg:       #0F0E0C;
$nav-height:    64px;

$amber-08:      rgba($amber, 0.08);
$amber-15:      rgba($amber, 0.15);
$amber-25:      rgba($amber, 0.25);
$amber-glow:    rgba($amber, 0.20);

$hero-text:     #EEE8DF;
$hero-muted:    rgba(238, 232, 223, 0.72);
$hero-muted-50: rgba(238, 232, 223, 0.55);
$hero-divider:  rgba(238, 232, 223, 0.10);
$hero-input-bg: rgba(255, 255, 255, 0.06);
$hero-input-border: rgba(255, 255, 255, 0.12);

// ─── Keyframes ────────────────────────────────────────────────────
@keyframes chipIn {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0); }
}

@keyframes searchGlow {
  0%, 100% { box-shadow: 0 0 0 0 $amber-15, inset 0 0 0 1.5px $hero-input-border; }
  50%       { box-shadow: 0 0 28px 4px $amber-15, inset 0 0 0 1.5px rgba($amber, 0.5); }
}

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
  padding: calc(#{$nav-height} + 3rem) 1.5rem 2.5rem;
  overflow: hidden;

  &__backdrop { position: absolute; inset: 0; pointer-events: none; }

  &__glow {
    position: absolute;
    width: 600px; height: 600px;
    top: -200px; right: -120px;
    border-radius: 50%;
    filter: blur(110px);
    background: $amber-glow;
    opacity: 0.8;
  }

  &__dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px);
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
    margin: 0 0 0.4rem;
  }

  &__count {
    font-size: 0.9375rem;
    color: $hero-muted;
    padding-bottom: 0;
    margin-bottom: 1.75rem;
  }
}

// ─── Hero Search ──────────────────────────────────────────────────
.hero-search {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  background: $hero-input-bg;
  border: 1.5px solid $hero-input-border;
  border-radius: 14px;
  padding: 0 1rem;
  backdrop-filter: blur(12px);
  transition: border-color 0.25s, box-shadow 0.25s, background 0.25s;

  &--active {
    background: rgba(255, 255, 255, 0.09);
    border-color: rgba($amber, 0.5);
    animation: searchGlow 2.5s ease-in-out infinite;
  }

  &__icon {
    color: $hero-muted;
    font-size: 1.125rem;
    flex-shrink: 0;
    transition: color 0.2s;

    .hero-search--active & { color: $amber; }
  }

  &__input {
    flex: 1;
    height: 52px;
    background: transparent;
    border: none;
    outline: none;
    font-size: 1rem;
    font-family: inherit;
    color: $hero-text;
    caret-color: $amber;

    &::placeholder { color: $hero-muted-50; }
  }

  &__clear {
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.08);
    border: none;
    border-radius: 6px;
    width: 28px;
    height: 28px;
    cursor: pointer;
    color: $hero-muted;
    transition: background 0.15s, color 0.15s;
    flex-shrink: 0;
    .icon { font-size: 0.875rem; }
    &:hover { background: rgba(255,255,255,0.14); color: $hero-text; }
  }
}

// ─── Filter Strip ─────────────────────────────────────────────────
.filter-strip {
  position: sticky;
  top: $nav-height;
  z-index: 50;
  background: var(--secondary-background);
  border-bottom: 1px solid var(--divider);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  transition: box-shadow 0.3s;

  &--filtered {
    box-shadow: 0 2px 12px rgba($amber, 0.08);
  }

  &__scroll {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    overflow-x: auto;
    flex: 1;
    scrollbar-width: none;
    -ms-overflow-style: none;
    &::-webkit-scrollbar { display: none; }
    padding: 0.25rem 0;
  }
}

// ─── Filter Chips ─────────────────────────────────────────────────
.chip-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.375rem 0.625rem 0.375rem 0.75rem;
  background: var(--background);
  border: 1px solid var(--divider);
  border-radius: 999px;
  cursor: pointer;
  white-space: nowrap;
  flex-shrink: 0;
  transition: border-color 0.2s, background 0.2s, color 0.2s;
  animation: chipIn 0.35s ease calc(var(--i, 0) * 40ms) backwards;

  &--active {
    background: $amber-08;
    border-color: $amber-25;
    color: $amber;
  }

  &--sort {
    margin-left: auto;
    border-style: dashed;
  }
}

.chip-select {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
  width: 100%;
  height: 100%;
  border: none;
}

.chip-label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--secondary-text);
  pointer-events: none;

  .chip-wrap--active & { color: $amber; font-weight: 600; }
}

.chip-chevron {
  font-size: 0.875rem;
  color: var(--secondary-text);
  pointer-events: none;
  flex-shrink: 0;
  transition: transform 0.2s;

  .chip-wrap--active & { color: $amber; }
  .chip-wrap--open & { transform: rotate(180deg); }
}

// ─── Availability toggle chip ─────────────────────────────────────
.chip-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.375rem 0.75rem;
  background: var(--background);
  border: 1px solid var(--divider);
  border-radius: 999px;
  font-size: 0.8125rem;
  font-weight: 500;
  font-family: inherit;
  color: var(--secondary-text);
  cursor: pointer;
  white-space: nowrap;
  flex-shrink: 0;
  transition: border-color 0.2s, background 0.2s, color 0.2s;
  animation: chipIn 0.35s ease calc(var(--i, 0) * 40ms) backwards;

  &__icon {
    font-size: 0.875rem;
    opacity: 0.4;
    transition: opacity 0.2s;
  }

  &--active {
    background: $amber-08;
    border-color: $amber-25;
    color: $amber;
    font-weight: 600;

    .chip-btn__icon { opacity: 1; }
  }
}

// ─── Clear button ─────────────────────────────────────────────────
.filter-clear {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  flex-shrink: 0;
  padding: 0.375rem 0.5rem;
  background: none;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  color: var(--secondary-text);
  transition: color 0.2s, background 0.15s;

  .icon { font-size: 1rem; }

  &:hover {
    color: var(--primary-text);
    background: var(--background);
  }

  &__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 4px;
    background: $amber;
    color: #1a0d00;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
    line-height: 1;
  }
}

// ─── Transitions ──────────────────────────────────────────────────
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.slide-clear-enter-active, .slide-clear-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.slide-clear-enter-from, .slide-clear-leave-to { opacity: 0; transform: scale(0.8); }

// ─── Catalog ──────────────────────────────────────────────────────
.catalog {
  flex: 1;
  padding: 2.5rem 1.5rem 4rem;

  &__inner { max-width: 1100px; margin: 0 auto; }

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

// ─── Pagination ───────────────────────────────────────────────────
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

    &:disabled { opacity: 0.35; cursor: not-allowed; }
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
  &__name { font-size: 0.95rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em; }

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

<!-- Global (non-scoped) styles for the teleported category popover -->
<style lang="scss">
.cat-popover {
  position: fixed;
  z-index: 200;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.06);
  overflow: hidden;
  display: flex;
  flex-direction: column;

  // GroupPicker fills the popover
  .group-picker {
    display: flex;
    flex-direction: column;
    max-height: 460px;
    overflow: hidden;
    padding: 0.75rem;
  }

  // Make the selection summary compact inside the popover
  .group-picker__selection {
    min-height: unset;
    margin-bottom: 0.5rem;
    box-shadow: none;

    &:empty { display: none; } // hide when nothing is selected
  }

  // Make the list scrollable
  .group-picker__list {
    flex: 1;
    overflow-y: auto;
    scrollbar-width: thin;
  }

  // Search input spacing
  .group-picker__search {
    margin-bottom: 0.5rem;
  }
}

// Popover enter/leave animation
.cat-pop-enter-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.cat-pop-leave-active {
  transition: opacity 0.12s ease, transform 0.1s ease;
}
.cat-pop-enter-from,
.cat-pop-leave-to {
  opacity: 0;
  transform: translateY(-4px) scale(0.98);
}
</style>
