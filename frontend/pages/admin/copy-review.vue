<template>
  <div class="admin-page">
    <!-- ── Page Hero ────────────────────────────────────────────── -->
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <AdminBreadcrumb :label="$t('admin.breadcrumb.copy_review')" />
        <h1 class="page-hero__title">{{ $t('admin.copy_review.title') }}</h1>
        <p class="page-hero__subtitle">{{ $t('admin.copy_review.subtitle') }}</p>
      </div>
    </section>

    <div class="admin-content">
      <div class="admin-content__inner">
        <!-- ── Scan-Konsole ─────────────────────────────────────── -->
        <section class="scan-console" :class="{ 'scan-console--shake': scanShake }">
          <form class="scan-console__form" @submit.prevent="submitCode">
            <div class="scan-console__field">
              <label class="scan-console__label" for="scan-code">{{
                $t('admin.copy_review.scan_hint')
              }}</label>
              <input
                id="scan-code"
                ref="codeInputRef"
                v-model="scanCode"
                class="scan-console__input"
                type="text"
                autocomplete="off"
                autocapitalize="characters"
                autocorrect="off"
                spellcheck="false"
                enterkeyhint="search"
                :placeholder="$t('admin.copy_review.scan_placeholder')"
                :disabled="cameraOpen"
              />
            </div>
            <button
              v-if="cameraSupported"
              type="button"
              class="scan-console__camera-btn"
              :class="{ 'scan-console__camera-btn--active': cameraOpen }"
              :aria-label="$t('admin.copy_review.scan_start')"
              @click="toggleCamera"
            >
              <svg class="icon-svg" aria-hidden="true">
                <use href="/svg-icons/icons.svg#qr_code_scanner" />
              </svg>
            </button>
          </form>

          <div v-if="cameraOpen" class="scan-console__camera">
            <video ref="videoRef" class="scan-console__video" playsinline muted />
            <div class="scan-console__frame" aria-hidden="true" />
            <button type="button" class="action-btn scan-console__cancel" @click="toggleCamera">
              {{ $t('admin.copy_review.scan_stop') }}
            </button>
          </div>

          <p v-if="scanBusy" class="scan-console__status">
            {{ $t('admin.copy_review.scan_searching') }}
          </p>
          <p v-else-if="scanError" class="scan-console__status scan-console__status--error">
            {{ scanError }}
          </p>
        </section>

        <!-- ── Warteschlange ────────────────────────────────────── -->
        <section class="dash-section">
          <header class="dash-section__header">
            <h2 class="dash-section__title">{{ $t('admin.copy_review.queue') }}</h2>
            <span class="dash-section__count">{{ copies.length }}</span>
          </header>

          <div v-if="loading" class="admin-state"><div class="spinner" /></div>

          <div v-else-if="!copies.length" class="dash-empty">
            <svg class="icon-svg dash-empty__icon" aria-hidden="true">
              <use href="/svg-icons/icons.svg#checklist" />
            </svg>
            <p class="dash-empty__text">{{ $t('admin.empty.copy_review') }}</p>
          </div>

          <ul v-else class="queue-list">
            <li
              v-for="(copy, i) in copies"
              :key="copy.id"
              class="queue-card"
              :class="ageClass(copy)"
              :style="{ '--i': i }"
              tabindex="0"
              role="button"
              @click="openDetail(copy)"
              @keydown.enter="openDetail(copy)"
            >
              <div class="queue-card__main">
                <span class="queue-card__title">{{ copy.game?.title ?? '—' }}</span>
                <span class="queue-card__code text-mono">{{ copy.qr_code ?? '—' }}</span>
              </div>
              <div class="queue-card__meta">
                <span class="badge" :class="reportedConditionClass(copy)">{{
                  reportedConditionLabel(copy)
                }}</span>
                <span v-if="copy.last_return?.user_name" class="queue-card__borrower">{{
                  copy.last_return.user_name
                }}</span>
                <span class="queue-card__wait">{{ waitingLabel(copy) }}</span>
              </div>
            </li>
          </ul>
        </section>
      </div>
    </div>

    <footer class="l-footer">
      <div class="l-footer__inner">
        <div class="l-footer__brand">
          <span class="l-footer__hex" aria-hidden="true">⬡</span
          ><span class="l-footer__name">AUA</span>
        </div>
        <p class="l-footer__copy">{{ $t('common.copyright_short', { year }) }}</p>
      </div>
    </footer>

    <!-- ── Detail Modal ─────────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="detail.open" class="modal-overlay" @click.self="closeDetail">
          <div class="dialog dialog--wide">
            <div class="dialog__header">
              <div>
                <div class="dialog__eyebrow">{{ detail.copy?.game?.title }}</div>
                <h3 class="dialog__title text-mono">{{ detail.copy?.qr_code }}</h3>
              </div>
              <button
                class="dialog__close"
                :aria-label="$t('admin.form.close')"
                @click="closeDetail"
              >
                <svg class="icon-svg" aria-hidden="true">
                  <use href="/svg-icons/icons.svg#close" />
                </svg>
              </button>
            </div>

            <div v-if="detail.copy" class="dialog__body">
              <div class="detail-return">
                <span class="badge" :class="conditionClass(detail.copy.condition)">{{
                  conditionLabel(detail.copy.condition)
                }}</span>

                <template v-if="detail.copy.last_return">
                  <p class="detail-return__row">
                    <strong>{{ $t('admin.copy_review.reported_condition') }}:</strong>
                    {{ reportedConditionLabel(detail.copy) }}
                  </p>
                  <p class="detail-return__row">
                    <strong>{{ $t('admin.copy_review.returned_at') }}:</strong>
                    {{ formatDate(detail.copy.last_return.returned_at) }}
                  </p>
                  <p v-if="detail.copy.last_return.user_name" class="detail-return__row">
                    <strong>{{ $t('admin.copy_review.borrower') }}:</strong>
                    {{ detail.copy.last_return.user_name }}
                  </p>
                </template>
              </div>

              <template v-if="detail.copy.condition === 'REVIEW'">
                <div class="form-field">
                  <label class="form-label">{{ $t('admin.copy_review.target_condition') }}</label>
                  <UiVirtualDropdown
                    v-model="detail.targetCondition"
                    class="form-select"
                    :options="targetConditionOptions"
                  />
                </div>

                <div class="detail-actions">
                  <UiButton :loading="detail.saving" @click="doApprove">{{
                    $t('admin.actions.approve_copy')
                  }}</UiButton>
                  <button
                    class="action-btn action-btn--danger"
                    @click="detail.damagedOpen = !detail.damagedOpen"
                  >
                    {{ $t('admin.actions.mark_damaged') }}
                  </button>
                </div>

                <div v-if="detail.damagedOpen" class="detail-damaged">
                  <p class="form-hint">{{ $t('admin.copy_review.mark_damaged_hint') }}</p>
                  <UiInput v-model="detail.damagedNotes" :label="$t('admin.form.notes')" />
                  <UiButton
                    variant="danger"
                    :loading="detail.damagedSaving"
                    @click="doMarkDamaged"
                    >{{ $t('admin.actions.mark_damaged') }}</UiButton
                  >
                </div>
              </template>
              <p v-else class="form-hint">{{ notInReviewHint }}</p>

              <div v-if="detail.error" class="form-error">{{ detail.error }}</div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import type { AdminCopy } from '~/composables/useAdmin'

