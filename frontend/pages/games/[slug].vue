<template>
  <div class="game-detail">

    <AppNav />

    <!-- ── Loading ─────────────────────────────────────────────── -->
    <div v-if="loading" class="detail-state">
      <div class="spinner" />
    </div>

    <!-- ── Not Found ───────────────────────────────────────────── -->
    <div v-else-if="!game" class="detail-state">
      <p class="detail-state__title">{{ $t('pages.game.not_found') }}</p>
      <NuxtLink to="/games" class="button button-primary">{{ $t('btn.to_collection') }}</NuxtLink>
    </div>

    <!-- ── Content ─────────────────────────────────────────────── -->
    <template v-else>

      <!-- Page header -->
      <section class="page-hero">
        <div class="page-hero__backdrop" aria-hidden="true">
          <div class="page-hero__glow" />
          <div class="page-hero__dots" />
        </div>
        <div class="page-hero__body">
          <NuxtLink to="/games" class="page-hero__back">{{ $t('btn.to_collection') }}</NuxtLink>
          <div class="page-hero__meta-row">
            <p v-if="game.category" class="page-hero__eyebrow">{{ game.category.name }}</p>
            <span
              v-if="auth.isLoggedIn"
              class="page-hero__badge"
              :class="game.available_copies_count > 0 ? 'page-hero__badge--avail' : 'page-hero__badge--out'"
            >
              {{ game.available_copies_count > 0
                ? `${game.available_copies_count} ${$t('common.badge.available')}`
                : (game.copies_count === 0 ? $t('pages.game.not_available') : $t('pages.game.currently_loaned')) }}
            </span>
          </div>
          <h1 class="page-hero__title">{{ game.title }}</h1>
        </div>
      </section>

      <!-- Detail card -->
      <section class="detail">
        <div class="detail__inner">

          <div v-if="game.cover_image_url" class="detail__cover">
            <img :src="game.cover_image_url" :alt="game.title" class="detail__cover-img" />
          </div>

          <div class="detail__info">

            <!-- Action Buttons -->
            <div class="detail__actions">
              <!-- Not logged in -->
              <NuxtLink v-if="!auth.isLoggedIn" to="/login" class="detail__btn detail__btn--primary">
                {{ $t('pages.game.login_to_borrow') }}
              </NuxtLink>

              <!-- Logged in but not a member -->
              <template v-else-if="auth.isActive && !auth.isMember">
                <NuxtLink to="/upgrade" class="detail__btn detail__btn--secondary">
                  {{ $t('pages.game.membership_required') }}
                </NuxtLink>
              </template>

              <!-- Member: bereits ausgeliehen -->
              <template v-else-if="auth.isMember && game.already_borrowed">
                <span class="detail__btn detail__btn--secondary detail__btn--disabled">
                  {{ $t('pages.game.already_borrowed') }}
                </span>
              </template>

              <!-- Member: game available -->
              <template v-else-if="auth.isMember && game.available_copies_count > 0">
                <button
                  v-if="(auth.user?.tokens ?? 0) >= 2"
                  class="detail__btn detail__btn--primary"
                  @click="openLoanModal"
                >
                  {{ $t('btn.borrow_game') }}
                </button>
                <NuxtLink v-else to="/tokens" class="detail__btn detail__btn--secondary">
                  {{ $t('btn.load_tokens') }}
                </NuxtLink>
              </template>

              <!-- Member: game unavailable -->
              <template v-else-if="auth.isMember">
                <button
                  class="detail__btn detail__btn--secondary"
                  @click="handleReserve"
                  :disabled="reserving"
                >
                  {{ reserving ? $t('common.loading') : $t('btn.reserve') }}
                </button>
                <span v-if="game.earliest_available_at" class="detail__avail-hint">
                  Wieder verfügbar ab {{ new Date(game.earliest_available_at + 'T00:00:00').toLocaleDateString('de-DE') }}
                </span>
              </template>
            </div>

            <p v-if="game.short_description" class="detail__short-desc">
              {{ game.short_description }}
            </p>

            <div v-if="hasMeta" class="detail__stats">
              <div v-if="game.min_players" class="detail__stat">
                <span class="detail__stat-label">{{ $t('pages.game.stats.players') }}</span>
                <span class="detail__stat-value">
                  {{ game.min_players }}{{ game.max_players ? `–${game.max_players}` : '+' }}
                </span>
              </div>
              <div v-if="game.min_age" class="detail__stat">
                <span class="detail__stat-label">{{ $t('pages.game.stats.age') }}</span>
                <span class="detail__stat-value">ab {{ game.min_age }} J.</span>
              </div>
              <div v-if="game.duration_min" class="detail__stat">
                <span class="detail__stat-label">{{ $t('pages.game.stats.duration') }}</span>
                <span class="detail__stat-value">
                  {{ game.duration_min }}{{ game.duration_max ? `–${game.duration_max}` : '' }} Min.
                </span>
              </div>
              <div v-if="game.difficulty" class="detail__stat">
                <span class="detail__stat-label">{{ $t('pages.game.stats.difficulty') }}</span>
                <span class="detail__stat-value">{{ difficultyLabel(game.difficulty!) }}</span>
              </div>
              <div v-if="game.language" class="detail__stat">
                <span class="detail__stat-label">{{ $t('pages.game.stats.language') }}</span>
                <span class="detail__stat-value">{{ game.language }}</span>
              </div>
              <div v-if="game.year" class="detail__stat">
                <span class="detail__stat-label">{{ $t('pages.game.stats.year') }}</span>
                <span class="detail__stat-value">{{ game.year }}</span>
              </div>
            </div>

            <div v-if="game.tags?.length" class="detail__tags">
              <span v-for="tag in game.tags" :key="tag.id" class="detail__tag">{{ tag.name }}</span>
            </div>

            <a
              v-if="game.instagram_url"
              :href="game.instagram_url"
              target="_blank"
              rel="noopener noreferrer"
              class="detail__instagram"
            >
              <svg class="detail__instagram-logo" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
              </svg>
              {{ $t('pages.game.instagram_view') }}
              <span class="icon detail__instagram-ext" aria-hidden="true">open_in_new</span>
            </a>

            <div v-if="game.description" class="detail__desc prose" v-html="game.description" />

            <!-- Image gallery -->
            <div v-if="game.images?.length" class="detail__gallery">
              <img
                v-for="img in game.images"
                :key="img.id"
                :src="img.url"
                :alt="game.title"
                class="detail__gallery-img"
                loading="lazy"
              />
            </div>

          </div>
        </div>
      </section>

    </template>

    <!-- ── Footer ──────────────────────────────────────────────── -->
    <footer class="l-footer">
      <div class="l-footer__inner">
        <div class="l-footer__brand">
          <span class="l-footer__hex" aria-hidden="true">⬡</span>
          <span class="l-footer__name">AUA</span>
        </div>
        <nav class="l-footer__nav" aria-label="Footer-Navigation">
          <NuxtLink to="/games" class="l-footer__link">{{ $t('nav.collection') }}</NuxtLink>
          <NuxtLink to="/terms" class="l-footer__link">{{ $t('nav.terms') }}</NuxtLink>
          <NuxtLink to="/privacy" class="l-footer__link">{{ $t('nav.privacy') }}</NuxtLink>
          <NuxtLink to="/cookies" class="l-footer__link">{{ $t('nav.cookies') }}</NuxtLink>
        </nav>
        <p class="l-footer__copy">{{ $t('common.copyright', { year }) }}</p>
      </div>
    </footer>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { Game } from '~/composables/useGames'
