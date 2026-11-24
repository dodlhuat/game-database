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

        <div class="balance">
          <div class="balance__main">
            <span class="balance__value" :aria-label="String(freeTokens)">{{
              displayBalance
            }}</span>
            <span class="balance__label">
              <svg class="icon-svg" aria-hidden="true">
                <use href="/svg-icons/icons.svg#token" />
              </svg>
              {{ $t('pages.tokens.balance_free') }}
            </span>
          </div>

          <div class="balance__side">
            <!-- What the balance buys: the boxes grow with the game size -->
            <div
              v-if="auth.canBorrow && enoughFor"
              class="capacity"
              role="img"
              :aria-label="$t('pages.tokens.enough_for_aria', enoughFor)"
            >
              <span class="capacity__label" aria-hidden="true">
                {{ $t('pages.tokens.enough_for') }}
              </span>
              <div class="capacity__items" aria-hidden="true">
                <span
                  v-for="size in sizes"
                  :key="size"
                  class="capacity__item"
                  :class="[`capacity__item--${size}`, { 'is-zero': enoughFor[size] === 0 }]"
                >
                  <span class="capacity__box" />
                  <span class="capacity__count">{{ enoughFor[size] }}×</span>
                  <span class="capacity__size">{{ $t(`pages.tokens.size_${size}`) }}</span>
                </span>
              </div>
            </div>
            <p v-else-if="auth.canBorrow && freeTokens === 0" class="balance__hint">
              {{ $t('pages.tokens.balance_empty_hint') }}
            </p>

            <!-- The split only tells something new when bonus or blocked tokens exist -->
            <div v-if="auth.bonusTokens > 0 || blockedTokens > 0" class="balance__chips">
              <div class="balance-chip">
                <svg class="icon-svg balance-chip__icon" aria-hidden="true">
                  <use href="/svg-icons/icons.svg#wallet" />
                </svg>
                <span class="balance-chip__val">{{ auth.user?.tokens ?? 0 }}</span>
                <span class="balance-chip__label">{{ $t('pages.tokens.balance_normal') }}</span>
              </div>
              <div v-if="auth.bonusTokens > 0" class="balance-chip balance-chip--bonus">
                <svg class="icon-svg balance-chip__icon" aria-hidden="true">
                  <use href="/svg-icons/icons.svg#token" />
                </svg>
                <span class="balance-chip__val">{{ auth.bonusTokens }}</span>
                <span class="balance-chip__label">{{
                  $t('pages.tokens.balance_bonus', { date: formatDate(auth.bonusExpiresAt) })
                }}</span>
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
              auth.isMembershipExpired
                ? $t('pages.tokens.expired')
                : auth.isSupporter
                  ? $t('pages.tokens.not_member_supporter')
                  : $t('pages.tokens.not_member')
            }}
          </p>
          <NuxtLink to="/upgrade" class="button button-primary empty-state__cta">
            {{ auth.isMembershipExpired ? $t('btn.renew') : $t('btn.upgrade') }} →
          </NuxtLink>
        </div>

        <template v-else>
          <TransitionGroup name="alert-pop" tag="div" class="alert-stack">
            <div v-if="success" key="success" class="alert alert-success">{{ success }}</div>
            <div v-if="error" key="error" class="alert alert-error">{{ error }}</div>
          </TransitionGroup>

          <section class="packages" :aria-label="$t('pages.tokens.packages_title')">
            <div v-if="packagesLoading" class="token-grid">
              <div v-for="n in 3" :key="n" class="token-card token-card--skeleton">
                <span class="placeholder w-4 skeleton-bar skeleton-bar--lg" />
                <span class="placeholder w-6 skeleton-bar" />
                <span class="placeholder w-12 skeleton-bar skeleton-bar--btn" />
              </div>
            </div>

            <div v-else class="token-grid">
              <article
                v-for="(pkg, i) in packageViews"
                :key="pkg.amount"
                class="token-card"
                :class="{ 'token-card--featured': pkg.featured }"
                :style="{ '--i': i }"
              >
                <div v-if="pkg.featured" class="token-card__badge">
                  {{ $t('pages.tokens.popular') }}
                </div>

                <!-- Bigger package, taller stack of game tokens -->
                <div class="token-stack" :style="{ '--n': pkg.discs }" aria-hidden="true">
                  <span
                    v-for="d in pkg.discs"
                    :key="d"
                    class="token-stack__disc"
                    :style="{ '--d': d - 1 }"
                  />
                </div>

                <div class="token-card__head">
                  <span class="token-card__amount">{{ pkg.amount }}</span>
                  <span class="token-card__unit">Token</span>
                </div>

                <p class="token-card__rate">
                  {{ $t('pages.tokens.per_token', { price: pkg.perToken }) }}
                  <span
                    v-if="pkg.saving > 0"
                    class="token-card__save"
                    :aria-label="$t('pages.tokens.saving_aria', { pct: pkg.saving })"
                  >
                    {{ $t('pages.tokens.saving', { pct: pkg.saving }) }}
                  </span>
                </p>

                <div class="token-card__price">{{ pkg.price }}</div>

                <button
                  class="button token-card__buy"
                  :class="pkg.featured ? 'button-primary' : 'button-outline'"
                  :aria-label="
                    $t('pages.tokens.buy_aria', { amount: pkg.amount, price: pkg.price })
                  "
                  @click="openCheckout(pkg.source)"
                >
                  {{ $t('btn.buy') }}
                </button>
              </article>
            </div>
          </section>

          <div class="ledger">
            <!-- ── Preisliste ─────────────────────────────────────────── -->
            <aside class="price-list">
              <h2 class="price-list__title">{{ $t('pages.tokens.costs_title') }}</h2>
              <ul class="price-list__rows">
                <li v-for="row in priceRows" :key="row.key" class="price-row">
                  <span class="price-row__label">{{ row.label }}</span>
                  <span class="price-row__leader" aria-hidden="true" />
                  <span class="price-row__value">
                    <template v-if="row.value !== null">
                      {{ row.value }}
                      <span class="price-row__unit">{{ $t('pages.tokens.costs_unit') }}</span>
                    </template>
                    <svg v-else class="icon-svg" aria-hidden="true">
                      <use href="/svg-icons/icons.svg#lock" />
                    </svg>
                  </span>
                  <span v-if="row.hint" class="price-row__hint">{{ row.hint }}</span>
                </li>
              </ul>
            </aside>

            <!-- ── Transaktionsverlauf ────────────────────────────────── -->
            <section class="tx-section">
              <h2 class="tx-section__title">
                <svg class="icon-svg" aria-hidden="true">
                  <use href="/svg-icons/icons.svg#history" />
                </svg>
                {{ $t('pages.tokens.history_title') }}
              </h2>

              <div v-if="txLoading" class="tx-state"><div class="spinner" /></div>

              <div v-else-if="!transactions.length" class="tx-state tx-state--empty">
                <svg class="icon-svg" aria-hidden="true">
                  <use href="/svg-icons/icons.svg#inbox" />
                </svg>
                <p class="tx-empty">{{ $t('pages.tokens.history_empty') }}</p>
              </div>

              <div v-else>
                <div v-for="group in txGroups" :key="group.month" class="tx-group">
                  <h3 class="tx-group__month">{{ group.month }}</h3>
                  <ul class="tx-list">
                    <li
                      v-for="tx in group.items"
                      :key="tx.id"
                      class="tx-row"
                      :class="`tx-row--${txTone(tx.type)}`"
                      :style="{ '--i': tx.index }"
                    >
                      <span class="tx-row__dot" aria-hidden="true" />
                      <span class="tx-row__main">
                        <span class="tx-row__type">{{ txLabel(tx.type) }}</span>
                        <span v-if="tx.detail" class="tx-row__detail">{{ tx.detail }}</span>
                      </span>
                      <time class="tx-row__date" :datetime="tx.created_at">
                        {{ formatDate(tx.created_at) }}
                      </time>
                      <span
                        class="tx-row__amount"
                        :class="tx.amount >= 0 ? 'tx-row__amount--pos' : 'tx-row__amount--neg'"
                      >
                        {{ tx.amount >= 0 ? '+' : '−' }}{{ Math.abs(tx.amount) }}
                      </span>
                    </li>
                  </ul>
                </div>

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
            </section>
          </div>
        </template>
      </div>
    </div>

    <PaymentCheckoutModal
      :pkg="checkoutPackage"
      @error="onPurchaseError"
      @close="checkoutPackage = null"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import type { TokenTransaction } from '~/composables/useLoans'
