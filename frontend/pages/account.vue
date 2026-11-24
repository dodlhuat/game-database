<template>
  <div class="account-page">
    <!-- ── Hero: who you are ────────────────────────────────────── -->
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <NuxtLink to="/dashboard" class="back__cta">
          <svg class="icon-svg" aria-hidden="true">
            <use href="/svg-icons/icons.svg#arrow_back" />
          </svg>
          Dashboard
        </NuxtLink>
        <div class="identity">
          <span class="identity__avatar" aria-hidden="true">{{ initials }}</span>
          <div class="identity__text">
            <p class="identity__eyebrow">{{ $t('account.my_account') }}</p>
            <h1 class="identity__name">{{ auth.user?.name }}</h1>
            <p class="identity__email">{{ auth.user?.email }}</p>
          </div>
          <div class="identity__chips">
            <span class="status-chip" :class="`status-chip--${statusChip.tone}`">
              <i class="status-chip__dot" aria-hidden="true" />
              {{ statusChip.label }}
            </span>
            <NuxtLink v-if="auth.canBorrow" to="/tokens" class="status-chip__cta">
              <strong>{{ auth.totalTokens }}</strong> {{ $t('dashboard.tokens.label') }}
            </NuxtLink>
          </div>
        </div>
      </div>
    </section>

    <div class="account-content">
      <div class="account-content__inner">
        <!-- Section index: sticky rail on desktop, chip row on phones -->
        <nav class="section-nav" :aria-label="$t('account.nav_label')">
          <a
            v-for="item in navItems"
            :key="item.id"
            :href="`#${item.id}`"
            class="section-nav__cta"
            :class="{ 'is-active': activeSection === item.id }"
            @click.prevent="scrollToSection(item.id)"
            >{{ item.label }}</a
          >
        </nav>

        <div class="account-sections">
          <!-- Profil -->
          <section id="profil" class="account-section" style="--i: 0">
            <header class="account-section__header">
              <h2 class="account-section__title">{{ $t('account.profile_title') }}</h2>
            </header>

            <div v-if="profileSuccess" class="alert alert-success">{{ profileSuccess }}</div>
            <div v-if="profileError" class="alert alert-error">{{ profileError }}</div>

            <fieldset class="field-group">
              <legend class="field-group__legend">{{ $t('account.section_personal') }}</legend>
              <div class="field-group__grid">
                <UiInput
                  v-model="profileForm.name"
                  :label="$t('auth.name')"
                  :error="profileErrors.name"
                  autocomplete="name"
                />
                <UiInput
                  v-model="profileForm.email"
                  type="email"
                  :label="$t('auth.email')"
                  :error="profileErrors.email"
                  autocomplete="email"
                />
                <UiDatePicker
                  v-if="auth.canBorrow"
                  v-model="profileForm.date_of_birth"
                  :label="$t('account.date_of_birth')"
                  :max-date="new Date()"
                />
              </div>
            </fieldset>

            <fieldset v-if="auth.canBorrow" class="field-group">
              <legend class="field-group__legend">{{ $t('account.section_address') }}</legend>
              <p class="field-group__hint">{{ $t('account.address_hint') }}</p>
              <UiAddressFields
                v-model:street="profileForm.street"
                v-model:postal-code="profileForm.postal_code"
                v-model:city="profileForm.city"
                :street-error="profileErrors.street"
                :postal-code-error="profileErrors.postal_code"
                :city-error="profileErrors.city"
              />
            </fieldset>

            <footer class="account-section__footer">
              <Transition name="fade">
                <span v-if="profileDirty" class="unsaved">
                  <i class="unsaved__dot" aria-hidden="true" />
                  {{ $t('account.unsaved') }}
                </span>
              </Transition>
              <UiButton :loading="savingProfile" @click="saveProfile">{{
                $t('account.profile_save')
              }}</UiButton>
            </footer>
          </section>

          <!-- Newsletter -->
          <section id="newsletter" class="account-section account-section--row" style="--i: 1">
            <div class="account-section__intro">
              <h2 class="account-section__title">{{ $t('account.newsletter_title') }}</h2>
              <p class="account-section__text">{{ $t('account.newsletter_text') }}</p>
            </div>
            <div class="newsletter-toggle">
              <Transition name="fade">
                <span v-if="newsletterMsg" class="saved-flash" role="status">
                  <svg class="icon-svg" aria-hidden="true">
                    <use href="/svg-icons/icons.svg#check" />
                  </svg>
                  {{ newsletterMsg }}
                </span>
              </Transition>
              <UiSwitch
                v-model="newsletterOptIn"
                :label="$t('account.newsletter_subscribe')"
                @change="saveNewsletter"
              />
            </div>
          </section>

          <!-- Passwort -->
          <section id="passwort" class="account-section" style="--i: 2">
            <div class="account-section__row">
              <div class="account-section__intro">
                <h2 class="account-section__title">{{ $t('account.nav_password') }}</h2>
                <p class="account-section__text">
                  {{ $t('account.password_text', { email: auth.user?.email }) }}
                </p>
              </div>
              <button
                v-if="!pwOpen"
                type="button"
                class="ghost-btn password-reveal"
                :aria-expanded="pwOpen"
                aria-controls="password-form"
                @click="pwOpen = true"
              >
                <svg class="icon-svg" aria-hidden="true">
                  <use href="/svg-icons/icons.svg#lock" />
                </svg>
                {{ $t('account.password_title') }}
              </button>
            </div>

            <div v-if="pwSuccess" class="alert alert-success">{{ pwSuccess }}</div>

            <Transition name="expand">
              <div v-if="pwOpen" id="password-form" class="password-form">
                <div class="field-group__grid">
                  <UiInput
                    v-model="pwForm.current_password"
                    type="password"
                    :label="$t('account.password_current')"
                    :error="pwErrors.current_password"
                    autocomplete="current-password"
                  />
                  <UiInput
                    v-model="pwForm.new_password"
                    type="password"
                    :label="$t('account.password_new')"
                    :error="pwErrors.new_password"
                    autocomplete="new-password"
                  />
                  <UiInput
                    v-model="pwForm.new_password_confirmation"
                    type="password"
                    :label="$t('account.password_confirm_new')"
                    autocomplete="off"
                  />
                </div>

                <div v-if="pwError" class="alert alert-error">{{ pwError }}</div>

                <footer class="account-section__footer">
                  <button type="button" class="ghost-btn" @click="closePasswordForm">
                    {{ $t('btn.cancel') }}
                  </button>
                  <UiButton :loading="savingPw" class="password-save" @click="changePassword">{{
                    $t('account.password_save')
                  }}</UiButton>
                </footer>
              </div>
            </Transition>
          </section>

          <!-- Mitgliedschaft -->
          <section
            v-if="!auth.isAdmin"
            id="mitgliedschaft"
            class="account-section account-section--membership"
            style="--i: 3"
          >
            <header class="account-section__header">
              <h2 class="account-section__title">{{ $t('account.membership_title') }}</h2>
            </header>

            <template v-if="hasMembership || auth.isMembershipExpired">
              <div class="membership" :class="`membership--${expiryTone}`">
                <div class="membership__status">
                  <span class="membership__kind">{{
                    auth.user?.role === 'SUPPORTER'
                      ? $t('dashboard.membership.supporter_label')
                      : $t('account.membership_full')
                  }}</span>
                  <span class="membership__until">{{
                    $t(
                      auth.isMembershipExpired
                        ? 'account.membership_expired_on'
                        : 'account.membership_active_until',
                      { date: formatDate(auth.user?.membership_expires_at) }
                    )
                  }}</span>
                  <span v-if="auth.isMembershipExpired" class="membership__hint">{{
                    $t('account.membership_expired_hint')
                  }}</span>
                </div>
                <div class="membership__actions">
                  <button
                    v-if="canRenew"
                    type="button"
                    class="button button-primary"
                    :disabled="renewLoading"
                    @click="renew"
                  >
                    {{ $t('btn.renew') }}
                  </button>
                  <NuxtLink v-if="auth.isSupporter" to="/upgrade" class="membership__upgrade-cta">{{
                    $t('dashboard.membership.activate_full')
                  }}</NuxtLink>
                </div>
              </div>
              <div v-if="renewError" class="alert alert-error">{{ renewError }}</div>
            </template>
            <div v-else class="membership membership--none">
              <p class="account-section__text">{{ $t('dashboard.membership.not_member') }}</p>
              <NuxtLink to="/upgrade" class="membership__upgrade-cta">{{
                $t('dashboard.membership.upgrade')
              }}</NuxtLink>
            </div>

            <div v-if="canCancel" class="cancel-zone">
              <p class="cancel-zone__text">{{ $t('account.cancel.section_text') }}</p>
              <button type="button" class="cancel-zone__btn" @click="cancelOpen = true">
                {{ $t('account.cancel.open') }}
              </button>
            </div>
          </section>
        </div>
      </div>
    </div>

    <AppFooter />

    <CancelMembershipModal
      :open="cancelOpen"
      @close="cancelOpen = false"
      @cancelled="onCancelled"
    />
    <StripeCheckoutModal
      :membership="renewCheckout"
      @success="onRenewed"
      @error="renewError = $event"
      @close="renewCheckout = null"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue'

