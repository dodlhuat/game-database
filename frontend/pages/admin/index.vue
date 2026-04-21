<template>
  <div class="admin-page">
    <AppNav />

    <!-- ── Hero ───────────────────────────────────────────────────── -->
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <p class="page-hero__eyebrow">{{ $t('admin.heading') }}</p>
        <h1 class="page-hero__title">{{ $t('admin.dashboard') }}</h1>
        <div class="page-hero__meta">
          <span class="page-hero__date">{{ today }}</span>
          <template v-if="!loading && stats && alertCount > 0">
            <span class="page-hero__dot" aria-hidden="true" />
            <span class="page-hero__alert-hint">
              {{ alertCount }} {{ alertCount === 1 ? 'Aktion' : 'Aktionen' }} erforderlich
            </span>
          </template>
        </div>
      </div>
    </section>

    <!-- ── Content ────────────────────────────────────────────────── -->
    <div class="admin-content">
      <div class="admin-content__inner">

        <div v-if="loading" class="admin-state">
          <div class="spinner" />
        </div>

        <template v-else-if="stats">

          <!-- ── Attention Card (only when there are alerts) ──────── -->
          <div v-if="alertCount > 0" class="attention-card">
            <div class="attention-card__header">
              <span class="attention-pulse" aria-hidden="true" />
              <span class="attention-card__title">Erfordert Aufmerksamkeit</span>
              <span class="attention-card__count">{{ alertCount }}</span>
            </div>
            <div class="attention-card__list">
              <NuxtLink
                v-if="stats.loans.overdue > 0"
                to="/admin/loans?status=OVERDUE"
                class="attention-item attention-item--red"
              >
                <span class="attention-item__dot" />
                <span class="attention-item__text">
                  <strong>{{ stats.loans.overdue }}</strong> überfällige Ausleihen
                </span>
                <span class="icon icon-navigate_next attention-item__arrow" aria-hidden="true" />
              </NuxtLink>
              <NuxtLink
                v-if="stats.users.pending > 0"
                to="/admin/users?status=PENDING"
                class="attention-item"
              >
                <span class="attention-item__dot" />
                <span class="attention-item__text">
                  <strong>{{ stats.users.pending }}</strong> ausstehende Mitglieder
                </span>
                <span class="icon icon-navigate_next attention-item__arrow" aria-hidden="true" />
              </NuxtLink>
              <NuxtLink
                v-if="stats.extensions.pending > 0"
                to="/admin/extensions"
                class="attention-item"
              >
                <span class="attention-item__dot" />
                <span class="attention-item__text">
                  <strong>{{ stats.extensions.pending }}</strong> offene Verlängerungsanträge
                </span>
                <span class="icon icon-navigate_next attention-item__arrow" aria-hidden="true" />
              </NuxtLink>
              <NuxtLink
                v-if="stats.copies.to_review > 0"
                to="/admin/games?condition=REVIEW"
                class="attention-item"
              >
                <span class="attention-item__dot" />
                <span class="attention-item__text">
                  <strong>{{ stats.copies.to_review }}</strong> Kopien nach Rückgabe zu prüfen
                </span>
                <span class="icon icon-navigate_next attention-item__arrow" aria-hidden="true" />
              </NuxtLink>
            </div>
          </div>

          <!-- ── Operations (2×2 metric cards) ───────────────────── -->
          <div class="ops-grid">
            <NuxtLink
              to="/admin/loans"
              class="ops-card"
              :class="stats.loans.overdue > 0 ? 'ops-card--red' : ''"
            >
              <div class="ops-card__top">
                <span class="ops-card__icon">
                  <span class="icon icon-sync" aria-hidden="true" />
                </span>
                <span
                  v-if="stats.loans.overdue > 0"
                  class="ops-card__badge ops-card__badge--red"
                >{{ stats.loans.overdue }}</span>
              </div>
              <span class="ops-card__value">{{ stats.loans.active }}</span>
              <span class="ops-card__label">Ausleihen</span>
              <span class="ops-card__sub" :class="{ 'ops-card__sub--red': stats.loans.overdue > 0 }">
                {{ stats.loans.overdue > 0 ? `${stats.loans.overdue} überfällig` : 'aktiv' }}
              </span>
            </NuxtLink>

            <NuxtLink
              to="/admin/extensions"
              class="ops-card"
              :class="stats.extensions.pending > 0 ? 'ops-card--amber' : ''"
            >
              <div class="ops-card__top">
                <span class="ops-card__icon">
                  <span class="icon icon-calendar_today" aria-hidden="true" />
                </span>
                <span
                  v-if="stats.extensions.pending > 0"
                  class="ops-card__badge"
                >{{ stats.extensions.pending }}</span>
              </div>
              <span class="ops-card__value">{{ stats.extensions.pending }}</span>
              <span class="ops-card__label">Verlängerungen</span>
              <span class="ops-card__sub">
                {{ stats.extensions.pending > 0 ? 'offen' : 'keine offen' }}
              </span>
            </NuxtLink>

            <NuxtLink
              to="/admin/users"
              class="ops-card"
              :class="stats.users.pending > 0 ? 'ops-card--amber' : ''"
            >
              <div class="ops-card__top">
                <span class="ops-card__icon">
                  <span class="icon icon-person" aria-hidden="true" />
                </span>
                <span
                  v-if="stats.users.pending > 0"
                  class="ops-card__badge"
                >{{ stats.users.pending }}</span>
              </div>
              <span class="ops-card__value">{{ stats.users.total }}</span>
              <span class="ops-card__label">Mitglieder</span>
              <span class="ops-card__sub">
                {{ stats.users.pending > 0 ? `${stats.users.pending} ausstehend` : `${stats.users.active} aktiv` }}
              </span>
            </NuxtLink>

            <NuxtLink
              to="/admin/games?condition=REVIEW"
              class="ops-card"
              :class="stats.copies.to_review > 0 ? 'ops-card--amber' : ''"
            >
              <div class="ops-card__top">
                <span class="ops-card__icon">
                  <span class="icon icon-fact_check" aria-hidden="true" />
                </span>
                <span
                  v-if="stats.copies.to_review > 0"
                  class="ops-card__badge"
                >{{ stats.copies.to_review }}</span>
              </div>
              <span class="ops-card__value">{{ stats.copies.to_review }}</span>
              <span class="ops-card__label">Zu prüfen</span>
              <span class="ops-card__sub">Kopien nach Rückgabe</span>
            </NuxtLink>
          </div>

          <!-- ── Katalog ───────────────────────────────────────────── -->
          <section class="nav-section">
            <p class="nav-section__label">Katalog</p>
            <div class="nav-tiles">
              <NuxtLink to="/admin/games" class="nav-tile">
                <span class="nav-tile__icon"><span class="icon icon-article" aria-hidden="true" /></span>
                <span class="nav-tile__label">Spiele</span>
              </NuxtLink>
              <NuxtLink to="/admin/categories" class="nav-tile">
                <span class="nav-tile__icon"><span class="icon icon-label" aria-hidden="true" /></span>
                <span class="nav-tile__label">Kategorien</span>
              </NuxtLink>
              <NuxtLink to="/admin/tags" class="nav-tile">
                <span class="nav-tile__icon"><span class="icon icon-bookmark" aria-hidden="true" /></span>
                <span class="nav-tile__label">Tags</span>
              </NuxtLink>
              <NuxtLink to="/admin/packages" class="nav-tile">
                <span class="nav-tile__icon"><span class="icon icon-gift-outline" aria-hidden="true" /></span>
                <span class="nav-tile__label">Pakete</span>
              </NuxtLink>
            </div>
          </section>

          <!-- ── Aktivität ─────────────────────────────────────────── -->
          <section class="nav-section">
            <p class="nav-section__label">Aktivität</p>
            <div class="nav-list">
              <NuxtLink to="/admin/package-loans" class="nav-row">
                <span class="nav-row__icon"><span class="icon icon-cube-outline" aria-hidden="true" /></span>
                <span class="nav-row__label">Paket-Ausleihen</span>
                <span class="icon icon-navigate_next nav-row__arrow" aria-hidden="true" />
              </NuxtLink>
              <NuxtLink to="/admin/damage-reports" class="nav-row">
                <span class="nav-row__icon"><span class="icon icon-alert-triangle-outline" aria-hidden="true" /></span>
                <span class="nav-row__label">Schadensmeldungen</span>
                <span class="icon icon-navigate_next nav-row__arrow" aria-hidden="true" />
              </NuxtLink>
              <NuxtLink to="/admin/newsletters" class="nav-row">
                <span class="nav-row__icon"><span class="icon icon-mail" aria-hidden="true" /></span>
                <span class="nav-row__label">Newsletter</span>
                <span class="icon icon-navigate_next nav-row__arrow" aria-hidden="true" />
              </NuxtLink>
            </div>
          </section>

          <!-- ── System ────────────────────────────────────────────── -->
          <section class="nav-section">
            <p class="nav-section__label">System</p>
            <div class="nav-list">
              <NuxtLink to="/admin/loan-settings" class="nav-row">
                <span class="nav-row__icon"><span class="icon icon-settings" aria-hidden="true" /></span>
                <span class="nav-row__label">Ausleih-Einstellungen</span>
                <span class="icon icon-navigate_next nav-row__arrow" aria-hidden="true" />
              </NuxtLink>
              <NuxtLink to="/admin/emails" class="nav-row">
                <span class="nav-row__icon"><span class="icon icon-mail" aria-hidden="true" /></span>
                <span class="nav-row__label">E-Mail-Vorlagen</span>
                <span class="icon icon-navigate_next nav-row__arrow" aria-hidden="true" />
              </NuxtLink>
              <NuxtLink to="/admin/email-logs" class="nav-row">
                <span class="nav-row__icon"><span class="icon icon-format_list_bulleted" aria-hidden="true" /></span>
                <span class="nav-row__label">E-Mail-Protokoll</span>
                <span class="icon icon-navigate_next nav-row__arrow" aria-hidden="true" />
              </NuxtLink>
            </div>
          </section>

        </template>
      </div>
    </div>

    <!-- ── Footer ──────────────────────────────────────────────────── -->
    <footer class="l-footer">
      <div class="l-footer__inner">
        <div class="l-footer__brand">
          <span class="l-footer__hex" aria-hidden="true">⬡</span>
          <span class="l-footer__name">AUA</span>
        </div>
        <p class="l-footer__copy">{{ $t('common.copyright_short', { year }) }}</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { AdminStats } from '~/composables/useAdmin'

