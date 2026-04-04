<template>
  <div class="admin-page">
    <AppNav />

    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" /><div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <AdminBreadcrumb label="Verlängerungen" />
        <h1 class="page-hero__title">Verlängerungsanträge</h1>
      </div>
    </section>

    <div class="admin-content">
      <div class="admin-content__inner">

        <div v-if="loading" class="admin-state"><div class="spinner" /></div>

        <section v-else class="dash-section">
          <header class="dash-section__header">
            <h2 class="dash-section__title">Offene Anträge</h2>
            <span class="dash-section__count">{{ extensions.length }}</span>
          </header>

          <div v-if="!extensions.length" class="dash-empty">
            <span class="icon icon-calendar-outline dash-empty__icon" aria-hidden="true" />
            <p class="dash-empty__text">Keine offenen Verlängerungsanträge.</p>
          </div>

          <div v-else class="table-wrap">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>Mitglied</th>
                  <th>Spiel</th>
                  <th>Aktuell fällig</th>
                  <th>Beantragt bis</th>
                  <th>Beantragt am</th>
                  <th>Aktionen</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="ext in extensions" :key="ext.id">
                  <td class="dash-table__primary">{{ ext.loan?.user?.name ?? '—' }}</td>
                  <td>{{ ext.loan?.copy?.game?.title ?? '—' }}</td>
                  <td class="text-muted">{{ formatDate(ext.loan?.due_date) }}</td>
                  <td>{{ formatDate(ext.requested_due_date) }}</td>
                  <td class="text-muted">{{ formatDate(ext.requested_at) }}</td>
                  <td>
                    <div class="action-row">
                      <button class="action-btn action-btn--success" @click="approve(ext.id)">Genehmigen</button>
                      <button class="action-btn action-btn--danger" @click="openReject(ext)">Ablehnen</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

      </div>
    </div>

    <!-- ── Ablehnungs-Modal ──────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="rejectTarget" class="modal-overlay" @click.self="rejectTarget = null">
        <div class="dialog">
          <div class="dialog__header">
            <h3 class="dialog__title">Antrag ablehnen</h3>
            <button class="dialog__close" aria-label="Schließen" @click="rejectTarget = null">
              <span class="icon icon-close-outline" aria-hidden="true" />
            </button>
          </div>
          <p class="dialog__game">{{ rejectTarget.loan?.copy?.game?.title }}</p>
          <UiInput v-model="rejectNote" label="Begründung (optional)" />
          <div class="dialog__actions">
            <button class="action-btn action-btn--danger" :disabled="processing" @click="submitReject">Ablehnen</button>
            <button class="action-btn" @click="rejectTarget = null">Abbrechen</button>
          </div>
        </div>
      </div>
    </Transition>

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

const { fetchExtensions, approveExtension, rejectExtension } = useAdmin()

interface Extension {
  id: number; requested_due_date: string; requested_at: string
  loan: { due_date: string; user: { name: string } | null; copy: { game: { title: string } | null } | null } | null
}

const year = new Date().getFullYear()
const loading = ref(true); const processing = ref(false)
const extensions = ref<Extension[]>([])
const rejectTarget = ref<Extension | null>(null)
const rejectNote = ref('')

onMounted(load)

async function load() { loading.value = true; try { const data = await fetchExtensions('PENDING'); extensions.value = data.data as Extension[] } finally { loading.value = false } }
async function approve(id: number) { await approveExtension(id); await load() }
function openReject(ext: Extension) { rejectTarget.value = ext; rejectNote.value = '' }
async function submitReject() {
  if (!rejectTarget.value) return
  processing.value = true
  try { await rejectExtension(rejectTarget.value.id, rejectNote.value || undefined); rejectTarget.value = null; await load() }
  finally { processing.value = false }
}

function formatDate(iso?: string) { return iso ? new Date(iso).toLocaleDateString('de-DE') : '—' }
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

.action-row { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.action-btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 600; font-family: inherit; color: var(--primary-text); background: var(--background); border: 1px solid var(--divider); border-radius: 7px; cursor: pointer; transition: border-color 0.2s, color 0.2s; white-space: nowrap; &:hover { border-color: var(--accent-color); color: var(--accent-text); } &--success { color: #4ade80; border-color: rgba(34,197,94,0.25); background: rgba(34,197,94,0.06); &:hover { border-color: rgba(34,197,94,0.5); } } &--danger { color: #f87171; border-color: rgba(239,68,68,0.25); background: rgba(239,68,68,0.05); &:hover { border-color: rgba(239,68,68,0.5); } } &:disabled { opacity: 0.4; cursor: not-allowed; } }

.text-muted { color: var(--secondary-text); }

.modal-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
.dialog { background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 16px; padding: 1.75rem; width: 100%; max-width: 420px; box-shadow: 0 25px 60px rgba(0,0,0,0.4); &__header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 0.25rem; } &__title { font-size: 1.05rem; font-weight: 700; letter-spacing: -0.02em; color: var(--primary-text); } &__close { display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: transparent; border: none; border-radius: 6px; color: var(--secondary-text); cursor: pointer; transition: background 0.15s; .icon { width: 18px; height: 18px; } &:hover { background: var(--background); color: var(--primary-text); } } &__game { font-size: 0.9rem; color: var(--secondary-text); margin-bottom: 1.25rem; padding-bottom: 0; } &__actions { display: flex; gap: 0.75rem; margin-top: 1.5rem; } }
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; .dialog { transition: opacity 0.2s ease, transform 0.2s ease; } }
.modal-enter-from, .modal-leave-to { opacity: 0; .dialog { opacity: 0; transform: translateY(8px) scale(0.98); } }

.l-footer { background: $hero-bg; border-top: 1px solid $hero-divider; padding: 1.75rem 1.5rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; } &__brand { display: flex; align-items: center; gap: 0.4rem; } &__hex { font-size: 1.1rem; color: $amber; } &__name { font-size: 0.9rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em; } &__copy { font-size: 0.8rem; color: $hero-muted-50; padding-bottom: 0; } }
</style>
