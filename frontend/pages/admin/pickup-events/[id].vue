<template>
  <div class="admin-page">
    <!-- ── Hero: when and where ─────────────────────────────────── -->
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <AdminBreadcrumb :label="$t('admin.packing.breadcrumb')" />
        <template v-if="data">
          <p class="page-hero__eyebrow">{{ $t('admin.packing.eyebrow') }}</p>
          <h1 class="page-hero__title">{{ data.event.location?.name ?? data.event.title }}</h1>
          <p class="page-hero__when">
            <span class="page-hero__day">{{ dayLabel }}</span>
            <span v-if="data.event.time">{{ timeLabel }}</span>
          </p>
          <p v-if="data.event.location?.address" class="page-hero__address">
            {{ data.event.location.address }}
          </p>
        </template>
      </div>
    </section>

    <div class="admin-content">
      <div class="admin-content__inner">
        <div v-if="loading" class="admin-state"><div class="spinner" /></div>
        <p v-else-if="loadError" class="alert alert-error">{{ loadError }}</p>

        <template v-else-if="data">
          <!-- Progress: the one number that matters at the table -->
          <section class="tally" :aria-label="$t('admin.packing.progress_label')">
            <div class="tally__count">
              <strong>{{ handedOutCount }}</strong>
              <span>/ {{ data.bookings.length }}</span>
            </div>
            <div class="tally__text">
              <span class="tally__label">{{ $t('admin.packing.handed_out') }}</span>
              <span class="tally__games">{{
                $t('admin.packing.games_total', { n: gameCount }, gameCount)
              }}</span>
            </div>
            <div class="tally__bar" aria-hidden="true">
              <i :style="{ width: `${progress}%` }" />
            </div>
            <button type="button" class="tally__print" @click="print">
              <svg class="icon-svg" aria-hidden="true">
                <use href="/svg-icons/icons.svg#print" />
              </svg>
              {{ $t('admin.packing.print') }}
            </button>
          </section>

          <!-- Returns handed in here: scan the sticker, pick the condition -->
          <section class="intake" :aria-label="$t('admin.packing.intake_title')">
            <header class="intake__head">
              <h2 class="intake__title">{{ $t('admin.packing.intake_title') }}</h2>
              <span v-if="data.returns.length" class="intake__count">{{
                data.returns.length
              }}</span>
            </header>
            <form class="intake__form" @submit.prevent="takeBack">
              <input
                v-model="scanCode"
                class="intake__input"
                type="text"
                autocomplete="off"
                autocapitalize="characters"
                spellcheck="false"
                enterkeyhint="go"
                :placeholder="$t('admin.packing.intake_placeholder')"
                :aria-label="$t('admin.packing.intake_placeholder')"
              />
              <div
                class="intake__conditions"
                role="radiogroup"
                :aria-label="$t('dashboard.return_condition')"
              >
                <button
                  v-for="c in conditions"
                  :key="c.value"
                  type="button"
                  role="radio"
                  class="intake__condition"
                  :class="`intake__condition--${c.value.toLowerCase()}`"
                  :aria-checked="scanCondition === c.value"
                  @click="scanCondition = c.value"
                >
                  {{ c.label }}
                </button>
              </div>
              <button
                type="submit"
                class="button button-primary intake__submit"
                :disabled="!scanCode.trim() || scanning"
              >
                {{ $t('admin.packing.intake_submit') }}
              </button>
            </form>
            <p
              v-if="scanMessage"
              class="intake__message"
              :class="{ 'is-error': scanFailed }"
              role="status"
            >
              {{ scanMessage }}
            </p>
            <ul v-if="data.returns.length" class="intake__list">
              <li v-for="r in data.returns" :key="r.loan_id" class="intake__item">
                <span class="intake__game">{{ r.game_title ?? '—' }}</span>
                <span class="intake__member">{{ r.member ?? '' }}</span>
                <span class="intake__state">{{ conditionLabel(r.condition) }}</span>
              </li>
            </ul>
          </section>

          <div v-if="!data.bookings.length" class="dash-empty">
            <svg class="icon-svg dash-empty__icon" aria-hidden="true">
              <use href="/svg-icons/icons.svg#inventory_2" />
            </svg>
            <p class="dash-empty__text">{{ $t('admin.packing.empty') }}</p>
          </div>

          <ol v-else class="bookings">
            <li
              v-for="(booking, i) in data.bookings"
              :key="`${booking.type}-${booking.id}`"
              class="booking"
              :class="{ 'is-out': !!booking.handed_out_at }"
              :style="{ '--i': i }"
            >
              <div class="booking__who">
                <span class="booking__name">{{ booking.member?.name ?? '—' }}</span>
                <span v-if="booking.package_name" class="booking__package">
                  {{ $t('admin.packing.package', { name: booking.package_name }) }}
                </span>
              </div>

              <ul class="booking__items">
                <li v-for="item in booking.items" :key="item.loan_id" class="booking__item">
                  <span class="booking__box" aria-hidden="true" />
                  <span class="booking__game">{{ item.game_title ?? '—' }}</span>
                  <span class="booking__code">{{ item.qr_code ?? '—' }}</span>
                </li>
              </ul>

              <button
                v-if="!booking.handed_out_at"
                type="button"
                class="booking__noshow"
                :disabled="busyKey === `${booking.type}-${booking.id}`"
                @click="cancel(booking)"
              >
                {{ $t('admin.packing.no_show') }}
              </button>

              <button
                type="button"
                class="booking__toggle"
                :aria-pressed="!!booking.handed_out_at"
                :disabled="busyKey === `${booking.type}-${booking.id}`"
                @click="toggle(booking)"
              >
                <svg class="icon-svg" aria-hidden="true">
                  <use
                    :href="`/svg-icons/icons.svg#${booking.handed_out_at ? 'check_circle' : 'radio_button_unchecked'}`"
                  />
                </svg>
                {{
                  booking.handed_out_at ? $t('admin.packing.done') : $t('admin.packing.hand_out')
                }}
              </button>
            </li>
          </ol>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import type { ApiPickupLocation } from '~/types/api'