definePageMeta({ middleware: ['auth'] })

const api = useApi()
const auth = useAuthStore()
const { t } = useI18n()

// Profile
const profileForm = reactive({
  name: '',
  email: '',
  street: '',
  postal_code: '',
  city: '',
  date_of_birth: '',
})
const profileErrors = reactive({
  name: '',
  email: '',
  street: '',
  postal_code: '',
  city: '',
  date_of_birth: '',
})
const profileSuccess = ref('')
const profileError = ref('')
const savingProfile = ref(false)

// Compare against the stored user so the page can flag unsaved edits
const profileDirty = computed(() => {
  const u = auth.user
  if (!u) return false
  const same = (a: string, b: string | null | undefined) => a === (b ?? '')
  return !(
    same(profileForm.name, u.name) &&
    same(profileForm.email, u.email) &&
    (!auth.canBorrow ||
      (same(profileForm.street, u.street) &&
        same(profileForm.postal_code, u.postal_code) &&
        same(profileForm.city, u.city) &&
        same(profileForm.date_of_birth, u.date_of_birth)))
  )
})

// Newsletter
const newsletterOptIn = ref(false)
const newsletterMsg = ref('')

// Identity
const initials = computed(() =>
  (auth.user?.name ?? '')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]!.toUpperCase())
    .join('')
)

