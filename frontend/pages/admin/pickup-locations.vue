<template>
  <div class="admin-page">
    <!-- ── Hero ───────────────────────────────────────────────────── -->
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <AdminBreadcrumb :label="$t('admin.pickup_locations.breadcrumb')" />
        <div class="page-hero__row">
          <h1 class="page-hero__title">{{ $t('admin.pickup_locations.title') }}</h1>
          <div class="hero-actions">
            <button class="hero-btn" @click="openCreate">
              <svg class="icon-svg" aria-hidden="true"><use href="/svg-icons/icons.svg#add" /></svg>
              {{ $t('admin.pickup_locations.add') }}
            </button>
          </div>
        </div>
        <p class="page-hero__lead">{{ $t('admin.pickup_locations.lead') }}</p>
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
            <h2 class="dash-section__title">{{ $t('admin.pickup_locations.all') }}</h2>
            <span class="dash-section__count">{{ locations.length }}</span>
            <span v-if="locations.length > 1" class="dash-section__hint">
              {{ $t('admin.pickup_locations.order_hint') }}
            </span>
          </header>

          <div class="table-wrap">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>
                    <span class="sr-only">{{ $t('admin.pickup_locations.order_col') }}</span>
                  </th>
                  <th>{{ $t('admin.pickup_locations.location_col') }}</th>
                  <th>{{ $t('admin.pickup_locations.hours_col') }}</th>
                  <th>{{ $t('admin.pickup_locations.open_col') }}</th>
                  <th>{{ $t('admin.pickup_locations.active_col') }}</th>
                  <th>{{ $t('admin.table.actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(location, index) in locations"
                  :key="location.id"
                  class="location-row"
                  :class="{
                    'is-inactive': !location.is_active,
                    'is-dragging': dragId === location.id,
                    'is-drop-target': dropIndex === index && dragId !== location.id,
                  }"
                  :draggable="dragArmed"
                  @dragstart="onDragStart($event, location.id)"
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
                        :aria-label="$t('admin.pickup_locations.move_up', { name: location.name })"
                        @click="move(index, -1)"
                      >
                        <svg class="icon-svg" aria-hidden="true">
                          <use href="/svg-icons/icons.svg#keyboard_arrow_up" />
                        </svg>
                      </button>
                      <button
                        class="order-ctrl__btn"
                        :disabled="index === locations.length - 1"
                        :aria-label="
                          $t('admin.pickup_locations.move_down', { name: location.name })
                        "
                        @click="move(index, 1)"
                      >
                        <svg class="icon-svg" aria-hidden="true">
                          <use href="/svg-icons/icons.svg#keyboard_arrow_down" />
                        </svg>
                      </button>
                    </div>
                  </td>
                  <td class="location-cell">
                    <span class="location-cell__name">{{ location.name }}</span>
                    <span v-if="location.is_personal" class="access-badge access-badge--personal">
                      <svg class="icon-svg" aria-hidden="true">
                        <use href="/svg-icons/icons.svg#event" />
                      </svg>
                      {{ $t('admin.pickup_locations.personal.badge') }}
                    </span>
                    <span
                      v-if="location.users?.length"
                      class="access-badge"
                      :title="location.users.map((u) => u.name).join(', ')"
                    >
                      <svg class="icon-svg" aria-hidden="true">
                        <use href="/svg-icons/icons.svg#lock" />
                      </svg>
                      {{
                        $t(
                          'admin.pickup_locations.access.badge',
                          { n: location.users.length },
                          location.users.length
                        )
                      }}
                    </span>
                    <span v-if="location.address" class="location-cell__address">{{
                      location.address
                    }}</span>
                    <a
                      v-if="location.map_url"
                      class="location-cell__map"
                      :href="location.map_url"
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      <svg class="icon-svg" aria-hidden="true">
                        <use href="/svg-icons/icons.svg#map" />
                      </svg>
                      {{ $t('admin.pickup_locations.map') }}
                    </a>
                  </td>
                  <td class="hours-cell">{{ location.opening_hours || '—' }}</td>
                  <td>
                    <span
                      class="open-count"
                      :class="{ 'open-count--busy': (location.open_loans_count ?? 0) > 0 }"
                      >{{ location.open_loans_count ?? 0 }}</span
                    >
                  </td>
                  <td>
                    <span class="switch-wrap" :title="lockReason(location) ?? undefined">
                      <UiSwitch
                        :model-value="location.is_active"
                        :disabled="togglingId === location.id || !!lockReason(location)"
                        @update:model-value="toggle(location)"
                      />
                    </span>
                  </td>
                  <td>
                    <div class="action-row">
                      <button class="action-btn" @click="openEdit(location)">
                        {{ $t('admin.actions.edit') }}
                      </button>
                      <button
                        class="action-btn action-btn--danger"
                        :disabled="!!deleteLockReason(location)"
                        :title="deleteLockReason(location) ?? undefined"
                        @click="remove(location)"
                      >
                        {{ $t('admin.actions.delete') }}
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <p class="rule-note">
            <svg class="icon-svg" aria-hidden="true">
              <use href="/svg-icons/icons.svg#info" />
            </svg>
            {{ $t('admin.pickup_locations.rule_note') }}
          </p>
        </section>
      </div>
    </div>

    <!-- ── Location Form Modal ────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="form.open" class="modal-overlay" @click.self="closeForm">
          <div class="dialog dialog--wide">
            <div class="dialog__header">
              <h3 class="dialog__title">
                {{ form.id ? $t('admin.pickup_locations.edit') : $t('admin.pickup_locations.add') }}
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
                  <UiInput
                    v-model="form.name"
                    :label="$t('admin.pickup_locations.form.name')"
                    required
                  />
                </div>

                <div class="form-grid__full input-wrapper">
                  <label for="pl-address">{{ $t('admin.pickup_locations.form.address') }}</label>
                  <textarea id="pl-address" v-model="form.address" rows="2" maxlength="1000" />
                </div>

                <div class="form-grid__full input-wrapper">
                  <label for="pl-hours">{{
                    $t('admin.pickup_locations.form.opening_hours')
                  }}</label>
                  <textarea
                    id="pl-hours"
                    v-model="form.opening_hours"
                    rows="2"
                    maxlength="1000"
                    :placeholder="$t('admin.pickup_locations.form.opening_hours_placeholder')"
                  />
                </div>

                <div class="form-grid__full input-wrapper">
                  <label for="pl-notes">{{ $t('admin.pickup_locations.form.notes') }}</label>
                  <textarea
                    id="pl-notes"
                    v-model="form.notes"
                    rows="2"
                    maxlength="1000"
                    :placeholder="$t('admin.pickup_locations.form.notes_placeholder')"
                  />
                </div>

                <div class="form-grid__full">
                  <UiInput
                    v-model="form.map_url"
                    type="url"
                    :label="$t('admin.pickup_locations.form.map_url')"
                    placeholder="https://"
                  />
                </div>

                <div class="form-grid__full personal">
                  <UiSwitch
                    v-model="form.is_personal"
                    :label="$t('admin.pickup_locations.personal.label')"
                  />
                  <p class="form-hint">{{ $t('admin.pickup_locations.personal.hint') }}</p>
                </div>

                <fieldset class="form-grid__full access">
                  <legend class="access__legend">
                    {{ $t('admin.pickup_locations.access.title') }}
                  </legend>
                  <UiSwitch
                    v-model="form.restricted"
                    :label="$t('admin.pickup_locations.access.restrict')"
                  />
                  <p class="form-hint">
                    {{
                      form.restricted
                        ? $t('admin.pickup_locations.access.restricted_hint')
                        : $t('admin.pickup_locations.access.open_hint')
                    }}
                  </p>
                  <template v-if="form.restricted">
                    <AdminUserPicker v-model="form.users" />
                    <p v-if="!form.users.length" class="access__warn" role="status">
                      <svg class="icon-svg" aria-hidden="true">
                        <use href="/svg-icons/icons.svg#info" />
                      </svg>
                      {{ $t('admin.pickup_locations.access.empty_warning') }}
                    </p>
                  </template>
                </fieldset>

                <div class="form-grid__full">
                  <span class="switch-wrap" :title="formActiveLock ?? undefined">
                    <UiSwitch
                      v-model="form.is_active"
                      :disabled="!!formActiveLock"
                      :label="$t('admin.pickup_locations.form.active')"
                    />
                  </span>
                  <p v-if="formActiveLock" class="form-hint">{{ formActiveLock }}</p>
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
import { ref, reactive, computed, onMounted } from 'vue'
import type { ApiPickupLocation } from '~/types/api'
import type { PickupLocationPayload, PickupLocationUser } from '~/composables/usePickupLocations'

definePageMeta({ middleware: ['auth', 'admin'] })

const { t } = useI18n()
const {
  fetchAdminLocations,
  createLocation,
  updateLocation,
  toggleLocation,
  reorderLocations,
  deleteLocation,
} = usePickupLocations()

const locations = ref<ApiPickupLocation[]>([])
const loading = ref(true)
const saving = ref(false)
const formError = ref('')
const { confirmDialog, notify } = useDialogs()
const togglingId = ref<number | null>(null)

const form = reactive({
  open: false,
  id: null as number | null,
  name: '',
  address: '',
  opening_hours: '',
  notes: '',
  map_url: '',
  is_active: true,
  wasActive: true,
  restricted: false,
  users: [] as PickupLocationUser[],
  is_personal: false,
})

// ── Rules (mirrored from the backend, which enforces them) ──────
// At least one location must stay active, and a location can only be
// switched off once every game borrowed from it has come back.
// Personal locations only work through events, so they never count as the last one
const activeCount = computed(
  () => locations.value.filter((l) => l.is_active && !l.is_personal).length
)

function lockReason(location: ApiPickupLocation): string | null {
  if (!location.is_active) return null
  if (!location.is_personal && activeCount.value <= 1)
    return t('admin.pickup_locations.lock.last_active')
  const open = location.open_loans_count ?? 0
  if (open > 0) return t('admin.pickup_locations.lock.open_loans', { n: open }, open)
  return null
}

function deleteLockReason(location: ApiPickupLocation): string | null {
  const open = location.open_loans_count ?? 0
  if (open > 0) return t('admin.pickup_locations.lock.open_loans', { n: open }, open)
  return lockReason(location)
}

const formActiveLock = computed(() => {
  if (!form.id || !form.wasActive) return null
  const current = locations.value.find((l) => l.id === form.id)
  return current ? lockReason(current) : null
})

function apiMessage(err: unknown, fallback: string): string {
  const message = (err as { message?: unknown })?.message
  return typeof message === 'string' && message ? message : fallback
}

// ── Form ────────────────────────────────────────────────────────
function openCreate() {
  Object.assign(form, {
    open: true,
    id: null,
    name: '',
    address: '',
    opening_hours: '',
    notes: '',
    map_url: '',
    is_active: true,
    wasActive: true,
    restricted: false,
    users: [],
    is_personal: false,
  })
  formError.value = ''
}

function openEdit(location: ApiPickupLocation) {
  Object.assign(form, {
    open: true,
    id: location.id,
    name: location.name,
    address: location.address ?? '',
    opening_hours: location.opening_hours ?? '',
    notes: location.notes ?? '',
    map_url: location.map_url ?? '',
    is_active: location.is_active,
    wasActive: location.is_active,
    restricted: !!location.users?.length,
    users: [...(location.users ?? [])],
    is_personal: location.is_personal,
  })
  formError.value = ''
}

function closeForm() {
  form.open = false
}

function orNull(value: string): string | null {
  const trimmed = value.trim()
  return trimmed ? trimmed : null
}

async function save() {
  if (!form.name.trim()) {
    formError.value = t('admin.pickup_locations.errors.name')
    return
  }
  const mapUrl = form.map_url.trim()
  if (mapUrl && !/^https?:\/\/\S+\.\S+/i.test(mapUrl)) {
    formError.value = t('admin.pickup_locations.errors.url')
    return
  }
  const payload: PickupLocationPayload = {
    name: form.name.trim(),
    address: orNull(form.address),
    opening_hours: orNull(form.opening_hours),
    notes: orNull(form.notes),
    map_url: orNull(form.map_url),
    is_active: form.is_active,
    is_personal: form.is_personal,
    // Switching the restriction off, or leaving it without users, opens the place up
    user_ids: form.restricted ? form.users.map((u) => u.id) : [],
  }
  formError.value = ''
  saving.value = true
  try {
    if (form.id) {
      const updated = await updateLocation(form.id, payload)
      const idx = locations.value.findIndex((l) => l.id === form.id)
      if (idx !== -1) locations.value[idx] = updated
    } else {
      locations.value.push(await createLocation(payload))
    }
    closeForm()
    notify(t('admin.pickup_locations.saved', { name: payload.name }))
  } catch (err) {
    formError.value = apiMessage(err, t('admin.pickup_locations.errors.save'))
  } finally {
    saving.value = false
  }
}

// ── Active toggle ───────────────────────────────────────────────
async function toggle(location: ApiPickupLocation) {
  togglingId.value = location.id
  try {
    const updated = await toggleLocation(location.id)
    location.is_active = updated.is_active
  } catch (err) {
    notify(apiMessage(err, t('admin.pickup_locations.errors.save')), 'error')
  } finally {
    togglingId.value = null
  }
}

// ── Ordering ────────────────────────────────────────────────────
// Same interaction as the sponsors list: the handle arms dragging, the
// arrow buttons cover touch devices.
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
  const from = locations.value.findIndex((l) => l.id === dragId.value)
  onDragEnd()
  if (from === -1 || from === target) return
  const list = [...locations.value]
  const [item] = list.splice(from, 1)
  list.splice(target, 0, item!)
  commitOrder(list)
}

function move(index: number, delta: -1 | 1) {
  const target = index + delta
  if (target < 0 || target >= locations.value.length) return
  const list = [...locations.value]
  ;[list[index], list[target]] = [list[target]!, list[index]!]
  commitOrder(list)
}

async function commitOrder(list: ApiPickupLocation[]) {
  const previous = locations.value
  locations.value = list
  try {
    await reorderLocations(list.map((l) => l.id))
  } catch {
    locations.value = previous
    notify(t('admin.pickup_locations.errors.order'), 'error')
  }
}

// ── Delete (soft) ───────────────────────────────────────────────
async function remove(location: ApiPickupLocation) {
  const ok = await confirmDialog({
    title: t('admin.pickup_locations.delete_title'),
    message: t('admin.pickup_locations.confirm_delete', { name: location.name }),
    confirmLabel: t('admin.actions.delete'),
    danger: true,
  })
  if (!ok) return
  try {
    await deleteLocation(location.id)
    locations.value = locations.value.filter((l) => l.id !== location.id)
    notify(t('admin.pickup_locations.deleted', { name: location.name }))
  } catch (err) {
    notify(apiMessage(err, t('admin.pickup_locations.errors.delete')), 'error')
  }
}

onMounted(async () => {
  try {
    locations.value = await fetchAdminLocations()
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
// Small amber text: deeper burnt orange on light (4.5:1), bright accent on dark.
// Set on the elements themselves, the form dialog is teleported out of the page.
$amber-ink: var(--amber-ink);

.access-badge,
.access {
  --amber-ink: #b45f0f;
}
:root[data-theme='dark'] .access-badge,
:root[data-theme='dark'] .access {
  --amber-ink: #{$amber};
}

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

// ─── Pickup location specifics ────────────────────────────────────
.page-hero__lead {
  max-width: 60ch;
  margin: 0.75rem 0 0;
  font-size: 0.9rem;
  color: $hero-muted;
}

.dash-section__hint {
  margin-left: auto;
  font-size: 0.75rem;
  color: var(--secondary-text);
  @media (max-width: 600px) {
    display: none;
  }
}

.location-row {
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

// Restricted to a few members: quiet, but impossible to miss in the list
.access-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  width: fit-content;
  margin-top: 0.25rem;
  padding: 0.12rem 0.5rem 0.12rem 0.4rem;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 700;
  color: $amber-ink;
  background: color-mix(in srgb, #{$amber} 12%, transparent);
  border: 1px solid color-mix(in srgb, #{$amber} 35%, transparent);
  cursor: help;
  .icon-svg {
    width: 0.8rem;
    height: 0.8rem;
  }
}

.access-badge--personal {
  color: var(--primary-text);
  background: color-mix(in srgb, var(--primary-text) 6%, transparent);
  border-color: var(--divider);
  cursor: default;
}

.personal .form-hint {
  margin: 0.35rem 0 0;
}

.access {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  margin: 0.25rem 0;
  padding: 1rem 1.1rem 1.1rem;
  border: 1px solid var(--divider);
  border-radius: 12px;
  background: color-mix(in srgb, var(--primary-text) 2%, transparent);

  &__legend {
    padding: 0 0.4rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--secondary-text);
  }
  .form-hint {
    margin: 0;
  }
  &__warn {
    display: flex;
    align-items: flex-start;
    gap: 0.4rem;
    margin: 0;
    padding: 0;
    font-size: 0.8rem;
    color: $amber-ink;
    .icon-svg {
      flex: none;
      width: 1rem;
      height: 1rem;
      margin-top: 0.05rem;
    }
  }
}

.location-cell {
  min-width: 200px;
  &__name {
    display: block;
    font-weight: 600;
  }
  &__address {
    display: block;
    margin-top: 0.15rem;
    font-size: 0.8rem;
    color: var(--secondary-text);
    white-space: pre-line;
  }
  &__map {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    margin-top: 0.35rem;
    font-size: 0.75rem;
    .icon-svg {
      width: 0.9rem;
      height: 0.9rem;
    }
  }
}

.hours-cell {
  font-size: 0.8rem;
  color: var(--secondary-text) !important;
  white-space: pre-line;
  min-width: 140px;
}

.open-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 26px;
  height: 22px;
  padding: 0 7px;
  font-size: 0.75rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  color: var(--secondary-text);
  border: 1px solid var(--divider);
  border-radius: 999px;
  &--busy {
    color: $amber;
    background: $amber-08;
    border-color: $amber-25;
  }
}

.switch-wrap {
  display: inline-flex;
}

.rule-note {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  margin: 0;
  padding: 0.9rem 1.5rem;
  font-size: 0.8rem;
  color: var(--secondary-text);
  border-top: 1px solid var(--divider);
  .icon-svg {
    flex-shrink: 0;
    width: 1rem;
    height: 1rem;
    margin-top: 0.1rem;
    color: $amber;
  }
}

.input-wrapper textarea {
  resize: vertical;
}

.form-hint {
  margin: 0.4rem 0 0;
  font-size: 0.8rem;
  color: var(--secondary-text);
}
</style>