import { Modal } from '@dodlhuat/basix/js/modal'

const route = useRoute()
const { fetchGame } = useGames()
const { createLoan, addReservation } = useLoans()
const auth = useAuthStore()
const { fetchSettings, getNextAppointment, getDueDate, formatDate, toIsoDate } = useLoanSettings()
const { t } = useI18n()

const loading = ref(true)
const game = ref<Game | null>(null)
const year = new Date().getFullYear()
const reserving = ref(false)

const loanDates = { start_date: '', due_date: '' }

async function openLoanModal() {
  if (!game.value) return

  let startLabel = '…'
  let dueLabel = '…'

  try {
    const settings = await fetchSettings()
    const appointment = getNextAppointment(settings)
    const due = getDueDate(appointment, settings)
    loanDates.start_date = toIsoDate(appointment)
    loanDates.due_date = toIsoDate(due)
    startLabel = formatDate(appointment)
    dueLabel = formatDate(due)
  } catch {
    loanDates.start_date = new Date().toISOString().slice(0, 10)
    loanDates.due_date = new Date(Date.now() + 28 * 86400000).toISOString().slice(0, 10)
    startLabel = loanDates.start_date
    dueLabel = loanDates.due_date
  }

  const content = `
    <div style="padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:1rem">
      <p style="color:var(--accent-color);font-weight:600;margin:0;font-size:.95rem">${game.value.title}</p>
      <div style="display:flex;flex-direction:column;gap:.35rem">
        <label style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--secondary-text)">${t('pages.game.confirm_borrow.start_label')}</label>
        <p style="margin:0;font-size:.95rem;font-weight:600;color:var(--primary-text)">${startLabel}</p>
      </div>
      <div style="display:flex;flex-direction:column;gap:.35rem">
        <label style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--secondary-text)">${t('pages.game.confirm_borrow.due_label')}</label>
        <p style="margin:0;font-size:.95rem;font-weight:600;color:var(--primary-text)">${dueLabel}</p>
      </div>
      <div id="loan-msg" style="display:none;font-size:.85rem;margin:0"></div>
    </div>`

  const footer = `
    <div style="display:flex;gap:.75rem;justify-content:flex-end">
      <button id="loan-cancel" class="button">${t('pages.game.confirm_borrow.cancel_btn')}</button>
      <button id="loan-submit" class="button button-primary">${t('pages.game.confirm_borrow.confirm_btn')}</button>
    </div>`

  const modal = new Modal({ content, header: t('pages.game.confirm_borrow_title'), footer, closeable: true })
  modal.show()

  setTimeout(() => {
    document.getElementById('loan-cancel')?.addEventListener('click', () => modal.hide())
    document.getElementById('loan-submit')?.addEventListener('click', () => submitLoan(modal))
  }, 50)
}

