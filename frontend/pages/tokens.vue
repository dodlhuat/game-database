<template>
  <div class="tokens-page">
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <p class="page-hero__eyebrow">{{ $t('pages.tokens.my_account') }}</p>
        <h1 class="page-hero__title">{{ $t('pages.tokens.title') }}</h1>

        <div class="balance-panel">
          <div class="balance-panel__primary">
            <svg class="icon-svg balance-panel__glyph" aria-hidden="true">
              <use href="/svg-icons/icons.svg#token" />
            </svg>
            <div class="balance-panel__primary-text">
              <span class="balance-panel__value">{{ freeTokens }}</span>
              <span class="balance-panel__label">{{ $t('pages.tokens.balance_free') }}</span>
            </div>
          </div>

          <div class="balance-panel__secondary">
            <div class="balance-chip">
              <svg class="icon-svg balance-chip__icon" aria-hidden="true">
                <use href="/svg-icons/icons.svg#wallet" />
              </svg>
              <span class="balance-chip__val">{{ auth.user?.tokens ?? 0 }}</span>
              <span class="balance-chip__label">{{ $t('pages.tokens.balance_total') }}</span>
            </div>
            <div v-if="blockedTokens > 0" class="balance-chip balance-chip--locked">
              <svg class="icon-svg balance-chip__icon" aria-hidden="true">
                <use href="/svg-icons/icons.svg#lock" />
              </svg>
              <span class="balance-chip__val">{{ blockedTokens }}</span>
              <span class="balance-chip__label">{{ $t('pages.tokens.balance_blocked') }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="tokens-content">
      <div class="tokens-content__inner">
        <div v-if="!auth.canBorrow" class="empty-state">
          <div class="empty-state__icon">
            <svg class="icon-svg" aria-hidden="true"><use href="/svg-icons/icons.svg#lock" /></svg>
          </div>
          <p class="empty-state__text">
            {{
              auth.isSupporter
                ? $t('pages.tokens.not_member_supporter')
                : $t('pages.tokens.not_member')
            }}
          </p>
          <NuxtLink to="/upgrade" class="button button-primary empty-state__cta">
            {{ $t('btn.upgrade') }} →
          </NuxtLink>
        </div>

        <template v-else>
          <TransitionGroup name="alert-pop" tag="div" class="alert-stack">
            <div v-if="success" key="success" class="alert alert-success">{{ success }}</div>
            <div v-if="error" key="error" class="alert alert-error">{{ error }}</div>
          </TransitionGroup>

          <div v-if="packagesLoading" class="token-grid">
            <div v-for="n in 3" :key="n" class="token-card token-card--skeleton">
              <span class="placeholder w-6 skeleton-bar skeleton-bar--lg" />
              <span class="placeholder w-4 skeleton-bar" />
              <span class="placeholder w-8 skeleton-bar" />
              <span class="placeholder w-12 skeleton-bar skeleton-bar--btn" />
            </div>
          </div>

          <div v-else class="token-grid">
            <div
              v-for="(pkg, i) in tokenPackages"
              :key="pkg.amount"
              class="token-card"
              :class="{ 'token-card--featured': pkg.featured }"
              :style="{ '--i': i }"
            >
              <div v-if="pkg.featured" class="token-card__badge">
                {{ $t('pages.tokens.popular') }}
              </div>
              <div class="token-card__amount">{{ pkg.amount }}</div>
              <div class="token-card__label">Token</div>
              <div class="token-card__price">{{ formatPrice(pkg) }}</div>
              <PayPalButtons
                :amount="pkg.amount"
                @success="onPurchaseSuccess"
                @error="onPurchaseError"
              />
            </div>
          </div>

          <div class="token-info">
            <h3 class="token-info__title">{{ $t('pages.tokens.costs_title') }}</h3>
            <ul class="token-info__list">
              <li>{{ $t('pages.tokens.cost_game') }}</li>
              <li>{{ $t('pages.tokens.cost_deposit') }}</li>
              <li>{{ $t('pages.tokens.cost_package') }}</li>
              <li>{{ $t('pages.tokens.cost_extension') }}</li>
            </ul>
          </div>

          <!-- ── Transaktionsverlauf ──────────────────────────────── -->
          <div class="tx-section">
            <h3 class="tx-section__title">
              <svg class="icon-svg" aria-hidden="true">
                <use href="/svg-icons/icons.svg#history" />
              </svg>
              {{ $t('pages.tokens.history_title') }}
            </h3>

            <div v-if="txLoading" class="tx-state"><div class="spinner" /></div>

            <div v-else-if="!transactions.length" class="tx-state tx-state--empty">
              <svg class="icon-svg" aria-hidden="true">
                <use href="/svg-icons/icons.svg#inbox" />
              </svg>
              <p class="tx-empty">{{ $t('pages.tokens.history_empty') }}</p>
            </div>

            <div v-else>
              <table class="tx-table">
                <tbody>
                  <tr
                    v-for="(tx, i) in transactions"
                    :key="tx.id"
                    class="tx-row"
                    :style="{ '--i': i }"
                  >
                    <td class="tx-row__date">{{ formatDate(tx.created_at) }}</td>
                    <td class="tx-row__desc">
                      <span class="badge" :class="txClass(tx.type)">{{ txLabel(tx.type) }}</span>
                      <span v-if="tx.description" class="tx-row__detail">{{ tx.description }}</span>
                    </td>
                    <td
                      class="tx-row__amount"
                      :class="tx.amount >= 0 ? 'tx-row__amount--pos' : 'tx-row__amount--neg'"
                    >
                      {{ tx.amount >= 0 ? '+' : '' }}{{ tx.amount }}
                    </td>
                  </tr>
                </tbody>
              </table>

              <div v-if="txMeta.last_page > 1" class="tx-pagination">
                <button
                  class="action-btn"
                  :disabled="txPage === 1"
                  aria-label="Vorherige Seite"
                  @click="loadTx(txPage - 1)"
                >
                  <svg class="icon-svg" aria-hidden="true">
                    <use href="/svg-icons/icons.svg#chevron_left" />
                  </svg>
                </button>
                <span class="tx-pagination__pos">{{ txPage }} / {{ txMeta.last_page }}</span>
                <button
                  class="action-btn"
                  :disabled="txPage === txMeta.last_page"
                  aria-label="Nächste Seite"
                  @click="loadTx(txPage + 1)"
                >
                  <svg class="icon-svg" aria-hidden="true">
                    <use href="/svg-icons/icons.svg#chevron_right" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { TokenTransaction } from '~/composables/useLoans'

definePageMeta({ middleware: ['auth'] })

interface TokenPackage {
  amount: number
  price_cents: number
  currency: string
  featured: boolean
}

const auth = useAuthStore()
const api = useApi()
const { fetchTokenTransactions } = useLoans()
const { t } = useI18n()
const success = ref('')
const error = ref('')

const blockedTokens = computed(() => auth.user?.tokens_blocked ?? 0)
const freeTokens = computed(() => (auth.user?.tokens ?? 0) - blockedTokens.value)

const tokenPackages = ref<TokenPackage[]>([])
const packagesLoading = ref(true)

const transactions = ref<TokenTransaction[]>([])
const txLoading = ref(false)
const txPage = ref(1)
const txMeta = ref({ last_page: 1, total: 0 })

async function loadPackages() {
  packagesLoading.value = true
  try {
    const data = await api.get<{
      data: Array<{ amount: number; price_cents: number; currency: string }>
    }>('/tokens/packages')
    const middle = Math.floor(data.data.length / 2)
    tokenPackages.value = data.data.map((pkg, i) => ({ ...pkg, featured: i === middle }))
  } catch {
    error.value = 'Token-Pakete konnten nicht geladen werden.'
  } finally {
    packagesLoading.value = false
  }
}

function formatPrice(pkg: TokenPackage): string {
  return new Intl.NumberFormat('de-AT', { style: 'currency', currency: pkg.currency }).format(
    pkg.price_cents / 100
  )
}

function onPurchaseSuccess(payload: { message: string; user: unknown }) {
  if (payload.user) auth.setUser(payload.user as Parameters<typeof auth.setUser>[0])
  success.value = payload.message
  error.value = ''
  loadTx(1)
}

function onPurchaseError(message: string) {
  error.value = message
  success.value = ''
}

async function loadTx(page: number) {
  txLoading.value = true
  txPage.value = page
  try {
    const data = await fetchTokenTransactions(page)
    transactions.value = data.data
    txMeta.value = { last_page: data.meta.last_page, total: data.meta.total }
  } finally {
    txLoading.value = false
  }
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString('de-AT', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

const TX_LABELS: Record<string, string> = {
  BORROW: 'pages.tokens.tx_borrow',
  DEPOSIT_BLOCK: 'pages.tokens.tx_deposit_block',
  DEPOSIT_RELEASE: 'pages.tokens.tx_deposit_release',
  DEPOSIT_FORFEIT: 'pages.tokens.tx_deposit_forfeit',
  PURCHASE: 'pages.tokens.tx_purchase',
  ADMIN_ADJUSTMENT: 'pages.tokens.tx_admin',
}
const TX_CLASSES: Record<string, string> = {
  BORROW: 'badge-error',
  DEPOSIT_BLOCK: 'badge-warning',
  DEPOSIT_RELEASE: 'badge-success',
  DEPOSIT_FORFEIT: 'badge-error badge-solid',
  PURCHASE: 'badge-success',
  ADMIN_ADJUSTMENT: '',
}

function txLabel(type: string) {
  return TX_LABELS[type] ? t(TX_LABELS[type]) : type
}
function txClass(type: string) {
  return TX_CLASSES[type] ?? ''
}

onMounted(() => {
  loadPackages()
  loadTx(1)
})
</script>

<style lang="scss" scoped>
$hero-bg: var(--background);
$nav-height: 64px;
$amber-glow: rgba(212, 146, 30, 0.15);
$hero-text: var(--primary-text);
$hero-muted: var(--secondary-text);
$surface: rgba(255, 255, 255, 0.04);
$surface-hover: rgba(255, 255, 255, 0.07);
$border: rgba(238, 232, 223, 0.1);
$border-amber: rgba(212, 146, 30, 0.4);

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
  &__eyebrow {
    font-size: 0.78rem;
    font-weight: 600;
    color: $amber;
    letter-spacing: 0.02em;
    margin-bottom: 0.4rem;
    opacity: 0;
    animation: heroRise 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.02s both;
  }
  &__title {
    font-size: clamp(1.5rem, 3vw, 2.25rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    color: $hero-text;
    margin: 0 0 1.5rem;
    opacity: 0;
    animation: heroRise 0.55s cubic-bezier(0.16, 1, 0.3, 1) 0.08s both;
  }
}

@keyframes heroRise {
  from {
    opacity: 0;
    translate: 0 10px;
  }
  to {
    opacity: 1;
    translate: 0 0;
  }
}

// ── Balance panel — the free/available figure is the hero stat; total and
// blocked are quieter supporting chips, not three equal-weight numbers ──
.balance-panel {
  display: flex;
  align-items: center;
  gap: 1.75rem;
  flex-wrap: wrap;
  opacity: 0;
  animation: heroRise 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.16s both;

  &__primary {
    display: flex;
    align-items: center;
    gap: 0.85rem;
  }
  &__primary-text {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
  }
  &__glyph {
    width: 2.5rem;
    height: 2.5rem;
    padding: 0.55rem;
    border-radius: 50%;
    color: $amber;
    background: radial-gradient(circle at 30% 30%, rgba($amber, 0.28), rgba($amber, 0.08));
    box-shadow: 0 0 0 1px $border-amber;
    flex-shrink: 0;
  }
  &__value {
    font-size: 2.75rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -0.03em;
    color: $amber;
  }
  &__label {
    font-size: 0.78rem;
    color: $hero-muted;
    text-transform: uppercase;
    letter-spacing: 0.06em;
  }
  &__secondary {
    display: flex;
    gap: 0.6rem;
    flex-wrap: wrap;
  }
}

.balance-chip {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.5rem 0.85rem;
  border-radius: 999px;
  background: $surface;
  border: 1px solid $border;
  &__icon {
    width: 1rem;
    height: 1rem;
    color: $hero-muted;
    flex-shrink: 0;
  }
  &__val {
    font-size: 1rem;
    font-weight: 700;
    color: $hero-text;
  }
  &__label {
    font-size: 0.7rem;
    color: $hero-muted;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }
  &--locked &__icon,
  &--locked &__val {
    color: $amber;
  }
  &--locked {
    border-color: $border-amber;
    background: rgba($amber, 0.08);
  }
}

.tokens-content {
  padding: 2rem 1.5rem 4rem;
  min-height: 60vh;
  background: var(--background);
  &__inner {
    max-width: 800px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }
}

@keyframes tokenCardIn {
  from {
    opacity: 0;
    translate: 0 16px;
  }
  to {
    opacity: 1;
    translate: 0 0;
  }
}

.token-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  align-items: center;
  padding-top: 0.75rem;
  @media (max-width: 540px) {
    grid-template-columns: 1fr;
  }
}
.token-card {
  position: relative;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 14px;
  padding: 1.5rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  align-items: center;
  opacity: 0;
  translate: 0 16px;
  transform: scale(1);
  animation: tokenCardIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) calc(var(--i, 0) * 90ms) both;
  transition:
    transform 0.25s cubic-bezier(0.16, 1, 0.3, 1),
    border-color 0.25s ease,
    box-shadow 0.25s ease;

  &:hover {
    transform: scale(1.02);
    border-color: $border-amber;
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.28);
  }
  &:focus-within {
    border-color: $amber;
    box-shadow: 0 0 0 3px rgba($amber, 0.22);
  }

  // The featured package is the visual centerpiece — scaled up and lifted
  // above its siblings rather than just outlined, so the grid reads as a
  // deliberate hierarchy instead of three identical boxes.
  &--featured {
    z-index: 2;
    margin: -0.85rem 0;
    padding: 1.85rem 1.5rem;
    border-color: $amber;
    background: linear-gradient(160deg, rgba($amber, 0.12), var(--secondary-background) 65%);
    box-shadow: 0 20px 48px rgba(0, 0, 0, 0.32);
    transform: scale(1.06);

    &:hover {
      transform: scale(1.08);
      box-shadow: 0 24px 56px rgba(0, 0, 0, 0.4);
    }

    @media (max-width: 540px) {
      margin: 0;
      transform: scale(1);
      &:hover {
        transform: scale(1);
      }
    }
  }
  &__badge {
    position: absolute;
    top: -0.7rem;
    left: 50%;
    transform: translateX(-50%);
    background: $amber;
    color: #1a0d00;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.2rem 0.7rem;
    border-radius: 999px;
    white-space: nowrap;
    box-shadow: 0 4px 12px rgba($amber, 0.35);
  }
  &__amount {
    font-size: 2rem;
    font-weight: 800;
    color: var(--primary-text);
  }
  &--featured &__amount {
    font-size: 2.5rem;
    color: $amber;
  }
  &__label {
    font-size: 0.8rem;
    color: var(--secondary-text);
    margin-bottom: 0.25rem;
  }
  &__price {
    font-size: 0.9rem;
    font-weight: 700;
    color: $amber;
    margin-bottom: 0.5rem;
  }

  &--skeleton {
    animation: none;
    opacity: 1;
    translate: 0;
    cursor: wait;
    .skeleton-bar {
      display: block;
      margin: 0 auto 0.6rem;
      height: 0.9rem;
      &--lg {
        height: 1.75rem;
        margin-bottom: 0.75rem;
      }
      &--btn {
        height: 2.25rem;
        margin-top: 0.5rem;
        margin-bottom: 0;
      }
    }
  }
}

