<template>
  <div class="games-page">
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

        <!-- ── Smart-Search Intent Badge ── -->
        <Transition name="intent-slide">
          <div v-if="smartMeta && smartMeta.intent !== 'FULLTEXT'" class="intent-badge">
            <template v-if="smartMeta.intent === 'SIMILARITY'">
              <span class="icon icon-sync_alt intent-badge__icon" aria-hidden="true" />
              {{ $t('pages.games.intent_similar_to') }}
              <NuxtLink :to="`/games/${smartMeta.reference_slug}`" class="intent-badge__link">
                {{ smartMeta.reference_title }}
              </NuxtLink>
            </template>
            <template v-else-if="smartMeta.intent === 'CATEGORY'">
              <span class="icon icon-category intent-badge__icon" aria-hidden="true" />
              {{ $t('pages.games.intent_category') }}
            </template>
            <template v-else-if="smartMeta.intent === 'TAG'">
              <span class="icon icon-label intent-badge__icon" aria-hidden="true" />
              {{ $t('pages.games.intent_tag') }}
            </template>
          </div>
        </Transition>
      </div>
    </section>

    <!-- ── Filter Bar ────────────────────────────────────────── -->
    <div
      v-show="!isSmartSearch"
      class="filter-bar"
      :class="{ 'filter-bar--active': hasActiveFilters, 'filter-bar--open': filterPanelOpen }"
    >
      <!-- Top row -->
      <div class="filter-bar__row">
        <!-- Toggle button -->
        <button
          class="filter-toggle"
          :class="{ 'filter-toggle--open': filterPanelOpen }"
          :aria-expanded="filterPanelOpen"
          @click="filterPanelOpen = !filterPanelOpen"
        >
          <span class="icon icon-tune filter-toggle__icon" aria-hidden="true" />
          <span class="filter-toggle__label">{{ $t('pages.games.filter_open') }}</span>
          <Transition name="badge-pop">
            <span v-if="activeFilterCount" class="filter-toggle__badge">{{
              activeFilterCount
            }}</span>
          </Transition>
        </button>

        <!-- Active filter pills (only when panel is closed) -->
        <div v-if="hasActiveFilters && !filterPanelOpen" class="active-pills-wrap">
          <div class="active-pills">
            <button
              v-if="filters.category"
              class="active-pill"
              @click.stop="
                filters.category = ''
                resetPage()
              "
            >
              <span class="active-pill__text">{{ categoryLabel }}</span>
              <span class="icon icon-close active-pill__x" aria-hidden="true" />
            </button>
            <button
              v-if="filters.difficulty"
              class="active-pill"
              @click.stop="
                filters.difficulty = ''
                resetPage()
              "
            >
              <span class="active-pill__text">{{ difficultyLabel }}</span>
              <span class="icon icon-close active-pill__x" aria-hidden="true" />
            </button>
            <button
              v-if="filters.players"
              class="active-pill"
              @click.stop="
                filters.players = ''
                resetPage()
              "
            >
              <span class="active-pill__text">{{ playersLabel }}</span>
              <span class="icon icon-close active-pill__x" aria-hidden="true" />
            </button>
            <button
              v-if="filters.duration"
              class="active-pill"
              @click.stop="
                filters.duration = ''
                resetPage()
              "
            >
              <span class="active-pill__text">{{ durationLabel }}</span>
              <span class="icon icon-close active-pill__x" aria-hidden="true" />
            </button>
            <button
              v-if="filters.min_age"
              class="active-pill"
              @click.stop="
                filters.min_age = ''
                resetPage()
              "
            >
              <span class="active-pill__text">{{ ageLabel }}</span>
              <span class="icon icon-close active-pill__x" aria-hidden="true" />
            </button>
            <button
              v-if="filters.language"
              class="active-pill"
              @click.stop="
                filters.language = ''
                resetPage()
              "
            >
              <span class="active-pill__text">{{ languageLabel }}</span>
              <span class="icon icon-close active-pill__x" aria-hidden="true" />
            </button>
            <button
              v-if="filters.available"
              class="active-pill"
              @click.stop="
                filters.available = false
                resetPage()
              "
            >
              <span class="active-pill__text">{{ $t('pages.games.filter_available') }}</span>
              <span class="icon icon-close active-pill__x" aria-hidden="true" />
            </button>
          </div>
        </div>

        <!-- Spacer when no active pills -->
        <div v-else class="filter-bar__spacer" />

        <!-- Right cluster: sort + clear -->
        <div class="filter-bar__controls">
          <div class="sort-control" role="group" :aria-label="$t('pages.games.sort_az')">
            <button
              class="sort-btn"
              :class="{ 'sort-btn--active': filters.sort === 'title' }"
              @click="
                filters.sort = 'title'
                resetPage()
              "
            >
              <span class="sort-btn__label">{{ $t('pages.games.sort_az') }}</span>
            </button>
            <button
              class="sort-btn"
              :class="{ 'sort-btn--active': filters.sort === 'created_at' }"
              @click="
                filters.sort = 'created_at'
                resetPage()
              "
            >
              <span class="sort-btn__label">{{ $t('pages.games.sort_newest') }}</span>
            </button>
          </div>

          <Transition name="slide-clear">
            <button
              v-if="hasActiveFilters"
              class="clear-all-btn"
              :aria-label="$t('pages.games.filter_clear')"
              @click="clearFilters"
            >
              <span class="icon icon-close" aria-hidden="true" />
            </button>
          </Transition>
        </div>
      </div>

      <!-- ── Expandable filter panel ──────────────────────────── -->
      <div class="filter-panel" :class="{ 'filter-panel--open': filterPanelOpen }">
        <div class="filter-panel__inner">
          <!-- Category -->
          <div class="filter-section filter-section--category">
            <h4 class="filter-section__title">{{ $t('pages.games.filter_category') }}</h4>
            <div ref="catPickerEl" class="cat-picker-host" />
          </div>

          <!-- Difficulty -->
          <div class="filter-section">
            <h4 class="filter-section__title">{{ $t('pages.games.filter_difficulty') }}</h4>
            <div
              class="option-chips"
              role="group"
              :aria-label="$t('pages.games.filter_difficulty')"
            >
              <button
                v-for="d in difficultyOptions"
                :key="d.value"
                class="option-chip"
                :class="{ 'option-chip--active': filters.difficulty === d.value }"
                @click="selectFilter('difficulty', d.value)"
              >
                {{ d.label }}
              </button>
            </div>
          </div>

          <!-- Players -->
          <div class="filter-section">
            <h4 class="filter-section__title">{{ $t('pages.games.filter_players') }}</h4>
            <div class="player-chips" role="group" :aria-label="$t('pages.games.filter_players')">
              <button
                v-for="p in playerOptions"
                :key="p.value"
                class="player-chip"
                :class="{ 'player-chip--active': filters.players === p.value }"
                :aria-pressed="filters.players === p.value"
                @click="selectFilter('players', p.value)"
              >
                {{ p.label }}
              </button>
            </div>
          </div>

          <!-- Duration -->
          <div class="filter-section">
            <h4 class="filter-section__title">{{ $t('pages.games.filter_duration') }}</h4>
            <div class="option-chips" role="group" :aria-label="$t('pages.games.filter_duration')">
              <button
                v-for="d in durationOptions"
                :key="d.value"
                class="option-chip"
                :class="{ 'option-chip--active': filters.duration === d.value }"
                @click="selectFilter('duration', d.value)"
              >
                {{ d.label }}
              </button>
            </div>
          </div>

          <!-- Age -->
          <div class="filter-section">
            <h4 class="filter-section__title">{{ $t('pages.games.filter_age') }}</h4>
            <div class="option-chips" role="group" :aria-label="$t('pages.games.filter_age')">
              <button
                v-for="a in ageOptions"
                :key="a.value"
                class="option-chip"
                :class="{ 'option-chip--active': filters.min_age === a.value }"
                @click="selectFilter('min_age', a.value)"
              >
                {{ a.label }}
              </button>
            </div>
          </div>

          <!-- Language -->
          <div v-if="allLanguages.length" class="filter-section">
            <h4 class="filter-section__title">{{ $t('pages.games.filter_language') }}</h4>
            <div class="option-chips" role="group" :aria-label="$t('pages.games.filter_language')">
              <button
                v-for="l in allLanguages"
                :key="l.id"
                class="option-chip"
                :class="{ 'option-chip--active': String(filters.language) === String(l.id) }"
                @click="selectLanguage(l.id)"
              >
                {{ l.name }}
              </button>
            </div>
          </div>

          <!-- Available -->
          <div class="filter-section filter-section--switch">
            <div class="switch-row">
              <h4 class="filter-section__title filter-section__title--inline">
                {{ $t('pages.games.filter_available') }}
              </h4>
              <UiSwitch v-model="filters.available" @update:model-value="resetPage" />
            </div>
          </div>
        </div>
      </div>
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
            <GameCard
              v-for="(game, index) in games"
              :key="game.id"
              :game="game"
              :style="{ '--i': index }"
            />
          </div>

          <div v-if="!isSmartSearch && meta.last_page > 1" class="pagination">
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

    <AppFooter />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import type { Game, SmartSearchMeta } from '~/composables/useGames'
