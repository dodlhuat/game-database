<template>
  <div class="dashboard-page">
    <!-- ── Hero: greeting + wallet ──────────────────────────────── -->
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <div class="greeting">
          <p class="greeting__eyebrow">{{ $t('dashboard.member_area') }}</p>
          <h1 class="greeting__title">{{ $t('dashboard.welcome', { name: firstName }) }}</h1>
          <p class="greeting__sub">{{ $t('dashboard.subtitle') }}</p>
          <p v-if="!loading" class="greeting__meta">
            <span>{{ $t('dashboard.total_loans_meta', { n: totalLoans }, totalLoans) }}</span>
            <template
              v-if="
                (auth.canBorrow || auth.isSupporter || auth.isMembershipExpired) &&
                auth.user?.membership_expires_at
              "
            >
              <span class="greeting__meta-sep" aria-hidden="true" />
              <span class="membership-expiry" :class="expiryClass">
                {{
                  auth.isMembershipExpired
                    ? $t('dashboard.membership.expired_label')
                    : $t('dashboard.membership.label')
                }}
                <strong class="membership-expiry__date">{{
                  formatDate(auth.user?.membership_expires_at)
                }}</strong>
                <a
                  v-if="canRenew"
                  href="/dashboard"
                  class="membership-expiry__renew"
                  @click.prevent="renew"
                  >{{ $t('btn.renew') }}</a
                >
              </span>
            </template>
          </p>
        </div>

        <!-- Wallet: what the member can spend, or how to become one -->
        <div v-if="auth.canBorrow" class="wallet">
          <span class="wallet__label">{{ $t('dashboard.tokens.available') }}</span>
          <span class="wallet__value">{{ auth.totalTokens }}</span>
          <span v-if="auth.bonusTokens > 0" class="wallet__bonus">{{
            $t('dashboard.tokens.bonus_note', {
              bonus: auth.bonusTokens,
              date: formatDate(auth.bonusExpiresAt),
            })
          }}</span>
          <NuxtLink to="/tokens" class="wallet__cta">
            {{ $t('dashboard.tokens.charge') }}
            <svg class="icon-svg" aria-hidden="true">
              <use href="/svg-icons/icons.svg#arrow_forward" />
            </svg>
          </NuxtLink>
        </div>
        <div v-else-if="auth.isSupporter" class="wallet wallet--plain">
          <span class="wallet__badge">
            <svg class="icon-svg" aria-hidden="true">
              <use href="/svg-icons/icons.svg#support" />
            </svg>
            {{ $t('dashboard.membership.supporter_label') }}
          </span>
          <NuxtLink to="/upgrade" class="wallet__cta">{{
            $t('dashboard.membership.activate_full')
          }}</NuxtLink>
        </div>
        <div v-else-if="auth.isMembershipExpired" class="wallet wallet--expired">
          <span class="wallet__badge wallet__badge--locked">
            <svg class="icon-svg" aria-hidden="true">
              <use href="/svg-icons/icons.svg#lock" />
            </svg>
            {{ $t('dashboard.membership.expired') }}
          </span>
          <span v-if="auth.user?.role === 'MEMBER'" class="wallet__value wallet__value--frozen">{{
            auth.totalTokens
          }}</span>
          <button type="button" class="wallet__cta" :disabled="renewLoading" @click="renew">
            {{ $t('dashboard.membership.renew_cta') }}
          </button>
        </div>
        <div v-else-if="auth.isRegisteredUser" class="wallet wallet--plain">
          <span class="wallet__text">{{ $t('dashboard.membership.not_member') }}</span>
          <NuxtLink to="/upgrade" class="wallet__cta">{{
            $t('dashboard.membership.upgrade')
          }}</NuxtLink>
        </div>
      </div>
    </section>

    <!-- ── Content ──────────────────────────────────────────────── -->
    <div class="dashboard-content">
      <div class="dashboard-content__inner">
        <!-- Active loans, most urgent first ────────────────────── -->
        <section class="loans" :aria-label="$t('dashboard.active_loans_title')">
          <header class="loans__header">
            <h2 class="loans__title">{{ $t('dashboard.active_loans_title') }}</h2>
            <span v-if="!loading" class="count-pill">{{ activeLoans.length }}</span>
          </header>

          <div v-if="extensionSuccess" class="alert alert-success">{{ extensionSuccess }}</div>

          <div v-if="loading" class="loans__list">
            <div v-for="i in 2" :key="i" class="loan-card loan-card--skeleton" />
          </div>

          <div v-else-if="!activeLoans.length" class="loans-empty">
            <svg class="icon-svg loans-empty__icon" aria-hidden="true">
              <use href="/svg-icons/icons.svg#casino" />
            </svg>
            <p class="loans-empty__text">{{ $t('common.empty.no_active_loans') }}</p>
            <NuxtLink to="/games" class="button button-primary loans-empty__cta">{{
              $t('dashboard.browse_games')
            }}</NuxtLink>
          </div>

          <ol v-else class="loans__list">
            <li
              v-for="(loan, i) in activeLoans"
              :key="loan.id"
              class="loan-card"
              :class="`loan-card--${dueTone(loan)}`"
              :style="{ '--i': i }"
            >
              <NuxtLink
                :to="`/games/${loan.game?.slug}`"
                class="loan-card__cover"
                tabindex="-1"
                aria-hidden="true"
              >
                <img
                  v-if="loan.game?.cover_image_url"
                  :src="loan.game.cover_image_url"
                  alt=""
                  loading="lazy"
                />
                <span v-else class="loan-card__initial">{{ loan.game?.title?.charAt(0) }}</span>
              </NuxtLink>

              <div class="loan-card__main">
                <span class="badge" :class="loanStatusVariant(loan)">
                  {{ loanStatusLabel(loan) }}
                </span>
                <NuxtLink :to="`/games/${loan.game?.slug}`" class="loan-card__cta">
                  {{ loan.game?.title }}
                </NuxtLink>
                <span v-if="loan.pickup_location" class="loan-location">
                  <svg class="icon-svg" aria-hidden="true">
                    <use href="/svg-icons/icons.svg#location_on" />
                  </svg>
                  <span>
                    {{ $t('dashboard.return_to') }}
                    <strong>{{ loan.pickup_location.name }}</strong>
                    <a
                      v-if="loan.pickup_location.map_url"
                      class="loan-location__map"
                      :href="loan.pickup_location.map_url"
                      target="_blank"
                      rel="noopener noreferrer"
                      >{{ $t('pickup.map_link') }}</a
                    >
                  </span>
                </span>
              </div>

              <div class="loan-card__due">
                <span class="loan-card__due-rel">{{ dueText(loan) }}</span>
                <span class="loan-card__due-date">{{ formatDate(loan.due_date) }}</span>
                <span
                  v-if="loan.max_extensions"
                  class="ext-meter"
                  :aria-label="
                    $t('dashboard.extension_meter', {
                      used: loan.extensions_used ?? 0,
                      max: loan.max_extensions,
                    })
                  "
                >
                  <span class="ext-meter__pips" aria-hidden="true">
                    <i
                      v-for="n in loan.max_extensions"
                      :key="n"
                      class="ext-meter__pip"
                      :class="{ 'ext-meter__pip--used': n <= (loan.extensions_used ?? 0) }"
                    />
                  </span>
                  {{
                    $t('dashboard.extension_meter', {
                      used: loan.extensions_used ?? 0,
                      max: loan.max_extensions,
                    })
                  }}
                </span>
              </div>

              <div class="loan-card__actions">
                <template v-if="loan.status !== 'RETURNED'">
                  <span v-if="extensionNote(loan)" class="extension-note">
                    {{ extensionNote(loan) }}
                  </span>
                  <button
                    v-else
                    class="action-btn action-btn--primary"
                    @click="openExtension(loan)"
                  >
                    {{ $t('btn.extend') }}
                    <span
                      class="action-btn__price"
                      :class="{ 'action-btn__price--free': !loan.next_extension_cost }"
                      >{{ extensionCostLabel(loan) }}</span
                    >
                  </button>
                </template>
                <button class="action-btn" @click="openReturn(loan)">
                  {{ $t('btn.return') }}
                </button>
                <button class="action-btn action-btn--quiet" @click="openDamage(loan)">
                  <svg class="icon-svg" aria-hidden="true">
                    <use href="/svg-icons/icons.svg#warning" />
                  </svg>
                  {{ $t('btn.damage') }}
                </button>
              </div>
            </li>
          </ol>
        </section>

        <!-- Queue + history side by side ─────────────────────────── -->
        <div v-if="!loading" class="dash-columns">
          <section class="panel">
            <header class="panel__header">
              <h2 class="panel__title">{{ $t('dashboard.reservations_title') }}</h2>
              <span class="count-pill">{{ reservations.length }}</span>
            </header>
            <p v-if="!reservations.length" class="panel__empty">
              {{ $t('common.empty.no_reservations') }}
            </p>
            <template v-else>
              <ul class="queue">
                <li v-for="res in reservations" :key="res.id" class="queue__row">
                  <span class="queue__pos" :aria-label="$t('dashboard.table.position')"
                    ><small>#</small>{{ res.position }}</span
                  >
                  <span class="queue__body">
                    <NuxtLink
                      v-if="res.game?.slug"
                      :to="`/games/${res.game.slug}`"
                      class="queue__cta"
                      >{{ res.game?.title }}</NuxtLink
                    >
                    <span v-else class="queue__cta">{{ res.game?.title }}</span>
                    <span class="queue__hint">{{ $t('dashboard.queue_hint') }}</span>
                  </span>
                  <button class="action-btn action-btn--danger" @click="cancelReservation(res.id)">
                    {{ $t('dashboard.cancel_reservation') }}
                  </button>
                </li>
              </ul>
              <p class="panel__note">{{ $t('dashboard.reservation_fee_note') }}</p>
            </template>
          </section>

          <section class="panel panel--quiet">
            <header class="panel__header">
              <h2 class="panel__title">{{ $t('dashboard.loan_history_title') }}</h2>
              <span class="count-pill">{{ loanHistory.length }}</span>
            </header>
            <p v-if="!loanHistory.length" class="panel__empty">
              {{ $t('common.empty.no_loan_history') }}
            </p>
            <ol v-else class="history">
              <li v-for="loan in loanHistory" :key="loan.id" class="history__row">
                <span class="history__title">{{ loan.game?.title }}</span>
                <span class="history__date">
                  <span class="visually-hidden">{{ $t('dashboard.table.returned_at') }}</span>
                  {{ formatDate(loan.returned_at!) }}
                </span>
              </li>
            </ol>
          </section>
        </div>

        <div class="dash-footer-actions">
          <NuxtLink to="/account" class="dash-settings__cta">
            <svg class="icon-svg" aria-hidden="true">
              <use href="/svg-icons/icons.svg#settings" />
            </svg>
            {{ $t('dashboard.footer_settings') }}
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- ── Rückgabe-Dialog ──────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="returnLoan" class="modal-overlay" @click.self="returnLoan = null">
        <div class="dialog">
          <div class="dialog__header">
            <h3 class="dialog__title">{{ $t('dashboard.return_title') }}</h3>
            <button class="dialog__close" :aria-label="$t('btn.close')" @click="returnLoan = null">
              <svg class="icon-svg" aria-hidden="true">
                <use href="/svg-icons/icons.svg#close" />
              </svg>
            </button>
          </div>
          <p class="dialog__game">{{ returnLoan.game?.title }}</p>
          <div v-if="returnLoan.pickup_location" class="dialog__location">
            <p class="pickup-picker__hint">{{ $t('dashboard.return_location_hint') }}</p>
            <div class="pickup-option pickup-option--static">
              <span class="pickup-option__card">
                <span class="pickup-option__pin" aria-hidden="true">
                  <svg class="icon-svg"><use href="/svg-icons/icons.svg#location_on" /></svg>
                </span>
                <PickupLocationDetails :location="returnLoan.pickup_location" />
              </span>
            </div>
          </div>
          <label class="dialog__label">{{ $t('dashboard.return_condition') }}</label>
          <UiVirtualDropdown
            v-model="returnCondition"
            class="dialog__select"
            :options="[
              { label: $t('common.badge.good'), value: 'GOOD' },
              { label: $t('common.badge.worn'), value: 'WORN' },
              { label: $t('common.badge.damaged'), value: 'DAMAGED' },
            ]"
          />
          <div class="dialog__actions">
            <UiButton :loading="returning" @click="submitReturn">{{ $t('btn.confirm') }}</UiButton>
            <button class="action-btn" @click="returnLoan = null">{{ $t('btn.cancel') }}</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ── Schadensmeldungs-Dialog ──────────────────────────────── -->
    <Transition name="modal">
      <div v-if="damageLoan" class="modal-overlay" @click.self="damageLoan = null">
        <div class="dialog">
          <div class="dialog__header">
            <h3 class="dialog__title">{{ $t('dashboard.damage_title') }}</h3>
            <button class="dialog__close" :aria-label="$t('btn.close')" @click="damageLoan = null">
              <svg class="icon-svg" aria-hidden="true">
                <use href="/svg-icons/icons.svg#close" />
              </svg>
            </button>
          </div>
          <p class="dialog__game">{{ damageLoan.game?.title }}</p>
          <UiInput v-model="damageDescription" :label="$t('dashboard.damage_description')" />

          <label class="dialog__label">{{ $t('dashboard.damage_photo') }}</label>
          <div class="file-uploader">
            <div
              class="drop-zone"
              :class="{ 'drag-over': isDraggingDamagePhoto }"
              @dragover.prevent="isDraggingDamagePhoto = true"
              @dragleave.prevent="isDraggingDamagePhoto = false"
              @drop.prevent="onDamagePhotoDrop"
              @click="damagePhotoInputRef?.click()"
            >
              <input
                ref="damagePhotoInputRef"
                type="file"
                accept="image/*"
                style="display: none"
                @change="onDamagePhotoChange"
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

            <div v-if="damagePhotoFile" class="file-list">
              <div class="file-item">
                <div class="file-item-header">
                  <div class="file-info">
                    <img
                      :src="damagePhotoPreviewUrl!"
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
                      <span class="file-name">{{ damagePhotoFile.name }}</span>
                      <span class="file-size">{{ formatFileSize(damagePhotoFile.size) }}</span>
                    </div>
                  </div>
                  <button type="button" class="remove-btn" @click.stop="removeDamagePhoto">
                    <svg class="icon-svg"><use href="/svg-icons/icons.svg#close" /></svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div class="dialog__actions">
            <UiButton :loading="reporting" @click="submitDamage">{{
              $t('dashboard.damage_report')
            }}</UiButton>
            <button class="action-btn" @click="damageLoan = null">{{ $t('btn.cancel') }}</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ── Verlängerungs-Dialog ─────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="extensionLoan" class="modal-overlay" @click.self="extensionLoan = null">
        <div class="dialog">
          <div class="dialog__header">
            <h3 class="dialog__title">{{ $t('dashboard.extension_title') }}</h3>
            <button
              class="dialog__close"
              :aria-label="$t('btn.close')"
              @click="extensionLoan = null"
            >
              <svg class="icon-svg" aria-hidden="true">
                <use href="/svg-icons/icons.svg#close" />
              </svg>
            </button>
          </div>
          <p class="dialog__game">{{ extensionLoan.game?.title }}</p>
          <p class="extension-cost">
            <span>{{ $t('dashboard.extension_cost_label') }}</span>
            <strong>{{ extensionCostLabel(extensionLoan) }}</strong>
          </p>
          <p v-if="extensionLoan.next_extension_cost" class="extension-cost__hint">
            {{ $t('dashboard.extension_cost_hint') }}
          </p>
          <p v-if="extensionLoan.max_extensions" class="extension-round">
            {{ extensionRoundLabel(extensionLoan) }}
          </p>
          <UiDatePicker
            v-model="extensionDate"
            :label="$t('dashboard.extension_new_due_date')"
            :min-date="minExtensionDate"
            :max-date="maxExtensionDate"
          />
          <div v-if="extensionError" class="alert alert-error">{{ extensionError }}</div>
          <div class="dialog__actions">
            <UiButton :loading="extending" @click="submitExtension">{{
              $t('dashboard.extension_apply')
            }}</UiButton>
            <button class="action-btn" @click="extensionLoan = null">{{ $t('btn.cancel') }}</button>
          </div>
        </div>
      </div>
    </Transition>

    <AppFooter />

    <div v-if="renewError" class="alert alert-error renew-error">{{ renewError }}</div>

    <StripeCheckoutModal
      :membership="renewCheckout"
      @success="onRenewed"
      @error="renewError = $event"
      @close="renewCheckout = null"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { Loan, Reservation, DashboardData } from '~/composables/useLoans'

