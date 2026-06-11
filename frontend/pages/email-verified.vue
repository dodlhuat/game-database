<template>
  <div class="auth-card auth-card--centered">
    <template v-if="route.query.success">
      <div class="auth-card__icon">✓</div>
      <h1 class="auth-card__title">{{ $t('auth.email_verified_title') }}</h1>
      <p class="auth-card__text">{{ $t('auth.email_verified_desc') }}</p>
      <UiButton @click="navigateTo('/login')">{{ $t('btn.login') }}</UiButton>
    </template>

    <template v-else-if="route.query.already">
      <div class="auth-card__icon auth-card__icon--muted">✓</div>
      <h1 class="auth-card__title">{{ $t('auth.email_already_verified_title') }}</h1>
      <p class="auth-card__text">{{ $t('auth.email_already_verified_desc') }}</p>
      <UiButton @click="navigateTo('/login')">{{ $t('btn.login') }}</UiButton>
    </template>

    <template v-else>
      <div class="auth-card__icon auth-card__icon--error">✕</div>
      <h1 class="auth-card__title">{{ $t('auth.email_invalid_link_title') }}</h1>
      <p class="auth-card__text">{{ $t('auth.email_invalid_link_desc') }}</p>
      <UiButton @click="navigateTo('/login')">{{ $t('btn.login') }}</UiButton>
    </template>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'auth', middleware: [] })
const route = useRoute()
</script>

<style lang="scss">
.auth-card--centered {
  text-align: center;
}
</style>