import { GroupPicker } from '@dodlhuat/basix/js/group-picker'

const { fetchGames, fetchCategories, fetchLanguages, smartSearch } = useGames()
const { t } = useI18n()

type CategoryItem = {
  id: number
  slug: string
  name: string
  games_count: number
  children: CategoryItem[]
}

const loading = ref(true)
const games = ref<Game[]>([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 24, total: 0 })
const smartMeta = ref<SmartSearchMeta | null>(null)
const categories = ref<CategoryItem[]>([])
const allLanguages = ref<{ id: number; name: string }[]>([])
const searchFocused = ref(false)
const filterPanelOpen = ref(false)

const isSmartSearch = computed(() => !!filters.search)

// ── Filter options ─────────────────────────────────────────────────
const difficultyOptions = computed(() => [
  { label: t('pages.games.filter_difficulty_easy'), value: 'EASY' },
  { label: t('pages.games.filter_difficulty_medium'), value: 'MEDIUM' },
  { label: t('pages.games.filter_difficulty_hard'), value: 'HARD' },
  { label: t('pages.games.filter_difficulty_expert'), value: 'EXPERT' },
])

const playerOptions = computed(() => [
  { label: '1', value: '1' },
  { label: '2', value: '2' },
  { label: '3', value: '3' },
  { label: '4', value: '4' },
  { label: '5', value: '5' },
  { label: '6+', value: '6' },
])

