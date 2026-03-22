<template>
  <div class="admin-page">
    <AppNav />

    <!-- ── Page Hero ────────────────────────────────────────────── -->
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" /><div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <nav class="page-hero__breadcrumb" aria-label="Breadcrumb">
          <NuxtLink to="/admin" class="page-hero__back">
            <span class="icon icon-arrow-back-outline" aria-hidden="true" /> Admin
          </NuxtLink>
          <span class="page-hero__eyebrow">Administration</span>
        </nav>
        <h1 class="page-hero__title">E-Mail-Vorlagen</h1>
      </div>
    </section>

    <!-- ── Content ──────────────────────────────────────────────── -->
    <div class="admin-content">
      <div class="admin-content__inner">

        <div v-if="loading" class="admin-state"><div class="spinner" /></div>

        <template v-else>
          <div class="templates-grid">
            <article
              v-for="tpl in templates"
              :key="tpl.key"
              class="tpl-card"
              @click="openEdit(tpl)"
            >
              <div class="tpl-card__header">
                <div class="tpl-card__icon">
                  <span class="icon" :class="meta[tpl.key]?.icon ?? 'icon-email-outline'" aria-hidden="true" />
                </div>
                <div class="tpl-card__meta">
                  <span class="tpl-card__label">{{ meta[tpl.key]?.label ?? tpl.key }}</span>
                  <span class="tpl-card__recipient">→ {{ meta[tpl.key]?.recipient ?? '—' }}</span>
                </div>
              </div>
              <p class="tpl-card__subject">{{ tpl.subject }}</p>
              <div class="tpl-card__vars">
                <span
                  v-for="v in meta[tpl.key]?.vars ?? []"
                  :key="v"
                  class="tpl-card__var"
                >{{ '{' + v + '}' }}</span>
              </div>
            </article>
          </div>
        </template>

      </div>
    </div>

    <!-- ── Edit Modal ────────────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="form.open" class="modal-overlay" @click.self="closeForm">
        <div class="modal modal--wide">
          <div class="modal__header">
            <div>
              <div class="modal__eyebrow">E-Mail-Vorlage bearbeiten</div>
              <h3 class="modal__title">{{ meta[form.key]?.label ?? form.key }}</h3>
            </div>
            <button class="modal__close" aria-label="Schließen" @click="closeForm">
              <span class="icon icon-close-outline" aria-hidden="true" />
            </button>
          </div>

          <div class="modal__body">
            <!-- Variables hint -->
            <div v-if="meta[form.key]?.vars?.length" class="vars-hint">
              <span class="vars-hint__label">Verfügbare Variablen:</span>
              <span
                v-for="v in meta[form.key].vars"
                :key="v"
                class="vars-hint__chip"
              >{{ '{' + v + '}' }}</span>
            </div>

            <UiInput v-model="form.subject" label="Betreff" required />
            <UiInput v-model="form.greeting" label="Begrüßung" required />

            <div>
              <label class="form-label">Inhalt</label>
              <UiRichEditor v-model="form.body" />
            </div>

            <UiInput
              v-model="form.action_text"
              label="Button-Text (leer = kein Button)"
            />

            <div v-if="formError" class="form-error">{{ formError }}</div>
          </div>

          <div class="modal__actions">
            <UiButton :loading="saving" @click="save">Speichern</UiButton>
            <button class="action-btn" @click="closeForm">Abbrechen</button>
            <button class="action-btn action-btn--muted" @click="reset" :disabled="saving">
              Auf Standard zurücksetzen
            </button>
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
        <p class="l-footer__copy">&copy; {{ year }} AUA</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'

definePageMeta({ middleware: ['auth', 'admin'] })

const { fetchEmailTemplates, updateEmailTemplate, resetEmailTemplate } = useAdmin()

interface EmailTemplate {
  id: number
  key: string
  subject: string
  greeting: string
  body: string
  action_text: string | null
}

interface TemplateMeta {
  label: string
  recipient: string
  icon: string
  vars: string[]
}