definePageMeta({ middleware: ['auth', 'admin'] })

interface Booking {
  type: 'loan' | 'package'
  id: number
  member: { id: number; name: string; email: string } | null
  package_name: string | null
  handed_out_at: string | null
  items: { loan_id: number; game_title: string | null; qr_code: string | null }[]
}

interface PackingList {
  event: {
    id: number
    title: string
    date: string
    time: string | null
    end_time: string | null
    max_loans: number | null
    location: ApiPickupLocation | null
  }
  bookings: Booking[]
  returns: {
    loan_id: number
    game_title: string | null
    member: string | null
    condition: string | null
  }[]
}

const route = useRoute()
const api = useApi()
const { t } = useI18n()
const { confirmDialog, notify } = useDialogs()

const data = ref<PackingList | null>(null)
const loading = ref(true)
const loadError = ref('')
const busyKey = ref<string | null>(null)

const handedOutCount = computed(
  () => data.value?.bookings.filter((b) => b.handed_out_at).length ?? 0
)
const gameCount = computed(
  () => data.value?.bookings.reduce((sum, b) => sum + b.items.length, 0) ?? 0
)
const progress = computed(() =>
  data.value?.bookings.length ? (handedOutCount.value / data.value.bookings.length) * 100 : 0
)

const dayLabel = computed(() =>
  data.value
    ? new Date(`${data.value.event.date}T00:00:00`).toLocaleDateString('de-AT', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
      })
    : ''
)
const timeLabel = computed(() => {
  const e = data.value?.event
  if (!e?.time) return ''
  return `${e.time}${e.end_time ? `–${e.end_time}` : ''} Uhr`
})

onMounted(async () => {
  try {
    data.value = await api.get<PackingList>(`/admin/events/${route.params.id}/packing-list`)
  } catch (err: unknown) {
    const message = (err as { message?: unknown })?.message
    loadError.value = typeof message === 'string' && message ? message : t('common.error.generic')
  } finally {
    loading.value = false
  }
})

