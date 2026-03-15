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
        <h1 class="page-hero__title">Newsletter</h1>
      </div>
    </section>

    <div class="admin-content">
      <div class="admin-content__inner">

        <!-- Compose ─────────────────────────────────────────────── -->
        <section class="dash-section">
          <header class="dash-section__header">
            <h2 class="dash-section__title">Neuen Newsletter verfassen</h2>
          </header>
          <div class="compose-body">
            <UiInput v-model="form.subject" label="Betreff" />
            <div>
              <label class="form-label">Inhalt</label>
              <textarea v-model="form.body" rows="8" class="form-textarea" />
            </div>
            <Transition name="fade">
              <div v-if="successMsg" class="success-banner">
                <span class="icon icon-checkmark-circle-2-outline" aria-hidden="true" />
                {{ successMsg }}
              </div>
            </Transition>
            <div>
              <button class="hero-btn" :disabled="sending || !form.subject || !form.body" @click="send">
                <span class="icon icon-paper-plane-outline" aria-hidden="true" />
                {{ sending ? 'Wird gesendet…' : 'Newsletter senden' }}
              </button>
            </div>
          </div>
        </section>

        <!-- History ─────────────────────────────────────────────── -->
        <section class="dash-section">
          <header class="dash-section__header">
            <h2 class="dash-section__title">Versandhistorie</h2>
            <span class="dash-section__count">{{ newsletters.length }}</span>
          </header>

          <div v-if="loading" class="admin-state"><div class="spinner" /></div>

          <div v-else-if="!newsletters.length" class="dash-empty">
            <span class="icon icon-email-outline dash-empty__icon" aria-hidden="true" />
            <p class="dash-empty__text">Noch keine Newsletter versandt.</p>
          </div>

          <div v-else class="table-wrap">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>Betreff</th>
                  <th>Versandt am</th>
                  <th>Empfänger</th>
                  <th>Von</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="nl in newsletters" :key="nl.id">
                  <td class="dash-table__primary">{{ nl.subject }}</td>
                  <td class="text-muted">{{ formatDate(nl.sent_at) }}</td>
                  <td>{{ nl.recipient_count }}</td>
                  <td class="text-muted">{{ nl.sender?.name ?? '—' }}</td>
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

const { fetchNewsletters, sendNewsletter } = useAdmin()

interface Newsletter { id: number; subject: string; sent_at: string; recipient_count: number; sender: { name: string } | null }

const year = new Date().getFullYear()
const loading = ref(true); const sending = ref(false)
const newsletters = ref<Newsletter[]>([])
const successMsg = ref('')
const form = ref({ subject: '', body: '' })

onMounted(load)

async function load() { loading.value = true; try { const data = await fetchNewsletters(); newsletters.value = data.data as Newsletter[] } finally { loading.value = false } }

async function send() {
  if (!form.value.subject || !form.value.body) return
  sending.value = true; successMsg.value = ''
  try { await sendNewsletter(form.value.subject, form.value.body); form.value = { subject: '', body: '' }; successMsg.value = 'Newsletter wurde in die Warteschlange eingereiht.'; await load() }
  finally { sending.value = false }
}

function formatDate(iso?: string) { return iso ? new Date(iso).toLocaleDateString('de-DE', { dateStyle: 'medium' }) : '—' }
</script>

<style lang="scss" scoped>
$hero-bg: #0F0E0C; $amber: #D4921E; $nav-height: 64px;
$amber-08: rgba(212,146,30,0.08); $amber-25: rgba(212,146,30,0.25); $amber-glow: rgba(212,146,30,0.16);
$hero-text: #EEE8DF; $hero-muted: rgba(238,232,223,0.55); $hero-muted-50: rgba(238,232,223,0.50); $hero-divider: rgba(238,232,223,0.10);

.admin-page { min-height: 100vh; display: flex; flex-direction: column; background: var(--background); }

.page-hero { position: relative; background: $hero-bg; padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem; overflow: hidden; &__backdrop { position: absolute; inset: 0; pointer-events: none; } &__glow { position: absolute; width: 400px; height: 400px; top: -120px; right: -60px; border-radius: 50%; filter: blur(90px); background: $amber-glow; } &__dots { position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 24px 24px; mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%); } &__body { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; } &__back { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.78rem; font-weight: 600; color: $hero-muted; text-decoration: none; margin-bottom: 0.6rem; transition: color 0.2s; .icon { width: 14px; height: 14px; } &:hover { color: $hero-text; } } &__eyebrow { display: inline-block; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase; color: $amber; margin-bottom: 0.4rem; padding: 0.2rem 0.6rem; border: 1px solid $amber-25; border-radius: 999px; background: $amber-08; } &__title { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0; } }

.admin-content { flex: 1; padding: 2rem 1.5rem 4rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; } }
.admin-state { display: flex; justify-content: center; align-items: center; min-height: 120px; }

.dash-section { background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 14px; overflow: hidden; }
.dash-section__header { display: flex; align-items: center; gap: 0.75rem; padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--divider); }
.dash-section__title { font-size: 0.95rem; font-weight: 700; color: var(--primary-text); margin: 0; letter-spacing: -0.02em; }
.dash-section__count { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 22px; padding: 0 6px; font-size: 0.75rem; font-weight: 700; color: $amber; background: $amber-08; border: 1px solid $amber-25; border-radius: 999px; }

.compose-body { padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }

.form-label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--secondary-text); margin-bottom: 0.4rem; letter-spacing: 0.03em; }

.form-textarea {
  display: block; width: 100%; padding: 0.75rem; min-height: 180px; resize: vertical;
  border: 1px solid var(--divider); border-radius: 8px;
  background: var(--background); color: var(--primary-text);
  font-size: 0.875rem; font-family: inherit; line-height: 1.6;
  transition: border-color 0.2s;
  &:focus { outline: none; border-color: var(--accent-color); }
}

.hero-btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1.1rem; font-size: 0.875rem; font-weight: 600; font-family: inherit; background: $amber; color: #0F0E0C; border: none; border-radius: 8px; cursor: pointer; transition: opacity 0.2s; .icon { width: 16px; height: 16px; } &:hover:not(:disabled) { opacity: 0.88; } &:disabled { opacity: 0.4; cursor: not-allowed; } }

.success-banner { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.25); border-radius: 8px; color: #4ade80; font-size: 0.875rem; .icon { width: 18px; height: 18px; flex-shrink: 0; } }

.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.dash-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; padding: 3rem 1.5rem; color: var(--secondary-text); &__icon { width: 2rem; height: 2rem; opacity: 0.35; } &__text { font-size: 0.9rem; padding-bottom: 0; } }

.table-wrap { overflow-x: auto; }
.dash-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; th { padding: 0.7rem 1.5rem; text-align: left; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--secondary-text); background: var(--background); border-bottom: 1px solid var(--divider); white-space: nowrap; } td { padding: 0.9rem 1.5rem; color: var(--primary-text); border-bottom: 1px solid var(--divider); vertical-align: middle; } tbody tr:last-child td { border-bottom: none; } tbody tr { transition: background 0.15s; &:hover { background: var(--background); } } &__primary { font-weight: 600; } }

.text-muted { color: var(--secondary-text); }

.l-footer { background: $hero-bg; border-top: 1px solid $hero-divider; padding: 1.75rem 1.5rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; } &__brand { display: flex; align-items: center; gap: 0.4rem; } &__hex { font-size: 1.1rem; color: $amber; } &__name { font-size: 0.9rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em; } &__copy { font-size: 0.8rem; color: $hero-muted-50; padding-bottom: 0; } }
</style>
