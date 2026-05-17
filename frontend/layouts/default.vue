<template>
  <nav class="push-menu">
    <ul>
      <li><NuxtLink to="/games">{{ $t('nav.games') }}</NuxtLink></li>
      <li><NuxtLink to="/packages">{{ $t('nav.packages') }}</NuxtLink></li>
      <li v-show="auth.isLoggedIn"><NuxtLink to="/events">{{ $t('nav.events') }}</NuxtLink></li>

      <!-- Dashboard only for non-admin users at level 1 -->
      <li v-show="auth.isLoggedIn && !auth.isAdmin">
        <NuxtLink to="/dashboard">{{ $t('nav.dashboard') }}</NuxtLink>
      </li>

      <!-- Admin sub-panel — the <a> text becomes the panel title -->
      <li v-show="auth.isLoggedIn && auth.isAdmin" :class="{ 'push-nav__admin-active': isAdminRoute }">
        <NuxtLink to="/admin">{{ $t('nav.admin') }}</NuxtLink>
        <ul>
          <li><NuxtLink to="/dashboard">{{ $t('nav.dashboard') }}</NuxtLink></li>
          <li><NuxtLink to="/admin/games">{{ $t('admin.games.title') }}</NuxtLink></li>
          <li><NuxtLink to="/admin/events">{{ $t('admin.events.title') }}</NuxtLink></li>
          <li><NuxtLink to="/admin/packages">{{ $t('admin.packages_admin.title') }}</NuxtLink></li>
          <li><NuxtLink to="/admin/categories">{{ $t('admin.categories.title') }}</NuxtLink></li>
          <li><NuxtLink to="/admin/tags">{{ $t('admin.tags.title') }}</NuxtLink></li>
          <li><NuxtLink to="/admin/users">{{ $t('admin.users.title') }}</NuxtLink></li>
          <li><NuxtLink to="/admin/loans">{{ $t('admin.loans.title') }}</NuxtLink></li>
          <li><NuxtLink to="/admin/package-loans">{{ $t('admin.package_loans.title') }}</NuxtLink></li>
          <li><NuxtLink to="/admin/extensions">{{ $t('admin.extensions.title') }}</NuxtLink></li>
          <li><NuxtLink to="/admin/damage-reports">{{ $t('admin.damage_reports.title') }}</NuxtLink></li>
          <li><NuxtLink to="/admin/newsletters">{{ $t('admin.newsletters.title') }}</NuxtLink></li>
          <li><NuxtLink to="/admin/emails">{{ $t('admin.emails.title') }}</NuxtLink></li>
          <li><NuxtLink to="/admin/email-logs">{{ $t('admin.email_logs.title') }}</NuxtLink></li>
          <li><NuxtLink to="/admin/loan-settings">{{ $t('admin.loan_settings.title') }}</NuxtLink></li>
        </ul>
      </li>

      <li v-show="!auth.isLoggedIn"><NuxtLink to="/login">{{ $t('nav.login') }}</NuxtLink></li>
      <li v-show="!auth.isLoggedIn"><NuxtLink to="/register">{{ $t('nav.register') }}</NuxtLink></li>
      <li v-show="auth.isLoggedIn">
        <button class="push-nav__logout" @click="handleLogout">{{ $t('nav.logout') }}</button>
      </li>
      <li class="push-nav__divider" aria-hidden="true" />
      <li class="push-nav__legal"><NuxtLink to="/terms">{{ $t('nav.terms') }}</NuxtLink></li>
      <li class="push-nav__legal"><NuxtLink to="/privacy">{{ $t('nav.privacy') }}</NuxtLink></li>
      <li class="push-nav__legal"><NuxtLink to="/cookies">{{ $t('nav.cookies') }}</NuxtLink></li>
    </ul>
  </nav>

  <div class="push-menu-backdrop" />

  <div class="push-content">
    <AppNav />
    <slot />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, watch } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useAuth } from '~/composables/useAuth'

const auth = useAuthStore()
const { logout } = useAuth()
const route = useRoute()

const isAdminRoute = computed(() => route.path.startsWith('/admin'))

let PushMenuClass: typeof import('@dodlhuat/basix/js/push-menu').PushMenu | null = null
let checkboxEl: HTMLInputElement | null = null

function openAdminPanelIfNeeded() {
  if (!isAdminRoute.value) return
  const adminPanel = document.querySelector('.push-menu-panel[data-level="1"]') as HTMLElement | null
  if (adminPanel) PushMenuClass?.openPanel(adminPanel)
}

function onCheckboxChange(e: Event) {
  if ((e.target as HTMLInputElement).checked) {
    // Menu is opening — let PushMenu's handler run first, then navigate
    setTimeout(openAdminPanelIfNeeded, 0)
  }
}

onMounted(async () => {
  const { PushMenu } = await import('@dodlhuat/basix/js/push-menu')
  PushMenuClass = PushMenu
  PushMenu.init()

  checkboxEl = document.getElementById('push-nav-toggle') as HTMLInputElement
  checkboxEl?.addEventListener('change', onCheckboxChange)
})

onUnmounted(() => {
  checkboxEl?.removeEventListener('change', onCheckboxChange)
  PushMenuClass?.destroy()
  PushMenuClass = null
})

watch(() => route.path, () => {
  PushMenuClass?.close()
})

async function handleLogout() {
  PushMenuClass?.close()
  await logout()
}
</script>

<style>
nav.push-menu a,
nav.push-menu .push-menu-item {
  color: rgba(238, 232, 223, 0.85) !important;
  font-size: 1rem !important;
  font-weight: 600 !important;
  letter-spacing: 0.01em !important;
  text-transform: none !important;
}
nav.push-menu a:hover,
nav.push-menu .push-menu-item:hover {
  color: #f7963d !important;
  background: rgba(247, 150, 61, 0.07) !important;
}
nav.push-menu a.router-link-active {
  color: #f7963d !important;
}
nav.push-menu .push-nav__admin-active > .push-menu-item {
  color: #f7963d !important;
}
nav.push-menu ul {
  padding-left: 0;
  margin-left: 0;
}
</style>

<style scoped>
.push-nav__admin { color: #f7963d !important; }

.push-nav__logout {
  display: flex;
  align-items: center;
  width: 100%;
  background: none;
  border: none;
  padding: 0.85rem 1.5rem;
  font-size: 1rem;
  font-weight: 600;
  color: rgba(238, 232, 223, 0.55);
  cursor: pointer;
  font-family: inherit;
  text-align: left;
  transition: color 0.2s, background 0.2s;
}
.push-nav__logout:hover {
  color: rgba(238, 232, 223, 0.85);
  background: rgba(255, 255, 255, 0.06);
}

.push-nav__divider {
  height: 1px;
  background: rgba(238, 232, 223, 0.08);
  margin: 0.5rem 0;
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
