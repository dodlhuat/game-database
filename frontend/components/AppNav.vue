<template>
  <header class="l-nav">
    <div class="l-nav__inner">
      <NuxtLink to="/" class="l-nav__brand">
        <span class="l-nav__brand-hex" aria-hidden="true">⬡</span>
        <span class="l-nav__brand-name">AUA</span>
      </NuxtLink>

      <!-- Opens the basix FlyoutMenu (#flyoutMenu), which slides in from the left. -->
      <button
        type="button"
        class="l-nav__trigger menu-trigger"
        :aria-label="$t('nav.menu_open')"
        aria-haspopup="true"
        aria-controls="flyoutMenu"
      >
        <svg class="icon-svg" aria-hidden="true"><use href="/svg-icons/icons.svg#menu" /></svg>
      </button>

      <nav class="l-nav__links" aria-label="Hauptnavigation">
        <NuxtLink to="/games" class="l-nav__link">{{ $t('nav.games') }}</NuxtLink>
        <NuxtLink to="/packages" class="l-nav__link">{{ $t('nav.packages') }}</NuxtLink>
        <template v-if="auth.isLoggedIn">
          <NuxtLink to="/events" class="l-nav__link">{{ $t('nav.events') }}</NuxtLink>
          <NuxtLink to="/dashboard" class="l-nav__link">{{ $t('nav.dashboard') }}</NuxtLink>
        </template>
      </nav>

      <div class="l-nav__actions">
        <template v-if="!auth.isLoggedIn">
          <NuxtLink to="/login" class="button l-nav__btn">{{ $t('nav.login') }}</NuxtLink>
          <NuxtLink to="/register" class="button button-primary l-nav__btn">{{
            $t('nav.register')
          }}</NuxtLink>
        </template>
        <template v-else>
          <NuxtLink to="/account" class="l-nav__user">
            <svg class="icon-svg" aria-hidden="true">
              <use href="/svg-icons/icons.svg#person" />
            </svg>
            <span class="l-nav__user-name">{{ firstName }}</span>
          </NuxtLink>
          <button class="button l-nav__btn" @click="handleLogout">{{ $t('nav.logout') }}</button>
        </template>
      </div>

      <button class="l-nav__theme-btn" :aria-label="$t('nav.theme_toggle')" @click="toggleTheme">
        <svg class="icon-svg" aria-hidden="true">
          <use :href="`/svg-icons/icons.svg#${isDark ? 'light_mode' : 'dark_mode'}`" />
        </svg>
      </button>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useAuth } from '~/composables/useAuth'

const auth = useAuthStore()
const { logout } = useAuth()

const isDark = ref(false)

const firstName = computed(() => auth.user?.name?.split(' ')[0] ?? '')

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

async function handleLogout() {
  await logout()
}

onMounted(() => {
  initTheme()
  window.addEventListener('keydown', onKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown)
})
</script>

<style lang="scss" scoped>
// These used to be hardcoded dark-mode-only hex/rgba values, which is why
// the header stayed visually identical in light mode. Now theme-token
// based, same as the rest of the app: color-mix against --primary-text
// gives a hover/backdrop wash that goes the right direction in either
// theme (a light wash on dark bg, a dark wash on light bg).
$nav-height: 64px;
$hero-bg-85: color-mix(in srgb, var(--background) 85%, transparent);
$hero-text: var(--primary-text);
$hero-text-08: color-mix(in srgb, var(--primary-text) 8%, transparent);
$hero-text-10: color-mix(in srgb, var(--primary-text) 10%, transparent);
$hero-muted: var(--secondary-text);
$hero-divider: var(--divider);

// Always a solid, theme-matched backdrop — never transparent. A transparent
// header floats over whatever the page underneath happens to render (some
// heroes are photo backdrops with a fixed dark veil, independent of theme —
// see pages/games/[slug].vue), so the header's own theme-matched text can't
// guarantee contrast against arbitrary page content. A solid backdrop always
// renders on top of its own background, so contrast is guaranteed.
.l-nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  height: $nav-height;
  background: $hero-bg-85;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid $hero-divider;

  &__inner {
    max-width: 1200px;
    margin: 0 auto;
    height: 100%;
    padding: 0 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
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

  // ── Desktop inline nav links ────────────────────────────────
  &__links {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin-left: 1rem;

    @media (max-width: 900px) {
      display: none;
    }
  }

  &__link {
    font-size: 0.875rem;
    font-weight: 500;
    color: $hero-muted;
    text-decoration: none;
    padding: 0.3rem 0.6rem;
    border-radius: 6px;
    white-space: nowrap;
    transition:
      color 0.2s,
      background 0.2s;

    &:hover {
      color: $hero-text;
      background: $hero-text-08;
    }

    &.router-link-active {
      color: $amber;
    }
  }

  // ── Auth actions ────────────────────────────────────────────
  &__actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-left: auto;
    @media (max-width: 640px) {
      display: none;
    }
  }

  &__btn {
    font-size: 0.875rem;
    padding: 0.4rem 1rem;
  }

  &__user {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.9rem;
    padding: 0.3rem 0.55rem;
    border-radius: 6px;
    transition: background 0.2s;

    .icon {
      font-size: 1rem;
    }

    &:hover {
      background: $hero-text-08;
    }
  }

  // ── Theme toggle ────────────────────────────────────────────
  &__theme-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 0;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: $hero-muted;
    cursor: pointer;
    flex-shrink: 0;
    transition:
      background 0.2s,
      color 0.2s,
      transform 0.2s;
    .icon {
      font-size: 1.125rem;
    }
    &:hover {
      background: $hero-text-10;
      color: $hero-text;
      transform: rotate(12deg);
    }
  }

  // Flyout-menu trigger
  &__trigger {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    padding: 0;
    flex-shrink: 0;
    background: $hero-text-08;
    border: 1px solid $hero-text-08;
    border-radius: 8px;
    color: $hero-muted;
    cursor: pointer;
    transition:
      background 0.2s,
      color 0.2s;
    &:hover {
      background: $hero-text-10;
      color: $hero-text;
    }

    .icon {
      font-size: 1.25rem;
    }
  }
}
</style>
