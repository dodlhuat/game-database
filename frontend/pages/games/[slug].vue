<template>
  <div class="gd">
    <AppNav />

    <div v-if="loading" class="gd-state">
      <div class="gd-spin" />
    </div>

    <div v-else-if="!game" class="gd-state">
      <p class="gd-state__msg">{{ $t('pages.game.not_found') }}</p>
      <NuxtLink to="/games" class="button button-primary">{{ $t('btn.to_collection') }}</NuxtLink>
    </div>

    <template v-else>

      <!-- ═══ HERO ═══════════════════════════════════════════════════ -->
      <section class="gd-hero">
        <div class="gd-hero__bg" aria-hidden="true">
          <img v-if="game.cover_image_url" :src="game.cover_image_url" class="gd-hero__bg-img" alt="" />
          <div class="gd-hero__bg-veil" />
          <div class="gd-hero__grain" />
          <div class="gd-hero__fade" />
        </div>

        <NuxtLink to="/games" class="gd-back">
          <span class="icon">arrow_back</span>{{ $t('btn.to_collection') }}
        </NuxtLink>

        <div class="gd-hero__stage">
          <div class="gd-hero__poster-wrap">
            <div class="gd-hero__poster-halo" aria-hidden="true" />
            <img v-if="game.cover_image_url" :src="game.cover_image_url" :alt="game.title" class="gd-hero__poster" />
            <div v-else class="gd-hero__poster-empty"><span class="icon">extension</span></div>
          </div>
        </div>

        <div class="gd-hero__caption">
          <div class="gd-pills">
            <span v-if="game.category" class="gd-pill gd-pill--cat">{{ game.category.name }}</span>
            <span
              v-if="auth.isLoggedIn"
              class="gd-pill"
              :class="game.available_copies_count > 0 ? 'gd-pill--avail' : 'gd-pill--out'"
            >
              {{ game.available_copies_count > 0
                ? `${game.available_copies_count} ${$t('common.badge.available')}`
                : (game.copies_count === 0 ? $t('pages.game.not_available') : $t('pages.game.currently_loaned')) }}
            </span>
          </div>
          <h1 class="gd-title">{{ game.title }}</h1>
        </div>

        <div class="gd-hero__cue" aria-hidden="true">
          <span class="icon">expand_more</span>
        </div>
      </section>

      <!-- ═══ STATS BAR ══════════════════════════════════════════════ -->
      <div v-if="hasMeta" class="gd-stats">
        <div class="gd-stats__track">
          <template v-if="game.min_players">
            <div class="gd-stat">
              <span class="icon gd-stat__ico">group</span>
              <span class="gd-stat__lbl">{{ $t('pages.game.stats.players') }}</span>
              <span class="gd-stat__val">{{ game.min_players }}{{ game.max_players ? `–${game.max_players}` : '+' }}</span>
            </div>
          </template>
          <template v-if="game.duration_min">
            <div class="gd-stat__sep" />
            <div class="gd-stat">
              <span class="icon gd-stat__ico">schedule</span>
              <span class="gd-stat__lbl">{{ $t('pages.game.stats.duration') }}</span>
              <span class="gd-stat__val">{{ game.duration_min }}{{ game.duration_max ? `–${game.duration_max}` : '' }} Min.</span>
            </div>
          </template>
          <template v-if="game.min_age">
            <div class="gd-stat__sep" />
            <div class="gd-stat">
              <span class="icon gd-stat__ico">child_care</span>
              <span class="gd-stat__lbl">{{ $t('pages.game.stats.age') }}</span>
              <span class="gd-stat__val">ab {{ game.min_age }} J.</span>
            </div>
          </template>
          <template v-if="game.difficulty">
            <div class="gd-stat__sep" />
            <div class="gd-stat">
              <span class="icon gd-stat__ico">psychology</span>
              <span class="gd-stat__lbl">{{ $t('pages.game.stats.difficulty') }}</span>
              <span class="gd-stat__val">{{ difficultyLabel(game.difficulty!) }}</span>
            </div>
          </template>
          <template v-if="game.languages?.length">
            <div class="gd-stat__sep" />
            <div class="gd-stat">
              <span class="icon gd-stat__ico">translate</span>
              <span class="gd-stat__lbl">{{ $t('pages.game.stats.language') }}</span>
              <span class="gd-stat__val">{{ game.languages.map(l => l.name).join(', ') }}</span>
            </div>
          </template>
          <template v-if="game.year">
            <div class="gd-stat__sep" />
            <div class="gd-stat">
              <span class="icon gd-stat__ico">event</span>
              <span class="gd-stat__lbl">{{ $t('pages.game.stats.year') }}</span>
              <span class="gd-stat__val">{{ game.year }}</span>
            </div>
          </template>
        </div>
      </div>

      <!-- ═══ BODY ═══════════════════════════════════════════════════ -->
      <main class="gd-main">

        <!-- Desktop CTA (hidden on mobile — float bar handles it) -->
        <div class="gd-actions">
          <template v-if="!auth.isLoggedIn">
            <NuxtLink to="/login" class="gd-btn gd-btn--primary">
              <span class="icon">login</span>{{ $t('pages.game.login_to_borrow') }}
            </NuxtLink>
          </template>
          <template v-else-if="auth.isActive && !auth.isMember">
            <NuxtLink to="/upgrade" class="gd-btn gd-btn--secondary">{{ $t('pages.game.membership_required') }}</NuxtLink>
          </template>
          <template v-else-if="auth.isMember && game.already_borrowed">
            <span class="gd-btn gd-btn--done">
              <span class="icon">check_circle</span>{{ $t('pages.game.already_borrowed') }}
            </span>
          </template>
          <template v-else-if="auth.isMember && game.available_copies_count > 0">
            <button v-if="(auth.user?.tokens ?? 0) >= 2" class="gd-btn gd-btn--primary" @click="openLoanModal">
              <span class="icon">send</span>{{ $t('btn.borrow_game') }}
            </button>
            <NuxtLink v-else to="/tokens" class="gd-btn gd-btn--secondary">
              <span class="icon">toll</span>{{ $t('btn.load_tokens') }}
            </NuxtLink>
          </template>
          <template v-else-if="auth.isMember">
            <button class="gd-btn gd-btn--secondary" @click="handleReserve" :disabled="reserving">
              <span class="icon">bookmark_add</span>{{ reserving ? $t('common.loading') : $t('btn.reserve') }}
            </button>
            <span v-if="game.earliest_available_at" class="gd-avail-note">
              Wieder verfügbar ab {{ new Date(game.earliest_available_at + 'T00:00:00').toLocaleDateString('de-DE') }}
            </span>
          </template>
        </div>

        <p v-if="game.short_description" class="gd-summary">{{ game.short_description }}</p>

        <div v-if="game.tags?.length" class="gd-tags">
          <span v-for="tag in game.tags" :key="tag.id" class="gd-tag">{{ tag.name }}</span>
        </div>

        <div v-if="game.description" class="prose gd-desc" v-html="game.description" />

        <div v-if="game.images?.length" class="gd-gallery">
          <div class="gd-gallery__rail">
            <button
              v-for="(img, i) in game.images"
              :key="img.id"
              class="gd-gallery__thumb"
              @click="openLightbox(i)"
              :aria-label="`Bild ${i + 1} vergrößern`"
            >
              <img :src="img.url" :alt="game.title" class="gd-gallery__img" loading="lazy" />
              <span class="gd-gallery__zoom" aria-hidden="true"><span class="icon">zoom_in</span></span>
            </button>
          </div>
        </div>

        <!-- Lightbox -->
        <Teleport to="body">
          <Transition name="lb">
            <div
              v-if="lightbox.open"
              class="gd-lb"
              role="dialog"
              aria-modal="true"
              @touchstart.passive="onTouchStart"
              @touchend.passive="onTouchEnd"
            >
              <div class="gd-lb__backdrop" @click="closeLightbox" />

              <button class="gd-lb__close" @click="closeLightbox" aria-label="Schließen">
                <span class="icon">close</span>
              </button>

              <button
                v-if="game.images && game.images.length > 1"
                class="gd-lb__nav gd-lb__nav--prev"
                @click="lightboxStep(-1)"
                aria-label="Vorheriges Bild"
              >
                <span class="icon">chevron_left</span>
              </button>

              <div class="gd-lb__stage">
                <Transition :name="lightbox.dir === 1 ? 'lb-next' : 'lb-prev'" mode="out-in">
                  <img
                    v-if="game.images"
                    :key="lightbox.index"
                    :src="game.images[lightbox.index].url"
                    :alt="game.title"
                    class="gd-lb__img"
                  />
                </Transition>
              </div>

              <button
                v-if="game.images && game.images.length > 1"
                class="gd-lb__nav gd-lb__nav--next"
                @click="lightboxStep(1)"
                aria-label="Nächstes Bild"
              >
                <span class="icon">chevron_right</span>
              </button>

              <div v-if="game.images && game.images.length > 1" class="gd-lb__dots">
                <button
                  v-for="(_, i) in game.images"
                  :key="i"
                  class="gd-lb__dot"
                  :class="{ 'gd-lb__dot--active': i === lightbox.index }"
                  @click="lightboxGoto(i)"
                  :aria-label="`Bild ${i + 1}`"
                />
              </div>
            </div>
          </Transition>
        </Teleport>

        <a
          v-if="game.instagram_url"
          :href="game.instagram_url"
          target="_blank"
          rel="noopener noreferrer"
          class="gd-insta"
        >
          <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16" aria-hidden="true">
            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
          </svg>
          {{ $t('pages.game.instagram_view') }}
          <span class="icon" style="font-size:0.8rem;opacity:0.5">open_in_new</span>
        </a>

      </main>

      <!-- ═══ MOBILE FLOAT BAR ═══════════════════════════════════════ -->
      <div class="gd-bar">
        <div class="gd-bar__inner">
          <div class="gd-bar__left">
            <span class="gd-bar__name">{{ game.title }}</span>
            <span v-if="auth.isLoggedIn && auth.isMember" class="gd-bar__tokens">
              <span class="icon" style="font-size:.85rem">toll</span>{{ auth.user?.tokens ?? 0 }} Token
            </span>
          </div>
          <template v-if="!auth.isLoggedIn">
            <NuxtLink to="/login" class="gd-bar__btn">{{ $t('pages.game.login_to_borrow') }}</NuxtLink>
          </template>
          <template v-else-if="auth.isActive && !auth.isMember">
            <NuxtLink to="/upgrade" class="gd-bar__btn gd-bar__btn--sec">{{ $t('pages.game.membership_required') }}</NuxtLink>
          </template>
          <template v-else-if="auth.isMember && game.already_borrowed">
            <span class="gd-bar__btn gd-bar__btn--done"><span class="icon">check_circle</span></span>
          </template>
          <template v-else-if="auth.isMember && game.available_copies_count > 0">
            <button v-if="(auth.user?.tokens ?? 0) >= 2" class="gd-bar__btn" @click="openLoanModal">
              {{ $t('btn.borrow_game') }}
            </button>
            <NuxtLink v-else to="/tokens" class="gd-bar__btn gd-bar__btn--sec">{{ $t('btn.load_tokens') }}</NuxtLink>
          </template>
          <template v-else-if="auth.isMember">
            <button class="gd-bar__btn gd-bar__btn--sec" @click="handleReserve" :disabled="reserving">
              {{ reserving ? '…' : $t('btn.reserve') }}
            </button>
          </template>
        </div>
      </div>

    </template>

    <!-- Footer -->
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
import { ref, reactive, computed, onMounted } from 'vue'
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