async function toggle(booking: Booking) {
  const key = `${booking.type}-${booking.id}`
  const path =
    booking.type === 'package'
      ? `/admin/package-loans/${booking.id}/hand-out`
      : `/admin/loans/${booking.id}/hand-out`
  busyKey.value = key
  try {
    const res = await api.patch<{ handed_out_at: string | null }>(path, {
      handed_out: !booking.handed_out_at,
    })
    booking.handed_out_at = res.handed_out_at
  } catch (err: unknown) {
    const message = (err as { message?: unknown })?.message
    notify(typeof message === 'string' && message ? message : t('common.error.generic'), 'error')
  } finally {
    busyKey.value = null
  }
}

// No-show: cancel and refund, the booking leaves the list
async function cancel(booking: Booking) {
  if (!data.value) return
  const name = booking.member?.name ?? ''
  const ok = await confirmDialog({
    title: t('admin.packing.no_show_title'),
    message: t('admin.packing.no_show_confirm', { name }),
    confirmLabel: t('admin.packing.no_show_action'),
    danger: true,
  })
  if (!ok || !data.value) return
  const path =
    booking.type === 'package'
      ? `/admin/package-loans/${booking.id}/cancel-pickup`
      : `/admin/loans/${booking.id}/cancel-pickup`
  busyKey.value = `${booking.type}-${booking.id}`
  try {
    await api.post(path)
    data.value.bookings = data.value.bookings.filter((b) => b !== booking)
    notify(t('admin.packing.no_show_done', { name }))
  } catch (err: unknown) {
    const message = (err as { message?: unknown })?.message
    notify(typeof message === 'string' && message ? message : t('common.error.generic'), 'error')
  } finally {
    busyKey.value = null
  }
}

// ── Returns at the event ───────────────────────────────────────
const conditions = computed(() => [
  { value: 'GOOD', label: t('common.badge.good') },
  { value: 'WORN', label: t('common.badge.worn') },
  { value: 'DAMAGED', label: t('common.badge.damaged') },
])
const scanCode = ref('')
const scanCondition = ref('GOOD')
const scanning = ref(false)
const scanMessage = ref('')
const scanFailed = ref(false)

function conditionLabel(c: string | null) {
  return conditions.value.find((x) => x.value === c)?.label ?? ''
}

async function takeBack() {
  if (!data.value || !scanCode.value.trim()) return
  scanning.value = true
  scanMessage.value = ''
  try {
    const res = await api.post<{ message: string; returns: PackingList['returns'] }>(
      `/admin/events/${data.value.event.id}/returns`,
      { qr_code: scanCode.value.trim(), return_condition: scanCondition.value }
    )
    data.value.returns = res.returns
    scanMessage.value = res.message
    scanFailed.value = false
    scanCode.value = ''
    scanCondition.value = 'GOOD'
  } catch (err: unknown) {
    const message = (err as { message?: unknown })?.message
    scanMessage.value = typeof message === 'string' && message ? message : t('common.error.generic')
    scanFailed.value = true
  } finally {
    scanning.value = false
  }
}

function print() {
  window.print()
}

useHead(() => ({ title: t('admin.packing.breadcrumb') }))
</script>

<style lang="scss" scoped>
$nav-height: 64px;
$ease-out: cubic-bezier(0.16, 1, 0.3, 1);

.admin-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--background);
}

