<template>
  <div id="flyoutOverlay" class="flyout-overlay" />
  <div id="flyoutMenu" class="flyout-menu">
    <ul>
      <li>
        <NuxtLink to="/games">{{ $t('nav.games') }}</NuxtLink>
      </li>
      <li>
        <NuxtLink to="/packages">{{ $t('nav.packages') }}</NuxtLink>
      </li>
      <li>
        <NuxtLink to="/about">{{ $t('nav.about') }}</NuxtLink>
      </li>
      <li v-show="auth.isLoggedIn">
        <NuxtLink to="/events">{{ $t('nav.events') }}</NuxtLink>
      </li>

      <!-- Dashboard only for non-admin users at level 1 -->
      <li v-show="auth.isLoggedIn && !auth.isAdmin">
        <NuxtLink to="/dashboard">{{ $t('nav.dashboard') }}</NuxtLink>
      </li>

      <!-- Admin submenu — FlyoutMenu turns a bare text label + nested <ul>
           into an accordion toggle; it must NOT be a link itself (basix
           hydration only picks up a loose text node as the toggle label). -->
      <li v-show="auth.isLoggedIn && auth.isAdmin" ref="adminMenuItem">
        {{ $t('nav.admin') }}
        <ul>
          <li>
            <NuxtLink to="/dashboard">{{ $t('nav.dashboard') }}</NuxtLink>
          </li>
          <li>
            <NuxtLink to="/admin/games">{{ $t('admin.games.title') }}</NuxtLink>
          </li>
          <li>
            <NuxtLink to="/admin/copy-review">{{ $t('admin.copy_review.title') }}</NuxtLink>
          </li>
          <li>
            <NuxtLink to="/admin/events">{{ $t('admin.events.title') }}</NuxtLink>
          </li>
          <li>
            <NuxtLink to="/admin/packages">{{ $t('admin.packages_admin.title') }}</NuxtLink>
          </li>
          <li>
            <NuxtLink to="/admin/tags">{{ $t('admin.tags.title') }}</NuxtLink>
          </li>
          <li>
            <NuxtLink to="/admin/mechanics">{{ $t('admin.mechanics.title') }}</NuxtLink>
          </li>
          <li>
            <NuxtLink to="/admin/users">{{ $t('admin.users.title') }}</NuxtLink>
          </li>
          <li>
            <NuxtLink to="/admin/loans">{{ $t('admin.loans.title') }}</NuxtLink>
          </li>
          <li>
            <NuxtLink to="/admin/package-loans">{{ $t('admin.package_loans.title') }}</NuxtLink>
          </li>
          <li>
            <NuxtLink to="/admin/extensions">{{ $t('admin.extensions.title') }}</NuxtLink>
          </li>
          <li>
            <NuxtLink to="/admin/damage-reports">{{ $t('admin.damage_reports.title') }}</NuxtLink>
          </li>
          <li>
            <NuxtLink to="/admin/newsletters">{{ $t('admin.newsletters.title') }}</NuxtLink>
          </li>
          <li>
            <NuxtLink to="/admin/emails">{{ $t('admin.emails.title') }}</NuxtLink>
          </li>
          <li>
            <NuxtLink to="/admin/email-logs">{{ $t('admin.email_logs.title') }}</NuxtLink>
          </li>
          <li>
            <NuxtLink to="/admin/loan-settings">{{ $t('admin.loan_settings.title') }}</NuxtLink>
          </li>
        </ul>
      </li>

      <li v-show="!auth.isLoggedIn">
        <NuxtLink to="/login">{{ $t('nav.login') }}</NuxtLink>
      </li>
      <li v-show="!auth.isLoggedIn">
        <NuxtLink to="/register">{{ $t('nav.register') }}</NuxtLink>
      </li>
      <li v-show="auth.isLoggedIn">
        <button class="push-nav__logout" @click="handleLogout">{{ $t('nav.logout') }}</button>
      </li>
      <li class="push-nav__divider" aria-hidden="true" />
      <li class="push-nav__legal">
        <NuxtLink to="/terms">{{ $t('nav.terms') }}</NuxtLink>
      </li>
      <li class="push-nav__legal">
        <NuxtLink to="/privacy">{{ $t('nav.privacy') }}</NuxtLink>
      </li>
      <li class="push-nav__legal">
        <NuxtLink to="/cookies">{{ $t('nav.cookies') }}</NuxtLink>
      </li>
    </ul>
  </div>

  <div class="app-shell">
    <AppNav />
    <slot />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import type { FlyoutMenu as FlyoutMenuType } from '@dodlhuat/basix/js/flyout-menu'
import { useAuthStore } from '~/stores/auth'
import { useAuth } from '~/composables/useAuth'

const auth = useAuthStore()
const { logout } = useAuth()
const route = useRoute()
const { t } = useI18n()

const isAdminRoute = computed(() => route.path.startsWith('/admin'))
const adminMenuItem = ref<HTMLLIElement | null>(null)

let flyout: FlyoutMenuType | null = null