function formatDate(iso: string | null | undefined) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('de-DE')
}

const hasMembership = computed(() => auth.isMember || auth.isSupporter)
const canCancel = computed(() => auth.user?.role === 'MEMBER' || auth.user?.role === 'SUPPORTER')

const daysLeft = computed(() => {
  const exp = auth.user?.membership_expires_at
  if (!exp) return null
  return (new Date(exp).getTime() - Date.now()) / 86_400_000
})
const expiryTone = computed(() => {
  if (daysLeft.value === null) return 'ok'
  if (auth.isMembershipExpired) return 'expired'
  if (daysLeft.value < 30) return 'critical'
  if (daysLeft.value < 90) return 'warn'
  return 'ok'
})
const canRenew = computed(() => daysLeft.value !== null && daysLeft.value <= 90)

const statusChip = computed(() => {
  if (auth.isAdmin) return { label: t('account.role_admin'), tone: 'neutral' }
  if (hasMembership.value) {
    const date = formatDate(auth.user?.membership_expires_at)
    return {
      label: auth.isSupporter
        ? `${t('dashboard.membership.supporter_label')} · ${date}`
        : t('account.membership_active_until', { date }),
      tone: expiryTone.value === 'ok' ? 'active' : expiryTone.value,
    }
  }
  if (auth.isMembershipExpired) {
    return {
      label: t('account.membership_expired_on', {
        date: formatDate(auth.user?.membership_expires_at),
      }),
      tone: 'critical',
    }
  }
  return { label: t('account.no_membership'), tone: 'neutral' }
})

// Section index + scroll spy
const navItems = computed(() => {
  const items = [
    { id: 'profil', label: t('account.profile_title') },
    { id: 'newsletter', label: t('account.newsletter_title') },
    { id: 'passwort', label: t('account.nav_password') },
  ]
  if (!auth.isAdmin) items.push({ id: 'mitgliedschaft', label: t('account.membership_title') })
  return items
})
const activeSection = ref('profil')
let observer: IntersectionObserver | null = null