definePageMeta({ middleware: ['auth', 'admin'] })

const { fetchStats } = useAdmin()
const stats = ref<AdminStats | null>(null)
const loading = ref(true)
const year = new Date().getFullYear()

const today = new Date().toLocaleDateString('de-AT', {
  weekday: 'long', day: 'numeric', month: 'long',
})

const alertCount = computed(() => {
  if (!stats.value) return 0
  return (
    (stats.value.loans.overdue > 0 ? 1 : 0) +
    (stats.value.users.pending > 0 ? 1 : 0) +
    (stats.value.extensions.pending > 0 ? 1 : 0) +
    (stats.value.copies.to_review > 0 ? 1 : 0)
  )
})

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
$nav-height:  64px;
$amber-08:    rgba(212, 146, 30, 0.08);
$amber-12:    rgba(212, 146, 30, 0.12);
$amber-20:    rgba(212, 146, 30, 0.20);
$amber-30:    rgba(212, 146, 30, 0.30);
$amber-glow:  rgba(212, 146, 30, 0.16);
$red-08:      rgba(239, 68, 68, 0.08);
$red-20:      rgba(239, 68, 68, 0.20);
$red-30:      rgba(239, 68, 68, 0.30);
$hero-text:   #EEE8DF;
$hero-muted:  rgba(238, 232, 223, 0.55);
$hero-div:    rgba(238, 232, 223, 0.10);

