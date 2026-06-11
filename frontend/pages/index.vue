<template>
  <div class="landing">
    <!-- ── Hero ───────────────────────────────────────────────── -->
    <section class="hero">
      <div class="hero__backdrop" aria-hidden="true">
        <div class="hero__glow hero__glow--amber" />
        <div class="hero__glow hero__glow--indigo" />
        <div class="hero__dots" />
      </div>

      <div class="hero__body">
        <p class="hero__eyebrow">{{ $t('pages.home.hero_eyebrow') }}</p>
        <h1 class="hero__title">
          {{ $t('pages.home.hero_title_1') }}<br>
          {{ $t('pages.home.hero_title_2') }}<br>
          <em class="hero__title-em">{{ $t('pages.home.hero_title_3') }}</em>
        </h1>
        <p class="hero__desc">{{ $t('pages.home.hero_desc') }}</p>

        <div class="hero__cta-row">
          <NuxtLink to="/games" class="button button-primary hero__cta-primary">
            <span class="icon icon-article" aria-hidden="true" />
            {{ $t('btn.to_collection') }}
          </NuxtLink>
          <NuxtLink v-if="!auth.isLoggedIn" to="/register" class="hero__cta-ghost">
            {{ $t('btn.create_account') }} →
          </NuxtLink>
          <NuxtLink v-else to="/dashboard" class="hero__cta-ghost">
            {{ $t('btn.to_dashboard') }}
          </NuxtLink>
        </div>

        <Transition name="fade">
          <div v-if="auth.isLoggedIn && auth.isAdmin" class="hero__admin-chip">
            <NuxtLink to="/admin">
              <span class="icon icon-settings" aria-hidden="true" />
              {{ $t('nav.admin') }}
            </NuxtLink>
          </div>
        </Transition>
      </div>

      <div class="hero__scroll-hint" aria-hidden="true">
        <span class="hero__scroll-line" />
      </div>
    </section>

    <!-- ── Features ────────────────────────────────────────────── -->
    <section class="features" ref="featuresRef">
      <div class="features__inner">
        <header class="features__header">
          <h2 class="features__title">{{ $t('pages.home.features_title') }}</h2>
          <p class="features__subtitle">{{ $t('pages.home.features_sub') }}</p>
        </header>

        <div class="features__grid" :class="{ 'features__grid--visible': featVisible }">

          <!-- Primary feature: hero card spanning the full left column -->
          <article class="feat-card feat-card--hero" style="--idx: 0">
            <div class="feat-card__glow" aria-hidden="true" />
            <span class="feat-card__ghost-num" aria-hidden="true">01</span>
            <div class="feat-card__icon-wrap">
              <span class="icon icon-article" aria-hidden="true" />
            </div>
            <div class="feat-card__body">
              <h3 class="feat-card__title">{{ $t('pages.home.feature_variety_title') }}</h3>
              <p class="feat-card__text">{{ $t('pages.home.feature_variety_desc') }}</p>
            </div>
          </article>

          <!-- Satellite feature 02 -->
          <article class="feat-card feat-card--sat" style="--idx: 1">
            <div class="feat-card__sat-inner">
              <div class="feat-icon feat-icon--blue" aria-hidden="true">
                <span class="icon icon-calendar_today" />
              </div>
              <div class="feat-card__sat-content">
                <span class="feat-card__sat-num" aria-hidden="true">02</span>
                <h3 class="feat-card__title">{{ $t('pages.home.feature_easy_title') }}</h3>
                <p class="feat-card__text">{{ $t('pages.home.feature_easy_desc') }}</p>
              </div>
            </div>
          </article>

          <!-- Satellite feature 03 -->
          <article class="feat-card feat-card--sat" style="--idx: 2">
            <div class="feat-card__sat-inner">
              <div class="feat-icon feat-icon--green" aria-hidden="true">
                <span class="icon icon-stars" />
              </div>
              <div class="feat-card__sat-content">
                <span class="feat-card__sat-num" aria-hidden="true">03</span>
                <h3 class="feat-card__title">{{ $t('pages.home.feature_for_all_title') }}</h3>
                <p class="feat-card__text">{{ $t('pages.home.feature_for_all_desc') }}</p>
              </div>
            </div>
          </article>

        </div>
      </div>
    </section>

    <!-- ── CTA Strip ──────────────────────────────────────────── -->
    <Transition name="fade">
      <section v-if="!auth.isLoggedIn" class="cta-strip">
        <div class="cta-strip__inner">
          <div class="cta-strip__text">
            <h2 class="cta-strip__title">{{ $t('pages.home.cta_title') }}</h2>
            <p class="cta-strip__sub">{{ $t('pages.home.cta_sub') }}</p>
          </div>
          <div class="cta-strip__actions">
            <NuxtLink to="/register" class="button button-primary">{{ $t('pages.home.cta_register') }}</NuxtLink>
            <NuxtLink to="/login" class="button">{{ $t('pages.home.cta_login') }}</NuxtLink>
          </div>
        </div>
      </section>
    </Transition>

    <AppFooter />

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()

