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
      <slot />
    </div>
  </div>
</template>

<style lang="scss">
$_a-bg: #0f0e0c;
$_a-glow: rgba(212, 146, 30, 0.18);
$_a-text: #eee8df;
$_a-muted: rgba(238, 232, 223, 0.65);

@keyframes authIn {
  from {
    opacity: 0;
    transform: translateY(16px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes authSuccessPop {
  from {
    opacity: 0;
    transform: scale(0.5);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.auth-page {
  position: relative;
  min-height: 100vh;
  background: $_a-bg;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1.25rem;

  &__backdrop {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
  }

  &__glow {
    position: absolute;
    width: 560px;
    height: 560px;
    top: -160px;
    right: -100px;
    border-radius: 50%;
    filter: blur(110px);
    background: $_a-glow;
  }

  &__dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
    background-size: 24px 24px;
    mask-image: radial-gradient(ellipse 80% 80% at 70% 30%, black 20%, transparent 100%);
  }

  &__body {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 440px;
    display: flex;
    flex-direction: column;
    gap: 2rem;
    padding: 1.5rem 0;
  }

  &__brand {
    display: flex;
    justify-content: center;
  }

  &__brand-link {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    text-decoration: none;
  }

  &__hex {
    font-size: 1.2rem;
    color: $amber;
  }

  &__name {
    font-size: 1rem;
    font-weight: 800;
    color: $_a-text;
    letter-spacing: -0.03em;
  }
}

.auth-card {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(238, 232, 223, 0.1);
  border-radius: 16px;
  padding: 2rem;
  backdrop-filter: blur(8px);
  animation: authIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) 0.1s both;

  &__eyebrow {
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: $amber;
    margin-bottom: 0.4rem;
  }

  &__title {
    font-size: 1.6rem;
    font-weight: 800;
    letter-spacing: -0.04em;
    color: $_a-text;
    margin-bottom: 1.75rem;
  }

  &__subtitle {
    font-size: 0.875rem;
    color: $_a-muted;
    margin-bottom: 1.5rem;
    line-height: 1.55;
    padding-bottom: 0;
  }

  &__text {
    font-size: 0.9rem;
    color: $_a-muted;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    padding-bottom: 0;
  }

  &__footer-text {
    font-size: 0.875rem;
    color: $_a-muted;
    text-align: center;
    margin-top: 1.25rem;
    padding-bottom: 0;

    a {
      color: $amber;
      text-decoration: none;
      font-weight: 600;
      &:hover {
        text-decoration: underline;
      }
    }
  }

  &__icon {
    font-size: 2.5rem;
    color: $amber;
    margin-bottom: 1rem;
    display: block;
    text-align: center;
    animation: authSuccessPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.3s both;

    &--muted {
      color: $_a-muted;
    }
    &--error {
      color: rgba(200, 60, 60, 0.9);
    }
  }

  &__success {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 0;
    text-align: center;
  }

  &__success-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: rgba(212, 146, 30, 0.15);
    color: $amber;
    font-size: 1.4rem;
    font-weight: 700;
    animation: authSuccessPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.3s both;
  }

  &__success p {
    font-size: 0.9rem;
    color: $_a-muted;
    line-height: 1.55;
    padding-bottom: 0;
  }

  &__success-link {
    display: inline-block;
    margin-top: 0.25rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: $amber;
    text-decoration: none;
    &:hover {
      text-decoration: underline;
    }
  }
}
</style>
