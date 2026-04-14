<template>
  <div class="admin-page">
    <AppNav />

    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <AdminBreadcrumb :label="$t('admin.breadcrumb.tags')" />
        <div class="page-hero__row">
          <h1 class="page-hero__title">{{ $t('admin.tags.title') }}</h1>
          <button class="hero-btn" @click="openCreate">
            <span class="icon icon-add" aria-hidden="true" />
            {{ $t('admin.actions.add_tag') }}
          </button>
        </div>
      </div>
    </section>

    <div class="admin-content">
      <div class="admin-content__inner">

        <div v-if="loading" class="admin-state">
          <div class="spinner" />
        </div>

        <section v-else class="dash-section">
          <header class="dash-section__header">
            <h2 class="dash-section__title">{{ $t('admin.tags.all') }}</h2>
            <span class="dash-section__count">{{ tags.length }}</span>
          </header>

          <div v-if="!tags.length" class="dash-empty">
            <span class="icon icon-label dash-empty__icon" aria-hidden="true" />
            <p class="dash-empty__text">{{ $t('admin.empty.tags') }}</p>
          </div>

          <div v-else class="table-wrap">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>{{ $t('admin.table.name') }}</th>
                  <th>{{ $t('admin.table.slug') }}</th>
                  <th>{{ $t('admin.table.games') }}</th>
                  <th>{{ $t('admin.table.actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="tag in tags" :key="tag.id">
                  <td class="dash-table__primary">{{ tag.name }}</td>
                  <td class="text-mono">{{ tag.slug }}</td>
                  <td>{{ tag.games_count ?? 0 }}</td>
                  <td>
                    <div class="action-row">
                      <button class="action-btn" @click="openEdit(tag)">{{ $t('admin.actions.rename') }}</button>
                      <button class="action-btn action-btn--danger" @click="remove(tag)">{{ $t('admin.actions.delete') }}</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

      </div>
    </div>

    <!-- ── Formular Modal ─────────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="form.open" class="modal-overlay" @click.self="closeForm">
        <div class="dialog">
          <div class="dialog__header">
            <h3 class="dialog__title">{{ form.id ? $t('admin.tags.rename') : $t('admin.tags.add') }}</h3>
            <button class="dialog__close" :aria-label="$t('admin.form.close')" @click="closeForm">
              <span class="icon icon-close" aria-hidden="true" />
            </button>
          </div>
          <div class="dialog__body">
            <UiInput v-model="form.name" :label="$t('admin.form.name')" required @keydown.enter="save" />
            <div v-if="formError" class="form-error">{{ formError }}</div>
          </div>
          <div class="dialog__actions">
            <UiButton :loading="saving" @click="save">{{ $t('admin.form.save') }}</UiButton>
            <button class="action-btn" @click="closeForm">{{ $t('admin.form.cancel') }}</button>
          </div>
        </div>
      </div>
    </Transition>

    <footer class="l-footer">
      <div class="l-footer__inner">
        <div class="l-footer__brand">
          <span class="l-footer__hex" aria-hidden="true">⬡</span>
          <span class="l-footer__name">AUA</span>
        </div>
        <p class="l-footer__copy">{{ $t('common.copyright_short', { year }) }}</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'

definePageMeta({ middleware: ['auth', 'admin'] })

const { t } = useI18n()
const { fetchAdminTags, createTag, updateTag, deleteTag } = useAdmin()

interface Tag { id: number; name: string; slug: string; games_count: number }

const year = new Date().getFullYear()
const loading = ref(true)
const saving = ref(false)
const formError = ref('')
const tags = ref<Tag[]>([])

const form = reactive({ open: false, id: null as number | null, name: '' })

onMounted(load)

async function load() {
  loading.value = true
  try {
    const data = await fetchAdminTags()
    tags.value = data.data as Tag[]
  } finally {
    loading.value = false
  }
}

function openCreate() {
  Object.assign(form, { open: true, id: null, name: '' })
  formError.value = ''
}

function openEdit(tag: Tag) {
  Object.assign(form, { open: true, id: tag.id, name: tag.name })
  formError.value = ''
}

function closeForm() { form.open = false }

async function save() {
  if (!form.name.trim()) return
  saving.value = true
  formError.value = ''
  try {
    form.id ? await updateTag(form.id, form.name) : await createTag(form.name)
    await load()
    closeForm()
  } catch (err: unknown) {
    formError.value = (err as { message?: string }).message ?? t('common.error.save')
  } finally {
    saving.value = false
  }
}

async function remove(tag: Tag) {
  await deleteTag(tag.id)
  await load()
}
</script>

<style lang="scss" scoped>
$hero-bg:     #0F0E0C;
$nav-height:  64px;
$amber-08:    rgba(212, 146, 30, 0.08);
$amber-14:    rgba(212, 146, 30, 0.14);
$amber-25:    rgba(212, 146, 30, 0.25);
$amber-glow:  rgba(212, 146, 30, 0.16);
$hero-text:   #EEE8DF;
$hero-muted:  rgba(238, 232, 223, 0.72);
$hero-muted-50: rgba(238, 232, 223, 0.65);
$hero-divider:  rgba(238, 232, 223, 0.10);

.admin-page { min-height: 100vh; display: flex; flex-direction: column; background: var(--background); }

.page-hero {
  position: relative; background: $hero-bg;
  padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem; overflow: hidden;
  &__backdrop { position: absolute; inset: 0; pointer-events: none; }
  &__glow { position: absolute; width: 400px; height: 400px; top: -120px; right: -60px; border-radius: 50%; filter: blur(90px); background: $amber-glow; }
  &__dots { position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 24px 24px; mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%); }
  &__body { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; }
  &__row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
  &__title { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0; }
}

.hero-btn {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; font-family: inherit;
  background: $amber; color: #0F0E0C; border: none; border-radius: 8px; cursor: pointer;
  transition: opacity 0.2s;
  .icon { font-size: 1rem; }
  &:hover { opacity: 0.88; }
  &:disabled { opacity: 0.5; cursor: not-allowed; }
}

.admin-content { flex: 1; padding: 2rem 1.5rem 4rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; } }
.admin-state { display: flex; justify-content: center; align-items: center; min-height: 200px; }

.dash-section { background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 14px; overflow: hidden; }
.dash-section__header { display: flex; align-items: center; gap: 0.75rem; padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--divider); }
.dash-section__title { font-size: 0.95rem; font-weight: 700; color: var(--primary-text); margin: 0; letter-spacing: -0.02em; }
.dash-section__count { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 22px; padding: 0 6px; font-size: 0.75rem; font-weight: 700; color: $amber; background: $amber-08; border: 1px solid $amber-25; border-radius: 999px; }