import type { LoanSettings } from '~/composables/useLoanSettings'

definePageMeta({ middleware: ['auth'] })

interface TokenPackage {
  amount: number
  price_cents: number
  currency: string
  featured: boolean
}

type GameSize = 'small' | 'medium' | 'large'
type TxTone = 'pos' | 'neg' | 'warn' | 'neutral'

const auth = useAuthStore()
const api = useApi()
const { fetchTokenTransactions } = useLoans()
const { fetchSettings } = useLoanSettings()
const { t } = useI18n()
const success = ref('')
const error = ref('')
const costs = ref<LoanSettings | null>(null)

const blockedTokens = computed(() => auth.user?.tokens_blocked ?? 0)
const freeTokens = computed(() => auth.freeTokens)

// ── Balance count-up: the one expressive moment of the page ─────────
const displayBalance = ref(freeTokens.value)
let countFrame = 0

function animateBalance(from: number, to: number) {
  cancelAnimationFrame(countFrame)
  const reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
  if (reduced || from === to) {
    displayBalance.value = to
    return
  }
  const start = performance.now()
  const duration = 900
  const step = (now: number) => {
    const p = Math.min((now - start) / duration, 1)
    const eased = 1 - Math.pow(1 - p, 4)
    displayBalance.value = Math.round(from + (to - from) * eased)
    if (p < 1) countFrame = requestAnimationFrame(step)
  }
  countFrame = requestAnimationFrame(step)
}

