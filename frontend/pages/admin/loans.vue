<template>
  <div class="admin-page">
    <AppNav />

    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" /><div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <AdminBreadcrumb :label="$t('admin.breadcrumb.loans')" />
        <h1 class="page-hero__title">{{ $t('admin.loans.title') }}</h1>
      </div>
    </section>

    <div class="admin-content">
      <div class="admin-content__inner">

        <!-- Filter Bar ─────────────────────────────────────────────── -->
        <div class="filter-bar">
          <label class="filter-label">{{ $t('admin.loans.filter_label') }}</label>
          <div class="filter-tabs">
            <button
              v-for="opt in statusOptions" :key="opt.value"
              class="filter-tab"
              :class="{ 'filter-tab--active': statusFilter === opt.value }"
              @click="statusFilter = opt.value; load()"
            >
              {{ opt.label }}
            </button>
          </div>
        </div>

        <div v-if="loading" class="admin-state"><div class="spinner" /></div>

        <section v-else class="dash-section">
          <header class="dash-section__header">
            <h2 class="dash-section__title">{{ $t('admin.loans.title') }}</h2>
            <span class="dash-section__count">{{ loans.length }}</span>
          </header>

          <div v-if="!loans.length" class="dash-empty">
            <span class="icon icon-sync dash-empty__icon" aria-hidden="true" />
            <p class="dash-empty__text">{{ $t('admin.loans.empty') }}</p>
          </div>

          <div v-else class="table-wrap">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>{{ $t('admin.loans.member_col') }}</th>
                  <th>{{ $t('admin.table.title') }}</th>
                  <th>{{ $t('admin.loans.loaned_at') }}</th>
                  <th>{{ $t('admin.loans.due_at') }}</th>
                  <th>{{ $t('admin.table.status') }}</th>
                  <th>{{ $t('admin.table.actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="loan in loans" :key="loan.id">
                  <td class="dash-table__primary">{{ loan.user?.name ?? '—' }}</td>
                  <td>{{ loan.game?.title ?? '—' }}</td>
                  <td class="text-muted">{{ formatDate(loan.start_date) }}</td>
                  <td>
                    <span :class="{ 'text-danger': loan.status === 'OVERDUE' }">{{ formatDate(loan.due_date) }}</span>
                  </td>
                  <td>
                    <span class="badge" :class="statusClass(loan.status)">{{ statusLabel(loan.status) }}</span>
                  </td>
                  <td>
                    <button
                      v-if="['ACTIVE', 'EXTENDED'].includes(loan.status)"
                      class="action-btn action-btn--danger"
                      @click="setOverdue(loan.id)"
                    >
                      {{ $t('admin.loans.mark_overdue') }}
                    </button>
                    <span v-else class="text-muted text-sm">—</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

      </div>
    </div>

    <footer class="l-footer">
      <div class="l-footer__inner">
        <div class="l-footer__brand"><span class="l-footer__hex" aria-hidden="true">⬡</span><span class="l-footer__name">AUA</span></div>
        <p class="l-footer__copy">{{ $t('common.copyright_short', { year }) }}</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

definePageMeta({ middleware: ['auth', 'admin'] })

const { fetchAdminLoans, markOverdue } = useAdmin()
const { t } = useI18n()

const year = new Date().getFullYear()
const loading = ref(true)
const statusFilter = ref('')
const loans = ref<{ id: number; user: { name: string } | null; game: { title: string } | null; start_date: string; due_date: string; status: string }[]>([])

const statusOptions = computed(() => [
  { value: '', label: t('admin.loans.filter_all') },
  { value: 'ACTIVE', label: t('admin.loans.filter_active') },
  { value: 'EXTENDED', label: t('admin.loans.filter_extended') },
  { value: 'OVERDUE', label: t('admin.loans.filter_overdue') },
  { value: 'RETURNED', label: t('admin.loans.filter_returned') },
])

onMounted(load)

async function load() {
  loading.value = true
  try { const data = await fetchAdminLoans(statusFilter.value ? { status: statusFilter.value } : undefined); loans.value = data.data as typeof loans.value }
  finally { loading.value = false }
}

async function setOverdue(id: number) { await markOverdue(id); await load() }

function statusLabel(s: string) { const m: Record<string, string> = { ACTIVE: t('admin.loans.filter_active'), EXTENDED: t('admin.loans.filter_extended'), OVERDUE: t('admin.loans.filter_overdue'), RETURNED: t('admin.loans.filter_returned') }; return m[s] ?? s }
function statusClass(s: string) { const m: Record<string, string> = { ACTIVE: 'badge-success', EXTENDED: 'badge-warning', OVERDUE: 'badge-error', RETURNED: '' }; return m[s] ?? '' }
function formatDate(iso: string) { return new Date(iso).toLocaleDateString('de-DE') }
</script>

<style lang="scss" scoped>
$hero-bg: #0F0E0C; $nav-height: 64px;
$amber-08: rgba(212,146,30,0.08); $amber-14: rgba(212,146,30,0.14); $amber-25: rgba(212,146,30,0.25); $amber-glow: rgba(212,146,30,0.16);
$hero-text: #EEE8DF; $hero-muted: rgba(238,232,223,0.72); $hero-muted-50: rgba(238,232,223,0.65); $hero-divider: rgba(238,232,223,0.10);

.admin-page { min-height: 100vh; display: flex; flex-direction: column; background: var(--background); }

.page-hero { position: relative; background: $hero-bg; padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem; overflow: hidden; &__backdrop { position: absolute; inset: 0; pointer-events: none; } &__glow { position: absolute; width: 400px; height: 400px; top: -120px; right: -60px; border-radius: 50%; filter: blur(90px); background: $amber-glow; } &__dots { position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 24px 24px; mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%); } &__body { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; } &__title { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0; } }

.admin-content { flex: 1; padding: 2rem 1.5rem 4rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.25rem; } }
.admin-state { display: flex; justify-content: center; align-items: center; min-height: 200px; }

.filter-bar { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
.filter-label { font-size: 0.8rem; font-weight: 600; color: var(--secondary-text); }
.filter-tabs { display: flex; gap: 0.35rem; flex-wrap: wrap; }
.filter-tab { padding: 0.3rem 0.75rem; font-size: 0.8rem; font-weight: 600; font-family: inherit; color: var(--secondary-text); background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 999px; cursor: pointer; transition: border-color 0.2s, color 0.2s, background 0.2s; &:hover { border-color: var(--accent-color); color: var(--primary-text); } &--active { background: $amber-08; border-color: $amber-25; color: $amber; } }

.dash-section { background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 14px; overflow: hidden; }
.dash-section__header { display: flex; align-items: center; gap: 0.75rem; padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--divider); }
.dash-section__title { font-size: 0.95rem; font-weight: 700; color: var(--primary-text); margin: 0; letter-spacing: -0.02em; }
.dash-section__count { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 22px; padding: 0 6px; font-size: 0.75rem; font-weight: 700; color: $amber; background: $amber-08; border: 1px solid $amber-25; border-radius: 999px; }

.dash-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; padding: 3rem 1.5rem; color: var(--secondary-text); &__icon { width: 2rem; height: 2rem; opacity: 0.35; } &__text { font-size: 0.9rem; padding-bottom: 0; } }