async function submitLoan(modal: InstanceType<typeof Modal>) {
  if (!game.value) return

  const copy = game.value.copies?.find(c => c.is_available)
  const msgEl = document.getElementById('loan-msg') as HTMLElement | null
  const submitBtn = document.getElementById('loan-submit') as HTMLButtonElement | null

  if (!copy) {
    if (msgEl) { msgEl.style.color = 'var(--error)'; msgEl.textContent = t('pages.game.confirm_borrow.no_copy'); msgEl.style.display = 'block' }
    return
  }

  if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = t('pages.game.confirm_borrow.processing') }
  if (msgEl) { msgEl.style.display = 'none' }

  try {
    await createLoan({ copy_id: copy.id, start_date: loanDates.start_date, due_date: loanDates.due_date })
    if (msgEl) { msgEl.style.color = 'var(--success)'; msgEl.textContent = t('pages.game.confirm_borrow.success'); msgEl.style.display = 'block' }
    game.value.available_copies_count = Math.max(0, game.value.available_copies_count - 1)
    game.value.already_borrowed = true
    if (auth.user) auth.setUser({ ...auth.user, tokens: Math.max(0, auth.user.tokens - 2) })
    setTimeout(() => modal.hide(), 2000)
  } catch (e: unknown) {
    const err = e as { message?: string }
    if (msgEl) { msgEl.style.color = 'var(--error)'; msgEl.textContent = err?.message ?? t('pages.game.confirm_borrow.error'); msgEl.style.display = 'block' }
    if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = t('pages.game.confirm_borrow.confirm_btn') }
  }
}

async function handleReserve() {
  if (!game.value) return
  reserving.value = true
  try {
    await addReservation(game.value.id)
    alert(t('pages.game.reserve_success'))
  } catch (e: unknown) {
    const err = e as { message?: string }
    alert(err?.message ?? t('pages.game.reserve_failed'))
  } finally {
    reserving.value = false
  }
}

// ── Game meta ────────────────────────────────────────────────────
const DIFFICULTY: Record<string, string> = {
  EASY: 'admin.form.difficulty_easy',
  MEDIUM: 'admin.form.difficulty_medium',
  HARD: 'admin.form.difficulty_hard',
  EXPERT: 'admin.form.difficulty_expert',
}

function difficultyLabel(d: string) {
  return DIFFICULTY[d] ? t(DIFFICULTY[d]) : d
}

const hasMeta = computed(() =>
  game.value && (
    game.value.min_players || game.value.min_age ||
    game.value.duration_min || game.value.difficulty ||
    game.value.language || game.value.year
  )
)

