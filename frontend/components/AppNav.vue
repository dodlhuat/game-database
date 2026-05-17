<template>
  <header class="l-nav" :class="{ 'l-nav--solid': scrolled }">
    <div class="l-nav__inner">
      <NuxtLink to="/" class="l-nav__brand">
        <span class="l-nav__brand-hex" aria-hidden="true">⬡</span>
        <span class="l-nav__brand-name">AUA</span>
      </NuxtLink>

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
        <svg v-if="isDark" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M12 6a1 1 0 0 0 1-1V3a1 1 0 0 0-2 0v2a1 1 0 0 0 1 1z"/><path d="M21 11h-2a1 1 0 0 0 0 2h2a1 1 0 0 0 0-2z"/><path d="M6 12a1 1 0 0 0-1-1H3a1 1 0 0 0 0 2h2a1 1 0 0 0 1-1z"/><path d="M6.22 5a1 1 0 0 0-1.39 1.47l1.44 1.39a1 1 0 0 0 .73.28 1 1 0 0 0 .72-.31 1 1 0 0 0 0-1.41z"/><path d="M17 8.14a1 1 0 0 0 .69-.28l1.44-1.39A1 1 0 0 0 17.78 5l-1.44 1.42a1 1 0 0 0 0 1.41 1 1 0 0 0 .66.31z"/><path d="M12 18a1 1 0 0 0-1 1v2a1 1 0 0 0 2 0v-2a1 1 0 0 0-1-1z"/><path d="M17.73 16.14a1 1 0 0 0-1.39 1.44L17.78 19a1 1 0 0 0 .69.28 1 1 0 0 0 .72-.3 1 1 0 0 0 0-1.42z"/><path d="M6.27 16.14l-1.44 1.39a1 1 0 0 0 0 1.42 1 1 0 0 0 .72.3 1 1 0 0 0 .67-.25l1.44-1.39a1 1 0 0 0-1.39-1.44z"/><path d="M12 8a4 4 0 1 0 4 4 4 4 0 0 0-4-4zm0 6a2 2 0 1 1 2-2 2 2 0 0 1-2 2z"/>
        </svg>
        <svg v-else viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M12.3 22h-.1a10.31 10.31 0 0 1-7.34-3.15 10.46 10.46 0 0 1-.26-14 10.13 10.13 0 0 1 4-2.74 1 1 0 0 1 1.06.22 1 1 0 0 1 .24 1 8.4 8.4 0 0 0 1.94 8.81 8.47 8.47 0 0 0 8.83 1.94 1 1 0 0 1 1.27 1.29A10.16 10.16 0 0 1 19.6 19a10.28 10.28 0 0 1-7.3 3zM7.46 4.92a7.93 7.93 0 0 0-1.37 1.22 8.44 8.44 0 0 0 .2 11.32A8.29 8.29 0 0 0 12.22 20h.08a8.34 8.34 0 0 0 6.78-3.49A10.37 10.37 0 0 1 7.46 4.92z"/>
        </svg>
      </button>

      <!-- Push-menu toggle: label IS .navigation so navigation.click() toggles the checkbox -->
      <label
        for="push-nav-toggle"
        class="l-nav__trigger navigation navigation-controls"
        :aria-label="$t('nav.menu_open')"
      >
        <input id="push-nav-toggle" type="checkbox" />
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
          <line x1="3" y1="6" x2="21" y2="6" />
          <line x1="3" y1="12" x2="21" y2="12" />
          <line x1="3" y1="18" x2="21" y2="18" />
        </svg>
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
    padding: 0 1.5rem; display: flex; align-items: center; gap: 1.5rem;
  }

  &__brand {
    display: flex; align-items: center; gap: 0.5rem;
    text-decoration: none; flex-shrink: 0;
  }

  &__brand-hex { font-size: 1.4rem; color: $amber; line-height: 1; }

  &__brand-name {
    font-size: 1.1rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em;
  }

  &__actions {
    display: flex; align-items: center; gap: 0.5rem; margin-left: auto;
    @media (max-width: 640px) { display: none; }
  }

  &__btn { font-size: 0.875rem; padding: 0.4rem 1rem; }

  &__user { font-size: 0.9rem; font-weight: 500; color: $hero-muted; }

  &__theme-btn {
    display: flex; align-items: center; justify-content: center;
    width: 38px; height: 38px; padding: 0;
    background: $hero-divider; border: 1px solid $hero-text-08;
    border-radius: 8px; color: $hero-muted; cursor: pointer; flex-shrink: 0;
    transition: background 0.2s, color 0.2s, transform 0.2s;
    svg { width: 18px; height: 18px; flex-shrink: 0; }
    &:hover { background: $hero-text-10; color: $hero-text; transform: rotate(12deg); }
  }

  // Push-menu trigger — the label itself is .navigation so .click() toggles checkbox
  &__trigger {
    display: flex; align-items: center; justify-content: center;
    width: 38px; height: 38px; flex-shrink: 0;
    background: $hero-divider; border: 1px solid $hero-text-08;
    border-radius: 8px; color: $hero-muted; cursor: pointer;
    transition: background 0.2s, color 0.2s;
    &:hover { background: $hero-text-10; color: $hero-text; }

    input { display: none; }
    svg { display: block; flex-shrink: 0; }
  }
}
</style>
