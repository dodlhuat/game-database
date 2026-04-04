<template>
  <div class="admin-page">
    <AppNav />

    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" /><div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <AdminBreadcrumb label="Schadensmeldungen" />
        <h1 class="page-hero__title">Schadensmeldungen</h1>
      </div>
    </section>

    <div class="admin-content">
      <div class="admin-content__inner">

        <div v-if="loading" class="admin-state"><div class="spinner" /></div>

        <section v-else class="dash-section">
          <header class="dash-section__header">
            <h2 class="dash-section__title">Alle Meldungen</h2>
            <span class="dash-section__count">{{ reports.length }}</span>
          </header>

          <div v-if="!reports.length" class="dash-empty">
            <span class="icon icon-alert-triangle-outline dash-empty__icon" aria-hidden="true" />
            <p class="dash-empty__text">Keine Schadensmeldungen vorhanden.</p>
          </div>

          <div v-else class="table-wrap">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>Mitglied</th>
                  <th>Spiel</th>
                  <th>Beschreibung</th>
                  <th>Foto</th>
                  <th>Gemeldet am</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="report in reports" :key="report.id">
                  <td class="dash-table__primary">{{ report.user?.name ?? '—' }}</td>
                  <td>{{ report.loan?.copy?.game?.title ?? '—' }}</td>
                  <td class="cell-desc">{{ report.description }}</td>
                  <td>
                    <a v-if="report.photo_url" :href="report.photo_url" target="_blank" rel="noopener" class="photo-link">
                      <span class="icon icon-image-outline" aria-hidden="true" />
                      Ansehen
                    </a>
                    <span v-else class="text-muted">—</span>
                  </td>
                  <td class="text-muted">{{ formatDate(report.created_at) }}</td>
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
        <p class="l-footer__copy">&copy; {{ year }} AUA</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

definePageMeta({ middleware: ['auth', 'admin'] })

const { fetchDamageReports } = useAdmin()

interface DamageReport { id: number; description: string; photo_url: string | null; created_at: string; user: { name: string } | null; loan: { copy: { game: { title: string } | null } | null } | null }

const year = new Date().getFullYear()
const loading = ref(true)
const reports = ref<DamageReport[]>([])

onMounted(async () => { try { const data = await fetchDamageReports(); reports.value = data.data as DamageReport[] } finally { loading.value = false } })

function formatDate(iso: string) { return new Date(iso).toLocaleDateString('de-DE', { dateStyle: 'medium' }) }
</script>

<style lang="scss" scoped>
$hero-bg: #0F0E0C; $amber: #D4921E; $nav-height: 64px;
$amber-08: rgba(212,146,30,0.08); $amber-14: rgba(212,146,30,0.14); $amber-25: rgba(212,146,30,0.25); $amber-glow: rgba(212,146,30,0.16);
$hero-text: #EEE8DF; $hero-muted: rgba(238,232,223,0.55); $hero-muted-50: rgba(238,232,223,0.50); $hero-divider: rgba(238,232,223,0.10);

.admin-page { min-height: 100vh; display: flex; flex-direction: column; background: var(--background); }

.page-hero { position: relative; background: $hero-bg; padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem; overflow: hidden; &__backdrop { position: absolute; inset: 0; pointer-events: none; } &__glow { position: absolute; width: 400px; height: 400px; top: -120px; right: -60px; border-radius: 50%; filter: blur(90px); background: $amber-glow; } &__dots { position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 24px 24px; mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%); } &__body { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; } &__breadcrumb { display: flex; align-items: center; margin-bottom: 0.75rem; position: static; transform: none; width: auto; height: auto; } &__back { display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.78rem; font-weight: 500; color: $hero-muted; text-decoration: none; transition: color 0.2s; .icon { width: 13px; height: 13px; } &::after { content: "›"; margin: 0 0.35rem; opacity: 0.4; font-weight: 400; } &:hover { color: $hero-text; } } &__eyebrow { font-size: 0.78rem; font-weight: 600; color: $amber; letter-spacing: 0.02em; } &__title { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0; } }

.admin-content { flex: 1; padding: 2rem 1.5rem 4rem; &__inner { max-width: 1100px; margin: 0 auto; } }
.admin-state { display: flex; justify-content: center; align-items: center; min-height: 200px; }

.dash-section { background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 14px; overflow: hidden; }
.dash-section__header { display: flex; align-items: center; gap: 0.75rem; padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--divider); }
.dash-section__title { font-size: 0.95rem; font-weight: 700; color: var(--primary-text); margin: 0; letter-spacing: -0.02em; }
.dash-section__count { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 22px; padding: 0 6px; font-size: 0.75rem; font-weight: 700; color: $amber; background: $amber-08; border: 1px solid $amber-25; border-radius: 999px; }

.dash-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; padding: 3rem 1.5rem; color: var(--secondary-text); &__icon { width: 2rem; height: 2rem; opacity: 0.35; } &__text { font-size: 0.9rem; padding-bottom: 0; } }

.table-wrap { overflow-x: auto; }
.dash-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; th { padding: 0.7rem 1.5rem; text-align: left; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--secondary-text); background: var(--background); border-bottom: 1px solid var(--divider); white-space: nowrap; } td { padding: 0.9rem 1.5rem; color: var(--primary-text); border-bottom: 1px solid var(--divider); vertical-align: middle; } tbody tr:last-child td { border-bottom: none; } tbody tr { transition: background 0.15s; &:hover { background: var(--background); } } &__primary { font-weight: 600; } }

.cell-desc { max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.photo-link { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; font-weight: 600; color: var(--accent-color); text-decoration: none; transition: opacity 0.2s; .icon { width: 14px; height: 14px; } &:hover { opacity: 0.75; } }

.text-muted { color: var(--secondary-text); }

.l-footer { background: $hero-bg; border-top: 1px solid $hero-divider; padding: 1.75rem 1.5rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; } &__brand { display: flex; align-items: center; gap: 0.4rem; } &__hex { font-size: 1.1rem; color: $amber; } &__name { font-size: 0.9rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em; } &__copy { font-size: 0.8rem; color: $hero-muted-50; padding-bottom: 0; } }
</style>