definePageMeta({ middleware: ['auth'] })

const auth = useAuthStore()
const { t } = useI18n()
const {
  fetchDashboard,
  returnLoan: doReturn,
  requestExtension,
  removeReservation,
  reportDamage,
} = useLoans()

const loading = ref(true)
const data = ref<DashboardData | null>(null)

const returnLoan = ref<Loan | null>(null)
const returnCondition = ref('GOOD')
const returning = ref(false)

const extensionLoan = ref<Loan | null>(null)
const extensionError = ref('')
const extensionSuccess = ref('')
const extensionDate = ref('')
const extending = ref(false)

const damageLoan = ref<Loan | null>(null)
const damageDescription = ref('')
const damagePhotoFile = ref<File | null>(null)
const damagePhotoInputRef = ref<HTMLInputElement | null>(null)
const isDraggingDamagePhoto = ref(false)
const damagePhotoPreviewUrl = computed(() =>
  damagePhotoFile.value ? URL.createObjectURL(damagePhotoFile.value) : null
)
const reporting = ref(false)

// The backend owns the allowed window (it depends on the loan duration setting),
// so the picker just mirrors it.
const minExtensionDate = computed(() =>
  toLocalDate(extensionLoan.value?.extension_earliest_due_date)
)
const maxExtensionDate = computed(() => toLocalDate(extensionLoan.value?.extension_latest_due_date))

