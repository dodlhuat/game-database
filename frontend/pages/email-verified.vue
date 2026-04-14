<template>
  <div class="auth-page" data-theme="dark">
    <div class="auth-page__backdrop" aria-hidden="true">
      <div class="auth-page__glow" />
      <div class="auth-page__dots" />
    </div>

    <div class="auth-page__body">
      <div class="auth-page__brand">
        <NuxtLink to="/" class="auth-page__brand-link">
          <span class="auth-page__hex" aria-hidden="true">⬡</span>
          <span class="auth-page__name">AUA</span>
        </NuxtLink>
      </div>

      <div class="auth-card">
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
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: [] })
const route = useRoute()
</script>

<style lang="scss" scoped>
$bg: #0F0E0C;
$amber-glow: rgba(212, 146, 30, 0.18);
$text: #EEE8DF;
$muted: rgba(238, 232, 223, 0.65);

.auth-page {
  position: relative;
  min-height: 100vh;
  background: $bg;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1.25rem;

  &__backdrop { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
  &__glow { position: absolute; width: 560px; height: 560px; top: -160px; right: -100px; border-radius: 50%; filter: blur(110px); background: $amber-glow; }
  &__dots { position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,0.035) 1px, transparent 1px); background-size: 24px 24px; mask-image: radial-gradient(ellipse 80% 80% at 70% 30%, black 20%, transparent 100%); }

  &__body {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 420px;
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }

  &__brand { display: flex; justify-content: center; }
  &__brand-link { display: inline-flex; align-items: center; gap: 0.4rem; text-decoration: none; }
  &__hex { font-size: 1.2rem; color: $amber; }
  &__name { font-size: 1rem; font-weight: 800; color: $text; letter-spacing: -0.03em; }
}

.auth-card {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(238,232,223,0.1);
  border-radius: 16px;
  padding: 2rem;
  backdrop-filter: blur(8px);
  text-align: center;

  &__icon {
    font-size: 2.5rem;
    color: $amber;
    margin-bottom: 1rem;
    display: block;

    &--muted { color: $muted; }
    &--error { color: rgba(200, 60, 60, 0.9); }
  }

  &__title {
    font-size: 1.6rem;
    font-weight: 800;
    letter-spacing: -0.04em;
    color: $text;
    margin-bottom: 0.75rem;
  }

  &__text {
    font-size: 0.9rem;
    color: $muted;
    line-height: 1.6;
    margin-bottom: 1.5rem;
  }
}
</style>
