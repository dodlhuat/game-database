<template>
  <div class="admin-page">
    <AppNav />

    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" /><div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <AdminBreadcrumb label="E-Mail-Protokoll" />
        <h1 class="page-hero__title">E-Mail-Protokoll</h1>
      </div>
    </section>

    <div class="admin-content">
      <div class="admin-content__inner">

        <!-- Filters -->
        <div class="filter-row">
          <select v-model="filterTemplate" class="form-input filter-row__select" @change="load">
            <option value="">Alle Vorlagen</option>
            <option v-for="key in templateKeys" :key="key" :value="key">{{ key }}</option>
          </select>
          <input v-model="filterDateFrom" type="date" class="form-input filter-row__date" @change="load" />
          <input v-model="filterDateTo" type="date" class="form-input filter-row__date" @change="load" />
        </div>

        <div v-if="loading" class="admin-state"><div class="spinner" /></div>

        <section v-else class="dash-section">
          <header class="dash-section__header">
            <h2 class="dash-section__title">Gesendete E-Mails</h2>
            <span class="dash-section__count">{{ meta?.total ?? logs.length }}</span>
          </header>

          <div v-if="!logs.length" class="dash-empty">
            <span class="icon icon-email-outline dash-empty__icon" aria-hidden="true" />
            <p class="dash-empty__text">Keine E-Mails gefunden.</p>
          </div>

          <div v-else class="table-wrap">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>Empfänger</th>
                  <th>User</th>
                  <th>Vorlage</th>
                  <th>Betreff</th>
                  <th>Gesendet am</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="log in logs" :key="log.id">
                  <td class="dash-table__primary">{{ log.recipient_email }}</td>
                  <td class="text-muted">{{ log.user?.name ?? '—' }}</td>
                  <td><span class="template-tag">{{ log.template_key }}</span></td>
                  <td class="text-muted">{{ log.subject }}</td>
                  <td class="text-muted">{{ formatDate(log.sent_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
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
        <p class="l-footer__copy">&copy; {{ year }} AUA</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

definePageMeta({ middleware: ['auth', 'admin'] })

const api = useApi()

interface EmailLog {
  id: number
  recipient_email: string
  template_key: string
  subject: string
  sent_at: string
  user: { id: number; name: string } | null
}

interface Meta { total: number; last_page: number; current_page: number }

const year = new Date().getFullYear()
const loading = ref(true)
const logs = ref<EmailLog[]>([])
const meta = ref<Meta | null>(null)
const page = ref(1)
const filterTemplate = ref('')
const filterDateFrom = ref('')
const filterDateTo = ref('')

const templateKeys = [
  'email_verification', 'welcome_member', 'membership_renewal_reminder',
  'user_approved', 'user_rejected', 'new_user_registered',
  'loan_due_soon', 'reservation_available',
]

async function load() {
  loading.value = true
  try {
    const data = await api.get<{ data: EmailLog[]; meta: Meta }>('/admin/email-logs', {
      params: {
        page: page.value,
        template_key: filterTemplate.value || undefined,
        date_from: filterDateFrom.value || undefined,
        date_to: filterDateTo.value || undefined,
      },
    })
    logs.value = data.data
    meta.value = data.meta
  } finally {
    loading.value = false
  }
}

function changePage(p: number) {
  page.value = p
  load()
}

onMounted(load)

function formatDate(iso: string) { return new Date(iso).toLocaleString('de-DE', { dateStyle: 'short', timeStyle: 'short' }) }
</script>

<style lang="scss" scoped>
$hero-bg: #0F0E0C; $amber: #D4921E; $nav-height: 64px;
$amber-08: rgba(212,146,30,0.08); $amber-14: rgba(212,146,30,0.14); $amber-25: rgba(212,146,30,0.25); $amber-glow: rgba(212,146,30,0.16);
$hero-text: #EEE8DF; $hero-muted: rgba(238,232,223,0.55); $hero-muted-50: rgba(238,232,223,0.50); $hero-divider: rgba(238,232,223,0.10);

.admin-page { min-height: 100vh; display: flex; flex-direction: column; background: var(--background); }

.page-hero { position: relative; background: $hero-bg; padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem; overflow: hidden; &__backdrop { position: absolute; inset: 0; pointer-events: none; } &__glow { position: absolute; width: 400px; height: 400px; top: -120px; right: -60px; border-radius: 50%; filter: blur(90px); background: $amber-glow; } &__dots { position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 24px 24px; mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%); } &__body { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; } &__title { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0; } }

.admin-content { flex: 1; padding: 2rem 1.5rem 4rem; &__inner { max-width: 1100px; margin: 0 auto; } }
.admin-state { display: flex; justify-content: center; align-items: center; min-height: 200px; }

.filter-row { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1.25rem; &__select { min-width: 180px; } &__date { width: 145px; } }

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

.template-tag { font-size: 0.72rem; font-weight: 600; background: $amber-08; border: 1px solid $amber-25; color: $amber; padding: 0.15rem 0.5rem; border-radius: 999px; white-space: nowrap; }

.pagination { display: flex; align-items: center; justify-content: center; gap: 1rem; padding: 1rem; &__btn { background: none; border: 1px solid var(--divider); color: var(--primary-text); padding: 0.35rem 0.75rem; border-radius: 6px; cursor: pointer; &:disabled { opacity: 0.35; cursor: default; } } &__info { font-size: 0.85rem; color: var(--secondary-text); } }

.l-footer { background: $hero-bg; border-top: 1px solid $hero-divider; padding: 2rem 1.5rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; } &__brand { display: flex; align-items: center; gap: 0.4rem; } &__hex { color: $amber; } &__name { font-size: 0.9rem; font-weight: 700; color: $hero-text; } &__copy { font-size: 0.8rem; color: $hero-muted-50; padding-bottom: 0; } }
</style>