function toLocalDate(iso?: string) {
  return iso ? new Date(`${iso}T00:00:00`) : undefined
}

// Most urgent first: overdue, then whatever is due soonest
const activeLoans = computed<Loan[]>(() =>
  [...(data.value?.active_loans ?? [])].sort(
    (a, b) => new Date(a.due_date).getTime() - new Date(b.due_date).getTime()
  )
)
const totalLoans = computed(() => data.value?.stats.total_loans ?? 0)
const firstName = computed(() => auth.user?.name?.split(' ')[0] ?? '')
const loanHistory = computed<Loan[]>(() => data.value?.loan_history ?? [])
const reservations = computed<Reservation[]>(() => data.value?.reservations ?? [])

useAsyncData(
  'dashboard',
  async () => {
    try {
      data.value = await fetchDashboard()
    } finally {
      loading.value = false
    }
  },
  { server: false }
)

function openReturn(loan: Loan) {
  returnLoan.value = loan
  returnCondition.value = 'GOOD'
}

async function submitReturn() {
  if (!returnLoan.value) return
  returning.value = true
  try {
    await doReturn(returnLoan.value.id, returnCondition.value)
    data.value = await fetchDashboard()
    returnLoan.value = null
  } finally {
    returning.value = false
  }
}

function openExtension(loan: Loan) {
  extensionLoan.value = loan
  extensionDate.value = ''
  extensionError.value = ''
  extensionSuccess.value = ''
}

