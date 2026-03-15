<template>
  <div class="admin-page">
    <AppNav />

    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" /><div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <NuxtLink to="/admin" class="page-hero__back">
          <span class="icon icon-arrow-back-outline" aria-hidden="true" /> Admin
        </NuxtLink>
        <p class="page-hero__eyebrow">Administration</p>
        <div class="page-hero__row">
          <h1 class="page-hero__title">Kopien verwalten</h1>
          <button class="hero-btn" @click="openCreate">
            <span class="icon icon-plus-outline" aria-hidden="true" /> Kopie hinzufügen
          </button>
        </div>
      </div>
    </section>

    <div class="admin-content">
      <div class="admin-content__inner">

        <div v-if="loading" class="admin-state"><div class="spinner" /></div>

        <section v-else class="dash-section">
          <header class="dash-section__header">
            <h2 class="dash-section__title">Alle Kopien</h2>
            <span class="dash-section__count">{{ copies.length }}</span>
          </header>

          <div v-if="!copies.length" class="dash-empty">
            <span class="icon icon-layers-outline dash-empty__icon" aria-hidden="true" />
            <p class="dash-empty__text">Noch keine Kopien vorhanden.</p>
          </div>

          <div v-else class="table-wrap">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>Spiel</th>
                  <th>Zustand</th>
                  <th>QR-Code</th>
                  <th>Verfügbar</th>
                  <th>Aktionen</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="copy in copies" :key="copy.id">
                  <td class="dash-table__primary">{{ copy.game?.title ?? '—' }}</td>
                  <td>
                    <span class="status-badge" :class="conditionClass(copy.condition)">{{ conditionLabel(copy.condition) }}</span>
                  </td>
                  <td class="text-mono">{{ copy.qr_code ?? '—' }}</td>
                  <td>
                    <span class="status-badge" :class="copy.is_available ? 'status-badge--active' : 'status-badge--danger'">
                      {{ copy.is_available ? 'Verfügbar' : 'Ausgeliehen' }}
                    </span>
                  </td>
                  <td>
                    <div class="action-row">
                      <button class="action-btn" @click="openEdit(copy)">Bearbeiten</button>
                      <button class="action-btn action-btn--danger" @click="remove(copy.id)">Löschen</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

      </div>
    </div>

    <!-- ── Modal ──────────────────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="form.open" class="modal-overlay" @click.self="form.open = false">
        <div class="modal">
          <div class="modal__header">
            <h3 class="modal__title">{{ form.id ? 'Kopie bearbeiten' : 'Kopie hinzufügen' }}</h3>
            <button class="modal__close" aria-label="Schließen" @click="form.open = false">
              <span class="icon icon-close-outline" aria-hidden="true" />
            </button>
          </div>

          <div class="modal__body">
            <div class="form-field">
              <label class="form-label">Spiel</label>
              <select v-model="form.game_id" class="form-select" required>
                <option :value="null">Bitte wählen</option>
                <option v-for="game in games" :key="game.id" :value="game.id">{{ game.title }}</option>
              </select>
            </div>
            <div class="form-field">
              <label class="form-label">Zustand</label>
              <select v-model="form.condition" class="form-select">
                <option value="GOOD">Gut</option>
                <option value="WORN">Abgenutzt</option>
                <option value="DAMAGED">Beschädigt</option>
                <option value="LOCKED">Gesperrt</option>
              </select>
            </div>
            <UiInput v-model="form.qr_code" label="QR-Code (optional)" />
            <UiInput v-model="form.notes" label="Notizen" />
            <div v-if="formError" class="form-error">{{ formError }}</div>
          </div>

          <div class="modal__actions">
            <UiButton :loading="saving" @click="save">Speichern</UiButton>
            <button class="action-btn" @click="form.open = false">Abbrechen</button>
          </div>
        </div>
      </div>
    </Transition>

    <footer class="l-footer">
      <div class="l-footer__inner">
        <div class="l-footer__brand"><span class="l-footer__hex" aria-hidden="true">⬡</span><span class="l-footer__name">Spielothek</span></div>
        <p class="l-footer__copy">&copy; {{ year }} Spielothek</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'

definePageMeta({ middleware: ['auth', 'admin'] })

const { fetchCopies, createCopy, updateCopy, deleteCopy, fetchAdminGames } = useAdmin()

