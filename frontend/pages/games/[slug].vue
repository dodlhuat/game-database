<template>
  <main class="content">
    <div v-if="loading" class="center"><div class="spinner"></div></div>

    <div v-else-if="!game">
      <div class="alert alert-default">
        <p>Spiel nicht gefunden.</p>
        <NuxtLink to="/games">Zurück zum Katalog</NuxtLink>
      </div>
    </div>

    <div v-else>
      <NuxtLink to="/games">← Zurück</NuxtLink>

      <div class="row">
        <div v-if="game.cover_image_url" class="column" style="max-width: 280px; flex: 0 0 auto;">
          <img :src="game.cover_image_url" :alt="game.title" style="width: 100%; border-radius: 0.4rem;" />
        </div>

        <div class="column">
          <div class="card">
            <div class="header">
              <h1>{{ game.title }}</h1>
              <UiBadge :variant="game.available_copies_count > 0 ? 'available' : 'loaned'">
                {{ game.available_copies_count > 0
                  ? `${game.available_copies_count} Kopie(n) verfügbar`
                  : 'Aktuell ausgeliehen' }}
              </UiBadge>
            </div>

            <p v-if="game.category">Kategorie: {{ game.category.name }}</p>

            <ul v-if="hasMeta">
              <li v-if="game.min_players">
                Spieler: {{ game.min_players }}{{ game.max_players ? `–${game.max_players}` : '+' }}
              </li>
              <li v-if="game.duration_min">Dauer: ca. {{ game.duration_min }} Min.</li>
              <li v-if="game.difficulty">Schwierigkeit: {{ difficultyLabel(game.difficulty!) }}</li>
              <li v-if="game.language">Sprache: {{ game.language }}</li>
              <li v-if="game.year">Jahr: {{ game.year }}</li>
            </ul>

            <div v-if="game.tags?.length" class="chips">
              <span v-for="tag in game.tags" :key="tag.id" class="chip">{{ tag.name }}</span>
            </div>

            <p v-if="game.description">{{ game.description }}</p>
          </div>
        </div>
      </div>

      <!-- Reviews -->
      <section v-if="game.reviews_count">
        <h2>Bewertungen ({{ game.reviews_count }})</h2>
      </section>
    </div>
  </main>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { Game } from '~/composables/useGames'

const route = useRoute()
const { fetchGame } = useGames()

const loading = ref(true)
const game = ref<Game | null>(null)

const DIFFICULTY: Record<string, string> = {
  EASY: 'Leicht',
  MEDIUM: 'Mittel',
  HARD: 'Schwer',
  EXPERT: 'Experte',
}

function difficultyLabel(d: string) {
  return DIFFICULTY[d] ?? d
}

const hasMeta = computed(() =>
  game.value && (
    game.value.min_players ||
    game.value.duration_min ||
    game.value.difficulty ||
    game.value.language ||
    game.value.year
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
  title: game.value ? `${game.value.title} — Spielekatalog` : 'Spiel nicht gefunden',
}))
</script>
