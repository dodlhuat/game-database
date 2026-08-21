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
          <svg class="icon-svg" aria-hidden="true"><use href="/svg-icons/icons.svg#groups" /></svg>
          {{ $t('about.tag') }}
        </div>
        <h1 class="legal-hero__title">{{ $t('about.title') }}</h1>
      </div>
    </section>

    <!-- ── Content ───────────────────────────────────────────────── -->
    <div class="legal-content">
      <div class="legal-content__inner">
        <div class="legal-body">{{ $t('about.placeholder') }}</div>
      </div>
    </div>

    <AppFooter />
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'default' })
</script>

<style lang="scss" scoped>
$hero-bg: var(--background);
$amber-glow: rgba(212, 146, 30, 0.18);
$hero-text: var(--primary-text);
$hero-muted: color-mix(in srgb, var(--secondary-text) 80%, transparent);

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

  &__backdrop {
    position: absolute;
    inset: 0;
    pointer-events: none;
  }

  &__glow {
    position: absolute;
    width: 320px;
    height: 320px;
    top: -90px;
    right: -50px;
    border-radius: 50%;
    filter: blur(72px);
    background: $amber-glow;
  }

  &__dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(255, 255, 255, 0.032) 1px, transparent 1px);
    background-size: 20px 20px;
    mask-image: radial-gradient(ellipse 80% 100% at 75% 50%, black 20%, transparent 100%);
  }

  &__body {
    position: relative;
    z-index: 1;
    max-width: 720px;
    margin: 0 auto;
  }

  &__back {
    display: block;
    font-size: 0.78rem;
    font-weight: 500;
    color: $hero-muted;
    text-decoration: none;
    margin-bottom: 1.5rem;
    transition: color 0.15s;
    &:hover {
      color: $hero-text;
    }
  }

  &__tag {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: $amber;
    margin-bottom: 0.65rem;
    padding: 0.18rem 0.6rem;
    border: 1px solid rgba(212, 146, 30, 0.28);
    border-radius: 999px;
    background: rgba(212, 146, 30, 0.08);
    .icon {
      font-size: 0.85rem;
    }
  }

  &__title {
    font-size: clamp(1.65rem, 5vw, 2.25rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    color: $hero-text;
    margin: 0;
    line-height: 1.15;
  }
}

// ─── Content ──────────────────────────────────────────────────────
.legal-content {
  flex: 1;
  padding: 2.25rem 1.25rem 5rem;

  &__inner {
    max-width: 720px;
    margin: 0 auto;
  }
}

// ─── Body ─────────────────────────────────────────────────────────
.legal-body {
  white-space: pre-line;
  color: var(--primary-text);
  font-size: 0.9rem;
  line-height: 1.8;
}
</style>