async function submitExtension() {
  if (!extensionLoan.value || !extensionDate.value) return
  extending.value = true
  extensionError.value = ''
  try {
    await requestExtension(extensionLoan.value.id, extensionDate.value)
    auth.spendTokens(extensionLoan.value.next_extension_cost ?? 0)
    extensionSuccess.value = t('dashboard.extension_success', {
      title: extensionLoan.value.game?.title ?? '',
    })
    data.value = await fetchDashboard()
    extensionLoan.value = null
  } catch (e: unknown) {
    const err = e as { message?: string; reason?: string }
    extensionError.value = err?.message ?? t('common.error.generic')
    // Hat sich der Stand seit dem Laden geändert, soll die Liste das gleich zeigen
    if (err?.reason === 'game_reserved' || err?.reason === 'max_extensions_reached') {
      data.value = await fetchDashboard()
    }
  } finally {
    extending.value = false
  }
}

function openDamage(loan: Loan) {
  damageLoan.value = loan
  damageDescription.value = ''
  damagePhotoFile.value = null
}

function onDamagePhotoChange(e: Event) {
  damagePhotoFile.value = (e.target as HTMLInputElement).files?.[0] ?? null
}
function onDamagePhotoDrop(e: DragEvent) {
  isDraggingDamagePhoto.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file && file.type.startsWith('image/')) damagePhotoFile.value = file
}
function removeDamagePhoto() {
  damagePhotoFile.value = null
  if (damagePhotoInputRef.value) damagePhotoInputRef.value.value = ''
}
function formatFileSize(bytes: number) {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

async function submitDamage() {
  if (!damageLoan.value || !damageDescription.value) return
  reporting.value = true
  try {
    await reportDamage(damageLoan.value.id, damageDescription.value, damagePhotoFile.value)
    damageLoan.value = null
  } finally {
    reporting.value = false
  }
}

async function cancelReservation(id: number) {
  await removeReservation(id)
  data.value = await fetchDashboard()
}

function extensionNote(loan: Loan) {
  if (loan.extension_blocked_reason === 'reserved') return t('dashboard.extension_reserved')
  if (loan.extension_blocked_reason === 'max_reached') {
    return t('dashboard.extension_max_reached', { n: loan.max_extensions ?? 0 })
  }
  return ''
}

function extensionRoundLabel(loan: Loan) {
  const next = (loan.extensions_used ?? 0) + 1
  const max = loan.max_extensions ?? 0
  return next >= max
    ? t('dashboard.extension_last', { n: next, max })
    : t('dashboard.extension_round', { n: next, max })
}

function extensionCostLabel(loan: Loan) {
  const cost = loan.next_extension_cost ?? 0
  return cost > 0
    ? t('dashboard.extension_cost_tokens', { n: cost })
    : t('dashboard.extension_free')
}

/** Whole days from today until the due date (negative = overdue). */
function daysUntilDue(loan: Loan) {
  const due = new Date(loan.due_date)
  due.setHours(0, 0, 0, 0)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return Math.round((due.getTime() - today.getTime()) / 86_400_000)
}

function dueTone(loan: Loan) {
  const days = daysUntilDue(loan)
  if (loan.status === 'OVERDUE' || days < 0) return 'overdue'
  if (days <= 3) return 'soon'
  return 'ok'
}

function dueText(loan: Loan) {
  const days = daysUntilDue(loan)
  if (days < 0) return t('dashboard.due.overdue', { n: -days }, -days)
  if (days === 0) return t('dashboard.due.today')
  return t('dashboard.due.in_days', { n: days }, days)
}

function loanStatusVariant(loan: Loan) {
  if (loan.status === 'OVERDUE') return 'badge-error'
  if (loan.status === 'EXTENDED') return 'badge-warning'
  return 'badge-success'
}

function loanStatusLabel(loan: Loan) {
  const map: Record<string, string> = {
    ACTIVE: t('common.badge.active'),
    EXTENDED: t('common.badge.extended'),
    OVERDUE: t('common.badge.overdue'),
    RETURNED: t('common.badge.returned'),
  }
  return map[loan.status] ?? loan.status
}

function formatDate(iso: string | null | undefined) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('de-DE')
}