const year = new Date().getFullYear()
const loading = ref(true)
const saving = ref(false)
const formError = ref('')
const templates = ref<EmailTemplate[]>([])

const meta: Record<string, TemplateMeta> = {
  user_approved: {
    label: 'Konto freigeschaltet',
    recipient: 'Mitglied',
    icon: 'icon-checkmark-circle-outline',
    vars: ['name'],
  },
  user_rejected: {
    label: 'Registrierung abgelehnt',
    recipient: 'Mitglied',
    icon: 'icon-close-circle-outline',
    vars: ['name'],
  },
  new_user_registered: {
    label: 'Neue Registrierung',
    recipient: 'Admin',
    icon: 'icon-person-add-outline',
    vars: ['name', 'email'],
  },
  loan_due_soon: {
    label: 'Rückgabe-Erinnerung',
    recipient: 'Mitglied',
    icon: 'icon-clock-outline',
    vars: ['name', 'game', 'due_date'],
  },
  reservation_available: {
    label: 'Spiel wieder verfügbar',
    recipient: 'Mitglied',
    icon: 'icon-bell-outline',
    vars: ['name', 'game'],
  },
}

const form = reactive({
  open: false,
  key: '',
  subject: '',
  greeting: '',
  body: '',
  action_text: '',
})

onMounted(async () => {
  try {
    const data = await fetchEmailTemplates()
    templates.value = data.data as EmailTemplate[]
  } finally {
    loading.value = false
  }
})

function openEdit(tpl: EmailTemplate) {
  Object.assign(form, {
    open: true,
    key: tpl.key,
    subject: tpl.subject,
    greeting: tpl.greeting,
    body: tpl.body,
    action_text: tpl.action_text ?? '',
  })
  formError.value = ''
}

function closeForm() { form.open = false }

async function save() {
  saving.value = true; formError.value = ''
  try {
    const payload = {
      subject: form.subject,
      greeting: form.greeting,
      body: form.body,
      action_text: form.action_text || null,
    }
    const res = await updateEmailTemplate(form.key, payload)
    const updated = (res as { data: EmailTemplate }).data
    const idx = templates.value.findIndex(t => t.key === form.key)
    if (idx !== -1) templates.value[idx] = updated
    closeForm()
  } catch (err: unknown) {
    formError.value = (err as { message?: string }).message ?? 'Fehler beim Speichern.'
  } finally {
    saving.value = false
  }
}

async function reset() {
  saving.value = true; formError.value = ''
  try {
    const res = await resetEmailTemplate(form.key)
    const updated = (res as { data: EmailTemplate }).data
    const idx = templates.value.findIndex(t => t.key === form.key)
    if (idx !== -1) templates.value[idx] = updated
    Object.assign(form, {
      subject: updated.subject,
      greeting: updated.greeting,
      body: updated.body,
      action_text: updated.action_text ?? '',
    })
  } catch (err: unknown) {
    formError.value = (err as { message?: string }).message ?? 'Fehler.'
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

.page-hero { position: relative; background: $hero-bg; padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem; overflow: hidden;
  &__backdrop { position: absolute; inset: 0; pointer-events: none; }
  &__glow { position: absolute; width: 400px; height: 400px; top: -120px; right: -60px; border-radius: 50%; filter: blur(90px); background: $amber-glow; }
  &__dots { position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 24px 24px; mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%); }
  &__body { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; }
  &__breadcrumb { display: flex; align-items: center; margin-bottom: 0.75rem; }
  &__back { display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.78rem; font-weight: 500; color: $hero-muted; text-decoration: none; transition: color 0.2s; .icon { width: 13px; height: 13px; } &::after { content: "›"; margin: 0 0.35rem; opacity: 0.4; font-weight: 400; } &:hover { color: $hero-text; } }
  &__eyebrow { font-size: 0.78rem; font-weight: 600; color: $amber; letter-spacing: 0.02em; }
  &__title { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0; }
}

.admin-content { flex: 1; padding: 2rem 1.5rem 4rem; &__inner { max-width: 1100px; margin: 0 auto; } }
.admin-state { display: flex; justify-content: center; align-items: center; min-height: 200px; }

// ─── Template Cards ───────────────────────────────────────────────
.templates-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

.tpl-card {
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 14px;
  padding: 1.25rem 1.5rem;
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;

  &:hover {
    border-color: $amber;
    box-shadow: 0 0 0 3px $amber-08;
  }

  &__header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  &__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: $amber-08;
    border: 1px solid $amber-25;
    border-radius: 9px;
    flex-shrink: 0;
    color: $amber;

    .icon { width: 18px; height: 18px; }
  }

  &__meta {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
  }

  &__label {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--primary-text);
    letter-spacing: -0.02em;
  }

  &__recipient {
    font-size: 0.75rem;
    color: var(--secondary-text);
  }

  &__subject {
    font-size: 0.82rem;
    color: var(--secondary-text);
    padding-bottom: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  &__vars {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
  }

  &__var {
    font-size: 0.72rem;
    font-weight: 600;
    font-family: monospace;
    color: $amber;
    background: $amber-08;
    border: 1px solid $amber-25;
    border-radius: 4px;
    padding: 0.1rem 0.4rem;
  }
}