.token-info {
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 12px;
  padding: 1.25rem 1.5rem;
  &__title {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--primary-text);
    margin: 0 0 0.75rem;
  }
  &__list {
    margin: 0;
    padding-left: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    li {
      font-size: 0.875rem;
      color: var(--secondary-text);
    }
  }
}

// basix already ships fully themed, WCAG-checked .alert and .badge styles
// (see @dodlhuat/basix/css/alert.scss + badge.scss) — no custom overrides
// needed here beyond the mount transition below.

.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  &__icon {
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba($amber, 0.1);
    color: $amber;
    .icon-svg {
      width: 1.4rem;
      height: 1.4rem;
    }
  }
  &__text {
    max-width: 32rem;
    color: var(--secondary-text);
    line-height: 1.6;
  }
  &__cta {
    margin-top: 0.25rem;
  }
}

.alert-stack {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.alert-pop-enter-active {
  transition:
    opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1),
    translate 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.alert-pop-leave-active {
  transition: opacity 0.2s ease;
}
.alert-pop-enter-from {
  opacity: 0;
  translate: 0 -8px;
}
.alert-pop-leave-to {
  opacity: 0;
}

// ── Transaction history ───────────────────────────────────────────
.tx-section {
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 14px;
  overflow: hidden;
}
.tx-section__title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--primary-text);
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--divider);
  margin: 0;
  .icon-svg {
    width: 1.1rem;
    height: 1.1rem;
    color: $amber;
  }
}
.tx-state {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 80px;
  padding: 1rem;
  &--empty {
    flex-direction: column;
    gap: 0.6rem;
    padding: 2.5rem 1rem;
    .icon-svg {
      width: 1.75rem;
      height: 1.75rem;
      color: $hero-muted;
      opacity: 0.6;
    }
  }
}
.tx-empty {
  color: var(--secondary-text);
  font-size: 0.875rem;
}
.tx-table {
  width: 100%;
  border-collapse: collapse;
}