definePageMeta({ middleware: ['auth', 'admin'] })

const { t } = useI18n()
const { fetchCopies, lookupCopy, approveCopy, markCopyDamaged } = useAdmin()

const year = new Date().getFullYear()
const loading = ref(true)
const copies = ref<AdminCopy[]>([])

const codeInputRef = ref<HTMLInputElement | null>(null)
const scanCode = ref('')
const scanBusy = ref(false)
const scanError = ref('')
const scanShake = ref(false)

async function fetchQueue() {
  const data = await fetchCopies({ condition: 'REVIEW', per_page: 100 })
  copies.value = data.data as AdminCopy[]
}

onMounted(async () => {
  try {
    await fetchQueue()
  } finally {
    loading.value = false
  }
  focusCodeInput()
})

function focusCodeInput() {
  nextTick(() => codeInputRef.value?.focus())
}

async function submitCode() {
  const code = scanCode.value.trim()
  if (!code) return

  scanBusy.value = true
  scanError.value = ''
  try {
    const data = await lookupCopy(code)
    scanCode.value = ''
    openDetail(data.data)
  } catch {
    scanError.value = t('admin.copy_review.not_found')
    scanShake.value = true
    setTimeout(() => (scanShake.value = false), 300)
  } finally {
    scanBusy.value = false
    focusCodeInput()
  }
}

// ── Warteschlange ──────────────────────────────────────────────────
function waitingDays(copy: AdminCopy): number | null {
  if (!copy.last_return) return null
  const returned = new Date(copy.last_return.returned_at).getTime()
  return Math.max(0, Math.floor((Date.now() - returned) / (1000 * 60 * 60 * 24)))
}