.page-hero {
  position: relative;
  padding: calc(#{$nav-height} + 1.75rem) 1.5rem 4.5rem;
  overflow: hidden;

  &__backdrop {
    position: absolute;
    inset: 0;
    pointer-events: none;
  }
  &__glow {
    position: absolute;
    width: 420px;
    height: 420px;
    top: -140px;
    right: -80px;
    border-radius: 50%;
    filter: blur(90px);
    background: color-mix(in srgb, #{$amber} 16%, transparent);
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
    max-width: 860px;
    margin: 0 auto;
  }
  &__eyebrow {
    margin: 0.75rem 0 0.2rem;
    padding: 0;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: $amber;
  }
  &__title {
    margin: 0;
    font-size: clamp(1.75rem, 5vw, 2.6rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    color: var(--primary-text);
  }
  &__when {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem 0.9rem;
    margin: 0.5rem 0 0;
    padding: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--primary-text);
  }
  &__day {
    &::first-letter {
      text-transform: uppercase;
    }
  }
  &__address {
    margin: 0.2rem 0 0;
    padding: 0;
    font-size: 0.85rem;
    color: var(--secondary-text);
  }
}

.admin-content {
  flex: 1;
  margin-top: -3rem;
  padding: 0 1rem 4rem;
  position: relative;
  z-index: 2;
  &__inner {
    max-width: 860px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
}

.admin-state {
  display: flex;
  justify-content: center;
  padding: 4rem 0;
}

// ── Progress ──────────────────────────────────────────────────────
.tally {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 0.4rem 1rem;
  padding: 1.1rem 1.25rem;
  border-radius: 16px;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.12);

  &__count {
    display: flex;
    align-items: baseline;
    gap: 0.2rem;
    font-variant-numeric: tabular-nums;
    strong {
      font-size: 2.4rem;
      font-weight: 800;
      letter-spacing: -0.05em;
      line-height: 1;
      color: $amber;
    }
    span {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--secondary-text);
    }
  }
  &__text {
    display: flex;
    flex-direction: column;
    min-width: 0;
  }
  &__label {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--primary-text);
  }
  &__games {
    font-size: 0.78rem;
    color: var(--secondary-text);
  }
  &__bar {
    grid-column: 1 / -1;
    grid-row: 2;
    height: 6px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--primary-text) 8%, transparent);
    overflow: hidden;
    i {
      display: block;
      height: 100%;
      border-radius: inherit;
      background: $amber;
      transition: width 0.5s $ease-out;
    }
  }
  &__print {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.5rem 0.85rem;
    border-radius: 999px;
    font: inherit;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--primary-text);
    background: transparent;
    border: 1px solid var(--divider);
    cursor: pointer;
    .icon-svg {
      width: 1rem;
      height: 1rem;
    }
    &:hover {
      border-color: $amber;
    }
  }
}

// ── Returns at the event ──────────────────────────────────────────
.intake {
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
  padding: 1rem 1.1rem 1.1rem;
  border-radius: 14px;
  background: var(--secondary-background);
  border: 1px dashed color-mix(in srgb, #{$amber} 45%, var(--divider));

  &__head {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  &__title {
    margin: 0;
    font-size: 0.8rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--secondary-text);
  }
  &__count {
    min-width: 1.4rem;
    padding: 0.05rem 0.45rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 800;
    text-align: center;
    color: $amber;
    background: color-mix(in srgb, #{$amber} 12%, transparent);
  }
  &__form {
    display: grid;
    grid-template-columns: 1fr auto auto;
    gap: 0.6rem;
    align-items: center;
    @media (max-width: 640px) {
      grid-template-columns: 1fr;
    }
  }
  &__input {
    min-width: 0;
    padding: 0.7rem 0.9rem;
    font-family: monospace;
    font-size: 1rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--primary-text);
    background: var(--background);
    border: 1px solid var(--divider);
    border-radius: 10px;
    &:focus {
      outline: none;
      border-color: $amber;
    }
  }
  &__conditions {
    display: flex;
    gap: 0.3rem;
  }
  &__condition {
    flex: 1;
    padding: 0.55rem 0.7rem;
    border-radius: 8px;
    font: inherit;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--secondary-text);
    background: transparent;
    border: 1px solid var(--divider);
    cursor: pointer;
    &[aria-checked='true'] {
      color: var(--primary-text);
      border-color: $amber;
      background: color-mix(in srgb, #{$amber} 10%, transparent);
    }
    &--damaged[aria-checked='true'] {
      color: var(--error-text);
      border-color: var(--error-border);
      background: var(--error-muted);
    }
  }
  &__submit {
    margin: 0;
    white-space: nowrap;
  }
  &__message {
    margin: 0;
    padding: 0;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--primary-text);
    &.is-error {
      color: var(--error-text);
    }
  }
  &__list {
    list-style: none;
    margin: 0;
    padding: 0;
    border-top: 1px solid var(--divider);
  }
  &__item {
    display: grid;
    grid-template-columns: 1fr auto auto;
    gap: 0.75rem;
    padding: 0.5rem 0;
    font-size: 0.85rem;
    border-bottom: 1px solid var(--divider);
    &:last-child {
      border-bottom: 0;
    }
  }
  &__game {
    font-weight: 600;
    color: var(--primary-text);
  }
  &__member,
  &__state {
    color: var(--secondary-text);
  }
}

