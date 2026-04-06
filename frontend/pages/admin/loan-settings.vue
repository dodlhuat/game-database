<template>
  <div class="admin-page">
    <AppNav />

    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" /><div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <AdminBreadcrumb label="Ausleih-Einstellungen" />
        <h1 class="page-hero__title">Ausleih-Einstellungen</h1>
      </div>
    </section>

    <div class="admin-content">
      <div class="admin-content__inner">

        <div v-if="loading" class="admin-state"><div class="spinner" /></div>

        <div v-else-if="loadError" class="admin-state">
          <p class="load-error">{{ loadError }}</p>
        </div>

        <template v-else>
          <section class="dash-section">
            <header class="dash-section__header">
              <h2 class="dash-section__title">Konfiguration</h2>
            </header>
            <form class="settings-form" @submit.prevent="save">
              <div class="settings-grid">
                <div class="form-field">
                  <label class="form-label">Starttermin</label>
                  <input v-model="form.start_date" class="form-input" type="date" required />
                  <p class="form-hint">Erster Abholtermin, von dem aus das Intervall berechnet wird.</p>
                </div>
                <div class="form-field">
                  <label class="form-label">Intervall (Tage)</label>
                  <input v-model.number="form.interval_days" class="form-input" type="number" min="1" required />
                  <p class="form-hint">Abstand zwischen zwei Abholterminen in Tagen.</p>
                </div>
                <div class="form-field">
                  <label class="form-label">Vorlauf (Tage)</label>
                  <input v-model.number="form.grace_days" class="form-input" type="number" min="0" required />
                  <p class="form-hint">Wie viele Tage vor einem Termin dieser noch wählbar ist.</p>
                </div>
                <div class="form-field">
                  <label class="form-label">Ausleihdauer (Wochen)</label>
                  <input v-model.number="form.loan_duration_weeks" class="form-input" type="number" min="1" required />
                  <p class="form-hint">Anzahl Wochen bis zum Rückgabedatum.</p>
                </div>
                <div class="form-field">
                  <label class="form-label">Max. Verlängerungen pro Ausleihe</label>
                  <input v-model.number="form.max_extensions" class="form-input" type="number" min="0" required />
                  <p class="form-hint">Wie oft eine Ausleihe maximal verlängert werden darf (0 = keine Verlängerung möglich).</p>
                </div>
              </div>
              <p v-if="saveError" class="form-error">{{ saveError }}</p>
              <div class="settings-actions">
                <UiButton type="submit" :loading="saving">Einstellungen speichern</UiButton>
                <span v-if="saved" class="save-success">Gespeichert</span>
              </div>
            </form>
          </section>

          <section class="dash-section">
            <header class="dash-section__header">
              <h2 class="dash-section__title">Vorschau: Nächste Abholtermine</h2>
            </header>
            <div class="preview-list">
              <div v-for="(item, i) in preview" :key="i" class="preview-item">
                <span class="preview-item__n">{{ i + 1 }}.</span>
                <span class="preview-item__date">{{ item.appointment }}</span>
                <span class="preview-item__arrow">→</span>
                <span class="preview-item__due">Rückgabe {{ item.due }}</span>
              </div>
            </div>
          </section>
        </template>

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
import { ref, computed, onMounted } from 'vue'
import type { LoanSettings } from '~/composables/useLoanSettings'

definePageMeta({ middleware: ['auth', 'admin'] })

const api = useApi()
const { getNextAppointment, getDueDate, formatDate } = useLoanSettings()
const year = new Date().getFullYear()

const loading = ref(true)
const loadError = ref('')
const saving = ref(false)
const saved = ref(false)
const saveError = ref('')

const form = ref<LoanSettings>({
  start_date: '',
  interval_days: 14,
  grace_days: 3,
  loan_duration_weeks: 4,
  max_extensions: 2,
})

const preview = computed(() => {
  if (!form.value.start_date) return []
  const items: { appointment: string; due: string }[] = []
  const startDate = new Date(form.value.start_date.slice(0, 10) + 'T00:00:00')
  const today = new Date(); today.setHours(0, 0, 0, 0)
  const deadline = new Date(today); deadline.setDate(deadline.getDate() + form.value.grace_days)

  let n = 0
  let count = 0
  while (count < 4) {
    const appointment = new Date(startDate)
    appointment.setDate(appointment.getDate() + n * form.value.interval_days)
    if (appointment > deadline) {
      const due = new Date(appointment)
      due.setDate(due.getDate() + form.value.loan_duration_weeks * 7)
      items.push({ appointment: formatDate(appointment), due: formatDate(due) })
      count++
    }
    n++
  }
  return items
})