const durationOptions = computed(() => [
  { label: t('pages.games.filter_duration_short'), value: 'short' },
  { label: t('pages.games.filter_duration_medium'), value: 'medium' },
  { label: t('pages.games.filter_duration_long'), value: 'long' },
])

const ageOptions = computed(() => [
  { label: t('pages.games.filter_age_from', { n: 6 }), value: '6' },
  { label: t('pages.games.filter_age_from', { n: 8 }), value: '8' },
  { label: t('pages.games.filter_age_from', { n: 10 }), value: '10' },
  { label: t('pages.games.filter_age_from', { n: 12 }), value: '12' },
  { label: t('pages.games.filter_age_from', { n: 14 }), value: '14' },
  { label: t('pages.games.filter_age_from', { n: 16 }), value: '16' },
  { label: t('pages.games.filter_age_from', { n: 18 }), value: '18' },
])

// ── Category picker ────────────────────────────────────────────────
const catPickerEl = ref<HTMLElement | null>(null)
let catPicker: InstanceType<typeof GroupPicker> | null = null
let isInitializingPicker = false

function initCatPicker() {
  if (!catPickerEl.value) return
  catPicker?.destroy()

  const data = categories.value.map((cat) => {
    const subs = (cat.children ?? []).map((c) => ({ id: c.slug, label: c.name }))
    return subs.length
      ? { id: cat.slug, label: cat.name, subgroups: subs }
      : { id: cat.slug, label: cat.name }
  })

  catPicker = new GroupPicker(catPickerEl.value, data, {
    searchPlaceholder: t('pages.games.category_search'),
    selectAllLabel: t('pages.games.category_select_all'),
    deselectLabel: t('pages.games.category_deselect'),
    emptyLabel: t('pages.games.category_empty'),
    onSelectionChange: (sel: {
      parentGroups: string[]
      subgroups: { groupId: string; subgroupId: string }[]
    }) => {
      if (isInitializingPicker) return
      const slugs = [...sel.parentGroups, ...sel.subgroups.map((s) => s.subgroupId)]
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
      if (categories.value.some((c) => c.slug === slug)) {
        parentGroups.push(slug)
      } else {
        for (const parent of categories.value) {
          if ((parent.children ?? []).some((c) => c.slug === slug)) {
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

// Init/re-init picker when panel opens and categories are ready
watch([filterPanelOpen, categories], ([open, cats]) => {
  if (open && cats.length > 0) {
    nextTick(() => initCatPicker())
  }
})

function onEscapeKey(e: KeyboardEvent) {
  if (e.key === 'Escape' && filterPanelOpen.value) filterPanelOpen.value = false
}

// ── Filters ────────────────────────────────────────────────────────
const filters = reactive({
  search: '',
  category: '',
  difficulty: '',
  players: '',
  duration: '' as '' | 'short' | 'medium' | 'long',
  language: '' as string | number,
  min_age: '',
  available: false,
  sort: 'title',
  page: 1,
})

// ── Active filter labels ───────────────────────────────────────────
const categoryLabel = computed(() => {
  if (!filters.category) return t('pages.games.filter_category')
  const slugs = filters.category.split(',').filter(Boolean)
  if (slugs.length === 0) return t('pages.games.filter_category')
  if (slugs.length > 1) return `${slugs.length} ${t('pages.games.filter_category')}`
  const slug = slugs[0]
  for (const cat of categories.value) {
    if (cat.slug === slug) return cat.name
    const child = (cat.children ?? []).find((c) => c.slug === slug)
    if (child) return child.name
  }
  return t('pages.games.filter_category')
})

const difficultyLabel = computed(() => {
  return difficultyOptions.value.find((o) => o.value === filters.difficulty)?.label ?? ''
})

const playersLabel = computed(() => {
  return playerOptions.value.find((o) => o.value === filters.players)?.label ?? ''
})

const durationLabel = computed(() => {
  return durationOptions.value.find((o) => o.value === filters.duration)?.label ?? ''
})

const ageLabel = computed(() => {
  return ageOptions.value.find((o) => o.value === filters.min_age)?.label ?? ''
})

const languageLabel = computed(() => {
  if (!filters.language) return ''
  return allLanguages.value.find((l) => String(l.id) === String(filters.language))?.name ?? ''
})

// ── Filter state ───────────────────────────────────────────────────
const hasActiveFilters = computed(
  () =>
    !!(
      filters.search ||
      filters.category ||
      filters.difficulty ||
      filters.players ||
      filters.duration ||
      filters.language ||
      filters.min_age ||
      filters.available
    )
)

const activeFilterCount = computed(
  () =>
    [
      filters.search,
      filters.category,
      filters.difficulty,
      filters.players,
      filters.duration,
      filters.language,
      filters.min_age,
      filters.available,
    ].filter(Boolean).length
)

// ── Filter actions ─────────────────────────────────────────────────
function selectFilter(key: 'difficulty' | 'players' | 'duration' | 'min_age', value: string) {
  filters[key] = filters[key] === value ? '' : (value as never)
  resetPage()
}

function selectLanguage(id: number) {
  filters.language = String(filters.language) === String(id) ? '' : id
  resetPage()
}

function clearFilters() {
  filters.search = ''
  filters.category = ''
  filters.difficulty = ''
  filters.players = ''
  filters.duration = ''
  filters.language = ''
  filters.min_age = ''
  filters.available = false
  catPicker?.setSelection({ parentGroups: [], subgroups: [] })
  resetPage()
}

// ── Data loading ───────────────────────────────────────────────────
async function load() {
  loading.value = true
  try {
    if (filters.search) {
      const data = await smartSearch(filters.search)
      games.value = data.data
      smartMeta.value = data.meta
      meta.value = {
        current_page: 1,
        last_page: 1,
        per_page: data.data.length,
        total: data.data.length,
      }
    } else {
      smartMeta.value = null
      const data = await fetchGames(filters)
      games.value = data.data
      meta.value = data.meta
    }
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
watch(
  () => filters.search,
  () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(resetPage, 300)
  }
)

useAsyncData(
  'games-init',
  async () => {
    const [, cats, langs] = await Promise.all([load(), fetchCategories(), fetchLanguages()])
    categories.value = cats.data as CategoryItem[]
    allLanguages.value = langs
  },
  { server: false }
)

onMounted(() => {
  document.addEventListener('keydown', onEscapeKey)
})

onUnmounted(() => {
  document.removeEventListener('keydown', onEscapeKey)
  catPicker?.destroy()
})
</script>

<style lang="scss" scoped>
$hero-bg: #0f0e0c;
$nav-height: 64px;

$amber-08: rgba($amber, 0.08);
$amber-12: rgba($amber, 0.12);
$amber-15: rgba($amber, 0.15);
$amber-25: rgba($amber, 0.25);
$amber-35: rgba($amber, 0.35);
$amber-glow: rgba($amber, 0.2);

$hero-text: #eee8df;
$hero-muted: rgba(238, 232, 223, 0.72);
$hero-muted-50: rgba(238, 232, 223, 0.55);
$hero-divider: rgba(238, 232, 223, 0.1);
$hero-input-bg: rgba(255, 255, 255, 0.06);
$hero-input-border: rgba(255, 255, 255, 0.12);

// ─── Keyframes ────────────────────────────────────────────────────
@keyframes searchGlow {
  0%,
  100% {
    box-shadow:
      0 0 0 0 $amber-15,
      inset 0 0 0 1.5px $hero-input-border;
  }
  50% {
    box-shadow:
      0 0 28px 4px $amber-15,
      inset 0 0 0 1.5px rgba($amber, 0.5);
  }
}

@keyframes badgePop {
  0% {
    transform: scale(0.5);
    opacity: 0;
  }
  70% {
    transform: scale(1.15);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

@keyframes playerRingPulse {
  0% {
    box-shadow:
      0 0 0 0 $amber-25,
      0 0 0 0 $amber-12;
  }
  60% {
    box-shadow:
      0 0 0 6px $amber-08,
      0 0 0 11px rgba($amber, 0.03);
  }
  100% {
    box-shadow:
      0 0 0 0 transparent,
      0 0 0 0 transparent;
  }
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

  &__backdrop {
    position: absolute;
    inset: 0;
    pointer-events: none;
  }

  &__glow {
    position: absolute;
    width: 600px;
    height: 600px;
    top: -200px;
    right: -120px;
    border-radius: 50%;
    filter: blur(110px);
    background: $amber-glow;
    opacity: 0.8;
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
  transition:
    border-color 0.25s,
    box-shadow 0.25s,
    background 0.25s;

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

    .hero-search--active & {
      color: $amber;
    }
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

    &::placeholder {
      color: $hero-muted-50;
    }
  }

  &__clear {
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.08);
    border: none;
    border-radius: 6px;
    width: 28px;
    height: 28px;
    cursor: pointer;
    color: $hero-muted;
    transition:
      background 0.15s,
      color 0.15s;
    flex-shrink: 0;
    .icon {
      font-size: 0.875rem;
    }
    &:hover {
      background: rgba(255, 255, 255, 0.14);
      color: $hero-text;
    }
  }
}

// ─── Intent Badge ─────────────────────────────────────────────────
.intent-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin-top: 0.85rem;
  padding: 0.35rem 0.8rem;
  background: rgba($amber, 0.12);
  border: 1px solid rgba($amber, 0.3);
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 500;
  color: rgba($hero-text, 0.85);

  &__icon {
    font-size: 0.9em;
    color: $amber;
    opacity: 0.85;
  }

  &__link {
    font-weight: 700;
    color: $amber;
    text-decoration: underline;
    text-underline-offset: 2px;
    text-decoration-color: rgba($amber, 0.4);
    transition: text-decoration-color 0.15s;

    &:hover {
      text-decoration-color: $amber;
    }
  }
}

.intent-slide-enter-active,
.intent-slide-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}
.intent-slide-enter-from,
.intent-slide-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

// ─── Filter Bar ───────────────────────────────────────────────────
.filter-bar {
  position: sticky;
  top: $nav-height;
  z-index: 50;
  background: rgba($hero-bg, 0.9);
  -webkit-backdrop-filter: blur(20px) saturate(160%);
  backdrop-filter: blur(20px) saturate(160%);
  border-bottom: 1px solid var(--divider);
  transition:
    box-shadow 0.45s,
    border-bottom-color 0.3s;

  // Amber sweep line — contracts inward from edges as filters activate
  &::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 30%;
    right: 30%;
    height: 1px;
    background: linear-gradient(to right, transparent, $amber-35, transparent);
    opacity: 0;
    transition:
      opacity 0.5s ease,
      left 0.6s cubic-bezier(0.16, 1, 0.3, 1),
      right 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    pointer-events: none;
  }

  &--active {
    box-shadow: 0 4px 40px rgba($amber, 0.05);

    &::after {
      opacity: 1;
      left: 12%;
      right: 12%;
    }
  }

  // When panel is open, dissolve the bottom border so panel feels like an extension
  &--open {
    border-bottom-color: transparent;
  }

  &__row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    min-height: 52px;
  }

  &__spacer {
    flex: 1;
  }

  &__controls {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    flex-shrink: 0;
  }
}

// ─── Filter Toggle Button ─────────────────────────────────────────
.filter-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.4375rem 0.8125rem 0.4375rem 0.625rem;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.09);
  border-radius: 12px;
  cursor: pointer;
  font-family: inherit;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--secondary-text);
  white-space: nowrap;
  flex-shrink: 0;
  transition:
    border-color 0.25s,
    background 0.25s,
    color 0.25s,
    box-shadow 0.25s;

  &:hover:not(&--open) {
    border-color: rgba(255, 255, 255, 0.18);
    background: rgba(255, 255, 255, 0.07);
    color: var(--primary-text);
  }

  &--open {
    background: $amber-08;
    border-color: $amber-25;
    color: $amber;
    box-shadow:
      0 0 0 1px $amber-12,
      0 0 22px $amber-08;

    .filter-toggle__icon {
      transform: rotate(180deg);
      filter: drop-shadow(0 0 5px $amber-35);
    }
  }

  &__icon {
    font-size: 1rem;
    flex-shrink: 0;
    transition:
      transform 0.4s cubic-bezier(0.16, 1, 0.3, 1),
      filter 0.25s;
  }

  &__label {
    @media (max-width: 360px) {
      display: none;
    }
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
    font-size: 0.68rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: 0;
    box-shadow: 0 0 8px $amber-35;
  }
}

// ─── Active filter pills ──────────────────────────────────────────
.active-pills-wrap {
  flex: 1;
  min-width: 0;
  position: relative;
  overflow: hidden;

  &::after {
    content: '';
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 36px;
    background: linear-gradient(to right, transparent, rgba($hero-bg, 0.9));
    pointer-events: none;
  }
}

.active-pills {
  display: flex;
  gap: 0.3rem;
  overflow-x: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
  padding: 0.1875rem 0;

  &::-webkit-scrollbar {
    display: none;
  }
}

.active-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.4375rem 0.25rem 0.625rem;
  background: $amber-08;
  border: 1px solid $amber-15;
  border-radius: 8px;
  cursor: pointer;
  white-space: nowrap;
  flex-shrink: 0;
  font-family: inherit;
  transition:
    background 0.2s,
    border-color 0.2s,
    box-shadow 0.2s;

  &__text {
    font-size: 0.75rem;
    font-weight: 600;
    color: $amber;
    letter-spacing: 0.01em;
  }

  &__x {
    font-size: 0.625rem;
    color: rgba($amber, 0.45);
    transition:
      color 0.15s,
      transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    align-items: center;
  }

  &:hover {
    background: $amber-15;
    border-color: $amber-25;
    box-shadow: 0 0 12px $amber-08;

    .active-pill__x {
      color: $amber;
      transform: scale(1.25);
    }
  }

  &:focus-visible {
    outline: 2px solid $amber;
    outline-offset: 2px;
  }
}

// ─── Sort segmented control ───────────────────────────────────────
.sort-control {
  display: inline-flex;
  flex-shrink: 0;
  gap: 0;
  position: relative;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  padding: 0.1875rem;
}

.sort-btn {
  position: relative;
  padding: 0.25rem 0.625rem;
  font-family: inherit;
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--secondary-text);
  background: transparent;
  border: none;
  border-radius: 7px;
  cursor: pointer;
  white-space: nowrap;
  transition:
    background 0.2s,
    color 0.2s,
    box-shadow 0.2s;

  &--active {
    background: rgba(255, 255, 255, 0.07);
    color: $amber;
    font-weight: 600;
    box-shadow: 0 1px 6px rgba(0, 0, 0, 0.35);

    // Amber underline indicator
    &::after {
      content: '';
      position: absolute;
      bottom: 3px;
      left: 50%;
      transform: translateX(-50%);
      width: 55%;
      height: 2px;
      background: $amber;
      border-radius: 1px;
      box-shadow: 0 0 8px $amber-35;
    }
  }

  &:hover:not(&--active) {
    color: var(--primary-text);
    background: rgba(255, 255, 255, 0.04);
  }

  &:focus-visible {
    outline: 2px solid $amber;
    outline-offset: 2px;
    border-radius: 7px;
  }
}

