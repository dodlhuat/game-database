<template>
  <NuxtLink :to="`/games/${game.slug}`" class="game-card">
    <div class="game-card__media">
      <img
        v-if="game.cover_image_url"
        :src="game.cover_image_url"
        :alt="game.title"
        class="game-card__img"
      />
      <div v-else class="game-card__placeholder">
        <span class="icon icon-layers-outline" aria-hidden="true" />
      </div>
      <span
        class="game-card__badge"
        :class="game.available_copies_count > 0 ? 'game-card__badge--avail' : 'game-card__badge--out'"
      >
        {{ game.available_copies_count > 0 ? 'Verfügbar' : 'Ausgeliehen' }}
      </span>
    </div>

    <div class="game-card__body">
      <p v-if="game.category" class="game-card__cat">{{ game.category.name }}</p>
      <h3 class="game-card__title">{{ game.title }}</h3>

      <p v-if="game.short_description" class="game-card__short-desc">{{ game.short_description }}</p>

      <div v-if="game.min_players || game.min_age || game.duration_min || game.difficulty" class="game-card__meta">
        <span v-if="game.min_players" class="game-card__chip">
          {{ game.min_players }}{{ game.max_players ? `–${game.max_players}` : '+' }} Sp.
        </span>
        <span v-if="game.min_age" class="game-card__chip">ab {{ game.min_age }} J.</span>
        <span v-if="game.duration_min" class="game-card__chip">
          {{ game.duration_min }}{{ game.duration_max ? `–${game.duration_max}` : '' }} Min.
        </span>
        <span v-if="game.difficulty" class="game-card__chip">{{ difficultyLabel(game.difficulty) }}</span>
      </div>
    </div>
  </NuxtLink>
</template>

<script setup lang="ts">
import type { Game } from '~/composables/useGames'

defineProps<{ game: Game }>()

const DIFFICULTY: Record<string, string> = {
  EASY: 'Leicht', MEDIUM: 'Mittel', HARD: 'Schwer', EXPERT: 'Experte',
}

function difficultyLabel(d: string) {
  return DIFFICULTY[d] ?? d
}
</script>

<style lang="scss" scoped>
$amber:        #D4921E;
$amber-08:     rgba(212, 146, 30, 0.08);
$amber-30:     rgba(212, 146, 30, 0.30);

.game-card {
  display: flex;
  flex-direction: column;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 12px;
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;

  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
    border-color: $amber-30;
  }

  // ── Media ──────────────────────────────────────────────────────
  &__media {
    position: relative;
    aspect-ratio: 4 / 3;
    overflow: hidden;
    background: var(--background);
    flex-shrink: 0;
  }

  &__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.35s ease;
  }

  &:hover &__img {
    transform: scale(1.04);
  }

  &__placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;

    .icon {
      width: 40px;
      height: 40px;
      color: var(--disabled);
    }
  }

  &__badge {
    position: absolute;
    top: 0.6rem;
    right: 0.6rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 0.2rem 0.55rem;
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

  // ── Body ───────────────────────────────────────────────────────
  &__body {
    padding: 1rem 1.1rem 1.2rem;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    flex: 1;
  }

  &__cat {
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: $amber;
    padding-bottom: 0;
  }

  &__title {
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--primary-text);
    line-height: 1.3;
    margin: 0;
  }

  &__short-desc {
    font-size: 0.8rem;
    line-height: 1.5;
    color: var(--secondary-text);
    padding-bottom: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  &__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
    margin-top: 0.25rem;
  }

  &__chip {
    font-size: 0.72rem;
    font-weight: 500;
    color: var(--secondary-text);
    background: var(--background);
    border: 1px solid var(--divider);
    border-radius: 999px;
    padding: 0.15rem 0.5rem;
  }
}
</style>
