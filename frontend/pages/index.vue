<template>
  <div class="landing">

    <AppNav />

    <!-- ── Hero ───────────────────────────────────────────────── -->
    <section class="hero">
      <div class="hero__backdrop" aria-hidden="true">
        <div class="hero__glow hero__glow--amber" />
        <div class="hero__glow hero__glow--indigo" />
        <div class="hero__dots" />
      </div>

      <div class="hero__body">
        <p class="hero__eyebrow">Brettspiel-Ausleihplattform</p>
        <h1 class="hero__title">
          Alle.<br>
          Unsere.<br>
          <em class="hero__title-em">Abenteuer.</em>
        </h1>
        <p class="hero__desc">
          Greife auf unsere wachsende Sammlung von Brettspielen zu —
          ausleihen, einfach zurückgeben, gemeinsam genießen.
        </p>

        <div class="hero__cta-row">
          <NuxtLink to="/games" class="button button-primary hero__cta-primary">
            <span class="icon icon-book-open-outline" aria-hidden="true" />
            Zur Spielesammlung
          </NuxtLink>
          <NuxtLink v-if="!auth.isLoggedIn" to="/register" class="hero__cta-ghost">
            Konto erstellen →
          </NuxtLink>
          <NuxtLink v-else to="/dashboard" class="hero__cta-ghost">
            Mein Dashboard →
          </NuxtLink>
        </div>

        <Transition name="fade">
          <div v-if="auth.isLoggedIn && auth.isAdmin" class="hero__admin-chip">
            <NuxtLink to="/admin">
              <span class="icon icon-settings-2-outline" aria-hidden="true" />
              Admin-Bereich öffnen
            </NuxtLink>
          </div>
        </Transition>
      </div>

      <div class="hero__scroll-hint" aria-hidden="true">
        <span class="hero__scroll-line" />
      </div>
    </section>

    <!-- ── Features ────────────────────────────────────────────── -->
    <section class="features">
      <div class="features__inner">
        <header class="features__header">
          <h2 class="features__title">Was wir bieten</h2>
          <p class="features__subtitle">Alles, was du für einen gelungenen Spieleabend brauchst.</p>
        </header>

        <div class="features__grid">
          <article class="card card-hover features__card">
            <div class="feat-icon feat-icon--amber">
              <span class="icon icon-book-open-outline" aria-hidden="true" />
            </div>
            <h3 class="features__card-title">Große Auswahl</h3>
            <p class="features__card-text">Von Klassikern bis zu modernen Strategiespielen — für jeden Anlass das passende Spiel.</p>
          </article>

          <article class="card card-hover features__card">
            <div class="feat-icon feat-icon--blue">
              <span class="icon icon-calendar-outline" aria-hidden="true" />
            </div>
            <h3 class="features__card-title">Einfach ausleihen</h3>
            <p class="features__card-text">Spiel aussuchen, Ausleihzeit wählen und abholen — unkompliziert und in wenigen Klicks.</p>
          </article>

          <article class="card card-hover features__card">
            <div class="feat-icon feat-icon--green">
              <span class="icon icon-award-outline" aria-hidden="true" />
            </div>
            <h3 class="features__card-title">Für alle</h3>
            <p class="features__card-text">Familien, Freundesgruppen, erfahrene Taktiker — unsere Plattform ist für jeden zugänglich.</p>
          </article>
        </div>
      </div>
    </section>

    <!-- ── CTA Strip ──────────────────────────────────────────── -->
    <Transition name="fade">
      <section v-if="!auth.isLoggedIn" class="cta-strip">
        <div class="cta-strip__inner">
          <div class="cta-strip__text">
            <h2 class="cta-strip__title">Bereit für den nächsten Spieleabend?</h2>
            <p class="cta-strip__sub">Erstelle kostenlos ein Konto und starte noch heute.</p>
          </div>
          <div class="cta-strip__actions">
            <NuxtLink to="/register" class="button button-primary">Jetzt registrieren</NuxtLink>
            <NuxtLink to="/login" class="button">Bereits registriert?</NuxtLink>
          </div>
        </div>
      </section>
    </Transition>

    <!-- ── Footer ──────────────────────────────────────────────── -->
    <footer class="l-footer">
      <div class="l-footer__inner">
        <div class="l-footer__brand">
          <span class="l-footer__hex" aria-hidden="true">⬡</span>
          <span class="l-footer__name">AUA</span>
        </div>
        <nav class="l-footer__nav" aria-label="Footer-Navigation">
          <NuxtLink to="/games" class="l-footer__link">Spielesammlung</NuxtLink>
          <NuxtLink to="/terms" class="l-footer__link">Nutzungsbedingungen</NuxtLink>
          <NuxtLink to="/privacy" class="l-footer__link">Datenschutzerklärung</NuxtLink>
          <NuxtLink to="/cookies" class="l-footer__link">Cookie-Richtlinien</NuxtLink>
        </nav>
        <p class="l-footer__copy">&copy; {{ year }} AUA. Alle Rechte vorbehalten.</p>
      </div>
    </footer>

  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const year = new Date().getFullYear()
