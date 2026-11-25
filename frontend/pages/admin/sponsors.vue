<template>
  <div class="admin-page">
    <!-- ── Hero ───────────────────────────────────────────────────── -->
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <AdminBreadcrumb :label="$t('admin.sponsors.breadcrumb')" />
        <div class="page-hero__row">
          <h1 class="page-hero__title">{{ $t('admin.sponsors.title') }}</h1>
          <div class="hero-actions">
            <button class="hero-btn" @click="openCreate">
              <svg class="icon-svg" aria-hidden="true"><use href="/svg-icons/icons.svg#add" /></svg>
              {{ $t('admin.sponsors.add') }}
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Content ────────────────────────────────────────────────── -->
    <div class="admin-content">
      <div class="admin-content__inner">
        <div v-if="loading" class="admin-state">
          <div class="spinner" />
        </div>

        <section v-else class="dash-section">
          <header class="dash-section__header">
            <h2 class="dash-section__title">{{ $t('admin.sponsors.all') }}</h2>
            <span class="dash-section__count">{{ sponsors.length }}</span>
            <span v-if="sponsors.length > 1" class="dash-section__hint">
              {{ $t('admin.sponsors.order_hint') }}
            </span>
          </header>

          <div v-if="!sponsors.length" class="dash-empty">
            <svg class="icon-svg dash-empty__icon" aria-hidden="true">
              <use href="/svg-icons/icons.svg#handshake" />
            </svg>
            <p class="dash-empty__text">{{ $t('admin.sponsors.empty') }}</p>
          </div>

          <div v-else class="table-wrap">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>
                    <span class="sr-only">{{ $t('admin.sponsors.order_col') }}</span>
                  </th>
                  <th>{{ $t('admin.sponsors.logo_col') }}</th>
                  <th>{{ $t('admin.table.name') }}</th>
                  <th>{{ $t('admin.sponsors.link_col') }}</th>
                  <th>{{ $t('admin.sponsors.active_col') }}</th>
                  <th>{{ $t('admin.table.actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(sponsor, index) in sponsors"
                  :key="sponsor.id"
                  class="sponsor-row"
                  :class="{
                    'is-inactive': !sponsor.is_active,
                    'is-dragging': dragId === sponsor.id,
                    'is-drop-target': dropIndex === index && dragId !== sponsor.id,
                  }"
                  :draggable="dragArmed"
                  @dragstart="onDragStart($event, sponsor.id)"
                  @dragover.prevent="dropIndex = index"
                  @drop.prevent="onDrop(index)"
                  @dragend="onDragEnd"
                >
                  <td class="order-cell">
                    <div class="order-ctrl">
                      <span
                        class="order-ctrl__handle"
                        aria-hidden="true"
                        @mousedown="dragArmed = true"
                        @mouseup="dragArmed = false"
                      >
                        <svg class="icon-svg">
                          <use href="/svg-icons/icons.svg#drag_indicator" />
                        </svg>
                      </span>
                      <button
                        class="order-ctrl__btn"
                        :disabled="index === 0"
                        :aria-label="$t('admin.sponsors.move_up', { name: sponsor.name })"
                        @click="move(index, -1)"
                      >
                        <svg class="icon-svg" aria-hidden="true">
                          <use href="/svg-icons/icons.svg#keyboard_arrow_up" />
                        </svg>
                      </button>
                      <button
                        class="order-ctrl__btn"
                        :disabled="index === sponsors.length - 1"
                        :aria-label="$t('admin.sponsors.move_down', { name: sponsor.name })"
                        @click="move(index, 1)"
                      >
                        <svg class="icon-svg" aria-hidden="true">
                          <use href="/svg-icons/icons.svg#keyboard_arrow_down" />
                        </svg>
                      </button>
                    </div>
                  </td>
                  <td>
                    <span
                      v-if="sponsor.logo_url"
                      class="logo-chip"
                      :class="sponsor.logo_on_light ? 'logo-chip--light' : 'logo-chip--dark'"
                    >
                      <img :src="sponsor.logo_url" :alt="sponsor.name" loading="lazy" />
                    </span>
                    <span v-else class="logo-chip logo-chip__none">—</span>
                  </td>
                  <td class="dash-table__primary">{{ sponsor.name }}</td>
                  <td class="link-cell">
                    <a
                      v-if="sponsor.url"
                      :href="sponsor.url"
                      target="_blank"
                      rel="noopener noreferrer"
                      :title="sponsor.url"
                      >{{ prettyUrl(sponsor.url) }}</a
                    >
                    <span v-else>—</span>
                  </td>
                  <td>
                    <UiSwitch
                      :model-value="sponsor.is_active"
                      :disabled="togglingId === sponsor.id"
                      @update:model-value="toggle(sponsor)"
                    />
                  </td>
                  <td>
                    <div class="action-row">
                      <button class="action-btn" @click="openEdit(sponsor)">
                        {{ $t('admin.actions.edit') }}
                      </button>
                      <button class="action-btn action-btn--danger" @click="remove(sponsor)">
                        {{ $t('admin.actions.delete') }}
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </div>

    <!-- ── Sponsor Form Modal ─────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="form.open" class="modal-overlay" @click.self="closeForm">
          <div class="dialog dialog--wide">
            <div class="dialog__header">
              <h3 class="dialog__title">
                {{ form.id ? $t('admin.sponsors.edit') : $t('admin.sponsors.add') }}
              </h3>
              <button class="dialog__close" :aria-label="$t('admin.form.close')" @click="closeForm">
                <svg class="icon-svg" aria-hidden="true">
                  <use href="/svg-icons/icons.svg#close" />
                </svg>
              </button>
            </div>

            <div class="dialog__body">
              <div class="form-grid">
                <div class="form-grid__full">
                  <UiInput v-model="form.name" :label="$t('admin.sponsors.form.name')" required />
                </div>

                <div class="form-grid__full">
                  <UiInput
                    v-model="form.url"
                    type="url"
                    :label="$t('admin.sponsors.form.url')"
                    placeholder="https://"
                  />
                </div>

                <div class="form-grid__full">
                  <label class="form-label">{{ $t('admin.sponsors.form.logo') }}</label>
                  <div class="file-uploader">
                    <div
                      class="drop-zone"
                      :class="{ 'drag-over': isDraggingFile }"
                      @dragover.prevent="isDraggingFile = true"
                      @dragleave.prevent="isDraggingFile = false"
                      @drop.prevent="onFileDrop"
                      @click="logoInputRef?.click()"
                    >
                      <input
                        ref="logoInputRef"
                        type="file"
                        :accept="ACCEPTED_TYPES.join(',')"
                        style="display: none"
                        @change="onFileChange"
                      />
                      <div class="drop-zone-content">
                        <div class="icon-container">
                          <svg class="icon-svg">
                            <use href="/svg-icons/icons.svg#add_photo_alternate" />
                          </svg>
                        </div>
                        <span class="primary-text">{{ $t('admin.form.image_hint') }}</span>
                        <span class="secondary-text">{{
                          $t('admin.sponsors.form.logo_formats')
                        }}</span>
                      </div>
                    </div>

                    <div v-if="form.logoFile || form.existingLogoUrl" class="file-list">
                      <div class="file-item">
                        <div class="file-item-header">
                          <div class="file-info">
                            <div class="file-details">
                              <span class="file-name">{{
                                form.logoFile
                                  ? form.logoFile.name
                                  : $t('admin.sponsors.form.logo_current')
                              }}</span>
                              <span class="file-size">{{
                                form.logoFile ? formatFileSize(form.logoFile.size) : ''
                              }}</span>
                            </div>
                          </div>
                          <button type="button" class="remove-btn" @click.stop="removeLogo">
                            <svg class="icon-svg"><use href="/svg-icons/icons.svg#close" /></svg>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Pick the plate by looking at it, not by guessing from a label -->
                  <template v-if="logoPreviewUrl">
                    <label class="form-label" style="margin-top: 1rem">
                      {{ $t('admin.sponsors.form.plate') }}
                    </label>
                    <div class="plate-preview" role="radiogroup">
                      <button
                        v-for="variant in plateVariants"
                        :key="variant.light ? 'light' : 'dark'"
                        type="button"
                        role="radio"
                        class="plate-preview__option"
                        :class="[
                          variant.light
                            ? 'plate-preview__option--light'
                            : 'plate-preview__option--dark',
                          { 'is-selected': form.logo_on_light === variant.light },
                        ]"
                        :aria-checked="form.logo_on_light === variant.light"
                        @click="form.logo_on_light = variant.light"
                      >
                        <span class="plate-preview__plate">
                          <img :src="logoPreviewUrl" alt="" />
                        </span>
                        <span class="plate-preview__label">{{ $t(variant.label) }}</span>
                      </button>
                    </div>
                  </template>
                </div>

                <div class="form-grid__full">
                  <UiSwitch v-model="form.is_active" :label="$t('admin.sponsors.form.active')" />
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
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import type { ApiSponsor } from '~/types/api'

definePageMeta({ middleware: ['auth', 'admin'] })

const { t } = useI18n()
const { confirmDialog, notify } = useDialogs()
const {
  fetchAdminSponsors,
  createSponsor,
  updateSponsor,
  toggleSponsor,
  reorderSponsors,
  deleteSponsor,
} = useSponsors()

const ACCEPTED_TYPES = ['image/png', 'image/jpeg', 'image/webp', 'image/gif']

const plateVariants = [
  { light: false, label: 'admin.sponsors.form.plate_dark' },
  { light: true, label: 'admin.sponsors.form.plate_light' },
]

const sponsors = ref<ApiSponsor[]>([])
const loading = ref(true)
const saving = ref(false)
const formError = ref('')
const togglingId = ref<number | null>(null)
const isDraggingFile = ref(false)
const logoInputRef = ref<HTMLInputElement | null>(null)

const form = reactive({
  open: false,
  id: null as number | null,
  name: '',
  url: '',
  logo_on_light: false,
  is_active: true,
  logoFile: null as File | null,
  existingLogoUrl: null as string | null,
  removeLogo: false,
})

// ── Logo preview ────────────────────────────────────────────────
const objectUrl = ref<string | null>(null)
watch(
  () => form.logoFile,
  (file) => {
    if (objectUrl.value) URL.revokeObjectURL(objectUrl.value)
    objectUrl.value = file ? URL.createObjectURL(file) : null
  }
)
onBeforeUnmount(() => {
  if (objectUrl.value) URL.revokeObjectURL(objectUrl.value)
})
const logoPreviewUrl = computed(() => objectUrl.value ?? form.existingLogoUrl)

function prettyUrl(url: string) {
  return url.replace(/^https?:\/\/(www\.)?/, '').replace(/\/$/, '')
}

function formatFileSize(bytes: number) {
  return bytes < 1024 * 1024
    ? `${(bytes / 1024).toFixed(1)} KB`
    : `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

// ── Form ────────────────────────────────────────────────────────
function openCreate() {
  Object.assign(form, {
    open: true,
    id: null,
    name: '',
    url: '',
    logo_on_light: false,
    is_active: true,
    logoFile: null,
    existingLogoUrl: null,
    removeLogo: false,
  })
  formError.value = ''
}

function openEdit(sponsor: ApiSponsor) {
  Object.assign(form, {
    open: true,
    id: sponsor.id,
    name: sponsor.name,
    url: sponsor.url ?? '',
    logo_on_light: sponsor.logo_on_light,
    is_active: sponsor.is_active,
    logoFile: null,
    existingLogoUrl: sponsor.logo_url,
    removeLogo: false,
  })
  formError.value = ''
}

function closeForm() {
  form.open = false
}

function setLogo(file: File | undefined) {
  if (!file) return
  if (!ACCEPTED_TYPES.includes(file.type)) {
    formError.value = t('admin.sponsors.errors.format')
    return
  }
  formError.value = ''
  form.logoFile = file
  form.existingLogoUrl = null
  form.removeLogo = false
}

function onFileChange(e: Event) {
  setLogo((e.target as HTMLInputElement).files?.[0])
}

function onFileDrop(e: DragEvent) {
  isDraggingFile.value = false
  setLogo(e.dataTransfer?.files?.[0])
}

function removeLogo() {
  form.logoFile = null
  form.existingLogoUrl = null
  form.removeLogo = true
  if (logoInputRef.value) logoInputRef.value.value = ''
}

function buildFormData() {
  const fd = new FormData()
  fd.append('name', form.name.trim())
  fd.append('url', form.url.trim())
  fd.append('logo_on_light', form.logo_on_light ? '1' : '0')
  fd.append('is_active', form.is_active ? '1' : '0')
  if (form.logoFile) fd.append('logo', form.logoFile)
  if (form.removeLogo) fd.append('remove_logo', '1')
  return fd
}

async function save() {
  if (!form.name.trim()) {
    formError.value = t('admin.sponsors.errors.name')
    return
  }
  const url = form.url.trim()
  if (url && !/^https?:\/\/\S+\.\S+/i.test(url)) {
    formError.value = t('admin.sponsors.errors.url')
    return
  }
  formError.value = ''
  saving.value = true
  try {
    if (form.id) {
      const updated = await updateSponsor(form.id, buildFormData())
      const idx = sponsors.value.findIndex((s) => s.id === form.id)
      if (idx !== -1) sponsors.value[idx] = updated
    } else {
      sponsors.value.push(await createSponsor(buildFormData()))
    }
    closeForm()
  } catch (err: unknown) {
    // e.g. a logo that is too large — say why, not just that it failed
    const message = (err as { message?: unknown })?.message
    formError.value =
      typeof message === 'string' && message ? message : t('admin.sponsors.errors.save')
  } finally {
    saving.value = false
  }
}

// ── Active toggle ───────────────────────────────────────────────
async function toggle(sponsor: ApiSponsor) {
  togglingId.value = sponsor.id
  try {
    const updated = await toggleSponsor(sponsor.id)
    sponsor.is_active = updated.is_active
  } catch {
    notify(t('admin.sponsors.errors.save'), 'error')
  } finally {
    togglingId.value = null
  }
}

// ── Ordering ────────────────────────────────────────────────────
// The handle arms dragging, so text in the row stays selectable and the
// switch/buttons don't start a drag. Up/down buttons cover touch devices,
// where HTML5 drag & drop doesn't fire.
const dragArmed = ref(false)
const dragId = ref<number | null>(null)
const dropIndex = ref<number | null>(null)

function onDragStart(e: DragEvent, id: number) {
  dragId.value = id
  e.dataTransfer?.setData('text/plain', String(id))
  if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move'
}

function onDragEnd() {
  dragArmed.value = false
  dragId.value = null
  dropIndex.value = null
}

function onDrop(target: number) {
  const from = sponsors.value.findIndex((s) => s.id === dragId.value)
  onDragEnd()
  if (from === -1 || from === target) return
  const list = [...sponsors.value]
  const [item] = list.splice(from, 1)
  list.splice(target, 0, item!)
  commitOrder(list)
}

function move(index: number, delta: -1 | 1) {
  const target = index + delta
  if (target < 0 || target >= sponsors.value.length) return
  const list = [...sponsors.value]
  ;[list[index], list[target]] = [list[target]!, list[index]!]
  commitOrder(list)
}

async function commitOrder(list: ApiSponsor[]) {
  const previous = sponsors.value
  sponsors.value = list
  try {
    await reorderSponsors(list.map((s) => s.id))
  } catch {
    sponsors.value = previous
    notify(t('admin.sponsors.errors.order'), 'error')
  }
}

// ── Delete (soft) ───────────────────────────────────────────────
async function remove(sponsor: ApiSponsor) {
  const ok = await confirmDialog({
    title: t('admin.sponsors.delete_title'),
    message: t('admin.sponsors.confirm_delete', { name: sponsor.name }),
    confirmLabel: t('admin.actions.delete'),
    danger: true,
  })
  if (!ok) return
  try {
    await deleteSponsor(sponsor.id)
    sponsors.value = sponsors.value.filter((s) => s.id !== sponsor.id)
    notify(t('admin.sponsors.deleted', { name: sponsor.name }))
  } catch {
    notify(t('admin.sponsors.errors.delete'), 'error')
  }
}

onMounted(async () => {
  try {
    sponsors.value = await fetchAdminSponsors()
  } catch (err: unknown) {
    notify((err as { message?: string }).message ?? t('admin.errors.load'), 'error')
  } finally {
    loading.value = false
  }
})
</script>

<style lang="scss" scoped>
$hero-bg: var(--background);
$nav-height: 64px;
$amber-08: rgba(212, 146, 30, 0.08);
$amber-14: rgba(212, 146, 30, 0.14);
$amber-25: rgba(212, 146, 30, 0.25);
$amber-glow: rgba(212, 146, 30, 0.16);
$hero-text: var(--primary-text);
$hero-muted: var(--secondary-text);

.admin-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--background);
}

.page-hero {
  position: relative;
  background: $hero-bg;
  padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem;
  overflow: hidden;
  &__backdrop {
    position: absolute;
    inset: 0;
    pointer-events: none;
  }
  &__glow {
    position: absolute;
    width: 400px;
    height: 400px;
    top: -120px;
    right: -60px;
    border-radius: 50%;
    filter: blur(90px);
    background: $amber-glow;
  }
  &__dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
    background-size: 24px 24px;
    mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%);
  }
  &__body {
    position: relative;
    z-index: 1;
    max-width: 1100px;
    margin: 0 auto;
  }
  &__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
  }
  &__title {
    font-size: clamp(1.5rem, 3vw, 2.25rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    color: $hero-text;
    margin: 0;
  }
}

.hero-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.hero-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  font-family: inherit;
  background: $amber;
  color: #0f0e0c;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: opacity 0.2s;
  .icon {
    font-size: 1rem;
  }
  &:hover {
    opacity: 0.88;
  }
  &:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
}

.admin-content {
  flex: 1;
  padding: 2rem 1.5rem 4rem;
  &__inner {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }
}
.admin-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 200px;
}

.dash-section {
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 14px;
  overflow: hidden;
}
.dash-section__header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1.1rem 1.5rem;
  border-bottom: 1px solid var(--divider);
}
.dash-section__title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--primary-text);
  margin: 0;
  letter-spacing: -0.02em;
}
.dash-section__count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 22px;
  height: 22px;
  padding: 0 6px;
  font-size: 0.75rem;
  font-weight: 700;
  color: $amber;
  background: $amber-08;
  border: 1px solid $amber-25;
  border-radius: 999px;
}

.dash-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 3rem 1.5rem;
  color: var(--secondary-text);
  &__icon {
    width: 2rem;
    height: 2rem;
    opacity: 0.35;
  }
  &__text {
    font-size: 0.9rem;
    padding-bottom: 0;
  }
}

.table-wrap {
  overflow-x: auto;
}

.dash-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
  th {
    padding: 0.7rem 1.5rem;
    text-align: left;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--secondary-text);
    background: var(--background);
    border-bottom: 1px solid var(--divider);
    white-space: nowrap;
  }
  td {
    padding: 0.9rem 1.5rem;
    color: var(--primary-text);
    border-bottom: 1px solid var(--divider);
    vertical-align: middle;
  }
  tbody tr:last-child td {
    border-bottom: none;
  }
  tbody tr {
    transition: background 0.15s;
    &:hover {
      background: var(--background);
    }
  }
  &__primary {
    font-weight: 600;
  }
}

.action-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.75rem;
  font-size: 0.8rem;
  font-weight: 600;
  font-family: inherit;
  color: var(--primary-text);
  background: var(--background);
  border: 1px solid var(--divider);
  border-radius: 7px;
  cursor: pointer;
  transition:
    border-color 0.2s,
    color 0.2s;
  white-space: nowrap;
  .icon {
    font-size: 0.875rem;
  }
  &:hover {
    border-color: var(--accent-color);
    color: var(--accent-text);
  }
  &--danger {
    color: var(--error-text);
    border-color: rgba(239, 68, 68, 0.25);
    background: rgba(239, 68, 68, 0.05);
    &:hover {
      border-color: rgba(239, 68, 68, 0.5);
      color: var(--error-text-hover);
    }
  }
  &:disabled {
    opacity: 0.4;
    cursor: not-allowed;
  }
}

// ─── Modal ────────────────────────────────────────────────────────
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 200;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}
.dialog {
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 16px;
  padding: 1.75rem;
  width: 100%;
  max-width: 480px;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
  &--wide {
    max-width: 700px;
  }
  &__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1.5rem;
  }
  &__title {
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--primary-text);
  }
  &__close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: transparent;
    border: none;
    border-radius: 6px;
    color: var(--secondary-text);
    cursor: pointer;
    transition:
      background 0.15s,
      color 0.15s;
    .icon {
      font-size: 1.125rem;
    }
    &:hover {
      background: var(--background);
      color: var(--primary-text);
    }
  }
  &__body {
    margin-bottom: 1.5rem;
    max-height: 65vh;
    overflow-y: auto;
  }
  &__actions {
    display: flex;
    gap: 0.75rem;
  }
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
  .dialog {
    transition:
      opacity 0.2s ease,
      transform 0.2s ease;
  }
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  .dialog {
    opacity: 0;
    transform: translateY(8px) scale(0.98);
  }
}

// ─── Form Elements ────────────────────────────────────────────────
.form-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem 1rem;
  @media (max-width: 600px) {
    grid-template-columns: 1fr;
  }
  &__full {
    grid-column: 1 / -1;
  }
  &__center {
    display: flex;
    align-items: flex-end;
    padding-bottom: 0.25rem;
  }
}

.form-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--secondary-text);
  margin-bottom: 0.4rem;
  letter-spacing: 0.03em;
}

.form-check {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: var(--primary-text);
  cursor: pointer;
  user-select: none;
  input {
    accent-color: var(--accent-color);
    width: 15px;
    height: 15px;
    cursor: pointer;
  }
}

.form-error {
  margin-top: 0.75rem;
  padding: 0.75rem 1rem;
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.25);
  border-radius: 8px;
  color: var(--error-text);
  font-size: 0.875rem;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
}

// ─── Sponsor specifics ────────────────────────────────────────────
// Plate colours are fixed per variant, not theme-driven: the admin chose
// "light" or "dark" against how the logo looks, so the public page must
// render exactly that regardless of the viewer's theme.
$plate-light: #f4efe6;
$plate-dark: #181613;

.dash-section__hint {
  margin-left: auto;
  font-size: 0.75rem;
  color: var(--secondary-text);
  @media (max-width: 600px) {
    display: none;
  }
}

.sponsor-row {
  &.is-inactive td:not(:first-child):not(:last-child) {
    opacity: 0.5;
  }
  &.is-dragging {
    opacity: 0.35;
  }
  &.is-drop-target td {
    box-shadow: inset 0 2px 0 $amber;
  }
}

.order-cell {
  width: 1%;
  white-space: nowrap;
}
.order-ctrl {
  display: inline-flex;
  align-items: center;
  gap: 0.15rem;
  &__handle {
    display: inline-flex;
    padding: 0.25rem;
    color: var(--secondary-text);
    cursor: grab;
    border-radius: 6px;
    &:active {
      cursor: grabbing;
    }
    .icon-svg {
      width: 1.1rem;
      height: 1.1rem;
    }
  }
  &__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    padding: 0;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 6px;
    color: var(--secondary-text);
    cursor: pointer;
    transition:
      color 0.15s,
      border-color 0.15s;
    .icon-svg {
      width: 1rem;
      height: 1rem;
    }
    &:hover:not(:disabled) {
      color: var(--accent-text);
      border-color: var(--divider);
    }
    &:disabled {
      opacity: 0.25;
      cursor: default;
    }
  }
}

.logo-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 84px;
  height: 44px;
  padding: 6px 10px;
  border-radius: 8px;
  border: 1px solid var(--divider);
  &--light {
    background: $plate-light;
  }
  &--dark {
    background: $plate-dark;
  }
  img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
  }
  &__none {
    font-size: 0.7rem;
    color: var(--secondary-text);
    background: transparent;
  }
}

.link-cell {
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 0.8rem;
  color: var(--secondary-text);
}

.plate-preview {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  margin-top: 0.75rem;
  @media (max-width: 600px) {
    grid-template-columns: 1fr;
  }
  &__option {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    padding: 0;
    font-family: inherit;
    background: none;
    border: none;
    cursor: pointer;
    text-align: left;
  }
  &__plate {
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 16 / 9;
    padding: 1.25rem 1.75rem;
    border-radius: 12px;
    border: 2px solid transparent;
    outline: 1px solid var(--divider);
    transition:
      border-color 0.2s,
      transform 0.2s;
    img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }
  }
  &__option--light &__plate {
    background: $plate-light;
  }
  &__option--dark &__plate {
    background: $plate-dark;
  }
  &__option.is-selected &__plate {
    border-color: $amber;
  }
  &__option:hover &__plate {
    transform: translateY(-2px);
  }
  &__label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--secondary-text);
  }
  &__option.is-selected &__label {
    color: var(--accent-text);
  }
}
</style>