interface Copy { id: number; game: { id: number; title: string } | null; condition: string; qr_code: string | null; notes: string | null; is_available: boolean }

const year = new Date().getFullYear()
const loading = ref(true); const saving = ref(false); const formError = ref('')
const copies = ref<Copy[]>([]); const games = ref<{ id: number; title: string }[]>([])

const form = reactive({ open: false, id: null as number | null, game_id: null as number | null, condition: 'GOOD', qr_code: '', notes: '' })

onMounted(async () => { await load(); const g = await fetchAdminGames(); games.value = g.data as { id: number; title: string }[] })

async function load() { loading.value = true; try { const data = await fetchCopies(); copies.value = data.data as Copy[] } finally { loading.value = false } }

function openCreate() { Object.assign(form, { open: true, id: null, game_id: null, condition: 'GOOD', qr_code: '', notes: '' }); formError.value = '' }
function openEdit(copy: Copy) { Object.assign(form, { open: true, id: copy.id, game_id: copy.game?.id ?? null, condition: copy.condition, qr_code: copy.qr_code ?? '', notes: copy.notes ?? '' }); formError.value = '' }

async function save() {
  saving.value = true; formError.value = ''
  try { const payload = { game_id: form.game_id, condition: form.condition, qr_code: form.qr_code || undefined, notes: form.notes || undefined }; form.id ? await updateCopy(form.id, payload) : await createCopy(payload); await load(); form.open = false }
  catch (err: unknown) { formError.value = (err as { message?: string }).message ?? 'Fehler.' }
  finally { saving.value = false }
}

async function remove(id: number) { try { await deleteCopy(id); await load() } catch (err: unknown) { alert((err as { message?: string }).message ?? 'Fehler.') } }

function conditionLabel(c: string) { const m: Record<string, string> = { GOOD: 'Gut', WORN: 'Abgenutzt', DAMAGED: 'Beschädigt', LOCKED: 'Gesperrt' }; return m[c] ?? c }
function conditionClass(c: string) { const m: Record<string, string> = { GOOD: 'status-badge--active', WORN: 'status-badge--pending', DAMAGED: 'status-badge--danger', LOCKED: 'status-badge--muted' }; return m[c] ?? '' }
</script>

<style lang="scss" scoped>
$hero-bg: #0F0E0C; $amber: #D4921E; $nav-height: 64px;
$amber-08: rgba(212,146,30,0.08); $amber-14: rgba(212,146,30,0.14); $amber-25: rgba(212,146,30,0.25); $amber-glow: rgba(212,146,30,0.16);
$hero-text: #EEE8DF; $hero-muted: rgba(238,232,223,0.55); $hero-muted-50: rgba(238,232,223,0.50); $hero-divider: rgba(238,232,223,0.10);

.admin-page { min-height: 100vh; display: flex; flex-direction: column; background: var(--background); }

.page-hero { position: relative; background: $hero-bg; padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem; overflow: hidden; &__backdrop { position: absolute; inset: 0; pointer-events: none; } &__glow { position: absolute; width: 400px; height: 400px; top: -120px; right: -60px; border-radius: 50%; filter: blur(90px); background: $amber-glow; } &__dots { position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 24px 24px; mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%); } &__body { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; } &__back { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.78rem; font-weight: 600; color: $hero-muted; text-decoration: none; margin-bottom: 0.6rem; transition: color 0.2s; .icon { width: 14px; height: 14px; } &:hover { color: $hero-text; } } &__eyebrow { display: inline-block; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase; color: $amber; margin-bottom: 0.4rem; padding: 0.2rem 0.6rem; border: 1px solid $amber-25; border-radius: 999px; background: $amber-08; } &__row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; } &__title { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0; } }

.hero-btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; font-family: inherit; background: $amber; color: #0F0E0C; border: none; border-radius: 8px; cursor: pointer; transition: opacity 0.2s; .icon { width: 16px; height: 16px; } &:hover { opacity: 0.88; } }

.admin-content { flex: 1; padding: 2rem 1.5rem 4rem; &__inner { max-width: 1100px; margin: 0 auto; } }
.admin-state { display: flex; justify-content: center; align-items: center; min-height: 200px; }