// ─── Clear all button ─────────────────────────────────────────────
.clear-all-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 9px;
  cursor: pointer;
  color: var(--secondary-text);
  transition:
    color 0.2s,
    background 0.2s,
    border-color 0.2s,
    box-shadow 0.2s;

  .icon {
    font-size: 0.875rem;
  }

  &:hover {
    color: #e05555;
    background: rgba(200, 50, 50, 0.09);
    border-color: rgba(200, 50, 50, 0.22);
    box-shadow: 0 0 12px rgba(200, 50, 50, 0.12);
  }

  &:focus-visible {
    outline: 2px solid $amber;
    outline-offset: 2px;
  }
}

// ─── Filter Panel ─────────────────────────────────────────────────
.filter-panel {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.5s cubic-bezier(0.16, 1, 0.3, 1);

  &--open {
    max-height: 980px;
    border-top: 1px solid var(--divider);
  }

  &__inner {
    display: grid;
    grid-template-columns: 1fr;
    padding: 0.75rem 0.875rem 1.125rem;
    gap: 0.3125rem;

    @media (min-width: 640px) {
      grid-template-columns: repeat(2, 1fr);
      column-gap: 0.4375rem;

      .filter-section--category {
        grid-column: 1 / -1;
      }
    }

    @media (min-width: 1000px) {
      grid-template-columns: repeat(3, 1fr);
    }
  }
}