function scrollToSection(id: string) {
  activeSection.value = id
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

onMounted(() => {
  if (typeof IntersectionObserver === 'undefined') return
  observer = new IntersectionObserver(
    (entries) => {
      const visible = entries.filter((e) => e.isIntersecting)
      if (visible.length) activeSection.value = visible[0]!.target.id
    },
    { rootMargin: '-35% 0px -55% 0px' }
  )
  navItems.value.forEach((item) => {
    const el = document.getElementById(item.id)
    if (el) observer!.observe(el)
  })
})
onBeforeUnmount(() => observer?.disconnect())

// Renew membership (same flow as the dashboard)
const { renewCheckout, renewError, renewLoading, renew, onRenewed } = useMembershipRenewal()

// Cancel membership
const cancelOpen = ref(false)
async function onCancelled(user: unknown) {
  cancelOpen.value = false
  if (user) auth.setUser(user as NonNullable<typeof auth.user>)
  await navigateTo('/dashboard')
}

// Password
const pwForm = ref({ current_password: '', new_password: '', new_password_confirmation: '' })
const pwErrors = ref({ current_password: '', new_password: '' })
const pwError = ref('')
const pwSuccess = ref('')
const savingPw = ref(false)
const pwOpen = ref(false)

function closePasswordForm() {
  pwOpen.value = false
  pwForm.value = { current_password: '', new_password: '', new_password_confirmation: '' }
  pwErrors.value = { current_password: '', new_password: '' }
  pwError.value = ''
}

onMounted(() => {
  profileForm.name = auth.user?.name ?? ''
  profileForm.email = auth.user?.email ?? ''
  profileForm.street = auth.user?.street ?? ''
  profileForm.postal_code = auth.user?.postal_code ?? ''
  profileForm.city = auth.user?.city ?? ''
  profileForm.date_of_birth = auth.user?.date_of_birth ?? ''
  newsletterOptIn.value = auth.user?.newsletter_opt_in ?? false
})

async function saveProfile() {
  Object.keys(profileErrors).forEach((k) => ((profileErrors as Record<string, string>)[k] = ''))
  profileSuccess.value = ''
  profileError.value = ''
  savingProfile.value = true

  try {
    const payload: Record<string, string> = {
      name: profileForm.name,
      email: profileForm.email,
    }
    if (auth.canBorrow) {
      payload.street = profileForm.street
      payload.postal_code = profileForm.postal_code
      payload.city = profileForm.city
      payload.date_of_birth = profileForm.date_of_birth
    }

    const data = await api.patch<{ user: typeof auth.user; message: string }>('/account', payload)
    if (data.user) auth.setUser(data.user)
    profileSuccess.value = t('account.profile_saved')
  } catch (err: unknown) {
    const e = err as { errors?: Record<string, string[]>; message?: string }
    if (e.errors) {
      Object.entries(e.errors).forEach(([k, msgs]) => {
        if (k in profileErrors) (profileErrors as Record<string, string>)[k] = msgs[0] ?? ''
      })
    } else {
      profileError.value = e.message ?? t('common.error.generic')
    }
  } finally {
    savingProfile.value = false
  }
}

async function saveNewsletter() {
  newsletterMsg.value = ''
  try {
    await api.patch('/account', { newsletter_opt_in: newsletterOptIn.value })
    newsletterMsg.value = newsletterOptIn.value
      ? t('account.newsletter_subscribed')
      : t('account.newsletter_unsubscribed')
    const shown = newsletterMsg.value
    setTimeout(() => {
      if (newsletterMsg.value === shown) newsletterMsg.value = ''
    }, 2500)
  } catch {
    newsletterOptIn.value = !newsletterOptIn.value
  }
}

async function changePassword() {
  pwErrors.value = { current_password: '', new_password: '' }
  pwError.value = ''
  pwSuccess.value = ''

  if (!pwForm.value.current_password || !pwForm.value.new_password) return
  if (pwForm.value.new_password !== pwForm.value.new_password_confirmation) {
    pwErrors.value.new_password = t('account.password_mismatch')
    return
  }

  savingPw.value = true
  try {
    await api.patch('/account', {
      current_password: pwForm.value.current_password,
      new_password: pwForm.value.new_password,
      new_password_confirmation: pwForm.value.new_password_confirmation,
    })
    pwSuccess.value = t('account.password_changed')
    pwForm.value = { current_password: '', new_password: '', new_password_confirmation: '' }
  } catch (err: unknown) {
    const e = err as { errors?: Record<string, string[]>; message?: string }
    if (e.errors) {
      Object.entries(e.errors).forEach(([k, msgs]) => {
        if (k in pwErrors.value) (pwErrors.value as Record<string, string>)[k] = msgs[0] ?? ''
      })
    } else {
      pwError.value = e.message ?? t('common.error.generic')
    }
  } finally {
    savingPw.value = false
  }
}
</script>

<style lang="scss" scoped>
$nav-height: 64px;
$content-width: 1100px;
$ease-out: cubic-bezier(0.16, 1, 0.3, 1);

@function amber($pct) {
  @return color-mix(in srgb, #{$amber} $pct, transparent);
}
@function ink($pct) {
  @return color-mix(in srgb, var(--primary-text) $pct, transparent);
}
// Small amber text: #f7963d is ~2:1 on light, so light mode uses a deeper
// burnt orange (4.5:1); dark mode keeps the bright accent.
$amber-ink: var(--amber-ink);

.account-page {
  --amber-ink: #b45f0f;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--background);
}
:root[data-theme='dark'] .account-page {
  --amber-ink: #{$amber};
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
  padding: calc(#{$nav-height} + 1.75rem) 1.5rem 2.75rem;
  overflow: hidden;
  @media (max-width: 600px) {
    padding: calc(#{$nav-height} + 1.25rem) 1rem 2rem;
  }

  &__backdrop {
    position: absolute;
    inset: 0;
    pointer-events: none;
    // fade out so the glow never ends in a hard line
    mask-image: linear-gradient(to bottom, black 50%, transparent);
  }
  &__glow {
    position: absolute;
    width: 460px;
    height: 460px;
    top: -200px;
    left: -120px;
    border-radius: 50%;
    filter: blur(100px);
    background: amber(16%);
  }
  &__dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, #{ink(6%)} 1px, transparent 1px);
    background-size: 24px 24px;
    mask-image: radial-gradient(ellipse 70% 100% at 20% 30%, black 10%, transparent 100%);
  }
  &__body {
    position: relative;
    z-index: 1;
    max-width: $content-width;
    margin: 0 auto;
  }
}

// `__cta` opts out of the global amber link rule (see _typography.scss)
.back__cta {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  margin-bottom: 1.25rem;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--secondary-text);
  transition: color 0.15s;
  .icon-svg {
    width: 0.95rem;
    height: 0.95rem;
    transition: translate 0.2s $ease-out;
  }
  &:hover {
    color: var(--primary-text);
    .icon-svg {
      translate: -2px 0;
    }
  }
}

.identity {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  gap: 1rem 1.5rem;
  animation: rise 0.55s $ease-out both;
  @media (max-width: 720px) {
    grid-template-columns: auto minmax(0, 1fr);
  }

  &__avatar {
    display: grid;
    place-items: center;
    width: 76px;
    height: 76px;
    border-radius: 24px;
    rotate: -4deg;
    font-size: 1.6rem;
    font-weight: 800;
    letter-spacing: -0.04em;
    color: #1a0d00;
    background: linear-gradient(150deg, #ffc35a, #{$amber} 55%, #c96f12);
    box-shadow:
      0 12px 30px #{amber(32%)},
      inset 0 1px 0 rgba(255, 255, 255, 0.35);
    @media (max-width: 600px) {
      width: 60px;
      height: 60px;
      border-radius: 18px;
      font-size: 1.25rem;
    }
  }
  &__eyebrow {
    margin: 0 0 0.2rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: $amber-ink;
  }
  &__name {
    margin: 0;
    font-size: clamp(1.6rem, 4.5vw, 2.6rem);
    font-weight: 800;
    line-height: 1.05;
    letter-spacing: -0.045em;
    color: var(--primary-text);
    overflow-wrap: anywhere;
  }
  &__email {
    margin: 0.3rem 0 0;
    padding: 0;
    font-size: 0.9rem;
    color: var(--secondary-text);
    overflow-wrap: anywhere;
  }
  &__chips {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
    @media (max-width: 720px) {
      grid-column: 1 / -1;
      flex-direction: row;
      flex-wrap: wrap;
      align-items: center;
    }
  }
}

.status-chip {
  --tone: var(--secondary-text);
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.35rem 0.85rem;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--primary-text);
  background: ink(4%);
  border: 1px solid ink(12%);

  &--active {
    --tone: var(--success-text);
  }
  &--warn {
    --tone: #{$amber-ink};
    border-color: amber(40%);
  }
  &--critical {
    --tone: var(--error-text);
    border-color: var(--error-border);
  }
  &__dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--tone);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--tone) 18%, transparent);
  }
  &__cta {
    display: inline-flex;
    align-items: baseline;
    gap: 0.3rem;
    padding: 0.35rem 0.85rem;
    border-radius: 999px;
    font-size: 0.8rem;
    color: var(--secondary-text);
    background: amber(10%);
    border: 1px solid amber(30%);
    transition:
      border-color 0.2s,
      transform 0.2s $ease-out;
    strong {
      font-weight: 800;
      color: $amber-ink;
    }
    &:hover {
      border-color: $amber;
      transform: translateY(-1px);
    }
  }
}

