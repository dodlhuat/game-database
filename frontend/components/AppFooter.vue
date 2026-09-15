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
        <p class="app-footer__tagline">{{ $t('footer.tagline') }}</p>
      </div>

      <div class="app-footer__divider" aria-hidden="true" />

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
// Used to be hardcoded cream/off-white at low opacity — invisible once the
// background actually switches light in light mode. Theme-token based now.
$_dim: color-mix(in srgb, var(--secondary-text) 65%, transparent);
$_muted: var(--secondary-text);
$_sep: color-mix(in srgb, var(--secondary-text) 25%, transparent);

// Hex-badge treatment — reuses the theme's existing muted-accent tokens
// (same rgba(247,150,61,…) values in both light and dark, see _theme.scss)
// instead of inventing new opacity steps, so the badge reads consistently
// in both themes rather than needing its own per-theme tuning.
$_badge-bg: var(--accent-color-muted);
$_badge-border: color-mix(in srgb, $amber 32%, transparent);
$_badge-glow: color-mix(in srgb, $amber 45%, transparent);

// Brand lockup sizing, shared between the hex badge and the tagline's
// hanging indent below so the tagline lines up under the wordmark rather
// than the badge — a small masthead-style detail.
$_hex-size: 1.85rem;
$_brand-gap: 0.55rem;

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

  // Three-part row: brand identity (grows to fill the row) — a vertical
  // seam — legal/copyright (pinned to its natural width on the right).
  // Deliberately NOT a plain `justify-content: space-between` two-column
  // split: that reads as generic filler either side of empty space. Here
  // the seam gives the transition a visible edge, and the brand column's
  // own growth is what pushes it there, rather than the row just
  // stretching whitespace between two equal-weight blobs.
  &__inner {
    max-width: 1100px;
    margin: 0 auto;
    padding: 2.25rem 1.25rem 2rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;

    @media (min-width: 640px) {
      flex-direction: row;
      align-items: center;
      padding: 2.25rem 1.5rem;
      gap: 1.75rem;
    }
  }

  &__start {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.4rem;

    @media (min-width: 640px) {
      flex: 1 1 auto;
      min-width: 0;
    }
  }

  &__brand {
    display: inline-flex;
    align-items: center;
    gap: $_brand-gap;
    text-decoration: none;
    width: fit-content;

    &:hover .app-footer__hex {
      transform: rotate(30deg);
      border-color: $_badge-glow;
      box-shadow:
        0 0 0 1px $_badge-glow,
        0 0 14px -2px $_badge-glow;
    }

    &:focus-visible {
      outline: 2px solid $amber;
      outline-offset: 4px;
      border-radius: 10px;
    }
  }

  // A soft amber badge instead of a bare glyph — gives the mark real
  // presence (scale + depth contrast against the fine print opposite it)
  // rather than sitting flush with the text baseline like a stray symbol.
  &__hex {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: $_hex-size;
    height: $_hex-size;
    flex-shrink: 0;
    border-radius: 8px;
    font-size: 0.95rem;
    line-height: 1;
    color: $amber;
    background: $_badge-bg;
    border: 1px solid $_badge-border;
    transition:
      transform 0.45s cubic-bezier(0.34, 1.56, 0.64, 1),
      box-shadow 0.3s ease,
      border-color 0.3s ease;
  }

  &__name {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--primary-text);
    letter-spacing: -0.02em;
  }

  // Hangs below the wordmark instead of the old inline "AUA · tagline"
  // string — a proper masthead subhead reads as considered; joining two
  // unrelated fragments with a dot mid-sentence read as improvised.
  &__tagline {
    font-size: 0.76rem;
    font-style: italic;
    color: $_dim;
    line-height: 1.5;
    padding-left: calc(#{$_hex-size} + #{$_brand-gap});
  }

  // Vertical seam between the brand column and the legal column — only
  // meaningful once they sit side by side (≥640px); below that the two
  // stack, and __end's own top border takes over as the divider.
  &__divider {
    display: none;

    @media (min-width: 640px) {
      display: block;
      width: 1px;
      align-self: stretch;
      background: $_sep;
      flex-shrink: 0;
    }
  }

  &__end {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    row-gap: 0.5rem;
    column-gap: 0;
    padding-top: 1.25rem;
    border-top: 1px solid $_sep;

    @media (min-width: 640px) {
      // __inner's own gap already spaces this away from __divider — no
      // extra padding needed here, that would double it up.
      flex-shrink: 0;
      justify-content: flex-end;
      padding-top: 0;
      border-top: none;
    }
  }

  &__legal {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    // A wrapping flex container nested inside another flex container
    // otherwise collapses to fit just one item at a time, even when
    // there's plenty of room — this keeps it at its natural single-line
    // width by default, and only lets it shrink (wrapping its own links
    // onto multiple lines) when the surrounding layout actually runs out
    // of space, at any viewport width, without a guessed breakpoint.
    flex-basis: max-content;
    // Row-gap only (for the wrap case on narrow viewports) — horizontal
    // spacing between links is carried entirely by the separator dot's
    // own margins below, so it isn't doubled up with a column-gap too.
    row-gap: 0.5rem;
  }

  &__link {
    font-size: 0.71rem;
    color: $_muted;
    text-decoration: none;
    white-space: nowrap;
    letter-spacing: 0.01em;
    line-height: 1.5;
    transition: color 0.18s ease;

    &:not(:last-child)::after {
      content: '·';
      display: inline-block;
      margin: 0 0.65rem;
      color: $_sep;
    }

    &:hover {
      color: var(--primary-text);
    }

    &:focus-visible {
      outline: 2px solid $amber;
      outline-offset: 2px;
      border-radius: 2px;
    }
  }

  // Joined onto the legal nav with the same dot motif used inside it and
  // on the tagline above — one recurring signature detail instead of a
  // second, different separator treatment (the old border-left) for what
  // is conceptually the same kind of junction.
  &__copy {
    font-size: 0.68rem;
    color: $_dim;
    white-space: nowrap;
    letter-spacing: 0.01em;

    &::before {
      content: '·';
      margin: 0 0.65rem;
      color: $_sep;
      font-style: normal;
    }
  }
}
</style>