// ─── Filter sections ──────────────────────────────────────────────
// Default state: invisible (hidden by overflow anyway when panel is closed)
// Transition is fast with no delay — so closing is immediate
.filter-section {
  padding: 0.75rem 0.875rem;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.024);
  border: 1px solid transparent;
  opacity: 0;
  transform: translateY(-8px);
  transition:
    opacity 0.18s ease,
    transform 0.18s ease,
    background 0.25s,
    border-color 0.25s;

  &:hover {
    background: rgba(255, 255, 255, 0.042);
    border-color: rgba(255, 255, 255, 0.055);
  }

  // Staggered reveal — only applies while panel is open
  // Longer duration + per-child delay for the open direction
  .filter-panel--open & {
    opacity: 1;
    transform: translateY(0);
    transition:
      opacity 0.32s ease,
      transform 0.4s cubic-bezier(0.16, 1, 0.3, 1),
      background 0.25s,
      border-color 0.25s;

    @for $i from 1 through 8 {
      &:nth-child(#{$i}) {
        transition-delay: #{($i - 1) * 38}ms;
      }
    }
  }

  &__title {
    display: flex;
    align-items: center;
    gap: 0.4375rem;
    font-size: 0.67rem;
    font-weight: 700;
    letter-spacing: 0.11em;
    text-transform: uppercase;
    color: var(--secondary-text);
    opacity: 0.6;
    margin-bottom: 0.6875rem;

    // Amber accent dot — subtle hierarchy marker
    &::before {
      content: '';
      flex-shrink: 0;
      width: 3px;
      height: 3px;
      border-radius: 50%;
      background: $amber;
      opacity: 0.65;
    }

    &--inline {
      margin-bottom: 0;
    }
  }

  &--switch {
    .switch-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding-top: 0.125rem;
    }
  }

  &--category {
    background: rgba(255, 255, 255, 0.032);
  }
}

