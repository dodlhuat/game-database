<template>
  <div class="admin-page">
    <!-- ── Hero ───────────────────────────────────────────────────── -->
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <AdminBreadcrumb :label="$t('admin.events.breadcrumb')" />
        <div class="page-hero__row">
          <h1 class="page-hero__title">{{ $t('admin.events.title') }}</h1>
          <div class="hero-actions">
            <button class="hero-btn" @click="openCreate">
              <svg class="icon-svg" aria-hidden="true"><use href="/svg-icons/icons.svg#add" /></svg>
              {{ $t('admin.events.add') }}
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
            <h2 class="dash-section__title">{{ $t('admin.events.all') }}</h2>
            <span class="dash-section__count">{{ events.length }}</span>
          </header>

          <div v-if="!events.length" class="dash-empty">
            <svg class="icon-svg dash-empty__icon" aria-hidden="true">
              <use href="/svg-icons/icons.svg#calendar_today" />
            </svg>
            <p class="dash-empty__text">{{ $t('admin.events.empty') }}</p>
          </div>

          <div v-else class="table-wrap">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>{{ $t('admin.table.name') }}</th>
                  <th>{{ $t('admin.events.date_col') }}</th>
                  <th>{{ $t('admin.events.time_col') }}</th>
                  <th>{{ $t('admin.table.actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="event in events" :key="event.id">
                  <td class="dash-table__primary">
                    {{ event.title }}
                    <span v-if="event.is_pickup" class="pickup-tag">
                      <svg class="icon-svg" aria-hidden="true">
                        <use href="/svg-icons/icons.svg#location_on" />
                      </svg>
                      {{ event.pickup_location?.name }} ·
                      {{
                        event.max_loans
                          ? $t('admin.events.pickup.booked_of', {
                              n: event.bookings_count ?? 0,
                              max: event.max_loans,
                            })
                          : $t('admin.events.pickup.booked', { n: event.bookings_count ?? 0 })
                      }}
                    </span>
                  </td>
                  <td>{{ formatDate(event.date) }}</td>
                  <td>
                    {{
                      event.is_all_day
                        ? $t('events.all_day')
                        : formatTimeSpan(event.time, event.end_time)
                    }}
                  </td>
                  <td>
                    <div class="action-row">
                      <NuxtLink
                        v-if="event.is_pickup"
                        :to="`/admin/pickup-events/${event.id}`"
                        class="action-btn action-btn--accent"
                      >
                        {{ $t('admin.packing.open') }}
                      </NuxtLink>
                      <button class="action-btn" @click="openEdit(event)">
                        {{ $t('admin.actions.edit') }}
                      </button>
                      <button class="action-btn action-btn--danger" @click="remove(event)">
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

    <!-- ── Event Form Modal ───────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="form.open" class="modal-overlay" @click.self="closeForm">
          <div class="dialog dialog--wide">
            <div class="dialog__header">
              <h3 class="dialog__title">
                {{ form.id ? $t('admin.events.edit') : $t('admin.events.add') }}
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
                  <UiInput v-model="form.title" :label="$t('admin.events.form.title')" required />
                </div>

                <div>
                  <UiDatePicker
                    v-model="form.date"
                    :label="$t('admin.events.form.date')"
                    required
                  />
                </div>

                <div class="form-grid__center">
                  <UiSwitch
                    v-if="!form.is_pickup"
                    v-model="form.is_all_day"
                    :label="$t('events.all_day')"
                  />
                </div>

                <div v-if="!form.is_all_day" class="form-grid__full">
                  <UiTimeSpanPicker
                    v-model:start="form.time"
                    v-model:end="form.end_time"
                    :label="$t('admin.events.form.time_span')"
                    :from-label="$t('admin.events.form.time_from')"
                    :to-label="$t('admin.events.form.time_to')"
                  />
                </div>

                <fieldset class="form-grid__full pickup-box" :class="{ 'is-on': form.is_pickup }">
                  <legend class="pickup-box__legend">
                    {{ $t('admin.events.pickup.title') }}
                  </legend>
                  <UiSwitch
                    v-model="form.is_pickup"
                    :disabled="!personalLocations.length && !form.is_pickup"
                    :label="$t('admin.events.pickup.toggle')"
                  />
                  <p class="form-hint">
                    {{
                      personalLocations.length || form.is_pickup
                        ? $t('admin.events.pickup.hint')
                        : $t('admin.events.pickup.no_locations')
                    }}
                  </p>
                  <p v-if="form.is_pickup && editingBookings > 0" class="pickup-box__note">
                    {{
                      $t('admin.events.pickup.booked_note', { n: editingBookings }, editingBookings)
                    }}
                  </p>
                  <div v-if="form.is_pickup" class="pickup-box__fields">
                    <div>
                      <label class="form-label">{{ $t('admin.events.pickup.location') }}</label>
                      <UiVirtualDropdown
                        v-model="form.pickup_location_id"
                        :options="personalLocationOptions"
                        :placeholder="$t('admin.events.pickup.location_placeholder')"
                      />
                    </div>
                    <div>
                      <UiInput
                        v-model="form.max_loans"
                        type="number"
                        min="1"
                        :label="$t('admin.events.pickup.max_loans')"
                        :placeholder="$t('admin.events.pickup.max_loans_placeholder')"
                      />
                    </div>
                  </div>
                </fieldset>

                <div class="form-grid__full">
                  <label class="form-label">{{ $t('admin.events.form.description') }}</label>
                  <UiRichEditor v-model="form.description" />
                </div>

                <div class="form-grid__full">
                  <label class="form-label">{{ $t('admin.events.form.image') }}</label>
                  <div class="file-uploader">
                    <div
                      class="drop-zone"
                      :class="{ 'drag-over': isDragging }"
                      @dragover.prevent="isDragging = true"
                      @dragleave.prevent="isDragging = false"
                      @drop.prevent="onDrop"
                      @click="imageInputRef?.click()"
                    >
                      <input
                        ref="imageInputRef"
                        type="file"
                        accept="image/*"
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
                        <span class="secondary-text">{{ $t('admin.form.image_formats') }}</span>
                      </div>
                    </div>

                    <div v-if="form.imageFile || form.existingImageUrl" class="file-list">
                      <div class="file-item">
                        <div class="file-item-header">
                          <div class="file-info">
                            <img
                              :src="form.imageFile ? imagePreviewUrl! : form.existingImageUrl!"
                              :alt="$t('admin.form.preview')"
                              style="
                                width: 40px;
                                height: 40px;
                                object-fit: cover;
                                border-radius: 4px;
                                flex-shrink: 0;
                              "
                            />
                            <div class="file-details">
                              <span class="file-name">{{
                                form.imageFile
                                  ? form.imageFile.name
                                  : $t('admin.events.form.image_current')
                              }}</span>
                              <span class="file-size">{{
                                form.imageFile ? formatFileSize(form.imageFile.size) : ''
                              }}</span>
                            </div>
                          </div>
                          <button type="button" class="remove-btn" @click.stop="removeImage">
                            <svg class="icon-svg"><use href="/svg-icons/icons.svg#close" /></svg>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
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
import { ref, reactive, computed, onMounted, watch } from 'vue'
import type { ApiEvent, ApiPickupLocation } from '~/types/api'

definePageMeta({ middleware: ['auth', 'admin'] })

const { t } = useI18n()
const { confirmDialog, notify } = useDialogs()
const { fetchAdminEvents, createEvent, updateEvent, deleteEvent } = useEvents()
const { fetchAdminLocations } = usePickupLocations()

// Only personal locations host pickup events; the current one stays selectable
// even if it was switched off since, so editing an old event keeps working.
const locations = ref<ApiPickupLocation[]>([])
// Bookings of the event being edited: place is fixed, a new time is mailed out
const editingBookings = computed(
  () => events.value.find((e) => e.id === form.id)?.bookings_count ?? 0
)
const personalLocations = computed(() =>
  locations.value.filter((l) => l.is_personal && l.is_active)
)
const personalLocationOptions = computed(() => {
  const list = locations.value.filter(
    (l) => l.is_personal && (l.is_active || String(l.id) === form.pickup_location_id)
  )
  return list.map((l) => ({ label: l.name, value: String(l.id) }))
})

const events = ref<ApiEvent[]>([])
const loading = ref(true)
const saving = ref(false)
const formError = ref('')
const isDragging = ref(false)
const imageInputRef = ref<HTMLInputElement | null>(null)

const form = reactive({
  open: false,
  id: null as number | null,
  title: '',
  date: '',
  time: '',
  end_time: '',
  is_all_day: false,
  description: '',
  imageFile: null as File | null,
  existingImageUrl: null as string | null,
  removeImage: false,
  is_pickup: false,
  pickup_location_id: '' as string,
  max_loans: '' as string,
})

// A pickup is an appointment: it always has a start and an end
watch(
  () => form.is_pickup,
  (on) => {
    if (on) form.is_all_day = false
  }
)

const imagePreviewUrl = computed(() =>
  form.imageFile ? URL.createObjectURL(form.imageFile) : null
)

function formatDate(iso: string) {
  const [y, m, d] = iso.split('-')
  return `${d}.${m}.${y}`
}

function formatTimeSpan(time: string | null, endTime: string | null) {
  if (!time) return '—'
  const [h, m] = time.split(':')
  if (!endTime) return `${h}:${m} Uhr`
  const [eh, em] = endTime.split(':')
  return `${h}:${m}–${eh}:${em} Uhr`
}

function formatFileSize(bytes: number) {
  return bytes < 1024 * 1024
    ? `${(bytes / 1024).toFixed(1)} KB`
    : `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

function openCreate() {
  Object.assign(form, {
    open: true,
    id: null,
    title: '',
    date: '',
    time: '',
    end_time: '',
    is_all_day: false,
    description: '',
    imageFile: null,
    existingImageUrl: null,
    removeImage: false,
    is_pickup: false,
    pickup_location_id: '',
    max_loans: '',
  })
  formError.value = ''
}

function openEdit(event: ApiEvent) {
  Object.assign(form, {
    open: true,
    id: event.id,
    title: event.title,
    date: event.date,
    time: event.time ? event.time.substring(0, 5) : '',
    end_time: event.end_time ? event.end_time.substring(0, 5) : '',
    is_all_day: event.is_all_day,
    description: event.description ?? '',
    imageFile: null,
    existingImageUrl: event.image_url,
    removeImage: false,
    is_pickup: event.is_pickup,
    pickup_location_id: event.pickup_location?.id ? String(event.pickup_location.id) : '',
    max_loans: event.max_loans ? String(event.max_loans) : '',
  })
  formError.value = ''
}

function closeForm() {
  form.open = false
}

function onFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (file) {
    form.imageFile = file
    form.existingImageUrl = null
    form.removeImage = false
  }
}

function onDrop(e: DragEvent) {
  isDragging.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file && file.type.startsWith('image/')) {
    form.imageFile = file
    form.existingImageUrl = null
    form.removeImage = false
  }
}

function removeImage() {
  form.imageFile = null
  form.existingImageUrl = null
  form.removeImage = true
  if (imageInputRef.value) imageInputRef.value.value = ''
}

function buildFormData() {
  const fd = new FormData()
  fd.append('title', form.title)
  fd.append('date', form.date)
  fd.append('is_all_day', form.is_all_day ? '1' : '0')
  if (!form.is_all_day && form.time) fd.append('time', form.time)
  if (!form.is_all_day && form.end_time) fd.append('end_time', form.end_time)
  if (form.description) fd.append('description', form.description)
  if (form.imageFile) fd.append('image', form.imageFile)
  if (form.removeImage) fd.append('remove_image', '1')
  // Empty strings arrive as null, which turns a pickup event back into a regular one
  fd.append('pickup_location_id', form.is_pickup ? form.pickup_location_id : '')
  fd.append('max_loans', form.is_pickup ? String(form.max_loans).trim() : '')
  return fd
}

async function save() {
  if (!form.title || !form.date) {
    formError.value = 'Titel und Datum sind erforderlich.'
    return
  }
  if (!form.is_all_day && (!form.time || !form.end_time)) {
    formError.value = 'Start- und Endzeit sind erforderlich, wenn das Event nicht ganztägig ist.'
    return
  }
  if (!form.is_all_day && form.time >= form.end_time) {
    formError.value = 'Die Endzeit muss nach der Startzeit liegen.'
    return
  }
  if (form.is_pickup && !form.pickup_location_id) {
    formError.value = t('admin.events.pickup.location_required')
    return
  }
  formError.value = ''
  saving.value = true
  try {
    if (form.id) {
      const updated = await updateEvent(form.id, buildFormData())
      const idx = events.value.findIndex((e) => e.id === form.id)
      if (idx !== -1) events.value[idx] = updated
    } else {
      const created = await createEvent(buildFormData())
      events.value.push(created)
      events.value.sort((a, b) => a.date.localeCompare(b.date))
    }
    closeForm()
  } catch (err: unknown) {
    const message = (err as { message?: unknown })?.message
    formError.value = typeof message === 'string' && message ? message : 'Fehler beim Speichern.'
  } finally {
    saving.value = false
  }
}

async function remove(event: ApiEvent) {
  const ok = await confirmDialog({
    title: t('admin.events.delete_title'),
    message: t('admin.events.confirm_delete', { name: event.title }),
    confirmLabel: t('admin.actions.delete'),
    danger: true,
  })
  if (!ok) return
  try {
    await deleteEvent(event.id)
    events.value = events.value.filter((e) => e.id !== event.id)
    notify(t('admin.events.deleted', { name: event.title }))
  } catch (err: unknown) {
    // e.g. a pickup event that still has bookings
    const message = (err as { message?: unknown })?.message
    notify(
      typeof message === 'string' && message ? message : t('admin.events.delete_error'),
      'error'
    )
  }
}

onMounted(async () => {
  try {
    ;[events.value, locations.value] = await Promise.all([
      fetchAdminEvents(),
      fetchAdminLocations(),
    ])
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
  &--accent {
    color: #1a0d00;
    background: $amber;
    border-color: $amber;
    text-decoration: none;
    &:hover {
      color: #1a0d00;
      border-color: $amber;
      filter: brightness(1.05);
    }
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

.form-hint {
  margin: 0;
  padding: 0;
  font-size: 0.8rem;
  line-height: 1.45;
  color: var(--secondary-text);
}

// Pickup appointment: a framed block that lights up once switched on
.pickup-box {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
  margin: 0.25rem 0;
  padding: 1rem 1.1rem 1.1rem;
  border: 1px solid var(--divider);
  border-radius: 12px;
  transition:
    border-color 0.2s ease,
    background-color 0.2s ease;

  &.is-on {
    border-color: $amber-25;
    background: $amber-08;
  }
  &__legend {
    padding: 0 0.4rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--secondary-text);
  }
  &__note {
    margin: 0;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    font-size: 0.8rem;
    line-height: 1.45;
    color: var(--primary-text);
    background: color-mix(in srgb, #{$amber} 12%, transparent);
  }
  &__fields {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 0.75rem 1rem;
    margin-top: 0.25rem;
    animation: pickup-in 0.25s ease-out;
    @media (max-width: 600px) {
      grid-template-columns: 1fr;
    }
  }
}

@keyframes pickup-in {
  from {
    opacity: 0;
    transform: translateY(-4px);
  }
}

.pickup-tag {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  width: fit-content;
  margin-top: 0.3rem;
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--secondary-text);
  .icon-svg {
    width: 0.85rem;
    height: 0.85rem;
    color: $amber;
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
</style>
