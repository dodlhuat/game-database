<template>
  <div class="dashboard-page">

    <AppNav />

    <!-- ── Page Hero ────────────────────────────────────────────── -->
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow page-hero__glow--amber" />
        <div class="page-hero__glow page-hero__glow--warm" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <p class="page-hero__eyebrow">Mitglieder-Bereich</p>
        <h1 class="page-hero__title">Willkommen, {{ auth.user?.name }}</h1>
        <p class="page-hero__sub">Deine Ausleihen, Reservierungen und Kontoverwaltung auf einen Blick.</p>

        <!-- Stats ────────────────────────────────────────────────── -->
        <div v-if="!loading" class="stats-grid">
          <div class="stat-card">
            <span class="stat-card__icon">
              <span class="icon icon-book-open-outline" aria-hidden="true" />
            </span>
            <div class="stat-card__body">
              <span class="stat-card__value">{{ data?.stats.active_loans_count ?? 0 }}</span>
              <span class="stat-card__label">Aktive Ausleihen</span>
            </div>
          </div>
          <div class="stat-card stat-card--warn">
            <span class="stat-card__icon">
              <span class="icon icon-alert-triangle-outline" aria-hidden="true" />
            </span>
            <div class="stat-card__body">
              <span class="stat-card__value">{{ data?.stats.overdue_count ?? 0 }}</span>
              <span class="stat-card__label">Überfällig</span>
            </div>
          </div>
          <div class="stat-card">
            <span class="stat-card__icon">
              <span class="icon icon-calendar-outline" aria-hidden="true" />
            </span>
            <div class="stat-card__body">
              <span class="stat-card__value">{{ data?.stats.reservations_count ?? 0 }}</span>
              <span class="stat-card__label">Reservierungen</span>
            </div>
          </div>
          <div class="stat-card">
            <span class="stat-card__icon">
              <span class="icon icon-archive-outline" aria-hidden="true" />
            </span>
            <div class="stat-card__body">
              <span class="stat-card__value">{{ data?.stats.total_loans ?? 0 }}</span>
              <span class="stat-card__label">Ausleihen gesamt</span>
            </div>
          </div>
        </div>

        <div v-else class="stats-grid stats-grid--loading">
          <div v-for="i in 4" :key="i" class="stat-card stat-card--skeleton" />
        </div>
      </div>
    </section>

    <!-- ── Content ──────────────────────────────────────────────── -->
    <div class="dashboard-content">
      <div class="dashboard-content__inner">

        <div v-if="loading" class="dashboard-state">
          <div class="spinner" />
        </div>

        <template v-else>

          <!-- Aktive Ausleihen ───────────────────────────────────── -->
          <section class="dash-section">
            <header class="dash-section__header">
              <h2 class="dash-section__title">Aktive Ausleihen</h2>
              <span class="dash-section__count">{{ activeLoans.length }}</span>
            </header>

            <div v-if="!activeLoans.length" class="dash-empty">
              <span class="icon icon-inbox-outline dash-empty__icon" aria-hidden="true" />
              <p class="dash-empty__text">Keine aktiven Ausleihen vorhanden.</p>
            </div>

            <div v-else class="table-wrap">
              <table class="dash-table">
                <thead>
                  <tr>
                    <th>Spiel</th>
                    <th>Status</th>
                    <th>Fällig am</th>
                    <th>Aktionen</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="loan in activeLoans" :key="loan.id">
                    <td>
                      <NuxtLink :to="`/games/${loan.game?.slug}`" class="dash-table__link">
                        {{ loan.game?.title }}
                      </NuxtLink>
                    </td>
                    <td>
                      <span class="status-badge" :class="`status-badge--${loanStatusVariant(loan)}`">
                        {{ loanStatusLabel(loan) }}
                      </span>
                    </td>
                    <td>
                      <span :class="{ 'text-warn': loan.status === 'OVERDUE' }">
                        {{ formatDate(loan.due_date) }}
                      </span>
                    </td>
                    <td>
                      <div class="action-row">
                        <template v-if="loan.status !== 'RETURNED'">
                          <span v-if="pendingExtension(loan)" class="status-badge status-badge--pending">
                            Verlängerung beantragt
                          </span>
                          <button v-else class="action-btn" @click="openExtension(loan)">
                            Verlängern
                          </button>
                        </template>
                        <button class="action-btn" @click="openReturn(loan)">Zurückgeben</button>
                        <button class="action-btn action-btn--danger" @click="openDamage(loan)">Schaden</button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <!-- Reservierungen ─────────────────────────────────────── -->
          <section class="dash-section">
            <header class="dash-section__header">
              <h2 class="dash-section__title">Meine Reservierungen</h2>
              <span class="dash-section__count">{{ reservations.length }}</span>
            </header>

            <div v-if="!reservations.length" class="dash-empty">
              <span class="icon icon-bookmark-outline dash-empty__icon" aria-hidden="true" />
              <p class="dash-empty__text">Keine aktiven Reservierungen.</p>
            </div>

            <div v-else class="table-wrap">
              <table class="dash-table">
                <thead>
                  <tr>
                    <th>Spiel</th>
                    <th>Position in Warteschlange</th>
                    <th>Aktionen</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="res in reservations" :key="res.id">
                    <td>{{ res.game?.title }}</td>
                    <td>
                      <span class="queue-badge"># {{ res.position }}</span>
                    </td>
                    <td>
                      <button class="action-btn action-btn--danger" @click="cancelReservation(res.id)">
                        Stornieren
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <!-- Ausleihhistorie ────────────────────────────────────── -->
          <section class="dash-section">
            <header class="dash-section__header">
              <h2 class="dash-section__title">Letzte Ausleihen</h2>
              <span class="dash-section__count">{{ loanHistory.length }}</span>
            </header>

            <div v-if="!loanHistory.length" class="dash-empty">
              <span class="icon icon-clock-outline dash-empty__icon" aria-hidden="true" />
              <p class="dash-empty__text">Noch keine zurückgegebenen Ausleihen.</p>
            </div>

            <div v-else class="table-wrap">
              <table class="dash-table">
                <thead>
                  <tr>
                    <th>Spiel</th>
                    <th>Zurückgegeben am</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="loan in loanHistory" :key="loan.id">
                    <td>{{ loan.game?.title }}</td>
                    <td>{{ formatDate(loan.returned_at!) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <!-- Konto-Link ─────────────────────────────────────────── -->
          <div class="dash-footer-actions">
            <NuxtLink to="/account" class="dash-link">
              <span class="icon icon-settings-2-outline" aria-hidden="true" />
              Konto-Einstellungen
            </NuxtLink>
            <button class="action-btn action-btn--ghost" @click="handleLogout">
              <span class="icon icon-log-out-outline" aria-hidden="true" />
              Abmelden
            </button>
          </div>

        </template>
      </div>
    </div>

    <!-- ── Rückgabe-Dialog ──────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="returnLoan" class="modal-overlay" @click.self="returnLoan = null">
        <div class="modal">
          <div class="modal__header">
            <h3 class="modal__title">Spiel zurückgeben</h3>
            <button class="modal__close" aria-label="Schließen" @click="returnLoan = null">
              <span class="icon icon-close-outline" aria-hidden="true" />
            </button>
          </div>
          <p class="modal__game">{{ returnLoan.game?.title }}</p>
          <label class="modal__label">Zustand</label>
          <select v-model="returnCondition" class="modal__select">
            <option value="GOOD">Gut</option>
            <option value="WORN">Abgenutzt</option>
            <option value="DAMAGED">Beschädigt</option>
          </select>
          <div class="modal__actions">
            <UiButton :loading="returning" @click="submitReturn">Bestätigen</UiButton>
            <button class="action-btn" @click="returnLoan = null">Abbrechen</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ── Schadensmeldungs-Dialog ──────────────────────────────── -->
    <Transition name="modal">
      <div v-if="damageLoan" class="modal-overlay" @click.self="damageLoan = null">
        <div class="modal">
          <div class="modal__header">
            <h3 class="modal__title">Schaden melden</h3>
            <button class="modal__close" aria-label="Schließen" @click="damageLoan = null">
              <span class="icon icon-close-outline" aria-hidden="true" />
            </button>
          </div>
          <p class="modal__game">{{ damageLoan.game?.title }}</p>
          <UiInput v-model="damageDescription" label="Beschreibung" />
          <UiInput v-model="damagePhotoUrl" label="Foto-URL (optional)" />
          <div class="modal__actions">
            <UiButton :loading="reporting" @click="submitDamage">Melden</UiButton>
            <button class="action-btn" @click="damageLoan = null">Abbrechen</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ── Verlängerungs-Dialog ─────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="extensionLoan" class="modal-overlay" @click.self="extensionLoan = null">
        <div class="modal">
          <div class="modal__header">
            <h3 class="modal__title">Verlängerung beantragen</h3>
            <button class="modal__close" aria-label="Schließen" @click="extensionLoan = null">
              <span class="icon icon-close-outline" aria-hidden="true" />
            </button>
          </div>
          <p class="modal__game">{{ extensionLoan.game?.title }}</p>
          <UiInput v-model="extensionDate" type="date" label="Neues Rückgabedatum" />
          <div class="modal__actions">
            <UiButton :loading="extending" @click="submitExtension">Beantragen</UiButton>
            <button class="action-btn" @click="extensionLoan = null">Abbrechen</button>
          </div>
        </div>
      </div>
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
import { ref, computed, onMounted } from 'vue'
import type { Loan, DashboardData } from '~/composables/useLoans'

definePageMeta({ middleware: ['auth'] })

const auth = useAuthStore()
const { logout } = useAuth()
const { fetchDashboard, returnLoan: doReturn, requestExtension, removeReservation, reportDamage } = useLoans()

const year = new Date().getFullYear()
const loading = ref(true)
const data = ref<DashboardData | null>(null)

const returnLoan = ref<Loan | null>(null)
const returnCondition = ref('GOOD')
const returning = ref(false)

const extensionLoan = ref<Loan | null>(null)
const extensionDate = ref('')
const extending = ref(false)

const damageLoan = ref<Loan | null>(null)
const damageDescription = ref('')
const damagePhotoUrl = ref('')
const reporting = ref(false)

const activeLoans = computed(() => data.value?.active_loans.data ?? [])
const loanHistory = computed(() => data.value?.loan_history.data ?? [])
const reservations = computed(() => (data.value?.reservations as { data: { id: number; position: number; game?: { title: string; slug: string } }[] })?.data ?? [])

onMounted(async () => {
  try {
    data.value = await fetchDashboard()
  } finally {
    loading.value = false
  }
})

function openReturn(loan: Loan) {
  returnLoan.value = loan
  returnCondition.value = 'GOOD'
}

async function submitReturn() {
  if (!returnLoan.value) return
  returning.value = true
  try {
    await doReturn(returnLoan.value.id, returnCondition.value)
    data.value = await fetchDashboard()
    returnLoan.value = null
  } finally {
    returning.value = false
  }
}

function openExtension(loan: Loan) {
  extensionLoan.value = loan
  extensionDate.value = ''
}

async function submitExtension() {
  if (!extensionLoan.value || !extensionDate.value) return
  extending.value = true
  try {
    await requestExtension(extensionLoan.value.id, extensionDate.value)
    data.value = await fetchDashboard()
    extensionLoan.value = null
  } finally {
    extending.value = false
  }
}

function openDamage(loan: Loan) {
  damageLoan.value = loan
  damageDescription.value = ''
  damagePhotoUrl.value = ''
}

async function submitDamage() {
  if (!damageLoan.value || !damageDescription.value) return
  reporting.value = true
  try {
    await reportDamage(damageLoan.value.id, damageDescription.value, damagePhotoUrl.value || undefined)
    damageLoan.value = null
  } finally {
    reporting.value = false
  }
}

async function cancelReservation(id: number) {
  await removeReservation(id)
  data.value = await fetchDashboard()
}

async function handleLogout() {
  await logout()
}

function pendingExtension(loan: Loan) {
  return loan.extensions.some((e) => e.status === 'PENDING')
}

function loanStatusVariant(loan: Loan) {
  if (loan.status === 'OVERDUE') return 'danger'
  if (loan.status === 'EXTENDED') return 'pending'
  return 'active'
}

function loanStatusLabel(loan: Loan) {
  const map: Record<string, string> = {
    ACTIVE: 'Aktiv',
    EXTENDED: 'Verlängert',
    OVERDUE: 'Überfällig',
    RETURNED: 'Zurückgegeben',
  }
  return map[loan.status] ?? loan.status
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString('de-DE')
}
</script>

<style lang="scss" scoped>
$hero-bg:       #0F0E0C;
$amber:         #D4921E;
$nav-height:    64px;

$amber-08:      rgba(212, 146, 30, 0.08);
$amber-14:      rgba(212, 146, 30, 0.14);
$amber-25:      rgba(212, 146, 30, 0.25);
$amber-40:      rgba(212, 146, 30, 0.40);
$amber-glow:    rgba(212, 146, 30, 0.20);
$warm-glow:     rgba(44, 40, 32, 0.70);

$hero-text:     #EEE8DF;
$hero-muted:    rgba(238, 232, 223, 0.55);
$hero-muted-50: rgba(238, 232, 223, 0.50);
$hero-muted-20: rgba(238, 232, 223, 0.20);
$hero-divider:  rgba(238, 232, 223, 0.10);
$hero-divider-20: rgba(238, 232, 223, 0.20);

// ─── Page Shell ───────────────────────────────────────────────────
.dashboard-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--background);
}

