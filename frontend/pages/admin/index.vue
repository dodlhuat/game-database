<template>
  <div class="admin-page">
    <AppNav />

    <!-- ── Page Hero ────────────────────────────────────────────── -->
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <p class="page-hero__eyebrow">Administration</p>
        <h1 class="page-hero__title">Admin-Dashboard</h1>
      </div>
    </section>

    <!-- ── Content ──────────────────────────────────────────────── -->
    <div class="admin-content">
      <div class="admin-content__inner">

        <div v-if="loading" class="admin-state">
          <div class="spinner" />
        </div>

        <template v-else-if="stats">

          <!-- Stats ─────────────────────────────────────────────── -->
          <div class="stats-grid">
            <div class="stat-card">
              <span class="stat-card__icon">
                <span class="icon icon-people-outline" aria-hidden="true" />
              </span>
              <div class="stat-card__body">
                <span class="stat-card__value">{{ stats.users.total }}</span>
                <span class="stat-card__label">Mitglieder gesamt</span>
              </div>
              <NuxtLink
                v-if="stats.users.pending > 0"
                to="/admin/users?status=PENDING"
                class="stat-card__alert"
              >
                {{ stats.users.pending }} ausstehend
              </NuxtLink>
            </div>

            <div class="stat-card" :class="{ 'stat-card--warn': stats.loans.overdue > 0 }">
              <span class="stat-card__icon">
                <span class="icon icon-book-open-outline" aria-hidden="true" />
              </span>
              <div class="stat-card__body">
                <span class="stat-card__value">{{ stats.loans.active }}</span>
                <span class="stat-card__label">Aktive Ausleihen</span>
              </div>
              <NuxtLink
                v-if="stats.loans.overdue > 0"
                to="/admin/loans?status=OVERDUE"
                class="stat-card__alert stat-card__alert--danger"
              >
                {{ stats.loans.overdue }} überfällig
              </NuxtLink>
            </div>

            <div class="stat-card" :class="{ 'stat-card--amber': stats.extensions.pending > 0 }">
              <span class="stat-card__icon">
                <span class="icon icon-calendar-outline" aria-hidden="true" />
              </span>
              <div class="stat-card__body">
                <span class="stat-card__value">{{ stats.extensions.pending }}</span>
                <span class="stat-card__label">Offene Verlängerungsanträge</span>
              </div>
              <NuxtLink
                v-if="stats.extensions.pending > 0"
                to="/admin/extensions"
                class="stat-card__alert"
              >
                Jetzt prüfen →
              </NuxtLink>
            </div>
          </div>

          <!-- Navigation ─────────────────────────────────────────── -->
          <section class="dash-section">
            <header class="dash-section__header">
              <h2 class="dash-section__title">Verwaltung</h2>
            </header>
            <div class="nav-grid">
            <NuxtLink to="/admin/users" class="nav-card">
              <span class="nav-card__icon"><span class="icon icon-people-outline" aria-hidden="true" /></span>
              <span class="nav-card__label">Mitglieder</span>
              <span class="nav-card__arrow icon icon-arrow-forward-outline" aria-hidden="true" />
            </NuxtLink>
            <NuxtLink to="/admin/games" class="nav-card">
              <span class="nav-card__icon"><span class="icon icon-book-open-outline" aria-hidden="true" /></span>
              <span class="nav-card__label">Spiele</span>
              <span class="nav-card__arrow icon icon-arrow-forward-outline" aria-hidden="true" />
            </NuxtLink>
            <NuxtLink to="/admin/copies" class="nav-card">
              <span class="nav-card__icon"><span class="icon icon-layers-outline" aria-hidden="true" /></span>
              <span class="nav-card__label">Kopien</span>
              <span class="nav-card__arrow icon icon-arrow-forward-outline" aria-hidden="true" />
            </NuxtLink>
            <NuxtLink to="/admin/loans" class="nav-card">
              <span class="nav-card__icon"><span class="icon icon-swap-outline" aria-hidden="true" /></span>
              <span class="nav-card__label">Ausleihen</span>
              <span class="nav-card__arrow icon icon-arrow-forward-outline" aria-hidden="true" />
            </NuxtLink>
            <NuxtLink to="/admin/extensions" class="nav-card">
              <span class="nav-card__icon"><span class="icon icon-calendar-outline" aria-hidden="true" /></span>
              <span class="nav-card__label">Verlängerungsanträge</span>
              <span class="nav-card__arrow icon icon-arrow-forward-outline" aria-hidden="true" />
            </NuxtLink>
            <NuxtLink to="/admin/newsletters" class="nav-card">
              <span class="nav-card__icon"><span class="icon icon-email-outline" aria-hidden="true" /></span>
              <span class="nav-card__label">Newsletter</span>
              <span class="nav-card__arrow icon icon-arrow-forward-outline" aria-hidden="true" />
            </NuxtLink>
            <NuxtLink to="/admin/packages" class="nav-card">
              <span class="nav-card__icon"><span class="icon icon-gift-outline" aria-hidden="true" /></span>
              <span class="nav-card__label">Pakete</span>
              <span class="nav-card__arrow icon icon-arrow-forward-outline" aria-hidden="true" />
            </NuxtLink>
            <NuxtLink to="/admin/damage-reports" class="nav-card">
              <span class="nav-card__icon"><span class="icon icon-alert-triangle-outline" aria-hidden="true" /></span>
              <span class="nav-card__label">Schadensmeldungen</span>
              <span class="nav-card__arrow icon icon-arrow-forward-outline" aria-hidden="true" />
            </NuxtLink>
            </div>
          </section>

        </template>
      </div>
    </div>

    <!-- ── Footer ──────────────────────────────────────────────── -->
    <footer class="l-footer">
      <div class="l-footer__inner">
        <div class="l-footer__brand">
          <span class="l-footer__hex" aria-hidden="true">⬡</span>
          <span class="l-footer__name">AUA</span>
        </div>
        <p class="l-footer__copy">&copy; {{ year }} AUA</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import type { AdminStats } from '~/composables/useAdmin'