.admin-page { min-height: 100vh; display: flex; flex-direction: column; background: var(--background); }

// ─── Hero ─────────────────────────────────────────────────────────
.page-hero {
  position: relative;
  background: $hero-bg;
  padding: calc(#{$nav-height} + 1.5rem) 1.25rem 1.5rem;
  overflow: hidden;

  &__backdrop { position: absolute; inset: 0; pointer-events: none; }

  &__glow {
    position: absolute; width: 360px; height: 360px;
    top: -100px; right: -60px; border-radius: 50%;
    filter: blur(80px); background: $amber-glow;
  }

  &__dots {
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,0.035) 1px, transparent 1px);
    background-size: 22px 22px;
    mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%);
  }

  &__body { position: relative; z-index: 1; max-width: 600px; margin: 0 auto; }

  &__eyebrow {
    display: inline-block;
    font-size: 0.7rem; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase;
    color: $amber; margin-bottom: 0.45rem;
    padding: 0.18rem 0.55rem;
    border: 1px solid rgba(212, 146, 30, 0.30); border-radius: 999px;
    background: rgba(212, 146, 30, 0.08);
  }

  &__title {
    font-size: clamp(1.5rem, 5vw, 2rem);
    font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0 0 0.6rem;
  }

  &__meta {
    display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap;
  }

  &__date {
    font-size: 0.78rem; color: $hero-muted; font-weight: 500;
  }

  &__dot {
    width: 3px; height: 3px; border-radius: 50%; background: $hero-muted; flex-shrink: 0;
  }

  &__alert-hint {
    font-size: 0.78rem; font-weight: 600; color: $amber;
  }


}