// Membership helpers
const { renewCheckout, renewError, renewLoading, renew, onRenewed } = useMembershipRenewal()

const canRenew = computed(() => {
  const exp = auth.user?.membership_expires_at
  if (!exp) return false
  const months = (new Date(exp).getTime() - Date.now()) / (1000 * 60 * 60 * 24 * 30)
  return months <= 3
})

const expiryClass = computed(() => {
  const exp = auth.user?.membership_expires_at
  if (!exp) return ''
  const days = (new Date(exp).getTime() - Date.now()) / (1000 * 60 * 60 * 24)
  if (days <= 0) return 'membership-expiry--critical membership-expiry--expired'
  if (days < 30) return 'membership-expiry--critical'
  if (days < 90) return 'membership-expiry--warn'
  return ''
})
</script>

<style lang="scss" scoped>
$nav-height: 64px;
$content-width: 1100px;
$ease-out: cubic-bezier(0.16, 1, 0.3, 1);

// Amber tints are mixed from the live accent ($amber), never hard-coded rgba —
// the old rgba(212,146,30,…) values no longer matched the #f7963d text.
@function amber($pct) {
  @return color-mix(in srgb, #{$amber} $pct, transparent);
}
// Neutral tints derive from the text colour so they read on both themes.
@function ink($pct) {
  @return color-mix(in srgb, var(--primary-text) $pct, transparent);
}
// Small amber text: #f7963d is ~2:1 on the light background, so light mode
// uses a deeper burnt orange (4.5:1); dark mode keeps the bright accent.
$amber-ink: var(--amber-ink);

.dashboard-page {
  --amber-ink: #b45f0f;
  --wallet-gradient: linear-gradient(175deg, #f0a032 10%, #d4741a 55%, #8a520a 100%);
  --danger-tint: rgba(220, 38, 38, 0.06);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--background);
}
:root[data-theme='dark'] .dashboard-page {
  --amber-ink: #{$amber};
  --wallet-gradient: linear-gradient(175deg, #ffc35a 10%, #{$amber} 55%, #9a5f0c 100%);
  --danger-tint: rgba(239, 68, 68, 0.07);
}

.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
}

@keyframes rise {
  from {
    opacity: 0;
    translate: 0 14px;
  }
  to {
    opacity: 1;
    translate: 0 0;
  }
}

// ─── Hero ─────────────────────────────────────────────────────────
.page-hero {
  position: relative;
  padding: calc(#{$nav-height} + 2.5rem) 1.5rem 5.5rem;
  overflow: hidden;
  @media (max-width: 600px) {
    padding: calc(#{$nav-height} + 1.5rem) 1rem 4.5rem;
  }

  &__backdrop {
    position: absolute;
    inset: 0;
    pointer-events: none;
    mask-image: linear-gradient(to bottom, black 60%, transparent);
  }
  &__glow {
    position: absolute;
    width: 560px;
    height: 560px;
    top: -240px;
    right: -120px;
    border-radius: 50%;
    filter: blur(110px);
    background: amber(16%);
  }
  &__dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, #{ink(6%)} 1px, transparent 1px);
    background-size: 24px 24px;
    mask-image: radial-gradient(ellipse 60% 90% at 80% 30%, black 10%, transparent 100%);
  }
  &__body {
    position: relative;
    z-index: 1;
    max-width: $content-width;
    margin: 0 auto;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: end;
    gap: 2rem 3rem;
    @media (max-width: 760px) {
      grid-template-columns: 1fr;
      gap: 1.75rem;
    }
  }
}

.greeting {
  animation: rise 0.55s $ease-out both;

  &__eyebrow {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: $amber-ink;
    margin: 0 0 0.6rem;
    &::before {
      content: '';
      width: 1.5rem;
      height: 2px;
      border-radius: 2px;
      background: $amber;
    }
  }
  &__title {
    font-size: clamp(2rem, 5.5vw, 3.5rem);
    font-weight: 800;
    line-height: 1;
    letter-spacing: -0.045em;
    color: var(--primary-text);
    margin: 0 0 0.6rem;
  }
  &__sub {
    font-size: 0.975rem;
    color: var(--secondary-text);
    margin: 0 0 1rem;
    padding: 0;
    max-width: 34rem;
  }
  &__meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.4rem 0.75rem;
    margin: 0;
    padding: 0;
    font-size: 0.82rem;
    color: var(--secondary-text);
  }
  &__meta-sep {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: ink(25%);
  }
}

.membership-expiry {
  display: inline-flex;
  align-items: baseline;
  gap: 0.35rem;
  &__date {
    font-weight: 700;
    color: var(--primary-text);
  }
  &--warn &__date {
    color: $amber-ink;
  }
  &--critical &__date {
    color: var(--error-text);
  }
  &__renew {
    font-weight: 700;
    margin-left: 0.25rem;
    // the global link rule already paints it amber; only fix light contrast
    &:link,
    &:visited {
      color: $amber-ink;
    }
    &:hover {
      text-decoration: underline;
    }
  }
}

// Wallet: the balance as the hero's counterweight on the right
.wallet {
  display: grid;
  justify-items: end;
  text-align: right;
  gap: 0.3rem;
  animation: rise 0.6s $ease-out 0.1s both;
  @media (max-width: 760px) {
    justify-items: start;
    text-align: left;
    grid-template-columns: auto 1fr;
    align-items: end;
    column-gap: 1rem;
  }

  &__label {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--secondary-text);
    @media (max-width: 760px) {
      grid-column: 1 / -1;
    }
  }
  &__value {
    font-size: clamp(3.5rem, 9vw, 5.25rem);
    font-weight: 800;
    line-height: 0.85;
    letter-spacing: -0.06em;
    font-variant-numeric: tabular-nums;
    // Negative letter-spacing ends the box before the last glyph, and
    // background-clip: text would shave its right edge (same fix as tokens.vue).
    padding-right: 0.1em;
    margin-right: -0.1em;
    background: var(--wallet-gradient);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    filter: drop-shadow(0 10px 30px #{amber(22%)});
  }
  &__bonus {
    font-size: 0.75rem;
    color: var(--secondary-text);
    @media (max-width: 760px) {
      grid-column: 1 / -1;
      grid-row: 3;
    }
  }
  &__cta {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    margin-top: 0.35rem;
    padding: 0.45rem 0.95rem;
    border-radius: 999px;
    font-size: 0.82rem;
    font-weight: 700;
    color: #1a0d00;
    background: $amber;
    box-shadow: 0 6px 18px #{amber(30%)};
    transition:
      transform 0.2s $ease-out,
      box-shadow 0.2s ease;
    .icon-svg {
      width: 0.95rem;
      height: 0.95rem;
      transition: translate 0.2s $ease-out;
    }
    &:hover {
      transform: translateY(-1px);
      box-shadow: 0 10px 24px #{amber(38%)};
      .icon-svg {
        translate: 2px 0;
      }
    }
    &:focus-visible {
      outline: 2px solid $amber;
      outline-offset: 3px;
    }
    @media (max-width: 760px) {
      align-self: center;
      justify-self: start;
    }
  }

  &--plain {
    align-content: end;
    @media (max-width: 760px) {
      grid-template-columns: 1fr;
    }
  }
  &__badge {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.35rem 0.85rem;
    border-radius: 999px;
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--primary-text);
    background: ink(4%);
    border: 1px solid ink(14%);
    .icon-svg {
      width: 1rem;
      height: 1rem;
      color: var(--secondary-text);
    }
  }
  &__text {
    font-size: 0.9rem;
    color: var(--secondary-text);
  }

  // Lapsed membership: the balance is still there but frozen until renewal
  &--expired {
    align-content: end;
    @media (max-width: 760px) {
      grid-template-columns: 1fr;
    }
  }
  &__badge--locked {
    color: var(--error-text);
    background: var(--error-muted);
    border-color: var(--error-border);
    .icon-svg {
      color: currentColor;
    }
  }
  &__value--frozen {
    background: none;
    color: ink(22%);
    filter: none;
    text-decoration: line-through;
    text-decoration-thickness: 0.06em;
    text-decoration-color: var(--error-border);
  }
  &__cta:disabled {
    opacity: 0.6;
    cursor: progress;
  }
}

