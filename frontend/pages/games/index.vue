<template>
  <main class="content">
    <h1>Spielekatalog</h1>

    <!-- Filter -->
    <div class="card card-flat">
      <form @submit.prevent>
        <div class="row">
          <div class="column">
            <UiInput v-model="filters.search" placeholder="Suche..." />
          </div>
          <div class="column">
            <label>Kategorie</label>
            <select v-model="filters.category" @change="resetPage">
              <option value="">Alle Kategorien</option>
              <option v-for="cat in categories" :key="cat.slug" :value="cat.slug">
                {{ cat.name }} ({{ cat.games_count }})
              </option>
            </select>
          </div>
          <div class="column">
            <label>Schwierigkeit</label>
            <select v-model="filters.difficulty" @change="resetPage">
              <option value="">Alle Schwierigkeiten</option>
              <option value="EASY">Leicht</option>
              <option value="MEDIUM">Mittel</option>
              <option value="HARD">Schwer</option>
              <option value="EXPERT">Experte</option>
            </select>
          </div>
          <div class="column">
            <label>Sortierung</label>
            <select v-model="filters.sort" @change="resetPage">
              <option value="title">Name A–Z</option>
              <option value="created_at">Neueste zuerst</option>
            </select>
          </div>
        </div>
        <label>
          <input v-model="filters.available" type="checkbox" class="styled-checkbox" @change="resetPage" />
          Nur verfügbare Spiele
        </label>
      </form>
    </div>

    <!-- Ergebnisse -->
    <div v-if="loading" class="center"><div class="spinner"></div></div>

    <p v-else-if="!games.length">Keine Spiele gefunden.</p>

    <div v-else>
      <p>{{ meta.total }} Spiele gefunden</p>

      <div class="game-grid">
        <GameCard v-for="game in games" :key="game.id" :game="game" />
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="pagination">
        <span class="pagination-info">Seite {{ filters.page }} von {{ meta.last_page }}</span>
        <div class="pagination-buttons">
          <button
            class="page-btn"
            :disabled="filters.page === 1"
            @click="changePage(filters.page! - 1)"
          >
            Zurück
          </button>
          <button
            class="page-btn"
            :disabled="filters.page === meta.last_page"
            @click="changePage(filters.page! + 1)"
          >
            Weiter
          </button>
        </div>
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