.table-wrap { overflow-x: auto; }
.dash-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; th { padding: 0.7rem 1.5rem; text-align: left; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--secondary-text); background: var(--background); border-bottom: 1px solid var(--divider); white-space: nowrap; } td { padding: 0.9rem 1.5rem; color: var(--primary-text); border-bottom: 1px solid var(--divider); vertical-align: middle; } tbody tr:last-child td { border-bottom: none; } tbody tr { transition: background 0.15s; &:hover { background: var(--background); } } &__primary { font-weight: 600; } }


.action-row { display: flex; align-items: center; gap: 0.5rem; }
.action-btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 600; font-family: inherit; color: var(--primary-text); background: var(--background); border: 1px solid var(--divider); border-radius: 7px; cursor: pointer; transition: border-color 0.2s, color 0.2s; white-space: nowrap; &:hover { border-color: var(--accent-color); color: var(--accent-text); } &--danger { color: #f87171; border-color: rgba(239,68,68,0.25); background: rgba(239,68,68,0.05); &:hover { border-color: rgba(239,68,68,0.5); } } }

.text-muted { color: var(--secondary-text); } .text-sm { font-size: 0.8rem; } .text-danger { color: #f87171; font-weight: 600; }

.l-footer { background: $hero-bg; border-top: 1px solid $hero-divider; padding: 1.75rem 1.5rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; } &__brand { display: flex; align-items: center; gap: 0.4rem; } &__hex { font-size: 1.1rem; color: $amber; } &__name { font-size: 0.9rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em; } &__copy { font-size: 0.8rem; color: $hero-muted-50; padding-bottom: 0; } }
</style>