// ─── Content ──────────────────────────────────────────────────────
.account-content {
  flex: 1;
  padding: 0.5rem 1.5rem 4rem;
  @media (max-width: 600px) {
    padding: 0 1rem 3rem;
  }

  &__inner {
    max-width: $content-width;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 190px minmax(0, 1fr);
    gap: 3rem;
    align-items: start;
    @media (max-width: 860px) {
      grid-template-columns: 1fr;
      gap: 1rem;
    }
  }
}

.section-nav {
  position: sticky;
  top: calc(#{$nav-height} + 1.5rem);
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  padding-left: 0.75rem;
  border-left: 1px solid var(--divider);
  animation: rise 0.55s $ease-out 0.1s both;

  &__cta {
    position: relative;
    padding: 0.45rem 0.75rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--secondary-text);
    transition:
      color 0.2s,
      background 0.2s;
    &::before {
      content: '';
      position: absolute;
      left: calc(-0.75rem - 1px);
      top: 0.4rem;
      bottom: 0.4rem;
      width: 2px;
      border-radius: 2px;
      background: $amber;
      scale: 1 0;
      transition: scale 0.3s $ease-out;
    }
    &:hover {
      color: var(--primary-text);
      background: ink(4%);
    }
    &.is-active {
      color: var(--primary-text);
      &::before {
        scale: 1 1;
      }
    }
  }

  // Phones: a sticky, scrollable chip row under the nav bar
  @media (max-width: 860px) {
    top: $nav-height;
    z-index: 5;
    flex-direction: row;
    gap: 0.4rem;
    margin: 0 -1rem;
    padding: 0.6rem 1rem;
    border-left: none;
    overflow-x: auto;
    scrollbar-width: none;
    background: color-mix(in srgb, var(--background) 88%, transparent);
    backdrop-filter: blur(12px);
    &::-webkit-scrollbar {
      display: none;
    }
    &__cta {
      flex-shrink: 0;
      padding: 0.4rem 0.9rem;
      border-radius: 999px;
      border: 1px solid var(--divider);
      font-size: 0.8rem;
      &::before {
        display: none;
      }
      &.is-active {
        color: #1a0d00;
        background: $amber;
        border-color: $amber;
      }
    }
  }
}