// ─── Content ──────────────────────────────────────────────────────
.admin-content {
  flex: 1; padding: 1.25rem 1.25rem 4rem;
  &__inner { max-width: 600px; margin: 0 auto; display: flex; flex-direction: column; gap: 1rem; }
}

.admin-state { display: flex; justify-content: center; align-items: center; min-height: 200px; }

// ─── Attention Card ────────────────────────────────────────────────
@keyframes pulse-border {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

@keyframes pulse-dot {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.5); opacity: 0.6; }
}

.attention-card {
  border-radius: 16px;
  border: 1.5px solid rgba(212, 146, 30, 0.45);
  background: rgba(212, 146, 30, 0.05);
  overflow: hidden;
  animation: pulse-border 2.5s ease-in-out infinite;

  &__header {
    display: flex; align-items: center; gap: 0.6rem;
    padding: 0.85rem 1.1rem;
    border-bottom: 1px solid rgba(212, 146, 30, 0.15);
  }

  &__title {
    font-size: 0.82rem; font-weight: 700;
    color: $amber; letter-spacing: 0.01em; flex: 1;
  }

  &__count {
    font-size: 0.72rem; font-weight: 700;
    background: $amber; color: #1a0d00;
    min-width: 1.3rem; height: 1.3rem;
    border-radius: 999px;
    display: flex; align-items: center; justify-content: center; padding: 0 0.3rem;
  }

  &__list { display: flex; flex-direction: column; }
}

.attention-pulse {
  width: 8px; height: 8px; border-radius: 50%;
  background: $amber; flex-shrink: 0;
  animation: pulse-dot 1.8s ease-in-out infinite;
}