watch(freeTokens, (to) => {
  if (import.meta.client) animateBalance(displayBalance.value, to)
})
onBeforeUnmount(() => cancelAnimationFrame(countFrame))

const sizes: GameSize[] = ['small', 'medium', 'large']

const enoughFor = computed<Record<GameSize, number> | null>(() => {
  const c = costs.value
  if (!c || freeTokens.value <= 0) return null
  const fits = (cost: number) => (cost > 0 ? Math.floor(freeTokens.value / cost) : 0)
  return {
    small: fits(c.loan_cost_small),
    medium: fits(c.loan_cost_medium),
    large: fits(c.loan_cost_large),
  }
})

// ── Packages ────────────────────────────────────────────────────────
const tokenPackages = ref<TokenPackage[]>([])
const packagesLoading = ref(true)
const checkoutPackage = ref<TokenPackage | null>(null)

function openCheckout(pkg: TokenPackage) {
  checkoutPackage.value = pkg
}

function money(cents: number, currency: string): string {
  return new Intl.NumberFormat('de-AT', { style: 'currency', currency }).format(cents / 100)
}

const packageViews = computed(() => {
  const base = tokenPackages.value[0]
  const baseRate = base ? base.price_cents / base.amount : 0
  return tokenPackages.value.map((pkg, i) => {
    const rate = pkg.price_cents / pkg.amount
    // floor, so the saving never reads better than it is
    const saving = baseRate > 0 ? Math.floor((1 - rate / baseRate) * 100) : 0
    return {
      source: pkg,
      amount: pkg.amount,
      featured: pkg.featured,
      price: money(pkg.price_cents, pkg.currency),
      perToken: money(rate, pkg.currency),
      saving: Math.max(saving, 0),
      discs: Math.min(3 + i, 7),
    }
  })
})

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

function onPurchaseError(message: string) {
  error.value = message
  success.value = ''
}