// ─── Option chip grids (difficulty / duration / age / language) ───
.option-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.3125rem;
}

.option-chip {
  padding: 0.375rem 0.8125rem;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 999px;
  font-family: inherit;
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--secondary-text);
  cursor: pointer;
  white-space: nowrap;
  transition:
    background 0.2s,
    border-color 0.2s,
    color 0.2s,
    box-shadow 0.2s,
    transform 0.18s cubic-bezier(0.16, 1, 0.3, 1);

  &--active {
    background: radial-gradient(ellipse at 50% 0%, $amber-15 0%, $amber-08 100%);
    border-color: $amber-25;
    color: $amber;
    font-weight: 600;
    box-shadow:
      0 0 0 1px $amber-12,
      0 0 16px $amber-08;
    text-shadow: 0 0 10px $amber-35;
  }

  &:hover:not(&--active) {
    border-color: rgba(255, 255, 255, 0.16);
    color: var(--primary-text);
    background: rgba(255, 255, 255, 0.07);
    transform: translateY(-1px);
  }

  &:active {
    transform: scale(0.95);
  }

  &:focus-visible {
    outline: 2px solid $amber;
    outline-offset: 2px;
  }
}

// ─── Player number circles ────────────────────────────────────────
.player-chips {
  display: flex;
  gap: 0.4375rem;
  flex-wrap: wrap;
}