.attention-item {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.8rem 1.1rem;
  text-decoration: none; color: var(--primary-text);
  border-bottom: 1px solid rgba(212, 146, 30, 0.10);
  transition: background 0.15s;

  &:last-child { border-bottom: none; }
  &:active { background: rgba(212, 146, 30, 0.08); }

  &__dot {
    width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0;
    background: $amber;
  }

  &__text {
    flex: 1; font-size: 0.85rem; color: var(--primary-text);
    strong { font-weight: 700; color: $amber; }
  }

  &__arrow {
    font-size: 1.1rem; color: rgba(212, 146, 30, 0.5); flex-shrink: 0;
  }

  &--red {
    .attention-item__dot { background: #f87171; }
    .attention-item__text strong { color: #f87171; }
    .attention-item__arrow { color: rgba(239, 68, 68, 0.5); }
    &:active { background: rgba(239, 68, 68, 0.06); }
  }
}

// ─── Operations Grid (2×2) ─────────────────────────────────────────
.ops-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}

.ops-card {
  position: relative;
  display: flex; flex-direction: column;
  padding: 1rem 1.1rem 1rem;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 16px;
  text-decoration: none; overflow: hidden;
  transition: transform 0.12s, border-color 0.2s;

  &::after {
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 2.5px; background: transparent;
    transition: background 0.2s;
  }

  &:active { transform: scale(0.97); }

  &--amber {
    border-color: $amber-20;
    &::after { background: $amber; }
    .ops-card__value { color: $amber; }
  }

  &--red {
    border-color: $red-20;
    &::after { background: #ef4444; }
    .ops-card__value { color: #f87171; }
  }

  &__top {
    display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 0.65rem;
  }

  &__icon {
    width: 32px; height: 32px; border-radius: 8px;
    background: var(--background);
    display: flex; align-items: center; justify-content: center;
    color: var(--secondary-text);
    .icon { font-size: 1.1rem; }
  }

  &__badge {
    font-size: 0.68rem; font-weight: 700;
    background: $amber; color: #1a0d00;
    min-width: 1.25rem; height: 1.25rem;
    border-radius: 999px; padding: 0 0.3rem;
    display: flex; align-items: center; justify-content: center;

    &--red { background: #ef4444; color: #fff; }
  }

  &__value {
    font-size: 2rem; font-weight: 800; letter-spacing: -0.05em;
    color: var(--primary-text); line-height: 1; margin-bottom: 0.2rem;
  }

  &__label {
    font-size: 0.78rem; font-weight: 600; color: var(--primary-text); margin-bottom: 0.1rem;
  }

  &__sub {
    font-size: 0.7rem; color: var(--secondary-text);
    &--red { color: #f87171; }
  }
}

// ─── Nav Sections ─────────────────────────────────────────────────
.nav-section { display: flex; flex-direction: column; gap: 0.5rem; }

.nav-section__label {
  font-size: 0.7rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.10em;
  color: var(--secondary-text);
  padding: 0 0.25rem;
  margin: 0;
}

// ─── Tile Grid (Katalog) ──────────────────────────────────────────
.nav-tiles {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.6rem;
}

.nav-tile {
  display: flex; flex-direction: column; align-items: center;
  gap: 0.5rem; padding: 0.9rem 0.5rem;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 14px;
  text-decoration: none;
  transition: border-color 0.15s, background 0.15s, transform 0.12s;

  &:active { transform: scale(0.95); }

  &:hover {
    border-color: $amber-20;
    background: rgba(212, 146, 30, 0.04);
    .nav-tile__icon { background: $amber-12; color: $amber; }
  }

  &__icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: var(--background);
    display: flex; align-items: center; justify-content: center;
    color: var(--secondary-text);
    transition: background 0.15s, color 0.15s;
    .icon { font-size: 1.15rem; }
  }

  &__label {
    font-size: 0.68rem; font-weight: 600;
    color: var(--primary-text); text-align: center; line-height: 1.2;
  }
}

// ─── List Rows (Aktivität / System) ───────────────────────────────
.nav-list {
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 14px; overflow: hidden;
}

.nav-row {
  display: flex; align-items: center; gap: 0.875rem;
  padding: 0.9rem 1.1rem;
  text-decoration: none; color: var(--primary-text);
  border-bottom: 1px solid var(--divider);
  transition: background 0.12s;

  &:last-child { border-bottom: none; }
  &:active { background: var(--background); }

  &__icon {
    flex-shrink: 0; width: 32px; height: 32px; border-radius: 8px;
    background: var(--background);
    display: flex; align-items: center; justify-content: center;
    color: var(--secondary-text);
    .icon { font-size: 1.1rem; }
  }

  &__label { flex: 1; font-size: 0.875rem; font-weight: 500; }

  &__arrow {
    font-size: 1.1rem; color: var(--secondary-text); opacity: 0.5; flex-shrink: 0;
    transition: opacity 0.15s, color 0.15s;
  }

  &:hover .nav-row__arrow { opacity: 1; color: $amber; }
}

// ─── Footer ───────────────────────────────────────────────────────
.l-footer {
  background: $hero-bg; border-top: 1px solid $hero-div; padding: 1.5rem 1.25rem;
  &__inner { max-width: 600px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; }
  &__brand { display: flex; align-items: center; gap: 0.4rem; }
  &__hex { font-size: 1rem; color: $amber; }
  &__name { font-size: 0.875rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em; }
  &__copy { font-size: 0.75rem; color: $hero-muted; }
}
</style>