// ─── Page Hero ────────────────────────────────────────────────────
.page-hero {
  position: relative;
  background: $hero-bg;
  padding: calc(#{$nav-height} + 3rem) 1.5rem 3.5rem;
  overflow: hidden;

  &__backdrop { position: absolute; inset: 0; pointer-events: none; }

  &__glow {
    position: absolute;
    border-radius: 50%;
    filter: blur(90px);

    &--amber {
      width: 550px;
      height: 550px;
      top: -120px;
      right: -80px;
      background: $amber-glow;
    }

    &--warm {
      width: 400px;
      height: 400px;
      bottom: -80px;
      left: -100px;
      background: $warm-glow;
    }
  }

  &__dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px);
    background-size: 24px 24px;
    mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%);
  }

  &__body {
    position: relative;
    z-index: 1;
    max-width: 1100px;
    margin: 0 auto;
  }

  &__eyebrow {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: $amber;
    margin-bottom: 0.75rem;
    padding: 0.25rem 0.65rem;
    border: 1px solid $amber-25;
    border-radius: 999px;
    background: $amber-08;
  }

  &__title {
    font-size: clamp(1.75rem, 4vw, 2.75rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    color: $hero-text;
    margin: 0 0 0.4rem;
  }

  &__sub {
    font-size: 0.975rem;
    color: $hero-muted;
    margin-bottom: 2.5rem;
    padding-bottom: 0;
  }
}

