<template>
  <div class="sponsors-page">
    <!-- ── Hero ──────────────────────────────────────────────────── -->
    <section class="sp-hero">
      <div class="sp-hero__backdrop" aria-hidden="true">
        <div class="sp-hero__glow" />
        <div class="sp-hero__dots" />
        <svg class="sp-hero__hex" viewBox="0 0 100 100">
          <polygon points="50,3 91,26.5 91,73.5 50,97 9,73.5 9,26.5" />
          <polygon points="50,15 80.5,32.5 80.5,67.5 50,85 19.5,67.5 19.5,32.5" />
        </svg>
      </div>
      <div class="sp-hero__body">
        <NuxtLink to="/" class="sp-hero__back">{{ $t('common.back_to_home') }}</NuxtLink>
        <p class="sp-hero__eyebrow">
          <svg class="icon-svg" aria-hidden="true">
            <use href="/svg-icons/icons.svg#handshake" />
          </svg>
          {{ $t('sponsors.eyebrow') }}
        </p>
        <h1 class="sp-hero__title">
          {{ $t('sponsors.title_lead') }}
          <em>{{ $t('sponsors.title_accent') }}</em>
        </h1>
        <p class="sp-hero__lead">{{ $t('sponsors.lead') }}</p>
      </div>
    </section>

    <!-- ── Content ───────────────────────────────────────────────── -->
    <div class="sp-content">
      <div class="sp-content__inner">
        <div v-if="loading" class="sp-state">
          <div class="spinner" />
        </div>

        <div v-else-if="!sponsors.length" class="sp-state sp-state--empty">
          <p>{{ $t('sponsors.empty') }}</p>
        </div>

        <template v-else>
          <!-- Logo wall -->
          <ul v-if="logoSponsors.length" class="logo-wall" :aria-label="$t('sponsors.title')">
            <li
              v-for="(sponsor, i) in logoSponsors"
              :key="sponsor.id"
              class="logo-wall__item"
              :style="{ '--i': i }"
            >
              <component
                :is="sponsor.url ? 'a' : 'div'"
                class="logo-tile"
                :class="[
                  sponsor.logo_on_light ? 'logo-tile--light' : 'logo-tile--dark',
                  { 'logo-tile--link': sponsor.url },
                ]"
                v-bind="sponsor.url ? linkAttrs(sponsor.url) : {}"
              >
                <img
                  class="logo-tile__img"
                  :src="sponsor.logo_url!"
                  :alt="sponsor.name"
                  loading="lazy"
                  decoding="async"
                />
                <span v-if="sponsor.url" class="logo-tile__arrow" aria-hidden="true">
                  <svg class="icon-svg"><use href="/svg-icons/icons.svg#north_east" /></svg>
                </span>
              </component>
            </li>
          </ul>

          <!-- Name-only sponsors, set like closing credits -->
          <section
            v-if="nameSponsors.length"
            class="credits"
            :style="{ '--offset': logoSponsors.length }"
          >
            <h2 v-if="logoSponsors.length" class="credits__heading">
              {{ $t('sponsors.also') }}
            </h2>
            <ul class="credits__list">
              <li
                v-for="(sponsor, i) in nameSponsors"
                :key="sponsor.id"
                class="credits__item"
                :style="{ '--i': i }"
              >
                <a
                  v-if="sponsor.url"
                  class="credits__name credits__name--link"
                  v-bind="linkAttrs(sponsor.url)"
                >
                  {{ sponsor.name
                  }}<svg class="icon-svg credits__arrow" aria-hidden="true">
                    <use href="/svg-icons/icons.svg#north_east" />
                  </svg>
                </a>
                <span v-else class="credits__name">{{ sponsor.name }}</span>
              </li>
            </ul>
          </section>
        </template>
      </div>
    </div>

    <AppFooter />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { ApiSponsor } from '~/types/api'

definePageMeta({ layout: 'default' })

const { t } = useI18n()
useHead({ title: () => t('sponsors.title') })

const { fetchSponsors } = useSponsors()

const sponsors = ref<ApiSponsor[]>([])
const loading = ref(true)

const logoSponsors = computed(() => sponsors.value.filter((s) => s.logo_url))
const nameSponsors = computed(() => sponsors.value.filter((s) => !s.logo_url))

function linkAttrs(url: string) {
  return { href: url, target: '_blank', rel: 'noopener noreferrer sponsored' }
}