// ─── Content ──────────────────────────────────────────────────────
.dashboard-content {
  flex: 1;
  // the loan cards ride up into the hero
  margin-top: -3.25rem;
  padding: 0 1.5rem 4rem;
  position: relative;
  z-index: 1;
  @media (max-width: 600px) {
    padding: 0 1rem 3rem;
  }

  &__inner {
    max-width: $content-width;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 2.5rem;
  }
}

.count-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 22px;
  height: 22px;
  padding: 0 7px;
  font-size: 0.72rem;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  color: $amber-ink;
  background: amber(10%);
  border-radius: 999px;
}

// ─── Active loans ─────────────────────────────────────────────────
.loans {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;

  &__header {
    display: flex;
    align-items: center;
    gap: 0.6rem;
  }
  &__title {
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--secondary-text);
    margin: 0;
  }
  &__list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }
}

.loan-card {
  --tone: #{ink(14%)};
  position: relative;
  display: grid;
  grid-template-columns: 64px minmax(0, 1fr) auto auto;
  grid-template-areas: 'cover main due actions';
  align-items: center;
  gap: 0.75rem 1.5rem;
  padding: 1rem 1.25rem 1rem 1rem;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 16px;
  box-shadow: 0 14px 40px rgba(0, 0, 0, 0.08);
  overflow: hidden;
  animation: rise 0.55s $ease-out calc(0.15s + var(--i, 0) * 80ms) both;

  // urgency edge
  &::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: var(--tone);
  }
  &--soon {
    --tone: #{$amber};
  }
  &--overdue {
    --tone: var(--error-text);
    border-color: color-mix(in srgb, var(--error-text) 30%, var(--divider));
    background:
      linear-gradient(90deg, var(--danger-tint), transparent 55%), var(--secondary-background);
  }

  @media (max-width: 900px) {
    grid-template-columns: 56px minmax(0, 1fr) auto;
    grid-template-areas:
      'cover main due'
      'actions actions actions';
  }
  @media (max-width: 560px) {
    grid-template-columns: 52px minmax(0, 1fr);
    grid-template-areas:
      'cover main'
      'due due'
      'actions actions';
    padding: 1rem;
    gap: 0.85rem 1rem;
  }

  &--skeleton {
    min-height: 104px;
    background: ink(4%);
    box-shadow: none;
    animation: pulse 1.5s ease-in-out infinite;
    &::before {
      display: none;
    }
  }

  &__cover {
    grid-area: cover;
    display: grid;
    place-items: center;
    aspect-ratio: 3 / 4;
    width: 100%;
    border-radius: 8px;
    overflow: hidden;
    background: linear-gradient(160deg, #{amber(24%)}, #{amber(6%)});
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  }
  &__initial {
    font-size: 1.6rem;
    font-weight: 800;
    color: $amber-ink;
  }

  &__main {
    grid-area: main;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.3rem;
    min-width: 0;
  }
  &__cta {
    font-size: 1.15rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    line-height: 1.2;
    color: var(--primary-text);
    transition: color 0.2s;
    &:hover {
      color: $amber-ink;
    }
  }

  &__due {
    grid-area: due;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.15rem;
    text-align: right;
    @media (max-width: 560px) {
      display: grid;
      grid-template-columns: auto 1fr;
      align-items: baseline;
      justify-items: start;
      column-gap: 0.75rem;
      text-align: left;
      padding-top: 0.75rem;
      border-top: 1px solid var(--divider);
    }
  }
  &__due-rel {
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--secondary-text);
    @media (max-width: 560px) {
      grid-column: 1 / -1;
    }
  }
  &--soon &__due-rel {
    color: $amber-ink;
  }
  &--overdue &__due-rel {
    color: var(--error-text);
  }
  &__due-date {
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    font-variant-numeric: tabular-nums;
    color: var(--primary-text);
  }

  &__actions {
    grid-area: actions;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 0.5rem;
    @media (max-width: 900px) {
      justify-content: flex-start;
    }
  }
}

