<template>
  <!-- Flyout Overlay -->
  <div class="flyout-overlay" id="flyoutOverlay" />

  <!-- Flyout Menu -->
  <div class="flyout-menu flyout-from-right" id="flyoutMenu">
    <div class="flyout-header">
      <span class="flyout-title">Navigation</span>
      <button class="close-menu" aria-label="Menü schließen">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
        </svg>
      </button>
    </div>

    <ul>
      <li><NuxtLink to="/games">Spiele</NuxtLink></li>
      <li><NuxtLink to="/packages">Pakete</NuxtLink></li>
      <li v-show="auth.isLoggedIn"><NuxtLink to="/dashboard">Mein Konto</NuxtLink></li>
      <li v-show="auth.isLoggedIn && auth.isAdmin">
        <NuxtLink to="/admin" class="flyout-admin-link">Admin-Bereich</NuxtLink>
      </li>
      <li v-show="!auth.isLoggedIn"><NuxtLink to="/login">Anmelden</NuxtLink></li>
      <li v-show="!auth.isLoggedIn"><NuxtLink to="/register">Registrieren</NuxtLink></li>
      <li v-show="auth.isLoggedIn">
        <button class="flyout-logout-btn" @click="handleLogout">Abmelden</button>
      </li>
      <li class="flyout-divider" aria-hidden="true" />
      <li class="flyout-legal-item"><NuxtLink to="/terms">Nutzungsbedingungen</NuxtLink></li>
      <li class="flyout-legal-item"><NuxtLink to="/privacy">Datenschutz</NuxtLink></li>
      <li class="flyout-legal-item"><NuxtLink to="/cookies">Cookie-Richtlinien</NuxtLink></li>
    </ul>

    <div class="flyout-footer" style="--delay: 0.6s">
      <p class="flyout-copy">&copy; {{ year }} AUA</p>
    </div>
  </div>

  <!-- Nav Header -->
  <header class="l-nav" :class="{ 'l-nav--solid': scrolled }">
    <div class="l-nav__inner">
      <NuxtLink to="/" class="l-nav__brand">
        <span class="l-nav__brand-hex" aria-hidden="true">⬡</span>
        <span class="l-nav__brand-name">AUA</span>
      </NuxtLink>

      <div class="l-nav__actions">
        <template v-if="!auth.isLoggedIn">
          <NuxtLink to="/login" class="button l-nav__btn">Anmelden</NuxtLink>
          <NuxtLink to="/register" class="button button-primary l-nav__btn">Registrieren</NuxtLink>
        </template>
        <template v-else>
          <span class="l-nav__user">{{ firstName }}</span>
          <button class="button l-nav__btn" @click="handleLogout">Abmelden</button>
        </template>
      </div>

      <button class="l-nav__theme-btn" aria-label="Theme wechseln" @click="toggleTheme">
        <svg v-if="isDark" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M12 6a1 1 0 0 0 1-1V3a1 1 0 0 0-2 0v2a1 1 0 0 0 1 1z"/><path d="M21 11h-2a1 1 0 0 0 0 2h2a1 1 0 0 0 0-2z"/><path d="M6 12a1 1 0 0 0-1-1H3a1 1 0 0 0 0 2h2a1 1 0 0 0 1-1z"/><path d="M6.22 5a1 1 0 0 0-1.39 1.47l1.44 1.39a1 1 0 0 0 .73.28 1 1 0 0 0 .72-.31 1 1 0 0 0 0-1.41z"/><path d="M17 8.14a1 1 0 0 0 .69-.28l1.44-1.39A1 1 0 0 0 17.78 5l-1.44 1.42a1 1 0 0 0 0 1.41 1 1 0 0 0 .66.31z"/><path d="M12 18a1 1 0 0 0-1 1v2a1 1 0 0 0 2 0v-2a1 1 0 0 0-1-1z"/><path d="M17.73 16.14a1 1 0 0 0-1.39 1.44L17.78 19a1 1 0 0 0 .69.28 1 1 0 0 0 .72-.3 1 1 0 0 0 0-1.42z"/><path d="M6.27 16.14l-1.44 1.39a1 1 0 0 0 0 1.42 1 1 0 0 0 .72.3 1 1 0 0 0 .67-.25l1.44-1.39a1 1 0 0 0-1.39-1.44z"/><path d="M12 8a4 4 0 1 0 4 4 4 4 0 0 0-4-4zm0 6a2 2 0 1 1 2-2 2 2 0 0 1-2 2z"/>
        </svg>
        <svg v-else viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M12.3 22h-.1a10.31 10.31 0 0 1-7.34-3.15 10.46 10.46 0 0 1-.26-14 10.13 10.13 0 0 1 4-2.74 1 1 0 0 1 1.06.22 1 1 0 0 1 .24 1 8.4 8.4 0 0 0 1.94 8.81 8.47 8.47 0 0 0 8.83 1.94 1 1 0 0 1 1.27 1.29A10.16 10.16 0 0 1 19.6 19a10.28 10.28 0 0 1-7.3 3zM7.46 4.92a7.93 7.93 0 0 0-1.37 1.22 8.44 8.44 0 0 0 .2 11.32A8.29 8.29 0 0 0 12.22 20h.08a8.34 8.34 0 0 0 6.78-3.49A10.37 10.37 0 0 1 7.46 4.92z"/>
        </svg>
      </button>

      <button class="menu-trigger l-nav__trigger" aria-label="Menü öffnen" aria-expanded="false">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="3" y1="6" x2="21" y2="6" /><line x1="3" y1="12" x2="21" y2="12" /><line x1="3" y1="18" x2="21" y2="18" />
        </svg>
      </button>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useAuth } from '~/composables/useAuth'