definePageMeta({ middleware: ['auth', 'admin'] })

const { fetchStats } = useAdmin()
const stats = ref<AdminStats | null>(null)
const loading = ref(true)
const year = new Date().getFullYear()

onMounted(async () => {
  try {
    stats.value = await fetchStats()
  } finally {
    loading.value = false
  }
})
</script>

<style lang="scss" scoped>
$hero-bg:     #0F0E0C;
$amber:       #D4921E;
$nav-height:  64px;

$amber-08:    rgba(212, 146, 30, 0.08);
$amber-14:    rgba(212, 146, 30, 0.14);
$amber-25:    rgba(212, 146, 30, 0.25);
$amber-glow:  rgba(212, 146, 30, 0.16);

$hero-text:   #EEE8DF;
$hero-muted:  rgba(238, 232, 223, 0.55);
$hero-muted-50: rgba(238, 232, 223, 0.50);
$hero-divider:  rgba(238, 232, 223, 0.10);

.admin-page { min-height: 100vh; display: flex; flex-direction: column; background: var(--background); }

// ─── Hero (compact) ───────────────────────────────────────────────
.page-hero {
  position: relative;
  background: $hero-bg;
  padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem;
  overflow: hidden;

  &__backdrop { position: absolute; inset: 0; pointer-events: none; }

  &__glow {
    position: absolute;
    width: 400px; height: 400px;
    top: -120px; right: -60px;
    border-radius: 50%;
    filter: blur(90px);
    background: $amber-glow;
  }

  &__dots {
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px);
    background-size: 24px 24px;
    mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%);
  }

  &__body { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; }

  &__eyebrow {
    display: inline-block;
    font-size: 0.72rem; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase;
    color: $amber; margin-bottom: 0.5rem;
    padding: 0.2rem 0.6rem;
    border: 1px solid $amber-25; border-radius: 999px; background: $amber-08;
  }

  &__title {
    font-size: clamp(1.5rem, 3vw, 2.25rem);
    font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0;
  }
}