// Feature section: scroll-triggered entrance via IntersectionObserver
const featuresRef = ref<HTMLElement | null>(null)
const featVisible  = ref(false)

onMounted(() => {
  const el = featuresRef.value
  if (!el) return
  const observer = new IntersectionObserver(
    ([entry]) => {
      if (entry.isIntersecting) {
        featVisible.value = true
        observer.disconnect()
      }
    },
    { threshold: 0.1 },
  )
  observer.observe(el)
})
</script>

<style lang="scss" scoped>
$hero-bg:       #0F0E0C;
$nav-height:    64px;

$amber-08:      rgba(212, 146, 30, 0.08);
$amber-14:      rgba(212, 146, 30, 0.14);
$amber-35:      rgba(212, 146, 30, 0.35);
$amber-40:      rgba(212, 146, 30, 0.40);
$amber-65:      rgba(212, 146, 30, 0.65);
$amber-glow:    rgba(212, 146, 30, 0.28);

$hero-bg-85:    rgba(15, 14, 12, 0.85);

$hero-text:     #EEE8DF;
$hero-text-08:  rgba(238, 232, 223, 0.08);

$hero-muted:    rgba(238, 232, 223, 0.72);
$hero-muted-50: rgba(238, 232, 223, 0.65);
$hero-muted-60: rgba(238, 232, 223, 0.60);

$hero-divider:  rgba(238, 232, 223, 0.10);
$indigo-glow:   rgba(44, 40, 32, 0.60);

// ─── Page ─────────────────────────────────────────────────────────
.landing {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

// ─── Fade transition ──────────────────────────────────────────────
.fade-enter-active,
.fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }

// ─── Hero ─────────────────────────────────────────────────────────
.hero {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  overflow: hidden;
  background: $hero-bg;
  padding-top: $nav-height;

  &__backdrop {
    position: absolute;
    inset: 0;
    pointer-events: none;
  }

  &__glow {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);

    &--amber {
      width: 600px;
      height: 600px;
      top: -100px;
      left: -150px;
      background: $amber-glow;
    }

    &--indigo {
      width: 500px;
      height: 500px;
      bottom: -50px;
      right: -100px;
      background: $indigo-glow;
    }
  }

  &__dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(255, 255, 255, 0.06) 1px, transparent 1px);
    background-size: 28px 28px;
    mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 100%);
  }

  &__body {
    position: relative;
    z-index: 1;
    width: 100%;
    padding: 4rem 1.5rem 6rem;
    padding-left: max(1.5rem, calc((100vw - 1200px) / 2 + 1.5rem));
  }

  &__eyebrow {
    display: inline-block;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: $amber;
    margin-bottom: 1.25rem;
    padding: 0.3rem 0.75rem;
    border: 1px solid $amber-35;
    border-radius: 999px;
    background: $amber-08;
    animation: heroIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.05s both;
  }

  &__title {
    font-size: clamp(3rem, 8vw, 5.5rem);
    font-weight: 800;
    line-height: 1.05;
    letter-spacing: -0.04em;
    color: $hero-text;
    margin: 0 0 1.5rem;
    animation: heroIn 0.65s cubic-bezier(0.16, 1, 0.3, 1) 0.15s both;
  }

  &__title-em {
    font-style: italic;
    color: $amber;
  }

  &__desc {
    font-size: 1.1rem;
    line-height: 1.7;
    color: $hero-muted;
    max-width: 520px;
    margin-bottom: 2.5rem;
    padding-bottom: 0;
    animation: heroIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.28s both;
  }

  &__cta-row {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    flex-wrap: wrap;
    animation: heroIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.38s both;
  }

  &__cta-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    padding: 0.7rem 1.5rem;
  }

  &__cta-ghost {
    font-size: 0.95rem;
    font-weight: 600;
    color: $hero-muted;
    text-decoration: none;
    transition: color 0.2s;
    &:hover { color: $hero-text; }
  }

  &__admin-chip {
    margin-top: 1.75rem;

    a {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      font-size: 0.85rem;
      font-weight: 600;
      color: $amber;
      text-decoration: none;
      padding: 0.4rem 0.9rem;
      border: 1px solid $amber-40;
      border-radius: 999px;
      background: $amber-08;
      transition: background 0.2s, border-color 0.2s;

      &:hover {
        background: $amber-14;
        border-color: $amber-65;
      }
    }
  }

  &__scroll-hint {
    position: absolute;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  &__scroll-line {
    display: block;
    width: 1px;
    height: 48px;
    background: linear-gradient(to bottom, $hero-divider, transparent);
    animation: scrollHint 2s ease-in-out infinite;
  }
}

@keyframes scrollHint {
  0%, 100% { opacity: 0.3; transform: scaleY(1); }
  50%       { opacity: 0.8; transform: scaleY(1.3); }
}

@keyframes heroIn {
  from { opacity: 0; transform: translateY(18px); }
  to   { opacity: 1; transform: translateY(0); }
}

// ─── Features ─────────────────────────────────────────────────────
@keyframes featIn {
  from { opacity: 0; transform: translateY(24px); }
  to   { opacity: 1; transform: translateY(0); }
}

.features {
  background: var(--background);
  padding: 6rem 1.5rem 5rem;

  &__inner { max-width: 1100px; margin: 0 auto; }

  &__header { margin-bottom: 3.5rem; }

  &__title {
    font-size: clamp(1.75rem, 4vw, 2.5rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    color: var(--primary-text);
    margin-bottom: 0.5rem;
  }

  &__subtitle {
    font-size: 1rem;
    color: var(--secondary-text);
    padding-bottom: 0;
    max-width: 480px;
  }

  &__grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;

    @media (min-width: 700px) {
      grid-template-columns: 1.4fr 1fr;
      grid-template-rows: 1fr 1fr;
    }
  }
}