onMounted(async () => {
  try {
    // Always fresh here — the cached copy only serves the footer link check.
    sponsors.value = await fetchSponsors(true)
  } catch {
    sponsors.value = []
  } finally {
    loading.value = false
  }
})
</script>

<style lang="scss" scoped>
// Plates are fixed per sponsor, not theme-driven — the admin picked the
// plate the logo is legible on, so it must look the same in both themes.
$plate-light: #f4efe6;
$plate-dark: #181613;
$ease-out: cubic-bezier(0.22, 1, 0.36, 1);

.sponsors-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--background);
}

// ─── Hero ─────────────────────────────────────────────────────────
.sp-hero {
  position: relative;
  padding: calc(#{$nav-height} + 2.5rem) 1rem 2.5rem;
  // Clip horizontally only, so the hex can drift down behind the logo wall.
  overflow-x: clip;

  @media (min-width: 640px) {
    padding: calc(#{$nav-height} + 3.5rem) 1.5rem 3.5rem;
  }

  &__backdrop {
    position: absolute;
    inset: 0;
    pointer-events: none;
  }

  &__glow {
    position: absolute;
    width: 380px;
    height: 380px;
    top: -110px;
    left: -80px;
    border-radius: 50%;
    filter: blur(90px);
    background: rgba(212, 146, 30, 0.16);
  }

  &__dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(
      circle,
      color-mix(in srgb, var(--primary-text) 5%, transparent) 1px,
      transparent 1px
    );
    background-size: 22px 22px;
    mask-image: radial-gradient(ellipse 70% 90% at 20% 40%, black 15%, transparent 100%);
  }

  // Oversized brand hex (double outline) bleeding off the right edge and
  // down behind the logo wall — echoes the footer badge and gives the hero
  // a focal mass without an illustration.
  &__hex {
    position: absolute;
    right: clamp(-9rem, -10vw, -3rem);
    top: clamp(5rem, 12vw, 7rem);
    width: clamp(15rem, 42vw, 28rem);
    height: auto;
    fill: none;
    stroke: color-mix(in srgb, $amber 30%, transparent);
    stroke-width: 0.5;
    transform: rotate(8deg);
    animation: hex-drift 1.4s $ease-out both;

    polygon + polygon {
      stroke: color-mix(in srgb, $amber 16%, transparent);
    }
  }

  &__body {
    position: relative;
    z-index: 1;
    max-width: 1100px;
    margin: 0 auto;
  }

  &__back {
    display: inline-block;
    font-size: 0.8rem;
    color: var(--secondary-text);
    text-decoration: none;
    margin-bottom: 1.75rem;
    transition: color 0.2s;

    &:hover {
      color: var(--primary-text);
    }
  }

  &__eyebrow {
    display: flex;
    width: fit-content;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0 0.9rem;
    padding: 0;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: var(--accent-text);
    animation: rise 0.7s $ease-out both;

    .icon-svg {
      width: 1rem;
      height: 1rem;
    }
  }

  &__title {
    max-width: 14ch;
    margin: 0;
    font-size: clamp(2.4rem, 7vw, 4.75rem);
    font-weight: 800;
    line-height: 0.98;
    letter-spacing: -0.045em;
    color: var(--primary-text);
    animation: rise 0.8s 0.08s $ease-out both;

    em {
      font-style: italic;
      font-weight: 300;
      color: $amber;
    }
  }

  &__lead {
    max-width: 46ch;
    margin: 1.25rem 0 0;
    padding: 0;
    font-size: clamp(0.95rem, 1.6vw, 1.1rem);
    line-height: 1.6;
    color: var(--secondary-text);
    animation: rise 0.8s 0.16s $ease-out both;
  }
}

// ─── Content ──────────────────────────────────────────────────────
.sp-content {
  position: relative;
  z-index: 1;
  flex: 1;
  padding: 0.5rem 1rem 5rem;

  @media (min-width: 640px) {
    padding: 1rem 1.5rem 6rem;
  }

  &__inner {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: clamp(3rem, 7vw, 5rem);
  }
}

.sp-state {
  display: flex;
  justify-content: center;
  padding: 4rem 0;

  &--empty p {
    color: var(--secondary-text);
    font-size: 0.95rem;
  }
}

// ─── Logo wall ────────────────────────────────────────────────────
.logo-wall {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;

  @media (min-width: 640px) {
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1rem;
  }

  &__item {
    list-style: none;
    margin: 0;
    animation: tile-in 0.7s calc(0.25s + var(--i) * 60ms) $ease-out both;
  }
}

.logo-tile {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  aspect-ratio: 3 / 2;
  padding: 14% 16%;
  border-radius: 18px;
  text-decoration: none;
  isolation: isolate;
  transition:
    transform 0.45s $ease-out,
    box-shadow 0.45s $ease-out;

  // Hairline ring drawn inside, so light plates don't bleed into a light
  // page and dark plates still read as objects on the dark one.
  &::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    pointer-events: none;
    transition: box-shadow 0.3s ease;
  }

  &--light {
    background: $plate-light;
    &::after {
      box-shadow: inset 0 0 0 1px rgba(15, 14, 12, 0.08);
    }
  }

  &--dark {
    background: $plate-dark;
    &::after {
      box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.07);
    }
  }

  &__img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    transition: transform 0.45s $ease-out;
  }

  &__arrow {
    position: absolute;
    top: 0.65rem;
    right: 0.65rem;
    display: grid;
    place-items: center;
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 50%;
    background: $amber;
    color: #0f0e0c;
    opacity: 0;
    transform: translate(-4px, 4px) scale(0.7);
    transition:
      opacity 0.3s ease,
      transform 0.4s $ease-out;

    .icon-svg {
      width: 0.95rem;
      height: 0.95rem;
    }
  }

  &--link {
    &:hover,
    &:focus-visible {
      transform: translateY(-4px);
      box-shadow: 0 18px 40px -18px rgba(0, 0, 0, 0.55);

      &::after {
        box-shadow: inset 0 0 0 2px $amber;
      }

      .logo-tile__img {
        transform: scale(1.04);
      }

      .logo-tile__arrow {
        opacity: 1;
        transform: none;
      }
    }

    &:focus-visible {
      outline: none;
    }
  }
}