// ─── Stats Grid ───────────────────────────────────────────────────
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;

  @media (max-width: 900px) { grid-template-columns: repeat(2, 1fr); }
  @media (max-width: 480px) { grid-template-columns: 1fr; }

  &--loading { opacity: 0.5; }
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  background: rgba(238, 232, 223, 0.04);
  border: 1px solid $hero-divider;
  border-radius: 12px;
  transition: border-color 0.2s, background 0.2s;

  &:hover {
    background: rgba(238, 232, 223, 0.07);
    border-color: $hero-divider-20;
  }

  &--warn {
    border-color: rgba(239, 68, 68, 0.25);
    background: rgba(239, 68, 68, 0.05);

    .stat-card__icon { background: rgba(239, 68, 68, 0.12); color: #f87171; }
    .stat-card__value { color: #f87171; }
  }

  &--skeleton {
    min-height: 78px;
    background: rgba(238, 232, 223, 0.04);
    border-color: $hero-divider;
    animation: pulse 1.5s ease-in-out infinite;
  }

  &__icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: $amber-14;
    color: $amber;
    display: flex;
    align-items: center;
    justify-content: center;

    .icon { width: 20px; height: 20px; }
  }

  &__body {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
  }

  &__value {
    font-size: 1.6rem;
    font-weight: 800;
    letter-spacing: -0.04em;
    color: $hero-text;
    line-height: 1;
  }

  &__label {
    font-size: 0.775rem;
    color: $hero-muted;
    font-weight: 500;
  }
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

// ─── Content Area ─────────────────────────────────────────────────
.dashboard-content {
  flex: 1;
  padding: 2.5rem 1.5rem 4rem;

  &__inner {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }
}

.dashboard-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 200px;
}

