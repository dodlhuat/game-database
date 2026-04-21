<template>
  <div class="tokens-page" data-theme="dark">
    <AppNav />

    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <p class="page-hero__eyebrow">{{ $t('pages.tokens.my_account') }}</p>
        <h1 class="page-hero__title">{{ $t('pages.tokens.title') }}</h1>
        <div class="token-balance">
          <div class="token-balance__item">
            <span class="token-balance__val">{{ auth.user?.tokens ?? 0 }}</span>
            <span class="token-balance__label">{{ $t('pages.tokens.balance_total') }}</span>
          </div>
          <div v-if="(auth.user?.tokens_blocked ?? 0) > 0" class="token-balance__item token-balance__item--blocked">
            <span class="token-balance__val">{{ auth.user?.tokens_blocked ?? 0 }}</span>
            <span class="token-balance__label">{{ $t('pages.tokens.balance_blocked') }}</span>
          </div>
          <div class="token-balance__item token-balance__item--free">
            <span class="token-balance__val">{{ (auth.user?.tokens ?? 0) - (auth.user?.tokens_blocked ?? 0) }}</span>
            <span class="token-balance__label">{{ $t('pages.tokens.balance_free') }}</span>
          </div>
        </div>
      </div>
    </section>

    <div class="tokens-content">
      <div class="tokens-content__inner">

        <div v-if="!auth.isMember" class="no-member">
          <p>{{ $t('pages.tokens.not_member') }}</p>
          <NuxtLink to="/upgrade">{{ $t('btn.upgrade') }} →</NuxtLink>
        </div>

        <template v-else>
          <div v-if="success" class="alert alert-success">{{ success }}</div>
          <div v-if="error" class="alert alert-error">{{ error }}</div>

          <div class="token-grid">
            <div
              v-for="pkg in tokenPackages"
              :key="pkg.amount"
              class="token-card"
              :class="{ 'token-card--featured': pkg.featured }"
            >
              <div v-if="pkg.featured" class="token-card__badge">{{ $t('pages.tokens.popular') }}</div>
              <div class="token-card__amount">{{ pkg.amount }}</div>
              <div class="token-card__label">Token</div>
              <UiButton
                :loading="loadingAmount === pkg.amount"
                :disabled="!!loadingAmount"
                @click="addTokens(pkg.amount)"
              >
                {{ $t('btn.add') }}
              </UiButton>
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
            <h3 class="tx-section__title">{{ $t('pages.tokens.history_title') }}</h3>

            <div v-if="txLoading" class="tx-state"><div class="spinner" /></div>

            <div v-else-if="!transactions.length" class="tx-state">
              <p class="tx-empty">{{ $t('pages.tokens.history_empty') }}</p>
            </div>

            <div v-else>
              <table class="tx-table">
                <tbody>
                  <tr v-for="tx in transactions" :key="tx.id" class="tx-row">
                    <td class="tx-row__date">{{ formatDate(tx.created_at) }}</td>
                    <td class="tx-row__desc">
                      <span class="tx-type-badge" :class="txClass(tx.type)">{{ txLabel(tx.type) }}</span>
                      <span v-if="tx.description" class="tx-row__detail">{{ tx.description }}</span>
                    </td>
                    <td class="tx-row__amount" :class="tx.amount >= 0 ? 'tx-row__amount--pos' : 'tx-row__amount--neg'">
                      {{ tx.amount >= 0 ? '+' : '' }}{{ tx.amount }}
                    </td>
                  </tr>
                </tbody>
              </table>

              <div v-if="txMeta.last_page > 1" class="tx-pagination">
                <button class="action-btn" :disabled="txPage === 1" @click="loadTx(txPage - 1)">←</button>
                <span>{{ txPage }} / {{ txMeta.last_page }}</span>
                <button class="action-btn" :disabled="txPage === txMeta.last_page" @click="loadTx(txPage + 1)">→</button>
              </div>
            </div>
          </div>

        </template>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import type { TokenTransaction } from '~/composables/useLoans'

definePageMeta({ middleware: ['auth'] })

const auth = useAuthStore()
const api = useApi()
const { fetchTokenTransactions } = useLoans()
const { t } = useI18n()
const loadingAmount = ref<number | null>(null)
const success = ref('')
const error = ref('')

const tokenPackages = [
  { amount: 20, featured: false },
  { amount: 30, featured: true },
  { amount: 40, featured: false },
]

const transactions = ref<TokenTransaction[]>([])
const txLoading = ref(false)
const txPage = ref(1)
const txMeta = ref({ last_page: 1, total: 0 })

async function addTokens(amount: number) {
  loadingAmount.value = amount
  success.value = ''
  error.value = ''
  try {
    const data = await api.post<{ user: typeof auth.user; message: string }>('/tokens/add', { amount })
    if (data.user) auth.setUser(data.user)
    success.value = data.message
    await loadTx(1)
  } catch (err: unknown) {
    const e = err as { message?: string }
    error.value = e.message ?? 'Ein Fehler ist aufgetreten.'
  } finally {
    loadingAmount.value = null
  }
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
  return new Date(iso).toLocaleDateString('de-AT', { day: '2-digit', month: '2-digit', year: 'numeric' })
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
  BORROW: 'tx-type--debit',
  DEPOSIT_BLOCK: 'tx-type--blocked',
  DEPOSIT_RELEASE: 'tx-type--credit',
  DEPOSIT_FORFEIT: 'tx-type--danger',
  PURCHASE: 'tx-type--credit',
  ADMIN_ADJUSTMENT: '',
}

