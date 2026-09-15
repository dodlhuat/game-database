<template>
  <NuxtLink :to="`/games/${game.slug}`" class="game-card">
    <div class="game-card__media">
      <div v-if="game.cover_image_url && !imgLoaded" class="game-card__media-skeleton skeleton" />
      <img
        v-if="game.cover_image_url"
        :src="game.cover_image_url"
        :alt="game.title"
        class="game-card__img"
        :class="{ 'game-card__img--loaded': imgLoaded }"
        loading="lazy"
        @load="imgLoaded = true"
      />
      <div v-else class="game-card__placeholder">
        <svg class="icon-svg" aria-hidden="true"><use href="/svg-icons/icons.svg#layers" /></svg>
      </div>
      <span
        v-if="auth.isLoggedIn && game.copies_count > 0"
        class="game-card__badge"
        :class="
          game.available_copies_count > 0 ? 'game-card__badge--avail' : 'game-card__badge--out'
        "
      >
        {{
          game.available_copies_count > 0 ? $t('common.badge.available') : $t('common.badge.loaned')
        }}
      </span>
    </div>

    <div class="game-card__body">
      <h3 class="game-card__title">{{ game.title }}</h3>

      <p v-if="game.short_description" class="game-card__short-desc">
        {{ game.short_description }}
      </p>

      <div
        v-if="game.min_players || game.min_age || game.duration_min || game.difficulty"
        class="game-card__meta"
      >
        <span v-if="game.min_players" class="game-card__chip">
          {{ game.min_players }}{{ game.max_players ? `–${game.max_players}` : '+' }} Sp.
        </span>
        <span v-if="game.min_age" class="game-card__chip">ab {{ game.min_age }} J.</span>
        <span v-if="game.duration_min" class="game-card__chip">
          {{ game.duration_min }}{{ game.duration_max ? `–${game.duration_max}` : '' }} Min.
        </span>
        <span v-if="game.difficulty" class="game-card__chip">{{
          difficultyLabel(game.difficulty)
        }}</span>
      </div>
    </div>
  </NuxtLink>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { Game } from '~/composables/useGames'

defineProps<{ game: Game }>()

const auth = useAuthStore()
const imgLoaded = ref(false)

const { t } = useI18n()

const DIFFICULTY: Record<string, string> = {
  EASY: 'admin.form.difficulty_easy',
  MEDIUM: 'admin.form.difficulty_medium',
  HARD: 'admin.form.difficulty_hard',
  EXPERT: 'admin.form.difficulty_expert',
}

function difficultyLabel(d: string) {
  return DIFFICULTY[d] ? t(DIFFICULTY[d]) : d
}
</script>

<style lang="scss" scoped>
$amber-08: rgba(212, 146, 30, 0.08);
$amber-30: rgba(212, 146, 30, 0.3);

@keyframes cardIn {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.game-card {
  display: flex;
  flex-direction: column;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 12px;
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  transition:
    transform 0.22s ease,
    box-shadow 0.22s ease,
    border-color 0.22s ease;
  animation: cardIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) calc(min(var(--i, 0), 10) * 30ms) both;

  &:hover {
    transform: translateY(-6px);
    box-shadow:
      0 20px 48px rgba(0, 0, 0, 0.3),
      0 0 0 1px rgba(212, 146, 30, 0.18);
    border-color: $amber-30;
  }

  // ── Media ──────────────────────────────────────────────────────
  &__media {
    position: relative;
    aspect-ratio: 3 / 4;
    overflow: hidden;
    background: var(--background);
    flex-shrink: 0;

    @media (max-width: 480px) {
      aspect-ratio: unset;
      max-height: 200px;
    }
  }

  &__media-skeleton {
    position: absolute;
    inset: 0;
    border-radius: 0;
  }

  &__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    transition:
      opacity 0.4s ease,
      transform 0.35s ease;

    &--loaded {
      opacity: 1;
    }
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
      color: var(--secondary-text);
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

    // This sits on top of an arbitrary, unpredictable cover image — not
    // the page background — so a translucent tint (--success-tint, a 12%
    // color-mix) lets the artwork show through and can't guarantee
    // contrast against every possible cover. Needs a fully opaque surface
    // instead, like a chip over a photo. --success/--warning are already
    // solid, theme-aware colors; only the text needs to flip per theme,
    // since the dark-mode success/warning colors are much brighter than
    // their light-mode counterparts (same contrast flip basix's own
    // .badge-solid.badge-success does).
    &--avail {
      background: var(--success);
      color: var(--on-accent);
    }

    &--out {
      background: var(--warning);
      color: var(--on-accent);
    }

    // Dark mode's --success/--warning are much brighter (near-neon) than
    // their light-mode counterparts, so white text no longer has enough
    // contrast — flip to dark text, same as basix's own .badge-solid does.
    [data-theme='dark'] &--avail,
    [data-theme='dark'] &--out {
      color: var(--background);
    }
  }

  // ── Body ───────────────────────────────────────────────────────
  &__body {
    padding: 1rem 1.125rem;
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
    font-size: 1.05rem;
    font-weight: 800;
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