.player-chip {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 50%;
  font-family: inherit;
  font-size: 0.9375rem;
  font-weight: 700;
  color: var(--secondary-text);
  cursor: pointer;
  transition:
    background 0.22s,
    border-color 0.22s,
    color 0.22s,
    box-shadow 0.22s,
    transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);

  &--active {
    background: radial-gradient(circle at center, $amber-25 0%, $amber-08 70%);
    border-color: $amber-35;
    color: $amber;
    font-weight: 800;
    // Three-layer ring: tight border + soft halo + ambient glow
    box-shadow:
      0 0 0 1px $amber-25,
      0 0 0 5px $amber-08,
      0 0 22px $amber-12;
    text-shadow: 0 0 8px $amber-25;
    transform: scale(1.07);
    animation: playerRingPulse 2.4s ease-out infinite;
  }

  &:hover:not(&--active) {
    border-color: rgba(255, 255, 255, 0.2);
    color: var(--primary-text);
    background: rgba(255, 255, 255, 0.07);
    transform: scale(1.1);
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.4);
  }

  &:active:not(&--active) {
    transform: scale(0.93);
  }

  &:focus-visible {
    outline: 2px solid $amber;
    outline-offset: 3px;
  }
}

// ─── Transitions ──────────────────────────────────────────────────
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-clear-enter-active,
.slide-clear-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}
.slide-clear-enter-from,
.slide-clear-leave-to {
  opacity: 0;
  transform: scale(0.8);
}