function txLabel(type: string) { return TX_LABELS[type] ? t(TX_LABELS[type]) : type }
function txClass(type: string) { return TX_CLASSES[type] ?? '' }

onMounted(() => loadTx(1))
</script>

<style lang="scss" scoped>
$hero-bg: #0F0E0C;
$nav-height: 3.5rem;
$amber-glow: rgba(212, 146, 30, 0.15);
$hero-text: #EEE8DF;
$hero-muted: rgba(238, 232, 223, 0.72);
$surface: rgba(255, 255, 255, 0.04);
$surface-hover: rgba(255, 255, 255, 0.07);
$border: rgba(238, 232, 223, 0.1);
$border-amber: rgba(212, 146, 30, 0.4);

.page-hero { position: relative; background: $hero-bg; padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem; overflow: hidden; &__backdrop { position: absolute; inset: 0; pointer-events: none; } &__glow { position: absolute; width: 400px; height: 400px; top: -120px; right: -60px; border-radius: 50%; filter: blur(90px); background: $amber-glow; } &__dots { position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 24px 24px; mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%); } &__body { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; } &__eyebrow { font-size: 0.78rem; font-weight: 600; color: $amber; letter-spacing: 0.02em; margin-bottom: 0.4rem; } &__title { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0 0 1rem; } }

.token-balance {
  display: flex; gap: 1.5rem; flex-wrap: wrap;
  &__item { display: flex; flex-direction: column; gap: 0.15rem; }
  &__val { font-size: 1.4rem; font-weight: 800; color: $hero-text; }
  &__label { font-size: 0.75rem; color: $hero-muted; text-transform: uppercase; letter-spacing: 0.06em; }
  &__item--blocked &__val { color: rgba($amber, 0.7); }
  &__item--free &__val { color: $amber; }
}

.tokens-content { padding: 2rem 1.5rem 4rem; min-height: 60vh; background: var(--background); &__inner { max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: 2rem; } }

.token-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; @media (max-width: 540px) { grid-template-columns: 1fr; } }
.token-card { position: relative; background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 14px; padding: 1.5rem; text-align: center; display: flex; flex-direction: column; gap: 0.5rem; align-items: center; transition: border-color 0.2s; &--featured { border-color: $amber; } &__badge { position: absolute; top: -0.6rem; left: 50%; transform: translateX(-50%); background: $amber; color: #1a0d00; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.6rem; border-radius: 999px; white-space: nowrap; } &__amount { font-size: 2rem; font-weight: 800; color: var(--primary-text); } &__label { font-size: 0.8rem; color: var(--secondary-text); margin-bottom: 0.25rem; } }

.token-info { background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 12px; padding: 1.25rem 1.5rem; &__title { font-size: 0.9rem; font-weight: 700; color: var(--primary-text); margin: 0 0 0.75rem; } &__list { margin: 0; padding-left: 1.25rem; display: flex; flex-direction: column; gap: 0.35rem; li { font-size: 0.875rem; color: var(--secondary-text); } } }

.no-member { text-align: center; padding: 2rem; color: var(--secondary-text); a { color: $amber; text-decoration: none; } }

.alert { padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.875rem; font-weight: 500; &-success { background: rgba(74, 222, 128, 0.1); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.2); } &-error { background: rgba(248, 113, 113, 0.1); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.2); } }

// ── Transaction history ───────────────────────────────────────────
.tx-section { background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 14px; overflow: hidden; }
.tx-section__title { font-size: 0.9rem; font-weight: 700; color: var(--primary-text); padding: 1rem 1.25rem; border-bottom: 1px solid var(--divider); margin: 0; }
.tx-state { display: flex; align-items: center; justify-content: center; min-height: 80px; padding: 1rem; }
.tx-empty { color: var(--secondary-text); font-size: 0.875rem; }
.tx-table { width: 100%; border-collapse: collapse; }
.tx-row { border-bottom: 1px solid var(--divider); &:last-child { border-bottom: none; } td { padding: 0.75rem 1.25rem; font-size: 0.875rem; vertical-align: middle; } }
.tx-row__date { color: var(--secondary-text); white-space: nowrap; width: 6rem; }
.tx-row__desc { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; color: var(--primary-text); }
.tx-row__detail { color: var(--secondary-text); font-size: 0.8rem; }
.tx-row__amount { text-align: right; font-weight: 700; white-space: nowrap; &--pos { color: #4ade80; } &--neg { color: #f87171; } }
.tx-type-badge { font-size: 0.7rem; font-weight: 600; padding: 0.1rem 0.45rem; border-radius: 999px; white-space: nowrap; }
.tx-type--credit { background: rgba(74,222,128,0.12); color: #4ade80; }
.tx-type--debit { background: rgba(248,113,113,0.12); color: #f87171; }
.tx-type--blocked { background: rgba(212,146,30,0.12); color: $amber; }
.tx-type--danger { background: rgba(248,113,113,0.15); color: #f87171; }
.tx-pagination { display: flex; align-items: center; justify-content: center; gap: 1rem; padding: 0.75rem; border-top: 1px solid var(--divider); font-size: 0.875rem; color: var(--secondary-text); }
.action-btn { padding: 0.3rem 0.6rem; background: var(--background); border: 1px solid var(--divider); border-radius: 6px; cursor: pointer; font-size: 0.8rem; color: var(--primary-text); &:disabled { opacity: 0.4; cursor: not-allowed; } }
</style>