onMounted(async () => {
  try {
    const data = await fetchGame(route.params.slug as string)
    game.value = data.data
  } catch {
    game.value = null
  } finally {
    loading.value = false
  }
})

useHead(() => ({
  title: game.value ? `${game.value.title} — ${t('pages.games.title')}` : t('pages.game.not_found'),
}))
</script>

<style lang="scss" scoped>
$hero-bg:       #0F0E0C;
$nav-height:    64px;

$amber-08:      rgba(212, 146, 30, 0.08);
$amber-25:      rgba(212, 146, 30, 0.25);
$amber-glow:    rgba(212, 146, 30, 0.20);

$hero-text:     #EEE8DF;
$hero-muted:    rgba(238, 232, 223, 0.72);
$hero-muted-50: rgba(238, 232, 223, 0.65);
$hero-divider:  rgba(238, 232, 223, 0.10);

// ─── Page shell ───────────────────────────────────────────────────
.game-detail {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--background);
}

// ─── State (loading / 404) ────────────────────────────────────────
.detail-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: calc(#{$nav-height} + 4rem) 1.5rem 4rem;

  &__title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-text);
    padding-bottom: 0;
  }
}

// ─── Page Header ──────────────────────────────────────────────────
.page-hero {
  position: relative;
  background: $hero-bg;
  padding: calc(#{$nav-height} + 3rem) 1.5rem 3rem;
  overflow: hidden;

  &__backdrop { position: absolute; inset: 0; pointer-events: none; }

  &__glow {
    position: absolute;
    width: 600px;
    height: 400px;
    top: -100px;
    right: -100px;
    border-radius: 50%;
    filter: blur(100px);
    background: $amber-glow;
  }

  &__dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
    background-size: 24px 24px;
    mask-image: radial-gradient(ellipse 100% 100% at 80% 50%, black 10%, transparent 80%);
  }

  &__body {
    position: relative;
    z-index: 1;
    max-width: 1100px;
    margin: 0 auto;
  }

  &__back {
    display: inline-block;
    font-size: 0.85rem;
    font-weight: 500;
    color: $hero-muted;
    text-decoration: none;
    margin-bottom: 1.5rem;
    transition: color 0.2s;
    &:hover { color: $hero-text; }
  }

  &__meta-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.6rem;
    flex-wrap: wrap;
  }

  &__eyebrow {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: $amber;
    padding: 0.2rem 0.6rem;
    border: 1px solid $amber-25;
    border-radius: 999px;
    background: $amber-08;
    padding-bottom: 0.2rem;
  }

  &__badge {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;

    &--avail {
      background: var(--success-muted);
      color: var(--success);
    }

    &--out {
      background: var(--accent-color-muted);
      color: var(--accent-text);
    }
  }

  &__title {
    font-size: clamp(1.75rem, 4vw, 3rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    color: $hero-text;
    margin: 0;
  }
}