// ── Bookings ──────────────────────────────────────────────────────
.bookings {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.booking {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 0.6rem 1rem;
  padding: 1rem 1.1rem;
  border-radius: 14px;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  animation: rise 0.35s $ease-out backwards;
  animation-delay: calc(var(--i) * 35ms);
  transition:
    opacity 0.25s ease,
    border-color 0.25s ease;

  &.is-out {
    opacity: 0.6;
    border-color: color-mix(in srgb, #{$amber} 35%, var(--divider));
  }

  &__who {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 0.25rem 0.6rem;
  }
  &__name {
    font-size: 1.05rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--primary-text);
  }
  &__package {
    padding: 0.05rem 0.5rem;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 700;
    color: $amber;
    border: 1px dashed color-mix(in srgb, #{$amber} 45%, transparent);
  }

  &__items {
    grid-column: 1;
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
  }
  &__item {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.9rem;
  }
  &__box {
    // only visible on paper, where it becomes a tick box
    display: none;
    width: 0.9rem;
    height: 0.9rem;
    border: 1.5px solid currentColor;
    border-radius: 3px;
  }
  &__game {
    color: var(--primary-text);
  }
  &__code {
    font-family: monospace;
    font-size: 0.78rem;
    letter-spacing: 0.06em;
    color: var(--secondary-text);
  }

  // Quiet on purpose: a no-show is the exception, not the flow
  &__noshow {
    grid-column: 1;
    justify-self: start;
    padding: 0.2rem 0;
    font: inherit;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--error-text);
    background: none;
    border: 0;
    border-bottom: 1px dashed currentColor;
    cursor: pointer;
    opacity: 0.8;
    &:hover {
      opacity: 1;
    }
    &:disabled {
      opacity: 0.4;
      cursor: progress;
    }
  }

  &__toggle {
    grid-column: 2;
    grid-row: 1 / span 3;
    align-self: center;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    min-height: 3rem;
    padding: 0 1.1rem;
    border-radius: 999px;
    font: inherit;
    font-size: 0.85rem;
    font-weight: 700;
    color: #1a0d00;
    background: $amber;
    border: 1px solid $amber;
    cursor: pointer;
    transition:
      transform 0.15s ease,
      background-color 0.2s ease,
      color 0.2s ease;
    .icon-svg {
      width: 1.15rem;
      height: 1.15rem;
    }
    &:active {
      transform: scale(0.96);
    }
    &:disabled {
      opacity: 0.6;
      cursor: progress;
    }
    &[aria-pressed='true'] {
      color: var(--primary-text);
      background: transparent;
      border-color: var(--divider);
    }
  }

  @media (max-width: 520px) {
    grid-template-columns: 1fr;
    &__toggle {
      grid-column: 1;
      grid-row: auto;
      justify-content: center;
    }
    &__noshow {
      order: 3;
    }
  }
}

.dash-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 3rem 1.5rem;
  color: var(--secondary-text);
  &__icon {
    width: 2rem;
    height: 2rem;
    opacity: 0.35;
  }
}

@keyframes rise {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
}

// ── Paper: a plain checklist to take along ───────────────────────
@media print {
  // the fixed site nav would sit on top of the heading on paper
  :global(.l-nav) {
    display: none !important;
  }
  .page-hero {
    padding: 0 0 1rem;
    &__backdrop {
      display: none;
    }
  }
  .admin-content {
    margin-top: 0;
    padding: 0;
  }
  .intake {
    display: none;
  }
  .tally {
    box-shadow: none;
    &__bar,
    &__print {
      display: none;
    }
  }
  .booking {
    break-inside: avoid;
    opacity: 1 !important;
    animation: none;
    &__toggle,
    &__noshow {
      display: none;
    }
    &__box {
      display: inline-block;
    }
  }
}
</style>