@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.loan-location {
  display: inline-flex;
  align-items: flex-start;
  gap: 0.3rem;
  font-size: 0.8rem;
  color: var(--secondary-text);
  strong {
    font-weight: 600;
    color: var(--primary-text);
  }
  > .icon-svg {
    flex-shrink: 0;
    width: 0.95rem;
    height: 0.95rem;
    margin-top: 0.1rem;
    color: $amber;
  }
  &__map {
    margin-left: 0.35rem;
    font-size: 0.75rem;
    &:link,
    &:visited {
      color: $amber-ink;
    }
  }
}

.extension-note {
  max-width: 17rem;
  font-size: 0.78rem;
  line-height: 1.35;
  color: $amber-ink;
}

.ext-meter {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin-top: 0.15rem;
  font-size: 0.72rem;
  color: var(--secondary-text);
  white-space: nowrap;

  &__pips {
    display: inline-flex;
    gap: 3px;
  }
  &__pip {
    width: 0.5rem;
    height: 0.5rem;
    border: 1.5px solid amber(70%);
    border-radius: 50%;
    &--used {
      background: $amber;
      border-color: $amber;
    }
  }
}

.loans-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 2.5rem 1.5rem;
  text-align: center;
  background: var(--secondary-background);
  border: 1px dashed ink(18%);
  border-radius: 16px;
  animation: rise 0.55s $ease-out 0.15s both;

  &__icon {
    width: 2.25rem;
    height: 2.25rem;
    color: $amber;
  }
  &__text {
    margin: 0;
    padding: 0;
    color: var(--secondary-text);
    font-size: 0.95rem;
  }
}

// ─── Reservations + history ───────────────────────────────────────
.dash-columns {
  display: grid;
  grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr);
  gap: 1.25rem;
  align-items: start;
  animation: rise 0.55s $ease-out 0.3s both;
  @media (max-width: 860px) {
    grid-template-columns: 1fr;
  }
}

.panel {
  border-radius: 16px;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  overflow: hidden;

  &--quiet {
    background: transparent;
  }

  &__header {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--divider);
  }
  &__title {
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: -0.01em;
    color: var(--primary-text);
    margin: 0;
  }
  &__empty {
    margin: 0;
    padding: 1.5rem 1.25rem;
    font-size: 0.875rem;
    color: var(--secondary-text);
  }
  &__note {
    margin: 0;
    padding: 0.75rem 1.25rem 1rem;
    font-size: 0.75rem;
    color: var(--secondary-text);
    border-top: 1px dashed var(--divider);
  }
}

.queue {
  list-style: none;
  margin: 0;
  padding: 0;

  &__row {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    & + & {
      border-top: 1px solid var(--divider);
    }
  }
  &__pos {
    min-width: 2.5rem;
    font-size: 2rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -0.05em;
    font-variant-numeric: tabular-nums;
    color: $amber-ink;
    small {
      font-size: 0.55em;
      font-weight: 700;
      opacity: 0.6;
      margin-right: 1px;
    }
  }
  &__body {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
  }
  &__cta {
    font-weight: 700;
    color: var(--primary-text);
    &:hover {
      color: $amber-ink;
    }
  }
  &__hint {
    font-size: 0.75rem;
    color: var(--secondary-text);
  }
  @media (max-width: 420px) {
    &__row {
      grid-template-columns: auto minmax(0, 1fr);
      .action-btn {
        grid-column: 2;
        justify-self: start;
      }
    }
  }
}

