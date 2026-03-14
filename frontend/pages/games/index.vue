<template>
  <main>
    <h1>Spielekatalog</h1>

    <!-- Filter -->
    <form @submit.prevent>
      <UiInput v-model="filters.search" placeholder="Suche..." />

      <select v-model="filters.category" @change="resetPage">
        <option value="">Alle Kategorien</option>
        <option v-for="cat in categories" :key="cat.slug" :value="cat.slug">
          {{ cat.name }} ({{ cat.games_count }})
        </option>
      </select>

      <select v-model="filters.difficulty" @change="resetPage">
        <option value="">Alle Schwierigkeiten</option>
        <option value="EASY">Leicht</option>
        <option value="MEDIUM">Mittel</option>
        <option value="HARD">Schwer</option>
        <option value="EXPERT">Experte</option>
      </select>

      <label>
        <input v-model="filters.available" type="checkbox" @change="resetPage" />
        Nur verfügbare Spiele
      </label>

      <select v-model="filters.sort" @change="resetPage">
        <option value="title">Name A–Z</option>
        <option value="created_at">Neueste zuerst</option>
      </select>
    </form>

    <!-- Ergebnisse -->
    <p v-if="loading">Lädt...</p>
    <p v-else-if="!games.length">Keine Spiele gefunden.</p>

    <div v-else>
      <p>{{ meta.total }} Spiele gefunden</p>

      <div class="game-grid">
        <GameCard v-for="game in games" :key="game.id" :game="game" />
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1">
        <UiButton
          variant="secondary"
          :disabled="filters.page === 1"
          @click="changePage(filters.page! - 1)"
        >
          Zurück
        </UiButton>
        <span>Seite {{ filters.page }} von {{ meta.last_page }}</span>
        <UiButton
          variant="secondary"
          :disabled="filters.page === meta.last_page"
          @click="changePage(filters.page! + 1)"
        >
          Weiter
        </UiButton>
      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
import { ref, reactive, watch, onMounted } from 'vue'
import type { Game } from '~/composables/useGames'

const { fetchGames, fetchCategories } = useGames()

const loading = ref(false)
const games = ref<Game[]>([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 24, total: 0 })
const categories = ref<{ slug: string; name: string; games_count: number }[]>([])

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
}

// Suche mit Debounce
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
