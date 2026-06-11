<template>
  <header class="l-nav" :class="{ 'l-nav--solid': scrolled }">
    <div class="l-nav__inner">
      <NuxtLink to="/" class="l-nav__brand">
        <span class="l-nav__brand-hex" aria-hidden="true">⬡</span>
        <span class="l-nav__brand-name">AUA</span>
      </NuxtLink>

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
          <NuxtLink to="/register" class="button button-primary l-nav__btn">{{ $t('nav.register') }}</NuxtLink>
        </template>
        <template v-else>
          <span class="l-nav__user">{{ firstName }}</span>
          <button class="button l-nav__btn" @click="handleLogout">{{ $t('nav.logout') }}</button>
        </template>
      </div>

      <button class="l-nav__theme-btn" :aria-label="$t('nav.theme_toggle')" @click="toggleTheme">
        <span class="icon" :class="isDark ? 'icon-light' : 'icon-dark'" aria-hidden="true" />
      </button>

      <!-- Push-menu toggle: label IS .navigation so navigation.click() toggles the checkbox -->
      <label
        for="push-nav-toggle"
        class="l-nav__trigger navigation navigation-controls"
        :aria-label="$t('nav.menu_open')"
      >
        <input id="push-nav-toggle" type="checkbox" />
        <span class="icon icon-menu" aria-hidden="true" />
      </label>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useAuth } from '~/composables/useAuth'

const auth = useAuthStore()
const { logout } = useAuth()

const scrolled = ref(false)
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

function onScroll() {
  scrolled.value = window.scrollY > 40
}

async function handleLogout() {
  await logout()
}

onMounted(() => {
  initTheme()
  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('keydown', onKeydown)
})

onUnmounted(() => {
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('keydown', onKeydown)
})
</script>

<style lang="scss" scoped>
$hero-bg:       #0F0E0C;
$nav-height:    64px;
$hero-bg-85:    rgba(15, 14, 12, 0.85);
$hero-text:     #EEE8DF;
$hero-text-08:  rgba(238, 232, 223, 0.08);
$hero-text-10:  rgba(238, 232, 223, 0.10);
$hero-muted:    rgba(238, 232, 223, 0.72);
$hero-divider:  rgba(238, 232, 223, 0.10);

.l-nav {
  position: fixed;
  top: 0; left: 0; right: 0;
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
    max-width: 1200px; margin: 0 auto; height: 100%;
    padding: 0 1.5rem; display: flex; align-items: center; gap: 1rem;
  }

  &__brand {
    display: flex; align-items: center; gap: 0.5rem;
    text-decoration: none; flex-shrink: 0;
  }

  &__brand-hex { font-size: 1.4rem; color: $amber; line-height: 1; }

  &__brand-name {
    font-size: 1.1rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em;
  }

  // ── Desktop inline nav links ────────────────────────────────
  &__links {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin-left: 1rem;

    @media (max-width: 900px) { display: none; }
  }

  &__link {
    font-size: 0.875rem;
    font-weight: 500;
    color: $hero-muted;
    text-decoration: none;
    padding: 0.3rem 0.6rem;
    border-radius: 6px;
    white-space: nowrap;
    transition: color 0.2s, background 0.2s;

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
    display: flex; align-items: center; gap: 0.5rem; margin-left: auto;
    @media (max-width: 640px) { display: none; }
  }

  &__btn { font-size: 0.875rem; padding: 0.4rem 1rem; }

  &__user { font-size: 0.9rem; font-weight: 500; color: $hero-muted; }

  // ── Theme toggle ────────────────────────────────────────────
  &__theme-btn {
    display: flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; padding: 0;
    background: transparent; border: none;
    border-radius: 8px; color: $hero-muted; cursor: pointer; flex-shrink: 0;
    transition: background 0.2s, color 0.2s, transform 0.2s;
    .icon { font-size: 1.125rem; }
    &:hover { background: $hero-text-10; color: $hero-text; transform: rotate(12deg); }
  }

  // Push-menu trigger
  &__trigger {
    display: flex; align-items: center; justify-content: center;
    width: 38px; height: 38px; flex-shrink: 0;
    background: $hero-text-08; border: 1px solid $hero-text-08;
    border-radius: 8px; color: $hero-muted; cursor: pointer;
    transition: background 0.2s, color 0.2s;
    &:hover { background: $hero-text-10; color: $hero-text; }

    input { display: none; }
    .icon { font-size: 1.25rem; }
  }
}
</style>
