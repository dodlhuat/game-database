<script setup lang="ts">
const year = new Date().getFullYear()
</script>

<template>
  <footer class="app-footer">
    <div class="app-footer__top-line" aria-hidden="true" />

    <div class="app-footer__inner">
      <!-- Row 1: brand left, all links right -->
      <div class="app-footer__main">
        <NuxtLink to="/" class="app-footer__brand">
          <span class="app-footer__hex" aria-hidden="true">⬡</span>
          <span class="app-footer__name">AUA</span>
        </NuxtLink>

        <nav class="app-footer__links">
          <NuxtLink to="/games" class="app-footer__link">{{ $t('nav.collection') }}</NuxtLink>
          <NuxtLink to="/packages" class="app-footer__link">{{ $t('nav.packages') }}</NuxtLink>
          <span class="app-footer__sep" aria-hidden="true" />
          <NuxtLink to="/terms" class="app-footer__link app-footer__link--minor">{{ $t('nav.terms') }}</NuxtLink>
          <NuxtLink to="/privacy" class="app-footer__link app-footer__link--minor">{{ $t('nav.privacy') }}</NuxtLink>
          <NuxtLink to="/cookies" class="app-footer__link app-footer__link--minor">{{ $t('nav.cookies') }}</NuxtLink>
        </nav>
      </div>

      <!-- Row 2: tagline left, copyright right -->
      <div class="app-footer__sub">
        <p class="app-footer__tagline">{{ $t('footer.tagline') }}</p>
        <p class="app-footer__copy">{{ $t('common.copyright_short', { year }) }}</p>
      </div>
    </div>
  </footer>
</template>

<style lang="scss" scoped>
.app-footer {
  background: var(--background);

  // ── Amber signature line ──────────────────────────────────────
  &__top-line {
    height: 1px;
    background: linear-gradient(
      to right,
      transparent,
      rgba(212, 146, 30, 0.45) 20%,
      rgba(212, 146, 30, 0.45) 80%,
      transparent
    );
  }

  // ── Container ─────────────────────────────────────────────────
  &__inner {
    max-width: 1100px;
    margin: 0 auto;
    padding: 1.125rem 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  // ── Row 1 ─────────────────────────────────────────────────────
  &__main {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem 1.5rem;
    flex-wrap: wrap;
  }

  &__brand {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    text-decoration: none;
    flex-shrink: 0;

    &:hover .app-footer__hex {
      transform: rotate(30deg);
    }

    &:focus-visible {
      outline: 1px solid $amber;
      outline-offset: 3px;
      border-radius: 2px;
    }
  }

  &__hex {
    font-size: 1.1rem;
    color: $amber;
    line-height: 1;
    display: inline-block;
    transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
  }

  &__name {
    font-size: 0.9rem;
    font-weight: 800;
    color: var(--primary-text);
    letter-spacing: -0.02em;
  }

  // ── Navigation strip ──────────────────────────────────────────
  &__links {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.2rem 1rem;
  }

  &__sep {
    display: inline-block;
    width: 1px;
    height: 0.85em;
    background: var(--divider);
    align-self: center;
    flex-shrink: 0;

    @media (max-width: 500px) {
      display: none;
    }
  }

  &__link {
    font-size: 0.8rem;
    color: var(--secondary-text);
    text-decoration: none;
    white-space: nowrap;
    transition: color 0.2s;

    &:hover {
      color: var(--primary-text);
    }

    &:focus-visible {
      outline: 1px solid $amber;
      outline-offset: 2px;
      border-radius: 2px;
    }

    &--minor {
      font-size: 0.75rem;
      opacity: 0.6;

      &:hover {
        opacity: 1;
      }
    }
  }

  // ── Row 2 ─────────────────────────────────────────────────────
  &__sub {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 0.25rem 1rem;
    flex-wrap: wrap;
  }

  &__tagline {
    font-size: 0.72rem;
    color: var(--secondary-text);
    opacity: 0.55;
    padding-bottom: 0;
    font-style: italic;
    letter-spacing: 0.01em;
  }

  &__copy {
    font-size: 0.7rem;
    color: var(--secondary-text);
    opacity: 0.45;
    padding-bottom: 0;
    white-space: nowrap;
  }
}
</style>
