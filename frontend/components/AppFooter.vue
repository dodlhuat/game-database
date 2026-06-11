<script setup lang="ts">
const year = new Date().getFullYear()
</script>

<template>
  <footer class="app-footer">
    <div class="app-footer__top-line" aria-hidden="true" />

    <div class="app-footer__inner">
      <div class="app-footer__start">
        <NuxtLink to="/" class="app-footer__brand">
          <span class="app-footer__hex" aria-hidden="true">⬡</span>
          <span class="app-footer__name">AUA</span>
        </NuxtLink>
        <span class="app-footer__tagline">{{ $t('footer.tagline') }}</span>
      </div>

      <div class="app-footer__end">
        <nav class="app-footer__legal" aria-label="Rechtliches">
          <NuxtLink to="/terms" class="app-footer__link">{{ $t('nav.terms') }}</NuxtLink>
          <NuxtLink to="/privacy" class="app-footer__link">{{ $t('nav.privacy') }}</NuxtLink>
          <NuxtLink to="/cookies" class="app-footer__link">{{ $t('nav.cookies') }}</NuxtLink>
        </nav>
        <span class="app-footer__copy">{{ $t('common.copyright_short', { year }) }}</span>
      </div>
    </div>
  </footer>
</template>

<style lang="scss" scoped>
$_dim: rgba(238, 232, 223, 0.32);
$_muted: rgba(238, 232, 223, 0.52);
$_sep: rgba(238, 232, 223, 0.11);

.app-footer {
  background: var(--background);

  &__top-line {
    height: 1px;
    background: linear-gradient(
      to right,
      transparent,
      rgba($amber, 0.2) 25%,
      rgba($amber, 0.42) 50%,
      rgba($amber, 0.2) 75%,
      transparent
    );
  }

  &__inner {
    max-width: 1100px;
    margin: 0 auto;
    padding: 1.75rem 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;

    @media (min-width: 640px) {
      flex-direction: row;
      align-items: center;
      justify-content: space-between;
      padding: 2rem 1.5rem;
    }
  }

  &__start {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;

    @media (min-width: 640px) {
      flex-direction: row;
      align-items: center;
    }
  }

  &__brand {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    text-decoration: none;
    flex-shrink: 0;

    &:hover .app-footer__hex {
      transform: rotate(30deg);
    }

    &:focus-visible {
      outline: 2px solid $amber;
      outline-offset: 3px;
      border-radius: 2px;
    }
  }

  &__hex {
    font-size: 0.875rem;
    color: $amber;
    display: inline-block;
    transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
  }

  &__name {
    font-size: 0.8125rem;
    font-weight: 800;
    color: var(--primary-text);
    letter-spacing: -0.02em;
  }

  &__tagline {
    font-size: 0.71rem;
    font-style: italic;
    color: $_dim;
    line-height: 1.4;

    @media (min-width: 640px) {
      &::before {
        content: '·';
        margin: 0 0.625rem;
        font-style: normal;
        color: $_sep;
      }
    }
  }

  &__end {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem 1rem;

    @media (min-width: 640px) {
      flex-wrap: nowrap;
      gap: 1.25rem;
    }
  }

  &__legal {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  &__link {
    font-size: 0.71rem;
    color: $_muted;
    text-decoration: none;
    white-space: nowrap;
    letter-spacing: 0.01em;
    transition: color 0.18s ease;

    &:hover {
      color: var(--primary-text);
    }

    &:focus-visible {
      outline: 2px solid $amber;
      outline-offset: 2px;
      border-radius: 2px;
    }
  }

  &__copy {
    font-size: 0.68rem;
    color: $_dim;
    white-space: nowrap;
    letter-spacing: 0.01em;

    @media (min-width: 640px) {
      padding-left: 1.25rem;
      border-left: 1px solid $_sep;
    }
  }
}
</style>
