<template>
  <div class="admin-page">
    <AppNav />

    <!-- ── Page Hero ────────────────────────────────────────────── -->
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <AdminBreadcrumb :label="$t('admin.breadcrumb.categories')" />
        <div class="page-hero__row">
          <h1 class="page-hero__title">{{ $t('admin.categories.title') }}</h1>
          <div class="hero-actions">
            <button class="hero-btn hero-btn--secondary" :disabled="exporting" @click="doExport">
              <span class="icon icon-download-outline" aria-hidden="true" />
              {{ exporting ? $t('btn.exporting') : $t('btn.export') }}
            </button>
            <label class="hero-btn hero-btn--secondary" :class="{ 'hero-btn--loading': importing }">
              <span class="icon icon-cloud" aria-hidden="true" />
              {{ importing ? $t('btn.importing') : $t('btn.import') }}
              <input type="file" accept=".xlsx,.xls,.csv" class="hero-file-input" :disabled="importing" @change="doImport" />
            </label>
            <button class="hero-btn" @click="openCreate">
              <span class="icon icon-add" aria-hidden="true" />
              {{ $t('admin.actions.add_category') }}
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Content ──────────────────────────────────────────────── -->
    <div class="admin-content">
      <div class="admin-content__inner">

        <div v-if="loading" class="admin-state">
          <div class="spinner" />
        </div>

        <section v-else class="dash-section">
          <header class="dash-section__header">
            <h2 class="dash-section__title">{{ $t('admin.categories.all') }}</h2>
            <span class="dash-section__count">{{ totalCount }}</span>
          </header>

          <div v-if="!categories.length" class="dash-empty">
            <span class="icon icon-layers dash-empty__icon" aria-hidden="true" />
            <p class="dash-empty__text">{{ $t('admin.empty.categories') }}</p>
          </div>

          <div v-else class="table-wrap">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>{{ $t('admin.table.name') }}</th>
                  <th>{{ $t('admin.table.slug') }}</th>
                  <th>{{ $t('admin.table.games') }}</th>
                  <th>{{ $t('admin.table.status') }}</th>
                  <th>{{ $t('admin.table.actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="cat in categories" :key="cat.id">
                  <!-- Parent row -->
                  <tr>
                    <td class="dash-table__primary">{{ cat.name }}</td>
                    <td class="text-mono text-muted">{{ cat.slug }}</td>
                    <td>{{ cat.games_count ?? 0 }}</td>
                    <td>
                      <span class="badge" :class="cat.is_active ? 'badge-success' : 'badge'">
                        {{ cat.is_active ? $t('common.badge.active') : $t('common.badge.inactive') }}
                      </span>
                    </td>
                    <td>
                      <div class="action-row">
                        <button class="action-btn" @click="openEdit(cat)">{{ $t('admin.actions.edit') }}</button>
                        <button
                          class="action-btn"
                          :class="cat.is_active ? 'action-btn--warn' : 'action-btn--ok'"
                          @click="toggleActive(cat)"
                        >
                          {{ cat.is_active ? $t('admin.actions.deactivate') : $t('admin.actions.activate') }}
                        </button>
                        <button class="action-btn action-btn--danger" @click="remove(cat)">{{ $t('admin.actions.delete') }}</button>
                      </div>
                    </td>
                  </tr>
                  <!-- Child rows -->
                  <tr v-for="child in cat.children" :key="child.id" class="child-row">
                    <td class="dash-table__primary">
                      <span class="child-indent" aria-hidden="true" />
                      {{ child.name }}
                    </td>
                    <td class="text-mono text-muted">{{ child.slug }}</td>
                    <td>{{ child.games_count ?? 0 }}</td>
                    <td>
                      <span class="badge" :class="child.is_active ? 'badge-success' : 'badge'">
                        {{ child.is_active ? $t('common.badge.active') : $t('common.badge.inactive') }}
                      </span>
                    </td>
                    <td>
                      <div class="action-row">
                        <button class="action-btn" @click="openEdit(child)">{{ $t('admin.actions.edit') }}</button>
                        <button
                          class="action-btn"
                          :class="child.is_active ? 'action-btn--warn' : 'action-btn--ok'"
                          @click="toggleActive(child)"
                        >
                          {{ child.is_active ? $t('admin.actions.deactivate') : $t('admin.actions.activate') }}
                        </button>
                        <button class="action-btn action-btn--danger" @click="remove(child)">{{ $t('admin.actions.delete') }}</button>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </section>

      </div>
    </div>

    <!-- ── Formular Modal ───────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="form.open" class="modal-overlay" @click.self="closeForm">
        <div class="dialog">
          <div class="dialog__header">
            <h3 class="dialog__title">{{ form.id ? $t('admin.categories.edit') : $t('admin.categories.add') }}</h3>
            <button class="dialog__close" :aria-label="$t('admin.form.close')" @click="closeForm">
              <span class="icon icon-close" aria-hidden="true" />
            </button>
          </div>

          <div class="dialog__body">
            <div class="form-grid">
              <div class="form-grid__full">
                <UiInput v-model="form.name" :label="$t('admin.form.name')" required @input="autoSlug" />
              </div>
              <div class="form-grid__full">
                <UiInput v-model="form.slug" :label="$t('admin.form.slug')" required />
              </div>

              <div class="form-grid__full">
                <label class="form-label">{{ $t('admin.form.parent_category') }}</label>
                <select v-model="form.parent_id" class="form-select">
                  <option :value="null">{{ $t('admin.form.no_parent') }}</option>
                  <option
                    v-for="cat in parentOptions"
                    :key="cat.id"
                    :value="cat.id"
                  >{{ cat.name }}</option>
                </select>
              </div>

              <div class="form-grid__full">
                <UiInput v-model="form.icon_url" :label="$t('admin.form.icon_url')" />
              </div>

              <div>
                <UiInput v-model="form.sort_order" :label="$t('admin.form.sort_order')" type="number" />
              </div>

              <div class="form-grid__full">
                <label class="form-check">
                  <input v-model="form.is_active" type="checkbox" />
                  <span>{{ $t('admin.form.active_category') }}</span>
                </label>
              </div>
            </div>

            <div v-if="formError" class="form-error">{{ formError }}</div>
          </div>

          <div class="dialog__actions">
            <UiButton :loading="saving" @click="save">{{ $t('admin.form.save') }}</UiButton>
            <button class="action-btn" @click="closeForm">{{ $t('admin.form.cancel') }}</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ── Import Ergebnis Modal ───────────────────────────────── -->
    <Transition name="modal">
      <div v-if="importResult" class="modal-overlay" @click.self="importResult = null">
        <div class="dialog">
          <div class="dialog__header">
            <h3 class="dialog__title">{{ $t('admin.import.title') }}</h3>
            <button class="dialog__close" :aria-label="$t('admin.form.close')" @click="importResult = null">
              <span class="icon icon-close" aria-hidden="true" />
            </button>
          </div>
          <div class="dialog__body">
            <div class="import-result">
              <div class="import-result__row">
                <span class="import-result__label">{{ $t('admin.import.new_categories') }}</span>
                <span class="import-result__value import-result__value--new">{{ importResult.new }}</span>
              </div>
              <div class="import-result__row">
                <span class="import-result__label">{{ $t('admin.import.updated_categories') }}</span>
                <span class="import-result__value import-result__value--updated">{{ importResult.updated }}</span>
              </div>
              <div class="import-result__divider" />
              <div class="import-result__row">
                <span class="import-result__label">{{ $t('admin.import.total') }}</span>
                <span class="import-result__value">{{ importResult.total }}</span>
              </div>
            </div>
            <div v-if="importError" class="form-error">{{ importError }}</div>
          </div>
          <div class="dialog__actions">
            <button class="action-btn" @click="importResult = null">{{ $t('admin.form.close') }}</button>
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
        <p class="l-footer__copy">{{ $t('common.copyright_short', { year }) }}</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'

definePageMeta({ middleware: ['auth', 'admin'] })

const { t } = useI18n()
const { fetchAdminCategories, importCategories, exportCategories, createCategory, updateCategory, patchCategory, deleteCategory } = useAdmin()

interface CategoryItem {
  id: number
  name: string
  slug: string
  icon_url: string | null
  sort_order: number
  parent_id: number | null
  is_active: boolean
  games_count?: number
  children: CategoryItem[]
}

const year = new Date().getFullYear()
const loading = ref(true)
const saving = ref(false)
const importing = ref(false)
const exporting = ref(false)
const formError = ref('')
const importError = ref('')
const importResult = ref<{ new: number; updated: number; total: number } | null>(null)
const categories = ref<CategoryItem[]>([])

const totalCount = computed(() =>
  categories.value.reduce((n, c) => n + 1 + (c.children?.length ?? 0), 0)
)

// Top-level categories available as parents (exclude the category being edited)
const parentOptions = computed(() =>
  categories.value.filter(c => c.id !== form.id)
)

const form = reactive({
  open: false,
  id: null as number | null,
  name: '',
  slug: '',
  icon_url: '',
  sort_order: '0' as string | number,
  parent_id: null as number | null,
  is_active: true,
})

onMounted(load)

async function load() {
  loading.value = true
  try {
    const data = await fetchAdminCategories()
    categories.value = data.data as CategoryItem[]
  } finally {
    loading.value = false
  }
}

function autoSlug() {
  if (form.id) return // don't overwrite slug when editing
  form.slug = form.name
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '')
}

function openCreate() {
  Object.assign(form, { open: true, id: null, name: '', slug: '', icon_url: '', sort_order: '0', parent_id: null, is_active: true })
  formError.value = ''
}

function openEdit(cat: CategoryItem) {
  Object.assign(form, {
    open: true, id: cat.id,
    name: cat.name, slug: cat.slug,
    icon_url: cat.icon_url ?? '',
    sort_order: String(cat.sort_order),
    parent_id: cat.parent_id,
    is_active: cat.is_active,
  })
  formError.value = ''
}

function closeForm() { form.open = false }

async function save() {
  saving.value = true
  formError.value = ''
  try {
    const payload: Record<string, unknown> = {
      name: form.name,
      slug: form.slug,
      sort_order: Number(form.sort_order),
      parent_id: form.parent_id,
      is_active: form.is_active,
    }
    if (form.icon_url) payload.icon_url = form.icon_url

    form.id ? await updateCategory(form.id, payload) : await createCategory(payload)
    await load()
    closeForm()
  } catch (err: unknown) {
    formError.value = (err as { message?: string }).message ?? t('common.error.save')
  } finally {
    saving.value = false
  }
}

async function doImport(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  importing.value = true
  importError.value = ''
  try {
    const result = await importCategories(file)
    importResult.value = result
    await load()
  } catch (err: unknown) {
    importError.value = (err as { message?: string }).message ?? t('common.error.import_failed')
    importResult.value = { new: 0, updated: 0, total: 0 }
  } finally {
    importing.value = false
    ;(e.target as HTMLInputElement).value = ''
  }
}

async function doExport() {
  exporting.value = true
  try { await exportCategories() }
  catch (err: unknown) { alert((err as { message?: string }).message ?? t('common.error.export_failed')) }
  finally { exporting.value = false }
}

async function toggleActive(cat: CategoryItem) {
  await patchCategory(cat.id, { is_active: !cat.is_active })
  await load()
}

async function remove(cat: CategoryItem) {
  await deleteCategory(cat.id)
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

.hero-actions { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }

.hero-btn {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; font-family: inherit;
  background: $amber; color: #0F0E0C; border: none; border-radius: 8px; cursor: pointer;
  transition: opacity 0.2s;
  .icon { font-size: 1rem; }
  &:hover { opacity: 0.88; }
  &:disabled { opacity: 0.5; cursor: not-allowed; }
  &--secondary {
    background: transparent; color: $hero-text;
    border: 1px solid rgba(238,232,223,0.2);
    &:hover { background: rgba(238,232,223,0.06); opacity: 1; }
  }
  &--loading { opacity: 0.6; cursor: not-allowed; pointer-events: none; }
}

.hero-file-input { display: none; }

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

.child-row {
  td { background: rgba(0,0,0,0.15); }
  &:hover td { background: rgba(0,0,0,0.22) !important; }
}

.child-indent {
  display: inline-block; width: 1.1rem; height: 1px;
  background: var(--divider); margin-right: 0.5rem; vertical-align: middle; position: relative;
  &::before { content: ''; position: absolute; left: 0; top: -8px; width: 1px; height: 8px; background: var(--divider); }
}

.text-mono { font-family: monospace; font-size: 0.8rem; color: var(--secondary-text); }
.text-muted { color: var(--secondary-text); }

.badge { display: inline-flex; align-items: center; padding: 0.2rem 0.6rem; font-size: 0.75rem; font-weight: 600; border-radius: 999px; background: rgba(255,255,255,0.06); color: var(--secondary-text); border: 1px solid var(--divider); }
.badge-success { background: rgba(34,197,94,0.08); color: #4ade80; border-color: rgba(34,197,94,0.25); }

.action-row { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.action-btn {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 600; font-family: inherit;
  color: var(--primary-text); background: var(--background); border: 1px solid var(--divider); border-radius: 7px; cursor: pointer; transition: border-color 0.2s, color 0.2s; white-space: nowrap;
  .icon { font-size: 0.875rem; }
  &:hover { border-color: var(--accent-color); color: var(--accent-text); }
  &--danger { color: #f87171; border-color: rgba(239,68,68,0.25); background: rgba(239,68,68,0.05); &:hover { border-color: rgba(239,68,68,0.5); color: #fca5a5; } }
  &--warn   { color: #fb923c; border-color: rgba(251,146,60,0.25); background: rgba(251,146,60,0.05); &:hover { border-color: rgba(251,146,60,0.5); } }
  &--ok     { color: #4ade80; border-color: rgba(34,197,94,0.25); background: rgba(34,197,94,0.05); &:hover { border-color: rgba(34,197,94,0.5); } }
  &:disabled { opacity: 0.4; cursor: not-allowed; }
}

// ─── Modal ────────────────────────────────────────────────────────
.modal-overlay {
  position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center; padding: 1.5rem; overflow-y: auto;
}
.dialog {
  background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 16px; padding: 1.75rem; width: 100%; max-width: 480px; box-shadow: 0 25px 60px rgba(0,0,0,0.4);
  &__header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.5rem; }
  &__title { font-size: 1.05rem; font-weight: 700; letter-spacing: -0.02em; color: var(--primary-text); }
  &__close { display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: transparent; border: none; border-radius: 6px; color: var(--secondary-text); cursor: pointer; transition: background 0.15s, color 0.15s; .icon { font-size: 1.125rem; } &:hover { background: var(--background); color: var(--primary-text); } }
  &__body { margin-bottom: 1.5rem; }
  &__actions { display: flex; gap: 0.75rem; }
}

.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; .dialog { transition: opacity 0.2s ease, transform 0.2s ease; } }
.modal-enter-from, .modal-leave-to { opacity: 0; .dialog { opacity: 0; transform: translateY(8px) scale(0.98); } }

// ─── Form Elements ────────────────────────────────────────────────
.form-grid {
  display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem 1rem;
  @media (max-width: 500px) { grid-template-columns: 1fr; }
  &__full { grid-column: 1 / -1; }
}
.form-label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--secondary-text); margin-bottom: 0.4rem; letter-spacing: 0.03em; }
.form-select { display: block; width: 100%; height: 40px; padding: 0 0.75rem; border: 1px solid var(--divider); border-radius: 8px; background: var(--background); color: var(--primary-text); font-size: 0.875rem; font-family: inherit; cursor: pointer; transition: border-color 0.2s; &:focus { outline: none; border-color: var(--accent-color); } }
.form-check { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--primary-text); cursor: pointer; user-select: none; input { accent-color: var(--accent-color); width: 15px; height: 15px; cursor: pointer; } }
.form-error { margin-top: 0.75rem; padding: 0.75rem 1rem; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); border-radius: 8px; color: #f87171; font-size: 0.875rem; }

// ─── Import Result ────────────────────────────────────────────────
.import-result {
  display: flex; flex-direction: column; gap: 0; padding: 0.25rem 0;
  &__row { display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 0; }
  &__label { font-size: 0.9rem; color: var(--secondary-text); }
  &__value { font-size: 1rem; font-weight: 700; color: var(--primary-text); }
  &__value--new { color: #4ade80; }
  &__value--updated { color: $amber; }
  &__divider { height: 1px; background: var(--divider); margin: 0.25rem 0; }
}

// ─── Footer ───────────────────────────────────────────────────────
.l-footer { background: $hero-bg; border-top: 1px solid $hero-divider; padding: 1.75rem 1.5rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; } &__brand { display: flex; align-items: center; gap: 0.4rem; } &__hex { font-size: 1.1rem; color: $amber; } &__name { font-size: 0.9rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em; } &__copy { font-size: 0.8rem; color: $hero-muted-50; padding-bottom: 0; } }
</style>