// ─── Sections ─────────────────────────────────────────────────────
.dash-section {
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 14px;
  overflow: hidden;

  &__header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--divider);
  }

  &__title {
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--primary-text);
    margin: 0;
  }

  &__count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    font-size: 0.75rem;
    font-weight: 700;
    color: $amber;
    background: $amber-08;
    border: 1px solid $amber-25;
    border-radius: 999px;
  }
}

// ─── Empty States ─────────────────────────────────────────────────
.dash-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 3rem 1.5rem;
  color: var(--secondary-text);

  &__icon {
    width: 2rem;
    height: 2rem;
    opacity: 0.35;
  }

  &__text {
    font-size: 0.9rem;
    padding-bottom: 0;
  }
}

// ─── Tables ───────────────────────────────────────────────────────
.table-wrap {
  overflow-x: auto;
}

.dash-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;

  th {
    padding: 0.7rem 1.5rem;
    text-align: left;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--secondary-text);
    background: var(--background);
    border-bottom: 1px solid var(--divider);
    white-space: nowrap;
  }

  td {
    padding: 0.9rem 1.5rem;
    color: var(--primary-text);
    border-bottom: 1px solid var(--divider);
    vertical-align: middle;
  }

  tbody tr:last-child td {
    border-bottom: none;
  }

  tbody tr {
    transition: background 0.15s;
    &:hover { background: var(--background); }
  }

  &__link {
    color: var(--primary-text);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
    &:hover { color: var(--accent-color); }
  }
}