</script>

<style lang="scss" scoped>
$hero-bg:       #0F0E0C;
$amber:         #D4921E;
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

$hero-muted:    rgba(238, 232, 223, 0.55);
$hero-muted-50: rgba(238, 232, 223, 0.50);
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
  }

  &__title {
    font-size: clamp(3rem, 8vw, 5.5rem);
    font-weight: 800;
    line-height: 1.05;
    letter-spacing: -0.04em;
    color: $hero-text;
    margin: 0 0 1.5rem;
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
  }

  &__cta-row {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    flex-wrap: wrap;
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

// ─── Features ─────────────────────────────────────────────────────
.features {
  background: var(--background);
  padding: 5rem 1.5rem;

  &__inner { max-width: 1100px; margin: 0 auto; }

  &__header { text-align: center; margin-bottom: 3.5rem; }

  &__title {
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: var(--primary-text);
    margin-bottom: 0.5rem;
  }

  &__subtitle {
    font-size: 1rem;
    color: var(--secondary-text);
    padding-bottom: 0;
  }

  &__grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    @media (max-width: 900px) { grid-template-columns: 1fr; }
  }

  &__card {
    padding: 2rem;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    &:hover { transform: translateY(-4px); }
  }

  &__card-title {
    font-size: 1.15rem;
    font-weight: 700;
    margin: 1rem 0 0.5rem;
    color: var(--primary-text);
    letter-spacing: -0.02em;
  }

  &__card-text {
    font-size: 0.925rem;
    color: var(--secondary-text);
    line-height: 1.65;
    padding-bottom: 0;
  }
}

.feat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;

  .icon { width: 24px; height: 24px; }

  &--amber { background: var(--accent-color-muted); .icon { color: var(--accent-color); } }
  &--blue  { background: var(--accent-lighten-muted); .icon { color: var(--accent-color-lighten); } }
  &--green { background: var(--success-muted); .icon { color: var(--success); } }
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

// ─── Footer ───────────────────────────────────────────────────────
.l-footer {
  margin-top: auto;
  background: $hero-bg;
  border-top: 1px solid $hero-divider;
  padding: 2.5rem 1.5rem;

  &__inner {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
  }

  &__brand { display: flex; align-items: center; gap: 0.4rem; flex-shrink: 0; }

  &__hex { font-size: 1.2rem; color: $amber; }

  &__name {
    font-size: 0.95rem;
    font-weight: 700;
    color: $hero-text;
    letter-spacing: -0.02em;
  }

  &__nav {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    flex: 1;
    position: static;
    transform: none;
    width: auto;
    height: auto;
    justify-content: center;
    @media (max-width: 640px) { justify-content: flex-start; }
  }

  &__link {
    font-size: 0.85rem;
    color: $hero-muted;
    text-decoration: none;
    transition: color 0.2s;
    &:hover { color: $hero-text; }
  }

  &__copy {
    font-size: 0.8rem;
    color: $hero-muted-50;
    margin-left: auto;
    padding-bottom: 0;
    @media (max-width: 640px) { margin-left: 0; width: 100%; }
  }
}
</style>