// ─── Modal ────────────────────────────────────────────────────────
.modal-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 1.5rem; overflow-y: auto; }
.modal {
  background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 16px; padding: 1.75rem; width: 100%; max-width: 540px; box-shadow: 0 25px 60px rgba(0,0,0,0.4);
  &--wide { max-width: 620px; }
  &__header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.5rem; }
  &__eyebrow { font-size: 0.7rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: $amber; margin-bottom: 0.2rem; }
  &__title { font-size: 1.05rem; font-weight: 700; letter-spacing: -0.02em; color: var(--primary-text); }
  &__close { display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: transparent; border: none; border-radius: 6px; color: var(--secondary-text); cursor: pointer; transition: background 0.15s; .icon { width: 18px; height: 18px; } &:hover { background: var(--background); color: var(--primary-text); } }
  &__body { margin-bottom: 1.5rem; max-height: 65vh; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem; }
  &__actions { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; }
}

.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; .modal { transition: opacity 0.2s ease, transform 0.2s ease; } }
.modal-enter-from, .modal-leave-to { opacity: 0; .modal { opacity: 0; transform: translateY(8px) scale(0.98); } }

// ─── Form ─────────────────────────────────────────────────────────
.vars-hint {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.4rem;
  padding: 0.65rem 0.85rem;
  background: $amber-08;
  border: 1px solid $amber-25;
  border-radius: 8px;

  &__label {
    font-size: 0.78rem;
    font-weight: 600;
    color: $amber;
    margin-right: 0.25rem;
  }

  &__chip {
    font-size: 0.75rem;
    font-weight: 600;
    font-family: monospace;
    color: $amber;
    background: rgba(212,146,30,0.12);
    border: 1px solid $amber-25;
    border-radius: 4px;
    padding: 0.1rem 0.4rem;
    cursor: default;
  }
}

.form-label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--secondary-text); margin-bottom: 0.4rem; letter-spacing: 0.03em; }

.action-btn {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 600; font-family: inherit;
  color: var(--primary-text); background: var(--background); border: 1px solid var(--divider); border-radius: 7px; cursor: pointer; transition: border-color 0.2s, color 0.2s; white-space: nowrap;
  &:hover { border-color: var(--accent-color); color: var(--accent-text); }
  &--muted { color: var(--secondary-text); margin-left: auto; }
  &:disabled { opacity: 0.4; cursor: not-allowed; }
}

.form-error { padding: 0.75rem 1rem; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); border-radius: 8px; color: #f87171; font-size: 0.875rem; }

// ─── Footer ───────────────────────────────────────────────────────
.l-footer { background: $hero-bg; border-top: 1px solid $hero-divider; padding: 1.75rem 1.5rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; } &__brand { display: flex; align-items: center; gap: 0.4rem; } &__hex { font-size: 1.1rem; color: $amber; } &__name { font-size: 0.9rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em; } &__copy { font-size: 0.8rem; color: $hero-muted-50; padding-bottom: 0; } }
</style>