// ─── Content ──────────────────────────────────────────────────────
.admin-content {
  flex: 1; padding: 2rem 1.5rem 4rem;
  &__inner { max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; }
}

.admin-state { display: flex; justify-content: center; align-items: center; min-height: 200px; }

// ─── Stats ────────────────────────────────────────────────────────
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  @media (max-width: 700px) { grid-template-columns: 1fr; }
}

.stat-card {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 12px;
  transition: border-color 0.2s;

  &--warn  { border-color: rgba(239,68,68,0.3); background: rgba(239,68,68,0.04); .stat-card__value { color: #f87171; } .stat-card__icon { background: rgba(239,68,68,0.10); color: #f87171; } }
  &--amber { border-color: $amber-25; .stat-card__value { color: $amber; } }

  &__icon {
    flex-shrink: 0; width: 40px; height: 40px; border-radius: 10px;
    background: $amber-14; color: $amber;
    display: flex; align-items: center; justify-content: center;
    .icon { width: 20px; height: 20px; }
  }

  &__body { display: flex; flex-direction: column; gap: 0.15rem; flex: 1; }

  &__value { font-size: 1.7rem; font-weight: 800; letter-spacing: -0.04em; color: var(--primary-text); line-height: 1; }

  &__label { font-size: 0.78rem; color: var(--secondary-text); font-weight: 500; }

  &__alert {
    align-self: flex-end;
    font-size: 0.72rem; font-weight: 600;
    color: $amber; background: $amber-08;
    border: 1px solid $amber-25; border-radius: 999px;
    padding: 0.2rem 0.55rem;
    text-decoration: none; white-space: nowrap;
    transition: background 0.2s;
    &:hover { background: $amber-14; }
    &--danger { color: #f87171; background: rgba(239,68,68,0.08); border-color: rgba(239,68,68,0.25); &:hover { background: rgba(239,68,68,0.14); } }
  }
}

// ─── Section ──────────────────────────────────────────────────────
.dash-section {
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 14px;
  overflow: hidden;

  &__header {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 1.1rem 1.5rem;
    border-bottom: 1px solid var(--divider);
  }

  &__title { font-size: 0.95rem; font-weight: 700; color: var(--primary-text); margin: 0; letter-spacing: -0.02em; }
}

// ─── Nav Grid ─────────────────────────────────────────────────────
.nav-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  padding: 1.25rem 1.5rem;

  @media (max-width: 800px) { grid-template-columns: repeat(2, 1fr); }
  @media (max-width: 480px) { grid-template-columns: 1fr; }
}

.nav-card {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 1rem 1.25rem;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 12px;
  text-decoration: none;
  color: var(--primary-text);
  font-size: 0.9rem;
  font-weight: 600;
  transition: border-color 0.2s, background 0.2s;

  &:hover {
    border-color: $amber-25;
    background: $amber-08;
    .nav-card__icon { background: $amber-14; color: $amber; }
    .nav-card__arrow { opacity: 1; color: $amber; }
  }

  &__icon {
    flex-shrink: 0;
    width: 36px; height: 36px;
    border-radius: 9px;
    background: var(--background);
    display: flex; align-items: center; justify-content: center;
    color: var(--secondary-text);
    transition: background 0.2s, color 0.2s;
    .icon { width: 18px; height: 18px; }
  }

  &__label { flex: 1; }

  &__arrow {
    width: 16px; height: 16px;
    flex-shrink: 0;
    opacity: 0;
    color: var(--secondary-text);
    transition: opacity 0.2s, color 0.2s;
  }
}

// ─── Footer ───────────────────────────────────────────────────────
.l-footer {
  background: $hero-bg; border-top: 1px solid $hero-divider; padding: 1.75rem 1.5rem;
  &__inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
  &__brand { display: flex; align-items: center; gap: 0.4rem; }
  &__hex { font-size: 1.1rem; color: $amber; }
  &__name { font-size: 0.9rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em; }
  &__copy { font-size: 0.8rem; color: $hero-muted-50; padding-bottom: 0; }
}
</style>