// FlyoutMenu's submenu is an in-place accordion (not a separate panel like
// PushMenu), so "already on an admin route" just means pre-expanding it.
function openAdminSubmenuIfNeeded() {
  if (!isAdminRoute.value || !adminMenuItem.value) return
  adminMenuItem.value
    .querySelector<HTMLElement>(':scope > .submenu-toggle')
    ?.classList.add('active')
  adminMenuItem.value.querySelector<HTMLElement>(':scope > .submenu')?.classList.add('is-open')
}

function closeMenu() {
  flyout?.close()
}

onMounted(async () => {
  const { FlyoutMenu } = await import('@dodlhuat/basix/js/flyout-menu')
  flyout = new FlyoutMenu({
    triggerSelector: '.menu-trigger',
    direction: 'left',
    title: t('nav.menu_title'),
    enableFooter: false,
  })
  openAdminSubmenuIfNeeded()
})

onUnmounted(() => {
  flyout?.destroy()
  flyout = null
})

watch(isAdminRoute, (active) => {
  if (active) openAdminSubmenuIfNeeded()
})

watch(
  () => route.path,
  () => {
    closeMenu()
  }
)

async function handleLogout() {
  closeMenu()
  await logout()
}
</script>

<style>
/* Not .push-content: that name collides with basix's own push-menu.scss,
   still loaded globally, which applies `will-change: transform` to any
   element with that class — a leftover of the old PushMenu (see the
   FlyoutMenu migration). will-change: transform creates a new containing
   block, which silently breaks position:fixed on AppNav's header once the
   page scrolls. */
.app-shell {
  position: relative;
}

/* Un-scoped: FlyoutMenu builds its header/close-button/submenu-toggles by
   injecting raw DOM nodes at runtime, which never receive this component's
   scoped data-v attribute. --accent-color-text is repurposed project-wide
   as "text on an orange surface" (near-black, see _theme.scss), not the
   light muted-text tone basix's own component CSS expects it to be, so
   every text color here needs an explicit override — an ID selector on
   #flyoutMenu already out-specifies basix's own class-based rules without
   needing !important. */
/* basix's FlyoutMenu has no internal scroll container at all — content
   taller than the viewport (our admin submenu alone is 15 links) just
   overflows silently, and since body scroll is locked while open, nothing
   scrolls. Let the link list scroll while the header stays put; min-height:0
   is required for a flex child to actually be allowed to scroll. */
#flyoutMenu {
  overflow: hidden;
}
#flyoutMenu .flyout-links {
  min-height: 0;
  overflow-y: auto;
  overscroll-behavior: contain;
}

/* basix's default is 2rem/1.25rem — too large once the admin submenu (15
   links) is in the mix. Scale both tiers down. */
#flyoutMenu .flyout-links > li > a,
#flyoutMenu .flyout-links > li > .submenu-toggle {
  font-size: 1.35rem;
}
#flyoutMenu .submenu a,
#flyoutMenu .submenu .submenu-toggle {
  font-size: 1rem;
}
#flyoutMenu .flyout-links li {
  margin-bottom: 1.1rem;
}
#flyoutMenu .submenu li {
  margin-bottom: 0.4rem;
}

#flyoutMenu a,
#flyoutMenu .submenu-toggle {
  color: rgba(238, 232, 223, 0.85);
}
#flyoutMenu a:hover,
#flyoutMenu .submenu-toggle:hover {
  color: #f7963d;
}
#flyoutMenu a.router-link-active {
  color: #f7963d;
}
#flyoutMenu .submenu-toggle.active {
  color: #f7963d;
}
/* basix's own .flyout-menu button { font-size: inherit; font-weight: inherit }
   reset (for the close-button) is more specific than its .submenu-toggle
   { font-weight: 600 } rule, so a top-level toggle like "Admin-Bereich"
   collapses back to browser-default weight (the font-size override above
   already out-specifies it the same way). Scoped to a direct .flyout-links
   child so nested (deliberately lighter) submenu toggles are untouched. */
#flyoutMenu .flyout-links > li > .submenu-toggle {
  font-weight: 600;
}
#flyoutMenu .flyout-title {
  color: rgba(238, 232, 223, 0.55);
}
#flyoutMenu .close-menu {
  color: rgba(238, 232, 223, 0.65);
}
</style>

<style scoped>
.push-nav__logout {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  width: 100%;
  background: none;
  border: none;
  padding: 0;
  font-size: 1.35rem;
  font-weight: 600;
  color: rgba(238, 232, 223, 0.75);
  cursor: pointer;
  font-family: inherit;
  text-align: left;
  transition: color 0.2s;
}
.push-nav__logout:hover {
  color: #f7963d;
}

.push-nav__divider {
  height: 1px;
  background: rgba(238, 232, 223, 0.08);
  margin: 1.1rem 0;
  pointer-events: none;
  list-style: none;
}

.push-nav__legal :deep(a) {
  font-size: 0.8rem !important;
  font-weight: 500 !important;
  color: rgba(238, 232, 223, 0.45) !important;
  letter-spacing: 0 !important;
}
.push-nav__legal :deep(a):hover {
  color: rgba(238, 232, 223, 0.75) !important;
}
</style>