// ── Price list ──────────────────────────────────────────────────────
const priceRows = computed(() => {
  const c = costs.value
  const rows: Array<{ key: string; label: string; value: string | null; hint?: string }> = []
  if (c) {
    rows.push(
      {
        key: 'game',
        label: t('pages.tokens.cost_game_label'),
        value: `${c.loan_cost_small} / ${c.loan_cost_medium} / ${c.loan_cost_large}`,
        hint: t('pages.tokens.cost_game_hint'),
      },
      {
        key: 'package',
        label: t('pages.tokens.cost_package_label'),
        value: String(c.package_loan_cost),
      },
      {
        key: 'extension',
        label: t('pages.tokens.cost_extension_label'),
        value: String(c.extension_cost),
        hint: t('pages.tokens.cost_extension_hint', { max: c.max_extensions }),
      },
      {
        key: 'reservation',
        label: t('pages.tokens.cost_reservation_label'),
        value: String(c.reservation_cost),
        hint: t('pages.tokens.cost_reservation_hint'),
      }
    )
  }
  rows.push({
    key: 'deposit',
    label: t('pages.tokens.cost_deposit_label'),
    value: null,
    hint: t('pages.tokens.cost_deposit_hint'),
  })
  return rows
})

// ── Transactions ────────────────────────────────────────────────────
const transactions = ref<TokenTransaction[]>([])
const txLoading = ref(false)
const txPage = ref(1)
const txMeta = ref({ last_page: 1, total: 0 })

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

// The type label already says "purchase"; provider references (old Stripe
// payment intent ids) and the token count repeat what the row shows anyway.
function txDetail(tx: TokenTransaction): string | null {
  if (!tx.description) return null
  const cleaned = tx.description.replace(/\s*\(Payment Intent[^)]*\)/i, '').trim()
  if (tx.type === 'PURCHASE' && /^(stripe|token)-kauf\b/i.test(cleaned)) return null
  return cleaned || null
}

const monthFormat = new Intl.DateTimeFormat('de-AT', { month: 'long', year: 'numeric' })

const txGroups = computed(() => {
  const groups: Array<{
    month: string
    items: Array<TokenTransaction & { detail: string | null; index: number }>
  }> = []
  transactions.value.forEach((tx, index) => {
    const month = monthFormat.format(new Date(tx.created_at))
    let group = groups[groups.length - 1]
    if (!group || group.month !== month) {
      group = { month, items: [] }
      groups.push(group)
    }
    group.items.push({ ...tx, detail: txDetail(tx), index })
  })
  return groups
})

function formatDate(iso: string | null) {
  if (!iso) return '—'
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
  BONUS_GRANT: 'pages.tokens.tx_bonus_grant',
  BONUS_EXPIRE: 'pages.tokens.tx_bonus_expire',
  REFUND: 'pages.tokens.tx_refund',
  EXTENSION: 'pages.tokens.tx_extension',
  RESERVATION: 'pages.tokens.tx_reservation',
}
const TX_TONES: Record<string, TxTone> = {
  BORROW: 'neg',
  DEPOSIT_BLOCK: 'warn',
  DEPOSIT_RELEASE: 'pos',
  DEPOSIT_FORFEIT: 'neg',
  PURCHASE: 'pos',
  BONUS_GRANT: 'pos',
  BONUS_EXPIRE: 'warn',
  REFUND: 'pos',
  EXTENSION: 'neg',
  RESERVATION: 'neg',
}

function txLabel(type: string) {
  return TX_LABELS[type] ? t(TX_LABELS[type]) : type
}
function txTone(type: string): TxTone {
  return TX_TONES[type] ?? 'neutral'
}

onMounted(() => {
  animateBalance(0, freeTokens.value)
  loadPackages()
  loadTx(1)
  fetchSettings()
    .then((settings) => (costs.value = settings))
    .catch(() => {
      // die Kostenliste ist nur eine Info, die Seite funktioniert auch ohne sie
    })
})
</script>

<style lang="scss" scoped>
$nav-height: 64px;
$ease-out: cubic-bezier(0.16, 1, 0.3, 1);
// Derived from the text colour so chips, rows and leaders stay visible in
// both themes (a white tint disappears on the light background).
$surface: color-mix(in srgb, var(--primary-text) 4%, transparent);
$border: color-mix(in srgb, var(--primary-text) 12%, transparent);
$border-amber: rgba(212, 146, 30, 0.4);
$content-width: 1040px;
// Small amber text: #f7963d is ~2:1 on the light background, so light mode
// uses a deeper burnt orange (4.5:1); dark mode keeps the bright accent.
$amber-ink: var(--amber-ink);