import { FlyoutMenu } from '~/assets/basix/js/flyout-menu'

const auth = useAuthStore()
const { logout } = useAuth()

const scrolled = ref(false)
const isDark = ref(false)
const year = new Date().getFullYear()

const firstName = computed(() => auth.user?.name?.split(' ')[0] ?? '')

let flyoutInstance: InstanceType<typeof FlyoutMenu> | null = null

function applyTheme(dark: boolean) {
  document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light')
  localStorage.setItem('theme', dark ? 'dark' : 'light')
  isDark.value = dark
}

function toggleTheme() {
  applyTheme(!isDark.value)
}

function initTheme() {
  const saved = localStorage.getItem('theme')
  if (saved === 'dark' || saved === 'light') {
    applyTheme(saved === 'dark')
  } else {
    applyTheme(window.matchMedia('(prefers-color-scheme: dark)').matches)
  }
}

function onKeydown(e: KeyboardEvent) {
  const mod = /Mac|iPhone|iPad/i.test(navigator.userAgent) ? e.metaKey : e.ctrlKey
  if (mod && e.key.toLowerCase() === 'j') {
    e.preventDefault()
    toggleTheme()
  }
}

function onScroll() {
  scrolled.value = window.scrollY > 40
}

function closeFlyout() {
  document.getElementById('flyoutMenu')?.classList.remove('is-open')
  document.getElementById('flyoutOverlay')?.classList.remove('is-visible')
  document.body.style.overflow = ''
}

async function handleLogout() {
  closeFlyout()
  await logout()
}

onMounted(() => {
  auth.loadFromStorage()
  initTheme()
  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('keydown', onKeydown)

  flyoutInstance = new FlyoutMenu({
    triggerSelector: '.menu-trigger',
    menuSelector: '#flyoutMenu',
    overlaySelector: '#flyoutOverlay',
    closeSelector: '.close-menu',
    direction: 'right',
    enableHeader: false,
    enableFooter: false,
  })
})

onUnmounted(() => {
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('keydown', onKeydown)
  flyoutInstance?.destroy()
})
</script>

<style lang="scss" scoped>
$hero-bg:       #0F0E0C;
$amber:         #D4921E;
$nav-height:    64px;