const lightbox = reactive({ open: false, index: 0, dir: 1 })

function openLightbox(i: number) {
  lightbox.index = i
  lightbox.dir = 1
  lightbox.open = true
  document.body.style.overflow = 'hidden'
}

function closeLightbox() {
  lightbox.open = false
  document.body.style.overflow = ''
}

function lightboxStep(dir: 1 | -1) {
  const len = game.value?.images?.length ?? 1
  lightbox.dir = dir
  lightbox.index = (lightbox.index + dir + len) % len
}

function lightboxGoto(i: number) {
  lightbox.dir = i > lightbox.index ? 1 : -1
  lightbox.index = i
}

// Touch swipe support
let touchStartX = 0
function onTouchStart(e: TouchEvent) { touchStartX = e.touches[0].clientX }
function onTouchEnd(e: TouchEvent) {
  const dx = e.changedTouches[0].clientX - touchStartX
  if (Math.abs(dx) > 50) lightboxStep(dx < 0 ? 1 : -1)
}


const loanDates = { start_date: '', due_date: '' }

useHead({
  link: [
    { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
    { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
    { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300..700;1,9..144,700&display=swap' },
  ]
})

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

  const deposit = game.value.deposit_tokens ?? 0
  const depositHtml = deposit > 0
    ? `<div style="display:flex;justify-content:space-between;align-items:center;background:rgba(212,146,30,0.08);border:1px solid rgba(212,146,30,0.25);border-radius:8px;padding:.6rem .85rem">
        <span style="font-size:.8rem;color:var(--secondary-text)">${t('pages.game.confirm_borrow.deposit_label')}</span>
        <strong style="color:var(--accent-color)">${deposit} Token</strong>
       </div>`
    : ''

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
      ${depositHtml}
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
    if (auth.user) {
      const deposit = game.value.deposit_tokens ?? 0
      auth.setUser({
        ...auth.user,
        tokens: Math.max(0, auth.user.tokens - 2),
        tokens_blocked: (auth.user.tokens_blocked ?? 0) + deposit,
      })
    }
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
    game.value.languages?.length || game.value.year
  )
)

onMounted(async () => {
  window.addEventListener('keydown', (e) => {
    if (!lightbox.open) return
    if (e.key === 'Escape') closeLightbox()
    if (e.key === 'ArrowRight') lightboxStep(1)
    if (e.key === 'ArrowLeft') lightboxStep(-1)
  })

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
// ── Design tokens ─────────────────────────────────────────────────
$ink:        #090705;
$cream:      #F2EAD9;
$cream-60:   rgba(242, 234, 217, 0.60);
$amber:      #F7963D;
$amber-dim:  rgba(247, 150, 61, 0.12);
$amber-ring: rgba(247, 150, 61, 0.28);
$amber-glow: rgba(247, 150, 61, 0.25);
$nav-h:      64px;
$bar-h:      72px;

// ── Animations ────────────────────────────────────────────────────
@keyframes gd-up {
  from { opacity: 0; transform: translateY(28px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes gd-in {
  from { opacity: 0; }
  to   { opacity: 1; }
}
@keyframes gd-poster-in {
  from { opacity: 0; transform: scale(0.9) rotate(-2deg); }
  to   { opacity: 1; transform: scale(1) rotate(-2deg); }
}
@keyframes gd-cue {
  0%, 100% { transform: translateY(0); opacity: 0.4; }
  50%       { transform: translateY(8px); opacity: 0.9; }
}
@keyframes gd-spin {
  to { transform: rotate(360deg); }
}

// ── Page shell ────────────────────────────────────────────────────
.gd {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--background);
}

// ── Loading / 404 ─────────────────────────────────────────────────
.gd-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: calc(#{$nav-h} + 4rem) 1.5rem 4rem;

  &__msg {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-text);
  }
}

.gd-spin {
  width: 36px;
  height: 36px;
  border: 3px solid $amber-dim;
  border-top-color: $amber;
  border-radius: 50%;
  animation: gd-spin 0.8s linear infinite;
}

// ── Hero ──────────────────────────────────────────────────────────
.gd-hero {
  position: relative;
  background: $ink;
  min-height: 100svh;
  display: flex;
  flex-direction: column;

  // Background layer
  &__bg {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
  }

  &__bg-img {
    position: absolute;
    inset: -8%;
    width: 116%;
    height: 116%;
    object-fit: cover;
    filter: blur(52px) saturate(0.65);
    opacity: 0.5;
    animation: gd-in 1.4s ease both;
  }

  &__bg-veil {
    position: absolute;
    inset: 0;
    background: linear-gradient(
      180deg,
      rgba(9,7,5,0.55)  0%,
      rgba(9,7,5,0.20) 35%,
      rgba(9,7,5,0.65) 70%,
      rgba(9,7,5,0.97) 100%
    );
  }

  &__grain {
    position: absolute;
    inset: 0;
    opacity: 0.06;
    mix-blend-mode: overlay;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.72' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='300' height='300' filter='url(%23n)'/%3E%3C/svg%3E");
    background-size: 300px 300px;
  }

  // Fade hero into page background
  &__fade {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 220px;
    background: linear-gradient(to bottom, transparent, var(--background));
    pointer-events: none;
    z-index: 1;
  }

  // Poster stage
  &__stage {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: calc(#{$nav-h} + 2.5rem) 2rem 3rem;
    position: relative;
    z-index: 2;
  }

  &__poster-wrap {
    position: relative;
    animation: gd-poster-in 1s cubic-bezier(0.16, 1, 0.3, 1) both;
    animation-delay: 0.1s;
  }

  &__poster-halo {
    position: absolute;
    inset: -30px;
    border-radius: 24px;
    background: radial-gradient(ellipse at center, $amber-glow 0%, transparent 68%);
    filter: blur(24px);
    pointer-events: none;
    opacity: 0.7;
  }

  &__poster {
    display: block;
    max-height: 54vh;
    max-width: min(260px, 68vw);
    width: auto;
    height: auto;
    border-radius: 10px;
    transform: rotate(-2deg);
    box-shadow:
      0 40px 90px rgba(0,0,0,0.75),
      0 0 0 1px rgba(255,255,255,0.09),
      0 0 60px $amber-glow;
    position: relative;
    z-index: 1;
  }

  &__poster-empty {
    width: 190px;
    height: 265px;
    border-radius: 10px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    transform: rotate(-2deg);
    color: rgba(255,255,255,0.25);
    font-size: 3rem;
    position: relative;
    z-index: 1;
  }

  // Title block
  &__caption {
    position: relative;
    z-index: 3;
    padding: 0 1.5rem 1.25rem;
    max-width: 680px;
    margin: 0 auto;
    width: 100%;
    animation: gd-up 0.75s cubic-bezier(0.16, 1, 0.3, 1) both;
    animation-delay: 0.3s;
  }

  &__cue {
    position: relative;
    z-index: 3;
    text-align: center;
    color: $cream-60;
    font-size: 1.8rem;
    padding-bottom: 1.25rem;
    animation: gd-cue 2.2s ease-in-out infinite;
    animation-delay: 1.8s;
  }
}

// ── Back link ─────────────────────────────────────────────────────
.gd-back {
  position: absolute;
  top: calc(#{$nav-h} + 0.875rem);
  left: 1.25rem;
  z-index: 10;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.83rem;
  font-weight: 500;
  color: $cream-60;
  text-decoration: none;
  padding: 0.38rem 0.8rem 0.38rem 0.5rem;
  border-radius: 999px;
  border: 1px solid rgba(255,255,255,0.12);
  background: rgba(9,7,5,0.45);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  transition: color 0.2s, border-color 0.2s, background 0.2s;
  animation: gd-in 0.5s ease both;

  .icon { font-size: 1rem; }

  &:hover {
    color: $cream;
    border-color: rgba(255,255,255,0.22);
    background: rgba(9,7,5,0.65);
  }
}

// ── Pills ─────────────────────────────────────────────────────────
.gd-pills {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  flex-wrap: wrap;
  margin-bottom: 0.65rem;
}

.gd-pill {
  display: inline-flex;
  align-items: center;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
  border: 1px solid;

  &--cat  { color: $amber;   border-color: $amber-ring; background: $amber-dim; }
  &--avail { color: #4ade80; border-color: rgba(74,222,128,0.32); background: rgba(74,222,128,0.1); }
  &--out  { color: $amber;   border-color: $amber-ring; background: $amber-dim; }
}

// ── Game title ────────────────────────────────────────────────────
.gd-title {
  font-family: 'Fraunces', Georgia, 'Times New Roman', serif;
  font-size: clamp(2.1rem, 7.5vw, 3.6rem);
  font-weight: 700;
  font-style: italic;
  letter-spacing: -0.04em;
  line-height: 1.04;
  color: $cream;
  margin: 0;
  text-shadow: 0 2px 30px rgba(0,0,0,0.55);
}

// ── Stats bar ─────────────────────────────────────────────────────
.gd-stats {
  background: var(--secondary-background);
  border-bottom: 1px solid var(--divider);
  overflow-x: auto;
  overflow-y: hidden;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;

  &::-webkit-scrollbar { display: none; }

  &__track {
    display: flex;
    align-items: stretch;
    padding: 0 0.75rem;
    min-width: max-content;
  }
}

.gd-stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.1rem;
  padding: 0.9rem 1.1rem;
  text-align: center;

  &__ico {
    font-size: 1rem;
    color: $amber;
    margin-bottom: 0.15rem;
  }

  &__lbl {
    font-size: 0.63rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--secondary-text);
    white-space: nowrap;
  }

  &__val {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--primary-text);
    white-space: nowrap;
  }

  &__sep {
    width: 1px;
    background: var(--divider);
    flex-shrink: 0;
    margin: 0.7rem 0;
  }
}

// ── Main body ─────────────────────────────────────────────────────
.gd-main {
  flex: 1;
  padding: 2rem 1.5rem calc(#{$bar-h} + 2.5rem);
  max-width: 680px;
  margin: 0 auto;
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 1.75rem;
  box-sizing: border-box;

  @media (min-width: 640px) {
    padding-bottom: 4rem;
  }
}

// ── Desktop CTA actions ───────────────────────────────────────────
.gd-actions {
  display: none;

  @media (min-width: 640px) {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    align-items: center;
  }
}

.gd-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.75rem 1.5rem;
  border-radius: 12px;
  font-size: 0.93rem;
  font-weight: 700;
  cursor: pointer;
  border: none;
  text-decoration: none;
  transition: filter 0.18s, opacity 0.18s;

  .icon { font-size: 1.05rem; }

  &--primary  { background: $amber; color: #1a0d00; &:hover { filter: brightness(1.09); } }
  &--secondary {
    background: transparent;
    color: $amber;
    border: 1.5px solid $amber-ring;
    &:hover { background: $amber-dim; }
  }
  &--done     { background: transparent; color: var(--secondary-text); border: 1.5px solid var(--divider); opacity: 0.6; cursor: default; }
  &:disabled  { opacity: 0.45; cursor: default; pointer-events: none; }
}

.gd-avail-note {
  font-size: 0.78rem;
  font-weight: 500;
  color: var(--secondary-text);
  padding: 0.38rem 0.8rem;
  border: 1px solid var(--divider);
  border-radius: 8px;
  background: var(--secondary-background);
}

// ── Short description ─────────────────────────────────────────────
.gd-summary {
  font-size: 1.08rem;
  font-weight: 500;
  line-height: 1.62;
  color: var(--primary-text);
  margin: 0;
  padding-left: 1rem;
  border-left: 3px solid $amber;
}

// ── Tags ──────────────────────────────────────────────────────────
.gd-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.gd-tag {
  font-size: 0.78rem;
  font-weight: 500;
  color: var(--secondary-text);
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 999px;
  padding: 0.25rem 0.7rem;
  transition: color 0.18s, border-color 0.18s;

  &:hover {
    color: $amber;
    border-color: $amber-ring;
  }
}

// ── Prose (rich text) ─────────────────────────────────────────────
.gd-desc { font-size: 1rem; line-height: 1.75; color: var(--secondary-text); }

.prose {
  :deep(h2) { font-size: 1.2rem; font-weight: 700; color: var(--primary-text); margin: 1.25rem 0 0.5rem; letter-spacing: -0.02em; }
  :deep(h3) { font-size: 1.05rem; font-weight: 700; color: var(--primary-text); margin: 1rem 0 0.4rem; }
  :deep(p)  { margin-bottom: 0.75rem; }
  :deep(p:last-child) { margin-bottom: 0; }
  :deep(ul), :deep(ol) { margin-left: 1.5rem; margin-bottom: 0.75rem; }
  :deep(ul) { list-style: disc; }
  :deep(ol) { list-style: decimal; }
  :deep(li) { margin-bottom: 0.2rem; line-height: 1.65; }
  :deep(strong), :deep(b) { font-weight: 700; color: var(--primary-text); }
  :deep(em), :deep(i) { font-style: italic; }
  :deep(u) { text-underline-offset: 2px; }
  :deep(s) { opacity: 0.6; }
  :deep(a) { color: $amber; text-decoration: underline; text-underline-offset: 2px; &:hover { opacity: 0.8; } }
  :deep(blockquote) {
    border-left: 3px solid $amber-ring;
    padding-left: 1rem;
    margin: 0.75rem 0;
    font-style: italic;
  }
}

// ── Gallery ───────────────────────────────────────────────────────
.gd-gallery {
  margin: 0 -1.5rem;

  &__rail {
    display: flex;
    gap: 0.65rem;
    overflow-x: auto;
    overflow-y: hidden;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    padding: 0.25rem 1.5rem 0.5rem;

    &::-webkit-scrollbar { display: none; }
  }

  &__thumb {
    flex-shrink: 0;
    position: relative;
    width: 72vw;
    max-width: 310px;
    aspect-ratio: 4/3;
    border: none;
    padding: 0;
    background: none;
    cursor: zoom-in;
    border-radius: 12px;
    overflow: hidden;
    scroll-snap-align: start;

    &:hover .gd-gallery__zoom { opacity: 1; }
    &:hover .gd-gallery__img  { transform: scale(1.04); }
  }

  &__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid var(--divider);
    display: block;
    transition: transform 0.3s ease;
  }

  &__zoom {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(9,7,5,0.45);
    color: #fff;
    font-size: 1.75rem;
    opacity: 0;
    transition: opacity 0.2s;
    border-radius: 12px;
  }
}

// ── Lightbox ──────────────────────────────────────────────────────
.gd-lb {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(6, 5, 3, 0.96);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  display: flex;
  align-items: center;
  justify-content: center;

  &__backdrop {
    position: absolute;
    inset: 0;
    z-index: 0;
    cursor: default;
  }

  &__close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    z-index: 10;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.15);
    background: rgba(255,255,255,0.08);
    color: rgba(255,255,255,0.8);
    font-size: 1.4rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.18s, color 0.18s;

    &:hover { background: rgba(255,255,255,0.16); color: #fff; }
  }

  &__stage {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 4rem 1rem 4rem;
    min-height: 0;
    overflow: hidden;
    position: relative;
    z-index: 1;
  }

  &__img {
    max-width: 100%;
    max-height: 85vh;
    width: auto;
    height: auto;
    object-fit: contain;
    border-radius: 10px;
    box-shadow: 0 32px 80px rgba(0,0,0,0.7);
    user-select: none;
    -webkit-user-drag: none;
  }

  &__nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.15);
    background: rgba(255,255,255,0.08);
    color: rgba(255,255,255,0.8);
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.18s, color 0.18s;

    &:hover { background: rgba(255,255,255,0.16); color: #fff; }

    &--prev { left: 0.75rem; }
    &--next { right: 0.75rem; }

    @media (min-width: 640px) {
      &--prev { left: 1.5rem; }
      &--next { right: 1.5rem; }
    }
  }

  &__dots {
    position: absolute;
    bottom: 1.25rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 0.5rem;
    align-items: center;
    z-index: 10;
  }

  &__dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    border: none;
    background: rgba(255,255,255,0.3);
    cursor: pointer;
    padding: 0;
    transition: background 0.18s, transform 0.18s;

    &--active {
      background: $amber;
      transform: scale(1.4);
    }
  }
}

// Lightbox enter/leave transition
.lb-enter-active { transition: opacity 0.22s ease; }
.lb-leave-active { transition: opacity 0.18s ease; }
.lb-enter-from, .lb-leave-to { opacity: 0; }

// Image slide transitions
.lb-next-enter-active,
.lb-next-leave-active,
.lb-prev-enter-active,
.lb-prev-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.lb-next-enter-from { opacity: 0; transform: translateX(40px); }
.lb-next-leave-to   { opacity: 0; transform: translateX(-40px); }
.lb-prev-enter-from { opacity: 0; transform: translateX(-40px); }
.lb-prev-leave-to   { opacity: 0; transform: translateX(40px); }

// ── Instagram ─────────────────────────────────────────────────────
.gd-insta {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.87rem;
  font-weight: 600;
  color: var(--secondary-text);
  text-decoration: none;
  padding: 0.52rem 1rem;
  border: 1px solid var(--divider);
  border-radius: 999px;
  background: var(--secondary-background);
  align-self: flex-start;
  transition: color 0.18s, border-color 0.18s, background 0.18s;

  &:hover { color: $amber; border-color: $amber-ring; background: $amber-dim; }
}

// ── Mobile float CTA bar ──────────────────────────────────────────
.gd-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 200;
  border-top: 1px solid var(--divider);
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(20px) saturate(1.6);
  -webkit-backdrop-filter: blur(20px) saturate(1.6);
  padding-bottom: env(safe-area-inset-bottom, 0px);

  [data-theme="dark"] & {
    background: rgba(12, 10, 8, 0.92);
  }

  @media (min-width: 640px) {
    display: none;
  }

  &__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0 1.25rem;
    height: $bar-h;
  }

  &__left {
    display: flex;
    flex-direction: column;
    gap: 0.18rem;
    min-width: 0;
    flex: 1;
  }

  &__name {
    font-size: 0.83rem;
    font-weight: 700;
    color: var(--primary-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  &__tokens {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    font-size: 0.73rem;
    font-weight: 500;
    color: $amber;
  }

  &__btn {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    padding: 0.62rem 1.15rem;
    border-radius: 12px;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    border: none;
    text-decoration: none;
    background: $amber;
    color: #1a0d00;
    white-space: nowrap;
    transition: filter 0.18s;

    &:hover { filter: brightness(1.09); }

    &--sec {
      background: transparent;
      color: $amber;
      border: 1.5px solid $amber-ring;
      &:hover { background: $amber-dim; }
    }

    &--done {
      background: transparent;
      color: var(--secondary-text);
      border: 1.5px solid var(--divider);
      padding: 0.62rem 0.75rem;
      font-size: 1.2rem;
    }

    &:disabled { opacity: 0.45; cursor: default; }
  }
}

// ── Footer ────────────────────────────────────────────────────────
.l-footer {
  background: $ink;
  border-top: 1px solid rgba(242, 234, 217, 0.1);
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
  &__hex   { font-size: 1.2rem; color: $amber; }
  &__name  { font-size: 0.95rem; font-weight: 700; color: $cream; letter-spacing: -0.02em; }

  &__nav {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    flex: 1;
    justify-content: center;
    @media (max-width: 640px) { justify-content: flex-start; }
  }

  &__link {
    font-size: 0.85rem;
    color: $cream-60;
    text-decoration: none;
    transition: color 0.2s;
    &:hover { color: $cream; }
  }

  &__copy {
    font-size: 0.78rem;
    color: rgba(242, 234, 217, 0.38);
    margin-left: auto;
    padding-bottom: 0;
    @media (max-width: 640px) { margin-left: 0; width: 100%; }
  }
}
</style>