function waitingLabel(copy: AdminCopy) {
  const days = waitingDays(copy)
  if (days === null) return ''
  return days === 0
    ? t('admin.copy_review.waiting_today')
    : t('admin.copy_review.waiting_since', { days })
}

function ageClass(copy: AdminCopy) {
  const days = waitingDays(copy)
  if (days === null) return ''
  if (days > 7) return 'queue-card--old'
  if (days >= 3) return 'queue-card--warm'
  return ''
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString('de-DE', { dateStyle: 'medium' })
}

function reportedConditionLabel(copy: AdminCopy) {
  const m: Record<string, string> = {
    GOOD: 'common.badge.good',
    WORN: 'common.badge.worn',
    DAMAGED: 'common.badge.damaged',
  }
  const key = copy.last_return ? m[copy.last_return.return_condition] : undefined
  return key ? t(key) : '—'
}

function reportedConditionClass(copy: AdminCopy) {
  const m: Record<string, string> = {
    GOOD: '',
    WORN: 'badge-warning',
    DAMAGED: 'badge-error',
  }
  const cond = copy.last_return?.return_condition
  return cond ? (m[cond] ?? '') : ''
}

function conditionLabel(c?: string) {
  const m: Record<string, string> = {
    NEW: 'common.badge.condition_new',
    VERY_GOOD: 'common.badge.condition_very_good',
    GOOD: 'common.badge.condition_good',
    WORN: 'common.badge.condition_worn',
    REVIEW: 'common.badge.condition_review',
    DAMAGED: 'common.badge.condition_damaged',
    LOCKED: 'common.badge.condition_locked',
  }
  return c && m[c] ? t(m[c]) : (c ?? '')
}

function conditionClass(c?: string) {
  const m: Record<string, string> = {
    NEW: 'badge-success',
    VERY_GOOD: 'badge-success',
    GOOD: 'badge-warning',
    WORN: 'badge-warning',
    REVIEW: 'badge-info',
    DAMAGED: 'badge-error',
    LOCKED: '',
  }
  return c ? (m[c] ?? '') : ''
}

const targetConditionOptions = computed(() => [
  { label: t('admin.copy_review.condition_auto'), value: '' },
  { label: t('admin.form.condition_new'), value: 'NEW' },
  { label: t('admin.form.condition_very_good'), value: 'VERY_GOOD' },
  { label: t('admin.form.condition_good'), value: 'GOOD' },
  { label: t('admin.form.condition_worn'), value: 'WORN' },
])

const notInReviewHint = computed(() => {
  const c = detail.copy?.condition
  if (c === 'DAMAGED') return t('admin.copy_review.state_damaged')
  if (c === 'LOCKED') return t('admin.copy_review.state_locked')
  return t('admin.copy_review.state_available')
})

// ── Detail-Modal ───────────────────────────────────────────────────
const detail = reactive({
  open: false,
  copy: null as AdminCopy | null,
  targetCondition: '' as string,
  saving: false,
  damagedOpen: false,
  damagedNotes: '',
  damagedSaving: false,
  error: '',
})

function openDetail(copy: AdminCopy) {
  Object.assign(detail, {
    open: true,
    copy,
    targetCondition: '',
    saving: false,
    damagedOpen: false,
    damagedNotes: '',
    damagedSaving: false,
    error: '',
  })
}

function closeDetail() {
  detail.open = false
  focusCodeInput()
}

function removeFromQueue(id: number) {
  copies.value = copies.value.filter((c) => c.id !== id)
}

async function doApprove() {
  if (!detail.copy) return
  detail.saving = true
  detail.error = ''
  try {
    await approveCopy(detail.copy.id, detail.targetCondition || undefined)
    removeFromQueue(detail.copy.id)
    closeDetail()
  } catch (err: unknown) {
    detail.error = (err as { message?: string }).message ?? t('common.error.generic')
  } finally {
    detail.saving = false
  }
}

async function doMarkDamaged() {
  if (!detail.copy) return
  detail.damagedSaving = true
  detail.error = ''
  try {
    await markCopyDamaged(detail.copy.id, detail.damagedNotes || undefined)
    removeFromQueue(detail.copy.id)
    closeDetail()
  } catch (err: unknown) {
    detail.error = (err as { message?: string }).message ?? t('common.error.generic')
  } finally {
    detail.damagedSaving = false
  }
}

// ── Kamera-Scan ────────────────────────────────────────────────────
const cameraSupported = ref(false)
const cameraOpen = ref(false)
const videoRef = ref<HTMLVideoElement | null>(null)
// eslint-disable-next-line @typescript-eslint/no-explicit-any
let scanner: any = null