.dash-section { background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 14px; overflow: hidden; }
.dash-section__header { display: flex; align-items: center; gap: 0.75rem; padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--divider); }
.dash-section__title { font-size: 0.95rem; font-weight: 700; color: var(--primary-text); margin: 0; letter-spacing: -0.02em; }
.dash-section__count { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 22px; padding: 0 6px; font-size: 0.75rem; font-weight: 700; color: $amber; background: $amber-08; border: 1px solid $amber-25; border-radius: 999px; }

.dash-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; padding: 3rem 1.5rem; color: var(--secondary-text); &__icon { width: 2rem; height: 2rem; opacity: 0.35; } &__text { font-size: 0.9rem; padding-bottom: 0; } }

.table-wrap { overflow-x: auto; }
.dash-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; th { padding: 0.7rem 1.5rem; text-align: left; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--secondary-text); background: var(--background); border-bottom: 1px solid var(--divider); white-space: nowrap; } td { padding: 0.9rem 1.5rem; color: var(--primary-text); border-bottom: 1px solid var(--divider); vertical-align: middle; } tbody tr:last-child td { border-bottom: none; } tbody tr { transition: background 0.15s; &:hover { background: var(--background); } } &__primary { font-weight: 600; } }
.text-mono { font-family: monospace; font-size: 0.8rem; color: var(--secondary-text); }

.status-badge { display: inline-block; padding: 0.2rem 0.6rem; font-size: 0.72rem; font-weight: 600; border-radius: 999px; white-space: nowrap; }
.status-badge--active  { background: rgba(34,197,94,0.12); color: #4ade80; border: 1px solid rgba(34,197,94,0.25); }
.status-badge--pending { background: $amber-08; color: $amber; border: 1px solid $amber-25; }
.status-badge--danger  { background: rgba(239,68,68,0.10); color: #f87171; border: 1px solid rgba(239,68,68,0.25); }
.status-badge--muted   { background: var(--background); color: var(--secondary-text); border: 1px solid var(--divider); }

.action-row { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.action-btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 600; font-family: inherit; color: var(--primary-text); background: var(--background); border: 1px solid var(--divider); border-radius: 7px; cursor: pointer; transition: border-color 0.2s, color 0.2s; white-space: nowrap; &:hover { border-color: var(--accent-color); color: var(--accent-text); } &--danger { color: #f87171; border-color: rgba(239,68,68,0.25); background: rgba(239,68,68,0.05); &:hover { border-color: rgba(239,68,68,0.5); color: #fca5a5; } } }

.modal-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
.modal { background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 16px; padding: 1.75rem; width: 100%; max-width: 420px; box-shadow: 0 25px 60px rgba(0,0,0,0.4); &__header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.25rem; } &__title { font-size: 1.05rem; font-weight: 700; letter-spacing: -0.02em; color: var(--primary-text); } &__close { display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: transparent; border: none; border-radius: 6px; color: var(--secondary-text); cursor: pointer; transition: background 0.15s; .icon { width: 18px; height: 18px; } &:hover { background: var(--background); color: var(--primary-text); } } &__body { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.5rem; } &__actions { display: flex; gap: 0.75rem; } }
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; .modal { transition: opacity 0.2s ease, transform 0.2s ease; } }
.modal-enter-from, .modal-leave-to { opacity: 0; .modal { opacity: 0; transform: translateY(8px) scale(0.98); } }

.form-field { display: flex; flex-direction: column; gap: 0.4rem; }
.form-label { font-size: 0.8rem; font-weight: 600; color: var(--secondary-text); letter-spacing: 0.03em; }
.form-select { display: block; width: 100%; height: 40px; padding: 0 0.75rem; border: 1px solid var(--divider); border-radius: 8px; background: var(--background); color: var(--primary-text); font-size: 0.875rem; font-family: inherit; cursor: pointer; transition: border-color 0.2s; &:focus { outline: none; border-color: var(--accent-color); } }
.form-error { padding: 0.75rem 1rem; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); border-radius: 8px; color: #f87171; font-size: 0.875rem; }

.l-footer { background: $hero-bg; border-top: 1px solid $hero-divider; padding: 1.75rem 1.5rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; } &__brand { display: flex; align-items: center; gap: 0.4rem; } &__hex { font-size: 1.1rem; color: $amber; } &__name { font-size: 0.9rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em; } &__copy { font-size: 0.8rem; color: $hero-muted-50; padding-bottom: 0; } }
</style>
