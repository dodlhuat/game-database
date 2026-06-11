<template>
  <div class="legal-page">

    <!-- ── Hero ──────────────────────────────────────────────────── -->
    <section class="legal-hero">
      <div class="legal-hero__backdrop" aria-hidden="true">
        <div class="legal-hero__glow" />
        <div class="legal-hero__dots" />
      </div>
      <div class="legal-hero__body">
        <NuxtLink to="/" class="legal-hero__back">
          {{ $t('common.back_to_home') }}
        </NuxtLink>
        <div class="legal-hero__tag">
          <span class="icon icon-article" aria-hidden="true" />
          Rechtliches
        </div>
        <h1 class="legal-hero__title">{{ $t('legal.privacy.title') }}</h1>
      </div>
    </section>

    <!-- ── Content ───────────────────────────────────────────────── -->
    <div class="legal-content">
      <div class="legal-content__inner">

        <!-- Skeleton -->
        <template v-if="loading">
          <div class="legal-skeleton__meta skeleton" />
          <div class="legal-skeleton__line skeleton" />
          <div class="legal-skeleton__line skeleton" style="width:92%" />
          <div class="legal-skeleton__line skeleton" style="width:88%" />
          <div class="legal-skeleton__line skeleton legal-skeleton__line--gap" style="width:95%" />
          <div class="legal-skeleton__line skeleton" />
          <div class="legal-skeleton__line skeleton" style="width:80%" />
          <div class="legal-skeleton__line skeleton" style="width:90%" />
        </template>

        <!-- Unavailable -->
        <div v-else-if="!privacy" class="legal-empty">
          <span class="icon icon-article legal-empty__icon" aria-hidden="true" />
          <p class="legal-empty__text">{{ $t('legal.privacy.unavailable') }}</p>
        </div>

        <!-- Document -->
        <template v-else>
          <div class="legal-meta">
            <span class="legal-meta__version">v{{ privacy.version }}</span>
            <span class="legal-meta__sep" aria-hidden="true">·</span>
            <time class="legal-meta__date">{{ formatDate(privacy.published_at) }}</time>
          </div>
          <div class="legal-body">{{ privacy.content }}</div>
        </template>

      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

definePageMeta({ layout: 'default' })

interface PrivacyVersion {
  version: string
  content: string
  published_at: string
}

const api = useApi()
const loading = ref(true)
const privacy = ref<PrivacyVersion | null>(null)

onMounted(async () => {
  try {
    privacy.value = await api.get<PrivacyVersion>('/privacy')
  } catch {
    privacy.value = null
  } finally {
    loading.value = false
  }
})

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString('de-DE', { dateStyle: 'long' })
}
</script>

<style lang="scss" scoped>
$hero-bg:     #0F0E0C;
$amber-glow:  rgba(212, 146, 30, 0.18);
$hero-text:   #EEE8DF;
$hero-muted:  rgba(238, 232, 223, 0.50);

// ─── Page ─────────────────────────────────────────────────────────
.legal-page {
  min-height: 100vh;
  background: var(--background);
  display: flex;
  flex-direction: column;
}

// ─── Hero ─────────────────────────────────────────────────────────
.legal-hero {
  position: relative;
  background: $hero-bg;
  padding: calc(#{$nav-height} + 2.25rem) 1.25rem 2rem;
  overflow: hidden;

  &__backdrop { position: absolute; inset: 0; pointer-events: none; }

  &__glow {
    position: absolute;
    width: 320px; height: 320px;
    top: -90px; right: -50px;
    border-radius: 50%;
    filter: blur(72px);
    background: $amber-glow;
  }

  &__dots {
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,0.032) 1px, transparent 1px);
    background-size: 20px 20px;
    mask-image: radial-gradient(ellipse 80% 100% at 75% 50%, black 20%, transparent 100%);
  }

  &__body {
    position: relative; z-index: 1;
    max-width: 720px; margin: 0 auto;
  }

  &__back {
    display: inline-block;
    font-size: 0.78rem; font-weight: 500; color: $hero-muted;
    text-decoration: none; margin-bottom: 1.5rem;
    transition: color 0.15s;
    &:hover { color: $hero-text; }
  }

  &__tag {
    display: inline-flex; align-items: center; gap: 0.35rem;
    font-size: 0.68rem; font-weight: 600;
    letter-spacing: 0.12em; text-transform: uppercase;
    color: $amber; margin-bottom: 0.65rem;
    padding: 0.18rem 0.6rem;
    border: 1px solid rgba(212, 146, 30, 0.28);
    border-radius: 999px;
    background: rgba(212, 146, 30, 0.08);
    .icon { font-size: 0.85rem; }
  }

  &__title {
    font-size: clamp(1.65rem, 5vw, 2.25rem);
    font-weight: 800; letter-spacing: -0.04em;
    color: $hero-text; margin: 0; line-height: 1.15;
  }
}

// ─── Content ──────────────────────────────────────────────────────
.legal-content {
  flex: 1;
  padding: 2.25rem 1.25rem 5rem;

  &__inner {
    max-width: 720px; margin: 0 auto;
  }
}

// ─── Metadata chip ────────────────────────────────────────────────
.legal-meta {
  display: flex; align-items: center; gap: 0.5rem;
  margin-bottom: 2rem;
  font-size: 0.8rem; color: var(--secondary-text);

  &__version {
    padding: 0.14rem 0.5rem;
    background: var(--secondary-background);
    border: 1px solid var(--divider);
    border-radius: 4px;
    font-weight: 600;
    font-family: $font-family-mono;
    color: $amber; letter-spacing: 0.03em;
  }

  &__sep { opacity: 0.3; }

  &__date { font-weight: 500; }
}

// ─── Body ─────────────────────────────────────────────────────────
.legal-body {
  white-space: pre-line;
  color: var(--primary-text);
  font-size: 0.9rem;
  line-height: 1.8;
}

// ─── Skeleton ─────────────────────────────────────────────────────
.legal-skeleton {
  &__meta {
    width: 190px; height: 22px;
    border-radius: 4px; margin-bottom: 2rem;
  }
  &__line {
    width: 100%; height: 13px;
    border-radius: 3px; margin-bottom: 0.6rem;
    &--gap { margin-top: 1.5rem; }
  }
}

// ─── Empty state ──────────────────────────────────────────────────
.legal-empty {
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  padding: 4rem 0; text-align: center;

  &__icon {
    font-size: 2.5rem;
    color: var(--secondary-text);
    opacity: 0.3;
    margin-bottom: 0.75rem;
  }

  &__text {
    color: var(--secondary-text);
    font-size: 0.9rem;
  }
}
</style>