.account-sections {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.account-section {
  scroll-margin-top: calc(#{$nav-height} + 1.5rem);
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding: 1.5rem 1.75rem;
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 18px;
  animation: rise 0.55s $ease-out calc(0.15s + var(--i, 0) * 70ms) both;
  @media (max-width: 860px) {
    scroll-margin-top: calc(#{$nav-height} + 3.75rem);
  }
  @media (max-width: 600px) {
    padding: 1.25rem 1.1rem;
    border-radius: 16px;
  }

  &--row {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
  }

  &__header,
  &__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem 1rem;
  }
  &__intro {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 0;
  }
  &__title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--primary-text);
  }
  &__text {
    margin: 0;
    padding: 0;
    font-size: 0.85rem;
    color: var(--secondary-text);
  }
  &__footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding-top: 1rem;
    border-top: 1px solid var(--divider);
  }
}

.field-group {
  margin: 0;
  padding: 0;
  border: none;
  min-width: 0;

  &__legend {
    padding: 0;
    margin-bottom: 0.85rem;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--secondary-text);
  }
  &__hint {
    margin: -0.4rem 0 0.85rem;
    padding: 0;
    font-size: 0.8rem;
    color: var(--secondary-text);
  }
  &__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    // a lone last field (e.g. date of birth) keeps a single column
    > :last-child:nth-child(odd) {
      grid-column: 1 / -1;
      max-width: calc(50% - 0.5rem);
    }
    @media (max-width: 600px) {
      grid-template-columns: 1fr;
      > :last-child:nth-child(odd) {
        max-width: none;
      }
    }
  }
}

