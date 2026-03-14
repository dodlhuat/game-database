<template>
  <NuxtLink :to="`/games/${game.slug}`">
    <UiCard hoverable>
      <template #header>
        <img
          v-if="game.cover_image_url"
          :src="game.cover_image_url"
          :alt="game.title"
        />
      </template>

      <h3>{{ game.title }}</h3>
      <p v-if="game.category">{{ game.category.name }}</p>

      <UiBadge :variant="game.available_copies_count > 0 ? 'available' : 'loaned'">
        {{ game.available_copies_count > 0 ? 'Verfügbar' : 'Ausgeliehen' }}
      </UiBadge>

      <ul v-if="game.min_players || game.duration_min || game.difficulty">
        <li v-if="game.min_players">
          {{ game.min_players }}{{ game.max_players ? `–${game.max_players}` : '+' }} Spieler
        </li>
        <li v-if="game.duration_min">{{ game.duration_min }} Min.</li>
        <li v-if="game.difficulty">{{ difficultyLabel(game.difficulty) }}</li>
      </ul>
    </UiCard>
  </NuxtLink>
</template>

<script setup lang="ts">
import type { Game } from '~/composables/useGames'

defineProps<{ game: Game }>()

const DIFFICULTY: Record<string, string> = {
  EASY: 'Leicht',
  MEDIUM: 'Mittel',
  HARD: 'Schwer',
  EXPERT: 'Experte',
}

function difficultyLabel(d: string) {
  return DIFFICULTY[d] ?? d
}
</script>