onMounted(async () => {
  try {
    const data = await api.get<LoanSettings>('/admin/loan-settings')
    form.value = {
      start_date: String(data.start_date).slice(0, 10),
      interval_days: data.interval_days,
      grace_days: data.grace_days,
      loan_duration_weeks: data.loan_duration_weeks,
      max_extensions: data.max_extensions,
    }
  } catch (e: unknown) {
    const err = e as { data?: { message?: string }; message?: string }
    loadError.value = err?.data?.message ?? err?.message ?? 'Einstellungen konnten nicht geladen werden.'
  } finally {
    loading.value = false
  }
})

async function save() {
  saving.value = true
  saved.value = false
  saveError.value = ''
  try {
    const data = await api.patch<LoanSettings>('/admin/loan-settings', form.value)
    form.value = {
      start_date: String(data.start_date).slice(0, 10),
      interval_days: data.interval_days,
      grace_days: data.grace_days,
      loan_duration_weeks: data.loan_duration_weeks,
      max_extensions: data.max_extensions,
    }
    saved.value = true
    setTimeout(() => { saved.value = false }, 3000)
  } catch (e: unknown) {
    const err = e as { data?: { message?: string } }
    saveError.value = err?.data?.message ?? 'Fehler beim Speichern.'
  } finally {
    saving.value = false
  }
}
</script>

<style lang="scss" scoped>
$hero-bg: #0F0E0C; $amber: #D4921E; $nav-height: 64px;
$amber-08: rgba(212,146,30,0.08); $amber-14: rgba(212,146,30,0.14); $amber-25: rgba(212,146,30,0.25); $amber-glow: rgba(212,146,30,0.16);
$hero-text: #EEE8DF; $hero-muted: rgba(238,232,223,0.55); $hero-muted-50: rgba(238,232,223,0.50); $hero-divider: rgba(238,232,223,0.10);

.admin-page { min-height: 100vh; display: flex; flex-direction: column; background: var(--background); }

.page-hero { position: relative; background: $hero-bg; padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem; overflow: hidden; &__backdrop { position: absolute; inset: 0; pointer-events: none; } &__glow { position: absolute; width: 400px; height: 400px; top: -120px; right: -60px; border-radius: 50%; filter: blur(90px); background: $amber-glow; } &__dots { position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 24px 24px; mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%); } &__body { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; } &__title { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0; } }

.admin-content { flex: 1; padding: 2rem 1.5rem 4rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; } }
.admin-state { display: flex; justify-content: center; align-items: center; min-height: 200px; }
.load-error { color: #f87171; font-size: 0.9rem; font-weight: 500; padding-bottom: 0; }

.dash-section { background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 14px; overflow: hidden; }
.dash-section__header { display: flex; align-items: center; gap: 0.75rem; padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--divider); }
.dash-section__title { font-size: 0.95rem; font-weight: 700; color: var(--primary-text); margin: 0; letter-spacing: -0.02em; }

.settings-form { padding: 1.5rem; }
.settings-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.25rem; @media (max-width: 600px) { grid-template-columns: 1fr; } }
.settings-actions { display: flex; align-items: center; gap: 1rem; padding-top: 1.25rem; }

.form-field { display: flex; flex-direction: column; gap: 0.35rem; }
.form-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--secondary-text); }
.form-input { padding: 0.55rem 0.75rem; background: var(--background); border: 1px solid var(--divider); border-radius: 8px; font-size: 0.9rem; font-family: inherit; color: var(--primary-text); outline: none; transition: border-color 0.2s; &:focus { border-color: $amber; } }
.form-hint { font-size: 0.78rem; color: var(--secondary-text); margin: 0; padding-bottom: 0; }
.form-error { font-size: 0.85rem; color: #f87171; margin: 0; padding-bottom: 0; }

.save-success { font-size: 0.85rem; font-weight: 600; color: #4ade80; }

.preview-list { padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 0.6rem; }
.preview-item { display: flex; align-items: center; gap: 0.75rem; font-size: 0.9rem; &__n { font-weight: 700; color: var(--secondary-text); min-width: 1.2rem; } &__date { font-weight: 700; color: var(--primary-text); } &__arrow { color: var(--secondary-text); } &__due { color: var(--secondary-text); } }

.l-footer { background: $hero-bg; border-top: 1px solid $hero-divider; padding: 1.75rem 1.5rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; } &__brand { display: flex; align-items: center; gap: 0.4rem; } &__hex { font-size: 1.1rem; color: $amber; } &__name { font-size: 0.9rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em; } &__copy { font-size: 0.8rem; color: $hero-muted-50; padding-bottom: 0; } }
</style>