.password-form .field-group__grid {
  > :first-child {
    grid-column: 1 / -1;
    max-width: calc(50% - 0.5rem);
    @media (max-width: 600px) {
      max-width: none;
    }
  }
  > :last-child:nth-child(odd) {
    grid-column: auto;
    max-width: none;
  }
}

.password-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.unsaved {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin-right: auto;
  font-size: 0.8rem;
  font-weight: 600;
  color: $amber-ink;
  &__dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: $amber;
    animation: pulse 1.6s ease-in-out infinite;
  }
}

@keyframes pulse {
  50% {
    opacity: 0.35;
  }
}

.newsletter-toggle {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.saved-flash {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--success-text);
  .icon-svg {
    width: 0.95rem;
    height: 0.95rem;
  }
}

.ghost-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.95rem;
  font-family: inherit;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--primary-text);
  background: var(--background);
  border: 1px solid var(--divider);
  border-radius: 10px;
  cursor: pointer;
  transition:
    border-color 0.2s,
    color 0.2s;
  .icon-svg {
    width: 1rem;
    height: 1rem;
    color: $amber;
  }
  &:hover {
    border-color: $amber;
  }
  &:focus-visible {
    outline: 2px solid $amber;
    outline-offset: 2px;
  }
}

// ─── Membership ───────────────────────────────────────────────────
.membership {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  padding: 1.1rem 1.25rem;
  border-radius: 14px;
  background: linear-gradient(120deg, #{amber(12%)}, #{amber(3%)} 70%);
  border: 1px solid amber(28%);

  &--warn {
    border-color: amber(55%);
  }
  &--critical {
    background: var(--error-muted);
    border-color: var(--error-border);
  }
  &--none {
    background: ink(3%);
    border-color: var(--divider);
  }

  &__status {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
  }
  &__kind {
    font-size: 1.15rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--primary-text);
  }
  &__until {
    font-size: 0.85rem;
    color: var(--secondary-text);
  }
  &--warn &__until {
    color: $amber-ink;
    font-weight: 600;
  }
  &--critical &__until {
    color: var(--error-text);
    font-weight: 600;
  }
  // Lapsed: same urgency as critical, plus a hatched edge so it reads as
  // "stopped", not just "running out"
  &--expired {
    position: relative;
    overflow: hidden;
    padding-left: 1.6rem;
    background: var(--error-muted);
    border-color: var(--error-border);

    &::before {
      content: '';
      position: absolute;
      inset: 0 auto 0 0;
      width: 6px;
      background: repeating-linear-gradient(-45deg, var(--error-text) 0 4px, transparent 4px 8px);
      opacity: 0.7;
    }
  }
  &--expired &__until {
    color: var(--error-text);
    font-weight: 700;
  }
  &__hint {
    font-size: 0.8rem;
    color: var(--secondary-text);
  }
  &__actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
  }
  &__upgrade-cta {
    font-size: 0.85rem;
    font-weight: 700;
    color: $amber-ink;
    &:hover {
      text-decoration: underline;
    }
  }
}

// Cancelling is deliberately quiet: context first, then a text button
.cancel-zone {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.75rem 1.5rem;
  padding-top: 1rem;
  border-top: 1px dashed var(--divider);

  &__text {
    flex: 1 1 18rem;
    margin: 0;
    padding: 0;
    font-size: 0.8rem;
    line-height: 1.5;
    color: var(--secondary-text);
  }
  &__btn {
    padding: 0.4rem 0;
    font-family: inherit;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--error-text);
    background: none;
    border: none;
    border-bottom: 1px solid transparent;
    cursor: pointer;
    transition: border-color 0.2s;
    &:hover {
      border-bottom-color: currentColor;
    }
    &:focus-visible {
      outline: 2px solid var(--error-text);
      outline-offset: 3px;
      border-radius: 2px;
    }
  }
}

// ─── Transitions ──────────────────────────────────────────────────
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.expand-enter-active {
  transition:
    opacity 0.3s ease,
    translate 0.35s $ease-out;
}
.expand-enter-from {
  opacity: 0;
  translate: 0 -6px;
}
</style>