.tokens-page {
  --amber-ink: #b45f0f;
  --balance-gradient: linear-gradient(175deg, #f0a032 10%, #d4741a 55%, #8a520a 100%);
}
:root[data-theme='dark'] .tokens-page {
  --amber-ink: #{$amber};
  --balance-gradient: linear-gradient(175deg, #ffc35a 10%, #{$amber} 55%, #9a5f0c 100%);
}

// ── Hero ────────────────────────────────────────────────────────────
.page-hero {
  position: relative;
  background: var(--background);
  padding: calc(#{$nav-height} + 2rem) 1.5rem 2.5rem;
  overflow: hidden;
  @media (max-width: 600px) {
    padding: calc(#{$nav-height} + 1.25rem) 1rem 1.75rem;
  }
  &__backdrop {
    position: absolute;
    inset: 0;
    pointer-events: none;
    // fade out so the glow never ends in a hard edge above the packages
    mask-image: linear-gradient(to bottom, black 55%, transparent);
  }
  &__glow {
    position: absolute;
    width: 520px;
    height: 520px;
    top: -220px;
    left: -140px;
    border-radius: 50%;
    filter: blur(110px);
    background: rgba($amber, 0.16);
  }
  &__dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(
      circle,
      color-mix(in srgb, var(--primary-text) 6%, transparent) 1px,
      transparent 1px
    );
    background-size: 24px 24px;
    mask-image: radial-gradient(ellipse 70% 90% at 20% 30%, black 10%, transparent 100%);
  }
  &__body {
    position: relative;
    z-index: 1;
    max-width: $content-width;
    margin: 0 auto;
  }
  &__eyebrow {
    font-size: 0.78rem;
    font-weight: 600;
    color: $amber-ink;
    letter-spacing: 0.02em;
    margin-bottom: 0.4rem;
    animation: heroRise 0.5s $ease-out 0.02s both;
  }
  &__title {
    font-size: clamp(1.5rem, 3vw, 2.25rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    color: var(--primary-text);
    margin: 0 0 1.25rem;
    animation: heroRise 0.55s $ease-out 0.08s both;
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

.balance {
  display: flex;
  align-items: flex-end;
  gap: 1.25rem 3rem;
  flex-wrap: wrap;
  animation: heroRise 0.6s $ease-out 0.16s both;

  &__main {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
  }
  &__value {
    font-size: clamp(4rem, 11vw, 6.5rem);
    font-weight: 800;
    line-height: 0.85;
    letter-spacing: -0.06em;
    font-variant-numeric: tabular-nums;
    // The negative letter-spacing also applies after the last digit, so the
    // box ends before the glyph does — and background-clip: text only paints
    // inside the box, shaving off the right edge of the final digit. Pad the
    // box out to the glyph and cancel it with a negative margin so the
    // layout doesn't move.
    padding-right: 0.1em;
    margin-right: -0.1em;
    background: var(--balance-gradient);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    filter: drop-shadow(0 10px 30px rgba($amber, 0.25));
  }
  &__label {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--secondary-text);
    text-transform: uppercase;
    letter-spacing: 0.12em;
    .icon-svg {
      width: 0.95rem;
      height: 0.95rem;
      color: $amber;
    }
  }
  &__side {
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
    padding-bottom: 0.2rem;
  }
  &__hint {
    color: var(--secondary-text);
    font-size: 0.9rem;
  }
  &__chips {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
  }
}

// Three box sizes in proportion: a quick read of what the balance buys
.capacity {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
  &__label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--secondary-text);
  }
  &__items {
    display: flex;
    align-items: flex-end;
    gap: 1.25rem;
  }
  &__item {
    display: grid;
    grid-template-columns: auto auto;
    grid-template-rows: auto auto;
    align-items: end;
    column-gap: 0.5rem;
    &.is-zero {
      opacity: 0.4;
    }
  }
  &__box {
    grid-row: 1 / span 2;
    align-self: center;
    border-radius: 3px;
    border: 1.5px solid $border-amber;
    background: linear-gradient(160deg, rgba($amber, 0.22), rgba($amber, 0.05));
  }
  &__item--small &__box {
    width: 14px;
    height: 14px;
  }
  &__item--medium &__box {
    width: 20px;
    height: 20px;
  }
  &__item--large &__box {
    width: 27px;
    height: 27px;
  }
  &__count {
    font-size: 1.15rem;
    font-weight: 800;
    line-height: 1;
    color: var(--primary-text);
    font-variant-numeric: tabular-nums;
  }
  &__size {
    font-size: 0.72rem;
    color: var(--secondary-text);
  }
}

.balance-chip {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.4rem 0.8rem;
  border-radius: 999px;
  background: $surface;
  border: 1px solid $border;
  &__icon {
    width: 0.95rem;
    height: 0.95rem;
    color: var(--secondary-text);
    flex-shrink: 0;
  }
  &__val {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--primary-text);
  }
  &__label {
    font-size: 0.7rem;
    color: var(--secondary-text);
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }
  &--locked {
    border-color: $border-amber;
    background: rgba($amber, 0.08);
  }
  &--locked &__icon,
  &--locked &__val {
    color: $amber-ink;
  }
  &--bonus {
    border-color: color-mix(in srgb, var(--success, #4caf7d) 40%, transparent);
    background: color-mix(in srgb, var(--success, #4caf7d) 8%, transparent);
  }
  &--bonus &__icon,
  &--bonus &__val {
    color: var(--success, #4caf7d);
  }
}

// ── Content ─────────────────────────────────────────────────────────
.tokens-content {
  padding: 1rem 1.5rem 4rem;
  min-height: 60vh;
  background: var(--background);
  @media (max-width: 600px) {
    padding: 0.5rem 1rem 3rem;
  }
  &__inner {
    max-width: $content-width;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 2.5rem;
  }
}

// ── Packages ────────────────────────────────────────────────────────
@keyframes tokenCardIn {
  from {
    opacity: 0;
    translate: 0 18px;
  }
  to {
    opacity: 1;
    translate: 0 0;
  }
}

.token-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.25rem;
  align-items: end;
  padding-top: 1rem;
  @media (max-width: 680px) {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }
}

.token-card {
  position: relative;
  display: grid;
  justify-items: center;
  gap: 0.35rem;
  padding: 1.75rem 1.5rem 1.5rem;
  text-align: center;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 18px;
  animation: tokenCardIn 0.55s $ease-out calc(0.2s + var(--i, 0) * 90ms) both;
  transition:
    transform 0.3s $ease-out,
    border-color 0.25s ease,
    box-shadow 0.3s ease;

  &:hover {
    transform: translateY(-4px);
    border-color: $border-amber;
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.3);
  }
  &:focus-within {
    border-color: $amber;
    box-shadow: 0 0 0 3px rgba($amber, 0.22);
  }

  &--featured {
    z-index: 1;
    padding-top: 2.25rem;
    padding-bottom: 1.85rem;
    border-color: rgba($amber, 0.75);
    background:
      radial-gradient(120% 70% at 50% 0%, rgba($amber, 0.18), transparent 70%),
      var(--secondary-background);
    box-shadow:
      0 24px 60px rgba(0, 0, 0, 0.35),
      inset 0 1px 0 rgba(255, 220, 160, 0.12);
    &:hover {
      transform: translateY(-6px);
    }
  }

  &__badge {
    position: absolute;
    top: -0.7rem;
    left: 50%;
    transform: translateX(-50%);
    background: $amber;
    color: #1a0d00;
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    white-space: nowrap;
    box-shadow: 0 6px 16px rgba($amber, 0.35);
  }

  &__head {
    display: flex;
    align-items: baseline;
    gap: 0.35rem;
    margin-top: 0.5rem;
  }
  &__amount {
    font-size: 2.6rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -0.04em;
    color: var(--primary-text);
    font-variant-numeric: tabular-nums;
  }
  &--featured &__amount {
    font-size: 3.1rem;
    color: $amber-ink;
  }
  &__unit {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--secondary-text);
  }
  &__rate {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.8rem;
    color: var(--secondary-text);
    min-height: 1.4rem;
  }
  &__save {
    padding: 0.1rem 0.45rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--success-text);
    background: color-mix(in srgb, var(--success, #4caf7d) 14%, transparent);
  }
  &__price {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--primary-text);
    margin: 0.4rem 0 0.75rem;
    font-variant-numeric: tabular-nums;
  }
  &__buy {
    width: 100%;
  }

  // Phones: compact rows instead of three tall boxes
  @media (max-width: 680px) {
    grid-template-columns: auto 1fr auto;
    grid-template-areas:
      'stack head price'
      'stack rate buy';
    justify-items: start;
    align-items: center;
    column-gap: 1rem;
    row-gap: 0.2rem;
    padding: 1rem 1.1rem;
    text-align: left;

    &--featured {
      padding: 1.25rem 1.1rem 1.1rem;
    }
    &:hover,
    &--featured:hover {
      transform: none;
    }

    .token-stack {
      grid-area: stack;
      transform: scale(0.75);
      transform-origin: center;
    }
    &__head {
      grid-area: head;
      margin: 0;
    }
    &__amount,
    &--featured &__amount {
      font-size: 1.75rem;
    }
    &__rate {
      grid-area: rate;
      font-size: 0.75rem;
    }
    &__price {
      grid-area: price;
      justify-self: end;
      margin: 0;
      font-size: 1rem;
    }
    &__buy {
      grid-area: buy;
      justify-self: end;
      width: auto;
      padding: 0.4rem 1rem;
      font-size: 0.85rem;
    }
    &__badge {
      left: auto;
      right: 1rem;
      transform: none;
    }
  }

  &--skeleton {
    animation: none;
    cursor: wait;
    .skeleton-bar {
      display: block;
      margin: 0 auto 0.6rem;
      height: 0.9rem;
      &--lg {
        height: 2.25rem;
        margin-bottom: 0.75rem;
      }
      &--btn {
        height: 2.25rem;
        margin-top: 0.75rem;
        margin-bottom: 0;
      }
    }
  }
}

// Stacked game tokens, bottom disc first. Hovering the card fans the stack.
.token-stack {
  --disc-gap: 6px;
  position: relative;
  width: 62px;
  height: calc(18px + (var(--n) - 1) * var(--disc-gap) + 5px);
  flex-shrink: 0;

  &__disc {
    position: absolute;
    left: 0;
    bottom: calc(var(--d) * var(--disc-gap) + 5px);
    width: 62px;
    height: 18px;
    border-radius: 50%;
    background: radial-gradient(ellipse 60% 70% at 50% 35%, #6d6458, #3c372f 80%);
    box-shadow:
      0 5px 0 #24211c,
      0 7px 10px rgba(0, 0, 0, 0.4);
    transition: translate 0.4s $ease-out;
    transition-delay: calc(var(--d) * 25ms);

    &:last-child::after {
      content: '';
      position: absolute;
      inset: 4px 14px;
      border-radius: 50%;
      border: 1px solid rgba(255, 255, 255, 0.18);
    }
  }

  .token-card--featured & .token-stack__disc {
    background: radial-gradient(ellipse 60% 70% at 50% 35%, #ffd27a, $amber 75%);
    box-shadow:
      0 5px 0 #8f560a,
      0 8px 14px rgba($amber, 0.25);
    &:last-child::after {
      border-color: rgba(90, 50, 0, 0.45);
    }
  }

  .token-card:hover & .token-stack__disc {
    translate: 0 calc(var(--d) * -3px);
  }
}

// ── Ledger: price list + history ────────────────────────────────────
.ledger {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 320px;
  grid-template-areas: 'history prices';
  gap: 1.5rem;
  align-items: start;
  @media (max-width: 900px) {
    grid-template-columns: 1fr;
    grid-template-areas:
      'prices'
      'history';
  }
}

.price-list {
  grid-area: prices;
  position: sticky;
  top: calc(#{$nav-height} + 1rem);
  padding: 1.35rem 1.4rem 1.1rem;
  border-radius: 18px;
  border: 1px dashed rgba($amber, 0.35);
  background: linear-gradient(170deg, rgba($amber, 0.06), transparent 50%);
  @media (max-width: 900px) {
    position: static;
  }

  &__title {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: $amber-ink;
    margin: 0 0 0.9rem;
  }
  &__rows {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
  }
}

.price-row {
  display: grid;
  grid-template-columns: auto minmax(1rem, 1fr) auto;
  align-items: baseline;
  column-gap: 0.5rem;

  &__label {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--primary-text);
  }
  &__leader {
    border-bottom: 1px dotted color-mix(in srgb, var(--primary-text) 25%, transparent);
    translate: 0 -0.25em;
  }
  &__value {
    display: inline-flex;
    align-items: baseline;
    gap: 0.25rem;
    font-weight: 800;
    color: $amber-ink;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    .icon-svg {
      width: 0.95rem;
      height: 0.95rem;
      align-self: center;
    }
  }
  &__unit {
    font-size: 0.68rem;
    font-weight: 600;
    color: var(--secondary-text);
  }
  &__hint {
    grid-column: 1 / -1;
    font-size: 0.75rem;
    line-height: 1.45;
    color: var(--secondary-text);
    margin-top: 0.15rem;
  }
}

// ── Transaction history ─────────────────────────────────────────────
.tx-section {
  grid-area: history;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 18px;
  overflow: hidden;
  &__title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--primary-text);
    padding: 1.1rem 1.25rem;
    margin: 0;
    .icon-svg {
      width: 1.1rem;
      height: 1.1rem;
      color: $amber;
    }
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
      color: var(--secondary-text);
      opacity: 0.6;
    }
  }
}
.tx-empty {
  color: var(--secondary-text);
  font-size: 0.875rem;
}

.tx-group__month {
  padding: 0.45rem 1.25rem;
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: var(--secondary-text);
  background: $surface;
  border-top: 1px solid var(--divider);
  border-bottom: 1px solid var(--divider);
  margin: 0;
}

.tx-list {
  list-style: none;
  margin: 0;
  padding: 0;
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
  --tone: var(--secondary-text);
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto auto;
  grid-template-areas: 'dot main date amount';
  align-items: center;
  column-gap: 0.85rem;
  padding: 0.85rem 1.25rem;
  border-bottom: 1px solid var(--divider);
  animation: txRowIn 0.35s ease calc(min(var(--i, 0), 10) * 40ms) both;
  transition: background-color 0.15s ease;
  &:last-child {
    border-bottom: none;
  }
  &:hover {
    background: $surface;
  }

  &--pos {
    --tone: var(--success-text);
  }
  &--neg {
    --tone: var(--error-text);
  }
  &--warn {
    --tone: #{$amber-ink};
  }

  &__dot {
    grid-area: dot;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--tone);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--tone) 15%, transparent);
  }
  &__main {
    grid-area: main;
    display: flex;
    flex-direction: column;
    min-width: 0;
  }
  &__type {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--primary-text);
  }
  &__detail {
    font-size: 0.78rem;
    color: var(--secondary-text);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  &__date {
    grid-area: date;
    font-size: 0.8rem;
    color: var(--secondary-text);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
  }
  &__amount {
    grid-area: amount;
    min-width: 3rem;
    text-align: right;
    font-size: 1rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    &--pos {
      color: var(--success-text);
    }
    &--neg {
      color: var(--primary-text);
    }
  }

  @media (max-width: 520px) {
    grid-template-columns: auto minmax(0, 1fr) auto;
    grid-template-areas:
      'dot main amount'
      '. date amount';
    row-gap: 0.15rem;
    padding: 0.8rem 1rem;
    &__date {
      font-size: 0.72rem;
    }
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
    color: $amber-ink;
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

// ── States ──────────────────────────────────────────────────────────
.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 18px;
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
    color: $amber-ink;
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
  &:empty {
    display: none;
  }
}
.alert-pop-enter-active {
  transition:
    opacity 0.3s $ease-out,
    translate 0.3s $ease-out;
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

@media (prefers-reduced-motion: reduce) {
  .page-hero__eyebrow,
  .page-hero__title,
  .balance,
  .token-card,
  .tx-row {
    animation: none;
  }
  .token-stack__disc {
    transition: none;
  }
}
</style>
