<template>
  <div class="admin-page">
    <AppNav />

    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" /><div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <AdminBreadcrumb :label="$t('admin.breadcrumb.package_loans')" />
        <h1 class="page-hero__title">{{ $t('admin.package_loans.title') }}</h1>
      </div>
    </section>

    <div class="admin-content">
      <div class="admin-content__inner">

        <!-- Status Tabs -->
        <div class="status-tabs">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            class="status-tabs__btn"
            :class="{ 'status-tabs__btn--active': activeTab === tab.value }"
            @click="setTab(tab.value)"
          >
            {{ tab.label }}
          </button>
        </div>

        <div v-if="loading" class="admin-state"><div class="spinner" /></div>

        <section v-else class="dash-section">
          <header class="dash-section__header">
            <h2 class="dash-section__title">{{ activeTabLabel }}</h2>
            <span class="dash-section__count">{{ meta?.total ?? loans.length }}</span>
          </header>

          <div v-if="!loans.length" class="dash-empty">
            <span class="icon icon-cube-outline dash-empty__icon" aria-hidden="true" />
            <p class="dash-empty__text">{{ $t('admin.package_loans.empty') }}</p>
          </div>

          <div v-else class="table-wrap">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>{{ $t('admin.loans.member_col') }}</th>
                  <th>{{ $t('admin.package_loans.package_col') }}</th>
                  <th>{{ $t('admin.package_loans.loaned_at') }}</th>
                  <th>{{ $t('admin.package_loans.due_at') }}</th>
                  <th>{{ $t('admin.table.status') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="loan in loans" :key="loan.id">
                  <td class="dash-table__primary">{{ loan.user?.name ?? '—' }}</td>
                  <td>{{ loan.package?.name ?? '—' }}</td>
                  <td class="text-muted">{{ formatDate(loan.start_date) }}</td>
                  <td class="text-muted" :class="{ 'text-warn': loan.is_overdue }">{{ formatDate(loan.due_date) }}</td>
                  <td>
                    <span class="badge" :class="statusClass(loan.status)">{{ statusLabel(loan.status) }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="meta && meta.last_page > 1" class="pagination">
            <button class="pagination__btn" :disabled="page <= 1" @click="changePage(page - 1)">←</button>
            <span class="pagination__info">{{ page }} / {{ meta.last_page }}</span>
            <button class="pagination__btn" :disabled="page >= meta.last_page" @click="changePage(page + 1)">→</button>
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

const api = useApi()
const { t } = useI18n()

interface PackageLoan {
  id: number
  package: { id: number; name: string; slug: string } | null
  user: { id: number; name: string; email: string } | null
  start_date: string
  due_date: string
  returned_at: string | null
  status: string
  is_overdue: boolean
}

interface Meta { total: number; last_page: number; current_page: number }

const year = new Date().getFullYear()
const loading = ref(true)
const loans = ref<PackageLoan[]>([])
const meta = ref<Meta | null>(null)
const page = ref(1)
const activeTab = ref('')

const tabs = computed(() => [
  { label: t('admin.package_loans.tab_all'), value: '' },
  { label: t('admin.package_loans.tab_active'), value: 'ACTIVE' },
  { label: t('admin.package_loans.tab_overdue'), value: 'OVERDUE' },
  { label: t('admin.package_loans.tab_returned'), value: 'RETURNED' },
])

const activeTabLabel = computed(() => tabs.value.find(tab => tab.value === activeTab.value)?.label ?? t('admin.package_loans.tab_all'))

async function load() {
  loading.value = true
  try {
    const data = await api.get<{ data: PackageLoan[]; meta: Meta }>('/admin/package-loans', {
      params: { page: page.value, status: activeTab.value || undefined },
    })
    loans.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
  }
}

function setTab(value: string) {
  activeTab.value = value
  page.value = 1
  load()
}

function changePage(p: number) {
  page.value = p
  load()
}

onMounted(load)

function formatDate(iso: string | null) { if (!iso) return '—'; return new Date(iso).toLocaleDateString('de-DE', { dateStyle: 'medium' }) }
function statusLabel(s: string) { const m: Record<string, string> = { ACTIVE: t('admin.package_loans.tab_active'), RETURNED: t('admin.package_loans.tab_returned'), OVERDUE: t('admin.package_loans.tab_overdue') }; return m[s] ?? s }
function statusClass(s: string) { const m: Record<string, string> = { ACTIVE: 'badge-success', RETURNED: '', OVERDUE: 'badge-error' }; return m[s] ?? '' }
</script>

<style lang="scss" scoped>
$hero-bg: #0F0E0C; $nav-height: 64px;
$amber-08: rgba(212,146,30,0.08); $amber-14: rgba(212,146,30,0.14); $amber-25: rgba(212,146,30,0.25); $amber-glow: rgba(212,146,30,0.16);
$hero-text: #EEE8DF; $hero-muted: rgba(238,232,223,0.72); $hero-muted-50: rgba(238,232,223,0.65); $hero-divider: rgba(238,232,223,0.10);

.admin-page { min-height: 100vh; display: flex; flex-direction: column; background: var(--background); }

.page-hero { position: relative; background: $hero-bg; padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem; overflow: hidden; &__backdrop { position: absolute; inset: 0; pointer-events: none; } &__glow { position: absolute; width: 400px; height: 400px; top: -120px; right: -60px; border-radius: 50%; filter: blur(90px); background: $amber-glow; } &__dots { position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 24px 24px; mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%); } &__body { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; } &__title { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0; } }

.admin-content { flex: 1; padding: 2rem 1.5rem 4rem; &__inner { max-width: 1100px; margin: 0 auto; } }
.admin-state { display: flex; justify-content: center; align-items: center; min-height: 200px; }

.status-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.25rem; flex-wrap: wrap;
  &__btn { padding: 0.4rem 0.9rem; border-radius: 8px; border: 1px solid var(--divider); background: none; color: var(--secondary-text); font-size: 0.85rem; font-weight: 500; cursor: pointer; transition: border-color 0.15s, color 0.15s;
    &:hover { color: var(--primary-text); }
    &--active { border-color: $amber; color: $amber; background: $amber-08; }
  }
}

.dash-section { background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 14px; overflow: hidden; }
.dash-section__header { display: flex; align-items: center; gap: 0.75rem; padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--divider); }
.dash-section__title { font-size: 0.95rem; font-weight: 700; color: var(--primary-text); margin: 0; letter-spacing: -0.02em; }
.dash-section__count { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 22px; padding: 0 6px; font-size: 0.75rem; font-weight: 700; color: $amber; background: $amber-08; border: 1px solid $amber-25; border-radius: 999px; }