onMounted(() => {
  cameraSupported.value = !!navigator.mediaDevices?.getUserMedia
})

async function toggleCamera() {
  if (cameraOpen.value) {
    stopCamera()
    return
  }

  const { default: QrScanner } = await import('qr-scanner')
  if (!(await QrScanner.hasCamera())) {
    scanError.value = t('admin.copy_review.scan_unavailable')
    return
  }

  cameraOpen.value = true
  scanError.value = ''
  await nextTick()
  if (!videoRef.value) return

  scanner = new QrScanner(
    videoRef.value,
    (result: { data: string }) => {
      scanCode.value = result.data
      stopCamera()
      submitCode()
    },
    {
      preferredCamera: 'environment',
      highlightScanRegion: true,
      highlightCodeOutline: true,
      maxScansPerSecond: 5,
    }
  )

  try {
    await scanner.start()
  } catch (err: unknown) {
    const name = (err as { name?: string })?.name
    scanError.value =
      name === 'NotAllowedError'
        ? t('admin.copy_review.scan_denied')
        : t('admin.copy_review.scan_unavailable')
    stopCamera()
  }
}

function stopCamera() {
  scanner?.stop()
  scanner?.destroy()
  scanner = null
  cameraOpen.value = false
  focusCodeInput()
}

onBeforeUnmount(() => {
  stopCamera()
})
</script>

<style lang="scss" scoped>
$hero-bg: #0f0e0c;
$nav-height: 64px;
$amber-08: rgba(212, 146, 30, 0.08);
$amber-14: rgba(212, 146, 30, 0.14);
$amber-25: rgba(212, 146, 30, 0.25);
$amber-glow: rgba(212, 146, 30, 0.16);
$hero-text: #eee8df;
$hero-muted: rgba(238, 232, 223, 0.72);
$hero-muted-50: rgba(238, 232, 223, 0.65);
$hero-divider: rgba(238, 232, 223, 0.1);

.admin-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--background);
}

.page-hero {
  position: relative;
  background: $hero-bg;
  padding: calc(#{$nav-height} + 1.75rem) 1.5rem 4.5rem;
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
  &__title {
    font-size: clamp(1.5rem, 3vw, 2.25rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    color: $hero-text;
    margin: 0;
  }
  &__subtitle {
    margin-top: 0.4rem;
    color: $hero-muted;
    font-size: 0.9rem;
    padding-bottom: 0;
  }
}

.admin-content {
  flex: 1;
  padding: 0 1.5rem 4rem;
  margin-top: -3rem;
  position: relative;
  z-index: 2;
  &__inner {
    max-width: 1100px;
    margin: 0 auto;
  }
}
.admin-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 200px;
}

// ─── Scan-Konsole ───────────────────────────────────────────────────
.scan-console {
  background: #131110;
  border: 1px solid $amber-25;
  border-radius: 18px;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45);
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  transition: transform 0.05s;
  &--shake {
    animation: shake 0.3s;
  }
  &__form {
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: end;
    gap: 1rem;
    @media (max-width: 640px) {
      grid-template-columns: 1fr;
    }
  }
  &__label {
    display: block;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: $hero-muted-50;
    margin-bottom: 0.5rem;
  }
  &__input {
    width: 100%;
    background: transparent;
    border: none;
    border-bottom: 2px solid $amber-25;
    color: $hero-text;
    font-family: monospace;
    font-size: clamp(1.25rem, 4vw, 1.75rem);
    letter-spacing: 0.35em;
    text-transform: uppercase;
    padding: 0.4rem 0 0.4rem 0.25rem;
    transition: border-color 0.2s;
    &:focus {
      outline: none;
      border-color: $amber;
    }
    &::placeholder {
      letter-spacing: 0.35em;
      color: rgba(238, 232, 223, 0.25);
    }
  }
  &__camera-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    border-radius: 14px;
    background: $amber-08;
    border: 1px solid $amber-25;
    color: $amber;
    cursor: pointer;
    transition:
      background 0.2s,
      border-color 0.2s;
    .icon-svg {
      font-size: 1.5rem;
    }
    &:hover {
      background: $amber-14;
    }
    &--active {
      background: $amber;
      color: $hero-bg;
    }
  }
  &__camera {
    position: relative;
    margin-top: 1.25rem;
    border-radius: 12px;
    overflow: hidden;
    background: #000;
    aspect-ratio: 4 / 3;
    max-width: 420px;
  }
  &__video {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  &__frame {
    position: absolute;
    inset: 12%;
    border: 2px solid rgba(212, 146, 30, 0.6);
    border-radius: 8px;
    pointer-events: none;
  }
  &__cancel {
    position: absolute;
    bottom: 0.75rem;
    right: 0.75rem;
  }
  &__status {
    margin-top: 0.9rem;
    font-size: 0.85rem;
    color: $hero-muted-50;
    padding-bottom: 0;
    &--error {
      color: #f87171;
    }
  }
}