$amber-08:      rgba(212, 146, 30, 0.08);
$amber-35:      rgba(212, 146, 30, 0.35);
$hero-bg-85:    rgba(15, 14, 12, 0.85);
$hero-bg-94:    rgba(15, 14, 12, 0.94);
$hero-text:     #EEE8DF;
$hero-text-08:  rgba(238, 232, 223, 0.08);
$hero-text-10:  rgba(238, 232, 223, 0.10);
$hero-muted:    rgba(238, 232, 223, 0.55);
$hero-muted-35: rgba(238, 232, 223, 0.35);
$hero-muted-60: rgba(238, 232, 223, 0.60);
$hero-muted-70: rgba(238, 232, 223, 0.70);
$hero-divider:  rgba(238, 232, 223, 0.10);

// ─── Flyout z-index ───────────────────────────────────────────────
:global(#flyoutOverlay) { z-index: 200; }

:global(#flyoutMenu) {
  z-index: 201;
  --glass-bg: #{$hero-bg-94};
  --glass-border: #{$hero-divider};
  --menu-width: 300px;
}

:global(#flyoutMenu .flyout-links a) {
  font-size: 1.75rem;
  font-weight: 700;
  letter-spacing: -0.03em;
  color: $hero-text;
  &:hover { color: $amber; }
}

:global(#flyoutMenu .flyout-title) {
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: $hero-muted;
}

:global(#flyoutMenu .close-menu) {
  color: $hero-muted;
  transition: color 0.2s, transform 0.2s;
  &:hover { color: $hero-text; transform: rotate(90deg); }
}

.flyout-admin-link {
  color: $amber !important;
  &:hover { color: #E8A82A !important; }
}

.flyout-logout-btn {
  display: flex;
  align-items: center;
  width: 100%;
  background: none;
  border: none;
  padding: 0;
  font-size: 1.75rem;
  font-weight: 700;
  letter-spacing: -0.03em;
  color: $hero-muted-60;
  cursor: pointer;
  font-family: inherit;
  text-align: left;
  transition: color 0.2s;
  &:hover { color: $hero-text; }
}

:global(#flyoutMenu li.flyout-divider) {
  height: 1px;
  background: $hero-divider;
  margin: 0.75rem 0;
  pointer-events: none;
  list-style: none;
}

:global(#flyoutMenu li.flyout-legal-item a) {
  font-size: 0.82rem !important;
  font-weight: 500 !important;
  letter-spacing: 0 !important;
  color: $hero-muted-70 !important;
  text-decoration: none;
  transition: color 0.2s;
  transform: none !important;
  transition-delay: 0s !important;
  &:hover { color: $hero-text !important; }
}

.flyout-copy {
  font-size: 0.75rem;
  color: $hero-muted-35;
  padding-bottom: 0;
}

// ─── Navigation Bar ───────────────────────────────────────────────
.l-nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  height: $nav-height;
  transition: background 0.3s ease, border-color 0.3s ease, backdrop-filter 0.3s ease;
  border-bottom: 1px solid transparent;

  &--solid {
    background: $hero-bg-85;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom-color: $hero-divider;
  }

  &__inner {
    max-width: 1200px;
    margin: 0 auto;
    height: 100%;
    padding: 0 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
  }

  &__brand {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    flex-shrink: 0;
  }

  &__brand-hex {
    font-size: 1.4rem;
    color: $amber;
    line-height: 1;
  }

  &__brand-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: $hero-text;
    letter-spacing: -0.02em;
  }

  &__actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: auto;
    @media (max-width: 640px) { display: none; }
  }

  &__btn {
    font-size: 0.875rem;
    padding: 0.4rem 1rem;
  }

  &__user {
    font-size: 0.9rem;
    font-weight: 500;
    color: $hero-muted;
  }

  &__theme-btn,
  &__trigger {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    padding: 0;
    background: $hero-divider;
    border: 1px solid $hero-text-08;
    border-radius: 8px;
    color: $hero-muted;
    cursor: pointer;
    flex-shrink: 0;
    transition: background 0.2s, color 0.2s;

    svg { width: 18px; height: 18px; flex-shrink: 0; }

    &:hover { background: $hero-text-10; color: $hero-text; }
  }

  &__theme-btn:hover { transform: rotate(12deg); }
}
</style>