.dash-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; padding: 3rem 1.5rem; color: var(--secondary-text); &__icon { width: 2rem; height: 2rem; opacity: 0.35; } &__text { font-size: 0.9rem; padding-bottom: 0; } }

.table-wrap { overflow-x: auto; }
.dash-table { width: 100%; border-collapse: collapse; font-size: 0.875rem;
  th { padding: 0.65rem 1rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: var(--secondary-text); border-bottom: 1px solid var(--divider); white-space: nowrap; }
  td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--divider); vertical-align: middle; }
  tbody tr:last-child td { border-bottom: none; }
  &__primary { font-weight: 600; color: var(--primary-text); }
}
.text-muted { color: var(--secondary-text); }
.text-warn { color: #e8a838; }


.pagination { display: flex; align-items: center; justify-content: center; gap: 1rem; padding: 1rem; &__btn { background: none; border: 1px solid var(--divider); color: var(--primary-text); padding: 0.35rem 0.75rem; border-radius: 6px; cursor: pointer; &:disabled { opacity: 0.35; cursor: default; } } &__info { font-size: 0.85rem; color: var(--secondary-text); } }

.l-footer { background: $hero-bg; border-top: 1px solid $hero-divider; padding: 2rem 1.5rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; } &__brand { display: flex; align-items: center; gap: 0.4rem; } &__hex { color: $amber; } &__name { font-size: 0.9rem; font-weight: 700; color: $hero-text; } &__copy { font-size: 0.8rem; color: $hero-muted-50; padding-bottom: 0; } }
</style>
