<template>
  <main class="maintenance">
    <div class="maintenance__backdrop" aria-hidden="true">
      <div class="maintenance__glow maintenance__glow--amber" />
      <div class="maintenance__glow maintenance__glow--indigo" />
      <div class="maintenance__dots" />
    </div>

    <!-- Würfel als Brettspiel-Anker: kippt langsam, Augen leuchten amber -->
    <div class="maintenance__dice" aria-hidden="true">
      <div class="die die--a">
        <span v-for="n in 5" :key="n" class="die__pip" :class="`die__pip--${n}`" />
      </div>
      <div class="die die--b">
        <span v-for="n in 3" :key="n" class="die__pip" :class="`die__pip--t${n}`" />
      </div>
    </div>

    <div class="maintenance__body">
      <p class="maintenance__eyebrow">
        <span class="maintenance__pulse" aria-hidden="true" />
        {{ $t('pages.maintenance.status') }}
      </p>
      <h1 class="maintenance__title">
        {{ $t('pages.maintenance.title_1') }}<br />
        {{ $t('pages.maintenance.title_2') }}<br />
        <em class="maintenance__title-em">{{ $t('pages.maintenance.title_3') }}</em>
      </h1>
      <p class="maintenance__desc">{{ $t('pages.maintenance.desc') }}</p>

      <p class="maintenance__signoff">
        <span class="maintenance__hex" aria-hidden="true">⬡</span>
        {{ $t('pages.maintenance.signoff') }}
      </p>

      <NuxtLink to="/login" class="maintenance__login">{{
        $t('pages.maintenance.admin_login')
      }}</NuxtLink>
    </div>
  </main>
</template>

<style lang="scss" scoped>
$amber-08: rgba(212, 146, 30, 0.08);
$amber-35: rgba(212, 146, 30, 0.35);
$amber-glow: rgba(212, 146, 30, 0.28);
$indigo-glow: rgba(44, 40, 32, 0.6);

.maintenance {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  overflow: hidden;
  background: var(--background);

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
      animation: breathe 7s ease-in-out infinite;
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
    padding: 4rem 1.5rem;
    padding-left: max(1.5rem, calc((100vw - 1200px) / 2 + 1.5rem));
  }

  &__eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: $amber;
    margin-bottom: 1.25rem;
    padding: 0.3rem 0.85rem;
    border: 1px solid $amber-35;
    border-radius: 999px;
    background: $amber-08;
    animation: rise 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.05s both;
  }

  &__pulse {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: $amber;
    box-shadow: 0 0 0 0 rgba(212, 146, 30, 0.6);
    animation: pulse 2s ease-out infinite;
  }

  &__title {
    font-size: clamp(3rem, 8vw, 5.5rem);
    font-weight: 800;
    line-height: 1.05;
    letter-spacing: -0.04em;
    color: var(--primary-text);
    margin: 0 0 1.5rem;
    animation: rise 0.65s cubic-bezier(0.16, 1, 0.3, 1) 0.15s both;
  }

  &__title-em {
    font-style: italic;
    color: $amber;
  }

  &__desc {
    font-size: 1.1rem;
    line-height: 1.7;
    color: var(--secondary-text);
    max-width: 520px;
    margin-bottom: 2.5rem;
    padding-bottom: 0;
    animation: rise 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.28s both;
  }

  &__login {
    display: inline-block;
    margin-top: 2.5rem;
    font-size: 0.75rem;
    letter-spacing: 0.08em;
    opacity: 0.4;
    transition: opacity 0.25s ease;

    &:hover,
    &:focus-visible {
      opacity: 1;
    }
  }

  &__signoff {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--secondary-text);
    padding-bottom: 0;
    animation: rise 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.4s both;
  }

  &__hex {
    color: $amber;
    font-size: 1.1rem;
  }

  // ─── Würfel ───────────────────────────────────────────────────
  &__dice {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 0;
  }
}

.die {
  position: absolute;
  border: 1px solid var(--divider);
  background: linear-gradient(145deg, var(--secondary-background), transparent);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);

  &--a {
    width: clamp(110px, 18vw, 190px);
    aspect-ratio: 1;
    border-radius: 22%;
    right: max(6%, calc((100vw - 1200px) / 2));
    top: 22%;
    animation: tumble 14s ease-in-out infinite;
  }

  &--b {
    width: clamp(60px, 9vw, 96px);
    aspect-ratio: 1;
    border-radius: 22%;
    right: calc(max(6%, (100vw - 1200px) / 2) + clamp(120px, 20vw, 210px));
    bottom: 16%;
    opacity: 0.7;
    animation: tumble 11s ease-in-out -4s infinite reverse;
  }

  &__pip {
    position: absolute;
    width: 15%;
    height: 15%;
    border-radius: 50%;
    background: $amber;
    box-shadow: 0 0 18px rgba(212, 146, 30, 0.55);

    // Fünf-Augen-Muster (Würfel A)
    &--1 {
      top: 18%;
      left: 18%;
    }
    &--2 {
      top: 18%;
      right: 18%;
    }
    &--3 {
      top: 42.5%;
      left: 42.5%;
    }
    &--4 {
      bottom: 18%;
      left: 18%;
    }
    &--5 {
      bottom: 18%;
      right: 18%;
    }

    // Drei-Augen-Muster (Würfel B)
    &--t1 {
      top: 18%;
      left: 18%;
    }
    &--t2 {
      top: 42.5%;
      left: 42.5%;
    }
    &--t3 {
      bottom: 18%;
      right: 18%;
    }
  }
}

@keyframes tumble {
  0%,
  100% {
    transform: rotate(-12deg) translateY(0);
  }
  50% {
    transform: rotate(14deg) translateY(-18px);
  }
}

@keyframes breathe {
  0%,
  100% {
    opacity: 0.8;
    transform: scale(1);
  }
  50% {
    opacity: 1;
    transform: scale(1.12);
  }
}

@keyframes pulse {
  to {
    box-shadow: 0 0 0 10px rgba(212, 146, 30, 0);
  }
}

@keyframes rise {
  from {
    opacity: 0;
    transform: translateY(18px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .maintenance *,
  .die {
    animation: none !important;
  }
}

// Auf schmalen Screens rücken die Würfel hinter den Text
@media (max-width: 700px) {
  .die--a {
    top: auto;
    bottom: 6%;
    right: -2rem;
    opacity: 0.45;
  }
  .die--b {
    display: none;
  }
}
</style>