.badge-pop-enter-active {
  animation: badgePop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.badge-pop-leave-active {
  transition:
    opacity 0.15s,
    transform 0.15s;
}
.badge-pop-leave-to {
  opacity: 0;
  transform: scale(0.5);
}

// ─── Catalog ──────────────────────────────────────────────────────
.catalog {
  flex: 1;
  padding: 2.5rem 1.5rem 4rem;

  &__inner {
    max-width: 1100px;
    margin: 0 auto;
    min-height: 400px;
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

  @media (max-width: 1100px) {
    grid-template-columns: repeat(3, 1fr);
  }
  @media (max-width: 750px) {
    grid-template-columns: repeat(2, 1fr);
  }
  @media (max-width: 480px) {
    grid-template-columns: 1fr;
  }
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
    transition:
      border-color 0.2s,
      color 0.2s;

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
</style>

<!-- Global (non-scoped) styles for the inline GroupPicker in the filter panel -->
<style lang="scss">
// GroupPicker inside the filter panel — stripped back to match dark aesthetic
.cat-picker-host {
  .group-picker {
    display: flex;
    flex-direction: column;
    max-height: 280px;
    overflow: hidden;
  }

  .group-picker__search {
    margin-bottom: 0.5rem;
  }

  .group-picker__list {
    flex: 1;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.12) transparent;

    &::-webkit-scrollbar {
      width: 4px;
    }
    &::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.12);
      border-radius: 999px;
    }
  }

  .group-picker__selection {
    min-height: unset;
    margin-bottom: 0.5rem;

    &:empty {
      display: none;
    }
  }
}
</style>
