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
          <svg class="icon-svg" aria-hidden="true"><use href="/svg-icons/icons.svg#article" /></svg>
          {{ $t('imprint.tag') }}
        </div>
        <h1 class="legal-hero__title">{{ $t('imprint.title') }}</h1>
      </div>
    </section>

    <!-- ── Content ───────────────────────────────────────────────── -->
    <div class="legal-content">
      <div class="legal-content__inner">
        <p class="about-lead">{{ $t('imprint.lead') }}</p>

        <dl class="about-facts">
          <div v-for="key in factKeys" :key="key" class="about-facts__row">
            <dt>{{ $t(`imprint.facts.${key}_label`) }}</dt>
            <dd v-if="key === 'email'">
              <a :href="`mailto:${CONTACT_EMAIL}`">{{ CONTACT_EMAIL }}</a>
            </dd>
            <dd v-else>{{ $t(`imprint.facts.${key}`) }}</dd>
          </div>
        </dl>

        <section class="about-section">
          <h2>{{ $t('imprint.direction.title') }}</h2>
          <p>{{ $t('imprint.direction.text') }}</p>
        </section>

        <section class="about-section">
          <h2>{{ $t('imprint.liability.title') }}</h2>
          <p>{{ $t('imprint.liability.text') }}</p>
        </section>
      </div>
    </div>

    <AppFooter />
  </div>
</template>

<script setup lang="ts">
/**
 * Impressum / Offenlegung (§ 5 ECG, § 25 MedienG) of the club. Plain
 * facts that rarely change, so they live in the translations rather than
 * in the versioned legal documents.
 */
definePageMeta({ layout: 'default' })

const { t } = useI18n()
useHead({ title: () => `${t('imprint.title')} — AUA` })

const factKeys = ['owner', 'address', 'zvr', 'board', 'email', 'purpose']

// Not a translation: an "@" in a vue-i18n message starts a linked message
// and breaks loading the whole locale file
const CONTACT_EMAIL = 'alle.unsere.abenteuer@gmail.com'
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
.about-lead {
  font-size: clamp(1.15rem, 3.6vw, 1.45rem);
  font-weight: 600;
  line-height: 1.5;
  letter-spacing: -0.02em;
  color: var(--primary-text);
  margin: 0 0 2rem;
  padding-left: 1rem;
  border-left: 3px solid $amber;
}

.about-facts {
  margin: 0 0 2.5rem;
  border: 1px solid var(--divider);
  border-radius: 0.9rem;
  overflow: hidden;

  &__row {
    display: grid;
    grid-template-columns: 8.5rem 1fr;
    gap: 1rem;
    padding: 0.8rem 1rem;
    font-size: 0.88rem;
    line-height: 1.5;

    & + & {
      border-top: 1px solid var(--divider);
    }
  }

  dt {
    color: $amber;
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    padding-top: 0.2rem;
  }

  dd {
    margin: 0;
    color: var(--primary-text);
  }
}

.about-section {
  margin-bottom: 2.25rem;
  color: var(--primary-text);
  font-size: 0.92rem;
  line-height: 1.8;

  h2 {
    font-size: 1.15rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    margin: 0 0 0.6rem;
  }

  p {
    margin: 0;
    color: var(--secondary-text);
  }

  &--open {
    padding: 1.25rem 1.25rem 1.35rem;
    border-radius: 1rem;
    background: rgba(212, 146, 30, 0.07);
    border: 1px solid rgba(212, 146, 30, 0.22);
  }
}

.about-list {
  list-style: none;
  counter-reset: about;
  margin: 0;
  padding: 0;

  li {
    // basix sets list-style-type: decimal on `ol li`, so `none` on the ol is not enough
    list-style: none;
    counter-increment: about;
    display: grid;
    grid-template-columns: 2.4rem 1fr;
    align-items: baseline;
    padding: 0.7rem 0;
    border-top: 1px solid var(--divider);
    color: var(--primary-text);

    &::before {
      content: counter(about, decimal-leading-zero);
      font-size: 0.8rem;
      font-weight: 700;
      font-variant-numeric: tabular-nums;
      color: $amber;
    }

    &:last-child {
      border-bottom: 1px solid var(--divider);
    }
  }
}

.about-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 2.5rem;
}

@media (max-width: 480px) {
  .about-facts__row {
    grid-template-columns: 1fr;
    gap: 0.15rem;
  }
}
</style>
