<template>
  <main>
    <div v-if="loading">Lädt...</div>

    <div v-else-if="!game">
      <p>Spiel nicht gefunden.</p>
      <NuxtLink to="/games">Zurück zum Katalog</NuxtLink>
    </div>

    <div v-else>
      <NuxtLink to="/games">← Zurück</NuxtLink>

      <img v-if="game.cover_image_url" :src="game.cover_image_url" :alt="game.title" />

      <h1>{{ game.title }}</h1>

      <UiBadge :variant="game.available_copies_count > 0 ? 'available' : 'loaned'">
        {{ game.available_copies_count > 0
          ? `${game.available_copies_count} Kopie(n) verfügbar`
          : 'Aktuell ausgeliehen' }}
      </UiBadge>

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

      <div v-if="game.tags?.length">
        <UiBadge v-for="tag in game.tags" :key="tag.id" variant="info">{{ tag.name }}</UiBadge>
      </div>

      <p v-if="game.description">{{ game.description }}</p>

      <!-- Reviews -->
      <section v-if="game.reviews_count">
        <h2>Bewertungen ({{ game.reviews_count }})</h2>
        <!-- Reviews werden in Phase 4 vollständig eingebunden -->
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

// SEO
useHead(() => ({
  title: game.value ? `${game.value.title} — Spielekatalog` : 'Spiel nicht gefunden',
}))
</script>