// ─── Detail Content ───────────────────────────────────────────────
.detail {
  flex: 1;
  padding: 3rem 1.5rem 4rem;

  &__inner {
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 3rem;
    align-items: start;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
      gap: 2rem;
    }
  }

  &__cover {
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--divider);
    position: sticky;
    top: calc(#{$nav-height} + 1.5rem);
  }

  &__cover-img {
    width: 100%;
    display: block;
  }

  &__info { display: flex; flex-direction: column; gap: 1.5rem; }

  // Action buttons
  &__actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
  }

  &__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: background 0.2s, opacity 0.2s;

    &--primary {
      background: $amber;
      color: #0F0E0C;
      &:hover { background: color.adjust($amber, $lightness: -8%); }
    }

    &--secondary {
      background: transparent;
      color: $amber;
      border: 1px solid $amber-25;
      &:hover { background: $amber-08; }
    }

    &:disabled,
    &--disabled { opacity: 0.5; cursor: default; pointer-events: none; }
  }

  &__avail-hint {
    display: inline-flex;
    align-items: center;
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--secondary-text);
    padding: 0.4rem 0.75rem;
    border: 1px solid var(--divider);
    border-radius: 8px;
    background: var(--secondary-background);
  }

  &__short-desc {
    font-size: 1.05rem;
    line-height: 1.65;
    color: var(--primary-text);
    font-weight: 500;
    padding-bottom: 0;
  }

  // Stats grid
  &__stats {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1px;
    background: var(--divider);
    border: 1px solid var(--divider);
    border-radius: 12px;
    overflow: hidden;
  }

  &__stat {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 1rem 1.25rem;
    background: var(--secondary-background);
  }

  &__stat-label {
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--secondary-text);
  }

  &__stat-value {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--primary-text);
    letter-spacing: -0.01em;
  }

  // Tags
  &__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
  }

  &__tag {
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--secondary-text);
    background: var(--secondary-background);
    border: 1px solid var(--divider);
    border-radius: 999px;
    padding: 0.25rem 0.65rem;
  }

  // Instagram link
  &__instagram {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--secondary-text);
    text-decoration: none;
    padding: 0.55rem 1rem;
    border: 1px solid var(--divider);
    border-radius: 999px;
    background: var(--secondary-background);
    transition: color 0.2s, border-color 0.2s, background 0.2s;
    align-self: flex-start;

    &:hover {
      color: $amber;
      border-color: rgba($amber, 0.35);
      background: rgba($amber, 0.06);
    }
  }

  &__instagram-logo {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
  }

  &__instagram-ext {
    font-size: 0.9rem;
    opacity: 0.6;
  }

  // Description
  &__desc {
    font-size: 1rem;
    line-height: 1.75;
    color: var(--secondary-text);
    padding-bottom: 0;
  }

  // Image gallery
  &__gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 0.75rem;
  }

  &__gallery-img {
    width: 100%;
    aspect-ratio: 4/3;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid var(--divider);
    display: block;
  }
}

// ─── Prose (v-html rich text) ─────────────────────────────────────
.prose {
  font-size: 1rem;
  line-height: 1.75;
  color: var(--secondary-text);

  :deep(h2) { font-size: 1.25rem; font-weight: 700; color: var(--primary-text); margin: 1.25rem 0 0.5rem; letter-spacing: -0.02em; }
  :deep(h3) { font-size: 1.05rem; font-weight: 700; color: var(--primary-text); margin: 1rem 0 0.4rem; }
  :deep(p)  { margin-bottom: 0.75rem; }
  :deep(p:last-child) { margin-bottom: 0; }

  :deep(ul), :deep(ol) { margin-left: 1.5rem; margin-bottom: 0.75rem; }
  :deep(ul) { list-style: disc; }
  :deep(ol) { list-style: decimal; }
  :deep(li) { margin-bottom: 0.2rem; line-height: 1.65; }

  :deep(strong), :deep(b) { font-weight: 700; color: var(--primary-text); }
  :deep(em), :deep(i)     { font-style: italic; }
  :deep(u)                { text-underline-offset: 2px; }
  :deep(s)                { opacity: 0.6; }

  :deep(a) {
    color: $amber;
    text-decoration: underline;
    text-underline-offset: 2px;
    &:hover { opacity: 0.8; }
  }

  :deep(blockquote) {
    border-left: 3px solid $amber-25;
    padding-left: 1rem;
    margin: 0.75rem 0;
    color: var(--secondary-text);
    font-style: italic;
  }
}

// ─── Footer ───────────────────────────────────────────────────────
.l-footer {
  background: $hero-bg;
  border-top: 1px solid $hero-divider;
  padding: 2.5rem 1.5rem;

  &__inner {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
  }

  &__brand { display: flex; align-items: center; gap: 0.4rem; flex-shrink: 0; }

  &__hex { font-size: 1.2rem; color: $amber; }

  &__name {
    font-size: 0.95rem;
    font-weight: 700;
    color: $hero-text;
    letter-spacing: -0.02em;
  }

  &__nav {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    flex: 1;
    justify-content: center;
    position: static;
    transform: none;
    width: auto;
    height: auto;
    @media (max-width: 640px) { justify-content: flex-start; }
  }

  &__link {
    font-size: 0.85rem;
    color: $hero-muted;
    text-decoration: none;
    transition: color 0.2s;
    &:hover { color: $hero-text; }
  }

  &__copy {
    font-size: 0.8rem;
    color: $hero-muted-50;
    margin-left: auto;
    padding-bottom: 0;
    @media (max-width: 640px) { margin-left: 0; width: 100%; }
  }
}
</style>