// ─── Credits (name-only sponsors) ─────────────────────────────────
.credits {
  &__heading {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 0 0 1.5rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: var(--secondary-text);

    &::after {
      content: '';
      flex: 1;
      height: 1px;
      background: linear-gradient(to right, var(--divider), transparent);
    }
  }

  &__list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    row-gap: 0.4rem;
  }

  &__item {
    list-style: none;
    margin: 0;
    display: inline-flex;
    align-items: baseline;
    animation: rise 0.7s calc(0.3s + (var(--offset) + var(--i)) * 50ms) $ease-out both;

    // Brand hex as the separator — same motif as the footer and hero.
    &:not(:last-child)::after {
      content: '⬡';
      margin: 0 0.6em;
      font-size: 0.55em;
      color: color-mix(in srgb, $amber 70%, transparent);
      transform: translateY(-0.35em);
    }
  }

  &__name {
    font-size: clamp(1.35rem, 3.6vw, 2.35rem);
    font-weight: 700;
    letter-spacing: -0.035em;
    line-height: 1.2;
    color: var(--primary-text);

    &--link {
      position: relative;
      display: inline-flex;
      align-items: baseline;
      gap: 0.15em;
      text-decoration: none;
      // Beats the global amber anchor colour — plain text until hovered.
      color: var(--primary-text) !important;
      background: linear-gradient($amber, $amber) left bottom / 0 2px no-repeat;
      transition:
        background-size 0.45s $ease-out,
        color 0.25s ease;

      &:hover,
      &:focus-visible {
        background-size: 100% 2px;
        color: $amber !important;

        .credits__arrow {
          opacity: 1;
          transform: none;
        }
      }
    }
  }

  &__arrow {
    width: 0.5em;
    height: 0.5em;
    align-self: flex-start;
    margin-top: 0.2em;
    opacity: 0.45;
    transform: translate(-3px, 3px);
    transition:
      opacity 0.25s ease,
      transform 0.35s $ease-out;
  }
}

// ─── Motion ───────────────────────────────────────────────────────
@keyframes rise {
  from {
    opacity: 0;
    transform: translateY(14px);
  }
}

@keyframes tile-in {
  from {
    opacity: 0;
    transform: translateY(22px) scale(0.96);
  }
}

@keyframes hex-drift {
  from {
    opacity: 0;
    transform: rotate(-14deg) scale(0.9);
  }
}

@media (prefers-reduced-motion: reduce) {
  .sp-hero__hex,
  .sp-hero__eyebrow,
  .sp-hero__title,
  .sp-hero__lead,
  .logo-wall__item,
  .credits__item {
    animation: none;
  }
}
</style>