// History: a quiet timeline
.history {
  list-style: none;
  margin: 0;
  padding: 0.5rem 1.25rem 0.75rem;

  &__row {
    position: relative;
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 1rem;
    padding: 0.6rem 0 0.6rem 1.25rem;
    font-size: 0.875rem;
    // dot + rail
    &::before {
      content: '';
      position: absolute;
      left: 0;
      top: 1.05rem;
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--secondary-background);
      border: 1.5px solid ink(30%);
    }
    &:not(:last-child)::after {
      content: '';
      position: absolute;
      left: 3px;
      top: 1.6rem;
      bottom: -0.55rem;
      width: 1.5px;
      background: ink(12%);
    }
  }
  &__title {
    color: var(--primary-text);
  }
  &__date {
    font-size: 0.8rem;
    font-variant-numeric: tabular-nums;
    color: var(--secondary-text);
    white-space: nowrap;
  }
}

// ─── Action buttons ───────────────────────────────────────────────
.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.45rem 0.85rem;
  font-size: 0.8rem;
  font-weight: 600;
  font-family: inherit;
  color: var(--primary-text);
  background: var(--background);
  border: 1px solid var(--divider);
  border-radius: 9px;
  cursor: pointer;
  white-space: nowrap;
  transition:
    border-color 0.2s,
    color 0.2s,
    background 0.2s,
    transform 0.15s ease;

  .icon-svg {
    width: 0.95rem;
    height: 0.95rem;
  }
  &:hover {
    border-color: $amber;
    color: $amber-ink;
  }
  &:active {
    transform: scale(0.97);
  }
  &:focus-visible {
    outline: 2px solid $amber;
    outline-offset: 2px;
  }

  &--primary {
    border-color: amber(55%);
    background: amber(8%);
  }

  &--quiet {
    background: transparent;
    border-color: transparent;
    color: var(--secondary-text);
    &:hover {
      border-color: color-mix(in srgb, var(--error-text) 35%, transparent);
      color: var(--error-text);
    }
  }

  &--danger {
    color: var(--error-text);
    border-color: var(--error-border);
    background: var(--error-muted);
    &:hover {
      border-color: var(--error-text);
      color: var(--error-text-hover);
    }
  }
}

.action-btn__price {
  padding: 0.05rem 0.45rem;
  font-size: 0.68rem;
  font-weight: 800;
  color: $amber-ink;
  background: amber(14%);
  border-radius: 999px;

  &--free {
    color: var(--success-text);
    background: var(--success-muted);
  }
}

// ─── Footer ───────────────────────────────────────────────────────
.dash-footer-actions {
  display: flex;
  justify-content: center;
}

// `__cta` opts out of the global amber link rule (see _typography.scss)
.dash-settings__cta {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.5rem 1rem;
  border-radius: 999px;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--secondary-text);
  transition:
    color 0.2s,
    background 0.2s;
  .icon-svg {
    width: 1rem;
    height: 1rem;
    transition: rotate 0.4s $ease-out;
  }
  &:hover {
    color: var(--primary-text);
    background: ink(5%);
    .icon-svg {
      rotate: 60deg;
    }
  }
}

// ─── Dialog ───────────────────────────────────────────────────────
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 200;
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.dialog {
  display: flex;
  flex-direction: column;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 18px;
  padding: 1.5rem 1.75rem 1.75rem;
  width: 100%;
  max-width: 440px;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
  }
  &__title {
    font-size: 1.1rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--primary-text);
    margin: 0;
  }
  &__close {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    padding: 0;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: var(--secondary-text);
    cursor: pointer;
    transition:
      background 0.15s,
      color 0.15s;
    .icon-svg {
      width: 1.15rem;
      height: 1.15rem;
    }
    &:hover {
      background: ink(6%);
      color: var(--primary-text);
    }
  }
  &__game {
    font-size: 0.9rem;
    font-weight: 600;
    color: $amber-ink;
    margin: 0.15rem 0 1.25rem;
    padding: 0;
  }
  &__location {
    margin-bottom: 1.25rem;
  }
  &__label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--secondary-text);
    margin-bottom: 0.4rem;
    letter-spacing: 0.03em;
  }
  // UiVirtualDropdown draws its own complete box (background/border/
  // shadow) — unlike a plain <select>, this must NOT add a second one.
  &__select {
    display: block;
    width: 100%;
    margin-bottom: 0.5rem;
  }
  &__actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
  }
}

.extension-cost {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
  margin: 0 0 0.6rem;
  padding: 0.7rem 0.9rem;
  font-size: 0.85rem;
  color: var(--secondary-text);
  background: amber(8%);
  border: 1px solid amber(30%);
  border-radius: 10px;

  strong {
    color: $amber-ink;
  }
  &__hint {
    margin: 0 0 0.5rem;
    font-size: 0.8rem;
    color: var(--secondary-text);
  }
}

.extension-round {
  margin: 0 0 1.25rem;
  font-size: 0.8rem;
  line-height: 1.45;
  color: var(--secondary-text);
}

// ─── Modal transition ─────────────────────────────────────────────
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
  .dialog {
    transition:
      opacity 0.2s ease,
      transform 0.25s $ease-out;
  }
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  .dialog {
    opacity: 0;
    transform: translateY(10px) scale(0.98);
  }
}

.renew-error {
  position: fixed;
  bottom: 1.5rem;
  left: 50%;
  transform: translateX(-50%);
  z-index: 300;
}
</style>