// ─── Status Badges ────────────────────────────────────────────────
.status-badge {
  display: inline-block;
  padding: 0.2rem 0.6rem;
  font-size: 0.72rem;
  font-weight: 600;
  border-radius: 999px;
  white-space: nowrap;

  &--active {
    background: rgba(34, 197, 94, 0.12);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.25);
  }

  &--pending {
    background: $amber-08;
    color: $amber;
    border: 1px solid $amber-25;
  }

  &--danger {
    background: rgba(239, 68, 68, 0.10);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.25);
  }
}

.queue-badge {
  display: inline-block;
  padding: 0.2rem 0.6rem;
  font-size: 0.8rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  color: var(--secondary-text);
  background: var(--background);
  border: 1px solid var(--divider);
  border-radius: 6px;
}

.text-warn {
  color: #f87171;
  font-weight: 600;
}

// ─── Action Buttons ───────────────────────────────────────────────
.action-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.75rem;
  font-size: 0.8rem;
  font-weight: 600;
  font-family: inherit;
  color: var(--primary-text);
  background: var(--background);
  border: 1px solid var(--divider);
  border-radius: 7px;
  cursor: pointer;
  transition: border-color 0.2s, color 0.2s;
  white-space: nowrap;

  .icon { width: 14px; height: 14px; }

  &:hover {
    border-color: var(--accent-color);
    color: var(--accent-text);
  }

  &--danger {
    color: #f87171;
    border-color: rgba(239, 68, 68, 0.25);
    background: rgba(239, 68, 68, 0.05);

    &:hover {
      border-color: rgba(239, 68, 68, 0.5);
      color: #fca5a5;
    }
  }

  &--ghost {
    background: transparent;
    border-color: transparent;
    color: var(--secondary-text);

    &:hover {
      border-color: var(--divider);
      color: var(--primary-text);
      background: var(--secondary-background);
    }
  }
}

// ─── Footer Actions ───────────────────────────────────────────────
.dash-footer-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding-top: 0.5rem;
}

.dash-link {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--secondary-text);
  text-decoration: none;
  transition: color 0.2s;

  .icon { width: 16px; height: 16px; }

  &:hover { color: var(--primary-text); }
}

// ─── Modal ────────────────────────────────────────────────────────
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 200;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.modal {
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 16px;
  padding: 1.75rem;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);

  &__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 0.25rem;
  }

  &__title {
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--primary-text);
  }

  &__close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: transparent;
    border: none;
    border-radius: 6px;
    color: var(--secondary-text);
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
    flex-shrink: 0;

    .icon { width: 18px; height: 18px; }

    &:hover {
      background: var(--background);
      color: var(--primary-text);
    }
  }

  &__game {
    font-size: 0.9rem;
    color: var(--secondary-text);
    margin-bottom: 1.25rem;
    padding-bottom: 0;
  }

  &__label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--secondary-text);
    margin-bottom: 0.4rem;
    letter-spacing: 0.03em;
  }

  &__select {
    display: block;
    width: 100%;
    height: 40px;
    padding: 0 0.75rem;
    border: 1px solid var(--divider);
    border-radius: 8px;
    background: var(--background);
    color: var(--primary-text);
    font-size: 0.875rem;
    font-family: inherit;
    cursor: pointer;
    margin-bottom: 1.5rem;
    transition: border-color 0.2s;

    &:focus {
      outline: none;
      border-color: var(--accent-color);
    }
  }

  &__actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
  }
}

// ─── Modal Transition ─────────────────────────────────────────────
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;

  .modal {
    transition: opacity 0.2s ease, transform 0.2s ease;
  }
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;

  .modal {
    opacity: 0;
    transform: translateY(8px) scale(0.98);
  }
}

// ─── Footer ───────────────────────────────────────────────────────
.l-footer {
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