@keyframes shake {
  10%,
  90% {
    transform: translateX(-2px);
  }
  20%,
  80% {
    transform: translateX(4px);
  }
  30%,
  50%,
  70% {
    transform: translateX(-8px);
  }
  40%,
  60% {
    transform: translateX(8px);
  }
}

// ─── Warteschlange ──────────────────────────────────────────────────
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

.queue-list {
  list-style: none;
  margin: 0;
  padding: 0;
}
.queue-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--divider);
  border-left: 4px solid var(--divider);
  animation: card-in 0.3s ease backwards;
  animation-delay: calc(var(--i) * 40ms);
  cursor: pointer;
  transition: background 0.15s;
  &:last-child {
    border-bottom: none;
  }
  &:hover,
  &:focus-visible {
    background: var(--background);
  }
  &:focus-visible {
    outline: 2px solid $amber;
    outline-offset: -2px;
  }
  &--warm {
    border-left-color: $amber;
  }
  &--old {
    border-left-color: #f87171;
  }
  &__main {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
  }
  &__title {
    font-weight: 600;
    color: var(--primary-text);
  }
  &__code {
    font-size: 0.75rem;
  }
  &__meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    font-size: 0.8rem;
    color: var(--secondary-text);
  }
  &__borrower {
    font-weight: 600;
    color: var(--primary-text);
  }
  &__wait {
    color: var(--secondary-text);
  }
}

@keyframes card-in {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.text-mono {
  font-family: monospace;
  font-size: 0.8rem;
  color: var(--secondary-text);
}

// ─── Modal ──────────────────────────────────────────────────────────
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
    max-width: 600px;
  }
  &__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1.5rem;
  }
  &__eyebrow {
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: $amber;
    margin-bottom: 0.2rem;
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
    .icon-svg {
      font-size: 1.125rem;
    }
    &:hover {
      background: var(--background);
      color: var(--primary-text);
    }
  }
  &__body {
    max-height: 70vh;
    overflow-y: auto;
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

.detail-return {
  padding: 1rem;
  margin-bottom: 1.25rem;
  background: var(--background);
  border: 1px solid var(--divider);
  border-radius: 10px;
  &__row {
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: var(--secondary-text);
    padding-bottom: 0;
    strong {
      color: var(--primary-text);
    }
  }
}

.detail-actions {
  display: flex;
  gap: 0.75rem;
  margin: 1rem 0;
  flex-wrap: wrap;
}

.detail-damaged {
  padding: 1rem;
  margin-bottom: 1rem;
  background: rgba(239, 68, 68, 0.05);
  border: 1px solid rgba(239, 68, 68, 0.2);
  border-radius: 10px;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  margin-bottom: 0.75rem;
}
.form-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--secondary-text);
  margin-bottom: 0.4rem;
  letter-spacing: 0.03em;
}
.form-select {
  display: block;
  width: 100%;
}
.form-error {
  margin-top: 0.75rem;
  padding: 0.75rem 1rem;
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.25);
  border-radius: 8px;
  color: #f87171;
  font-size: 0.875rem;
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
  &:hover {
    border-color: var(--accent-color);
    color: var(--accent-text);
  }
  &--danger {
    color: #f87171;
    border-color: rgba(239, 68, 68, 0.25);
    background: rgba(239, 68, 68, 0.05);
    &:hover {
      border-color: rgba(239, 68, 68, 0.5);
      color: #fca5a5;
    }
  }
  &:disabled {
    opacity: 0.4;
    cursor: not-allowed;
  }
}

.l-footer {
  background: $hero-bg;
  border-top: 1px solid $hero-divider;
  padding: 1.75rem 1.5rem;
  &__inner {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
  }
  &__brand {
    display: flex;
    align-items: center;
    gap: 0.4rem;
  }
  &__hex {
    font-size: 1.1rem;
    color: $amber;
  }
  &__name {
    font-size: 0.9rem;
    font-weight: 700;
    color: $hero-text;
    letter-spacing: -0.02em;
  }
  &__copy {
    font-size: 0.8rem;
    color: $hero-muted-50;
    padding-bottom: 0;
  }
}
</style>