@keyframes txRowIn {
  from {
    opacity: 0;
    translate: 0 6px;
  }
  to {
    opacity: 1;
    translate: 0 0;
  }
}

.tx-row {
  border-bottom: 1px solid var(--divider);
  opacity: 0;
  translate: 0 6px;
  animation: txRowIn 0.35s ease calc(min(var(--i, 0), 10) * 40ms) both;
  transition: background-color 0.15s ease;
  &:last-child {
    border-bottom: none;
  }
  &:hover {
    background: $surface;
  }
  td {
    padding: 0.75rem 1.25rem;
    font-size: 0.875rem;
    vertical-align: middle;
  }
}
.tx-row__date {
  color: var(--secondary-text);
  white-space: nowrap;
  width: 6rem;
}
.tx-row__desc {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
  color: var(--primary-text);
}
.tx-row__detail {
  color: var(--secondary-text);
  font-size: 0.8rem;
}
.tx-row__amount {
  text-align: right;
  font-weight: 700;
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
  &--pos {
    color: var(--success-text, #4ade80);
  }
  &--neg {
    color: var(--error-text, #f87171);
  }
}

// Below ~520px a real <table> gets cramped fast — reflow each row into a
// two-line stack (badge/description on top, date + amount as a quieter
// second line) instead of just letting cells shrink.
@media (max-width: 520px) {
  .tx-table,
  .tx-table tbody {
    display: block;
    width: 100%;
  }
  .tx-row {
    display: grid;
    grid-template-columns: 1fr auto;
    column-gap: 0.75rem;
    row-gap: 0.3rem;
    padding: 0.75rem 1.1rem;
    td {
      display: block;
      padding: 0;
    }
  }
  .tx-row__desc {
    grid-column: 1;
    grid-row: 1;
  }
  .tx-row__date {
    grid-column: 1;
    grid-row: 2;
    width: auto;
    font-size: 0.75rem;
  }
  .tx-row__amount {
    grid-column: 2;
    grid-row: 1 / span 2;
    align-self: center;
  }
}

.tx-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 0.75rem;
  border-top: 1px solid var(--divider);
  font-size: 0.875rem;
  color: var(--secondary-text);
  &__pos {
    font-variant-numeric: tabular-nums;
    min-width: 3.5rem;
    text-align: center;
  }
}
.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  padding: 0;
  background: var(--background);
  border: 1px solid var(--divider);
  border-radius: 50%;
  cursor: pointer;
  color: var(--primary-text);
  transition:
    border-color 0.2s ease,
    color 0.2s ease,
    transform 0.15s ease;
  .icon-svg {
    width: 1rem;
    height: 1rem;
  }
  &:hover:not(:disabled) {
    border-color: $amber;
    color: $amber;
  }
  &:active:not(:disabled) {
    transform: scale(0.92);
  }
  &:focus-visible {
    outline: 2px solid $amber;
    outline-offset: 2px;
  }
  &:disabled {
    opacity: 0.4;
    cursor: not-allowed;
  }
}
</style>