// ─── Feature card ─────────────────────────────────────────────────
.feat-card {
  position: relative;
  overflow: hidden;
  border-radius: 16px;
  border: 1px solid var(--divider);
  background: var(--secondary-background);

  // Pre-animation hidden state; cleared by the animation fill once --visible fires
  opacity: 0;
  transform: translateY(24px);

  .features__grid--visible & {
    animation: featIn 0.7s cubic-bezier(0.16, 1, 0.3, 1) calc(var(--idx, 0) * 0.12s) both;
  }

  // ── Hero variant ──────────────────────────────────────────────
  &--hero {
    padding: 2.5rem;
    display: flex;
    flex-direction: column;
    min-height: 300px;

    @media (min-width: 700px) {
      grid-row: 1 / 3;
      min-height: 340px;
    }

    // Amber accent stripe at top: partial at rest, full-width on hover
    &::before {
      content: '';
      position: absolute;
      top: 0;
      left: 2.5rem;
      right: 2.5rem;
      height: 2px;
      background: $amber;
      border-radius: 0 0 2px 2px;
      transform-origin: left;
      transform: scaleX(0.28);
      opacity: 0.6;
      transition:
        transform 0.55s cubic-bezier(0.16, 1, 0.3, 1),
        opacity 0.3s ease;
    }

    &:hover::before {
      transform: scaleX(1);
      opacity: 1;
    }

    &:hover .feat-card__glow {
      opacity: 1;
      transform: scale(1.25);
    }

    &:hover .feat-card__icon-wrap .icon {
      filter: drop-shadow(0 0 40px rgba($amber, 0.6));
    }
  }

  // ── Satellite variant ─────────────────────────────────────────
  &--sat {
    padding: 1.75rem;
    transition: border-color 0.25s ease;

    // Left accent bar: invisible at rest, amber and taller on hover
    &::before {
      content: '';
      position: absolute;
      left: 0;
      top: 25%;
      bottom: 25%;
      width: 2px;
      border-radius: 0 2px 2px 0;
      background: transparent;
      transition:
        background 0.3s ease,
        top 0.4s cubic-bezier(0.16, 1, 0.3, 1),
        bottom 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    &:hover {
      border-color: rgba($amber, 0.25);

      &::before {
        background: $amber;
        top: 15%;
        bottom: 15%;
      }
    }
  }

  // Ambient radial glow blob (hero)
  &__glow {
    position: absolute;
    top: 12%;
    right: -18%;
    width: 280px;
    height: 280px;
    background: radial-gradient(circle, rgba($amber, 0.10) 0%, transparent 65%);
    border-radius: 50%;
    pointer-events: none;
    opacity: 0.55;
    transition:
      opacity 0.5s ease,
      transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
  }

  // Giant ghost number watermark behind body text (hero)
  &__ghost-num {
    position: absolute;
    bottom: 3.25rem;
    right: 1.75rem;
    font-size: 7rem;
    font-weight: 900;
    letter-spacing: -0.06em;
    line-height: 1;
    color: var(--primary-text);
    opacity: 0.04;
    pointer-events: none;
    user-select: none;
  }

  // Large floating icon zone (hero)
  &__icon-wrap {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem 0;

    .icon {
      font-size: 4.5rem;
      color: $amber;
      filter: drop-shadow(0 0 28px rgba($amber, 0.38));
      transition: filter 0.4s ease;
    }
  }

  // Text zone beneath divider (hero)
  &__body {
    border-top: 1px solid var(--divider);
    padding-top: 1.5rem;
  }

  // Horizontal row layout (sat cards)
  &__sat-inner {
    display: flex;
    gap: 1.25rem;
    align-items: flex-start;
  }

  &__sat-content { min-width: 0; }

  // Small numbered counter label (sat cards)
  &__sat-num {
    display: block;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--secondary-text);
    opacity: 0.55;
    margin-bottom: 0.4rem;
  }

  // Shared title
  &__title {
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--primary-text);
    margin: 0 0 0.5rem;
  }

  // Shared description
  &__text {
    font-size: 0.9rem;
    color: var(--secondary-text);
    line-height: 1.65;
    padding-bottom: 0;
    margin: 0;
  }

  // Hero size overrides
  &--hero &__title { font-size: 1.3rem; margin-bottom: 0.6rem; }
  &--hero &__text  { font-size: 0.95rem; }
}

.feat-icon {
  width: 46px;
  height: 46px;
  flex-shrink: 0;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;

  .icon { font-size: 1.4rem; }

  &--amber { background: var(--accent-color-muted);   .icon { color: var(--accent-color); } }
  &--blue  { background: var(--accent-lighten-muted); .icon { color: var(--accent-color-lighten); } }
  &--green { background: var(--success-muted);        .icon { color: var(--success); } }
}

// ─── CTA Strip ────────────────────────────────────────────────────
.cta-strip {
  background: var(--secondary-background);
  border-top: 1px solid var(--divider);
  border-bottom: 1px solid var(--divider);
  padding: 4rem 1.5rem;

  &__inner {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
    flex-wrap: wrap;
  }

  &__title {
    font-size: 1.6rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: var(--primary-text);
    margin-bottom: 0.4rem;
  }

  &__sub {
    font-size: 0.95rem;
    color: var(--secondary-text);
    padding-bottom: 0;
  }

  &__actions { display: flex; gap: 0.75rem; flex-shrink: 0; flex-wrap: wrap; }
}

</style>