.dash-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; padding: 3rem 1.5rem; color: var(--secondary-text); &__icon { width: 2rem; height: 2rem; opacity: 0.35; } &__text { font-size: 0.9rem; padding-bottom: 0; } }

.table-wrap { overflow-x: auto; }
.dash-table {
  width: 100%; border-collapse: collapse; font-size: 0.875rem;
  th { padding: 0.7rem 1.5rem; text-align: left; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--secondary-text); background: var(--background); border-bottom: 1px solid var(--divider); white-space: nowrap; }
  td { padding: 0.9rem 1.5rem; color: var(--primary-text); border-bottom: 1px solid var(--divider); vertical-align: middle; }
  tbody tr:last-child td { border-bottom: none; }
  tbody tr { transition: background 0.15s; &:hover { background: var(--background); } }
  &__primary { font-weight: 600; }
}

.text-mono { font-family: monospace; font-size: 0.8rem; color: var(--secondary-text); }

.action-row { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.action-btn {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 600; font-family: inherit;
  color: var(--primary-text); background: var(--background); border: 1px solid var(--divider); border-radius: 7px; cursor: pointer; transition: border-color 0.2s, color 0.2s; white-space: nowrap;
  .icon { font-size: 0.875rem; }
  &:hover { border-color: var(--accent-color); color: var(--accent-text); }
  &--danger { color: #f87171; border-color: rgba(239,68,68,0.25); background: rgba(239,68,68,0.05); &:hover { border-color: rgba(239,68,68,0.5); color: #fca5a5; } }
  &:disabled { opacity: 0.4; cursor: not-allowed; }
}

// ─── Modal ────────────────────────────────────────────────────────
.modal-overlay {
  position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center; padding: 1.5rem; overflow-y: auto;
}
.dialog {
  background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 16px; padding: 1.75rem; width: 100%; max-width: 420px; box-shadow: 0 25px 60px rgba(0,0,0,0.4);
  &__header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.5rem; }
  &__title { font-size: 1.05rem; font-weight: 700; letter-spacing: -0.02em; color: var(--primary-text); }
  &__close { display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: transparent; border: none; border-radius: 6px; color: var(--secondary-text); cursor: pointer; transition: background 0.15s, color 0.15s; .icon { font-size: 1.125rem; } &:hover { background: var(--background); color: var(--primary-text); } }
  &__body { margin-bottom: 1.5rem; }
  &__actions { display: flex; gap: 0.75rem; }
}

.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; .dialog { transition: opacity 0.2s ease, transform 0.2s ease; } }
.modal-enter-from, .modal-leave-to { opacity: 0; .dialog { opacity: 0; transform: translateY(8px) scale(0.98); } }

.form-error { margin-top: 0.75rem; padding: 0.75rem 1rem; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); border-radius: 8px; color: #f87171; font-size: 0.875rem; }

// ─── Footer ───────────────────────────────────────────────────────
.l-footer { background: $hero-bg; border-top: 1px solid $hero-divider; padding: 1.75rem 1.5rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; } &__brand { display: flex; align-items: center; gap: 0.4rem; } &__hex { font-size: 1.1rem; color: $amber; } &__name { font-size: 0.9rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em; } &__copy { font-size: 0.8rem; color: $hero-muted-50; padding-bottom: 0; } }
</style>
