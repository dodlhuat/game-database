<template>
  <nav class="admin-breadcrumb" aria-label="Breadcrumb">
    <NuxtLink to="/admin" class="admin-breadcrumb__back">
      <span class="icon icon-arrow-back-outline" aria-hidden="true" />
      Admin
    </NuxtLink>
    <span class="admin-breadcrumb__sep" aria-hidden="true">›</span>
    <span class="admin-breadcrumb__label">{{ label }}</span>
  </nav>
</template>

<script setup lang="ts">
defineProps<{ label: string }>()
</script>

<style lang="scss" scoped>
$hero-muted: rgba(238, 232, 223, 0.55);
$hero-text:  #EEE8DF;
$amber:      #D4921E;

.admin-breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin-bottom: 0.75rem;
  // basix push-menu.scss resets
  position: static;
  transform: none;
  width: auto;
  height: auto;

  &__back {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.78rem;
    font-weight: 500;
    color: $hero-muted;
    text-decoration: none;
    transition: color 0.2s;

    .icon { width: 13px; height: 13px; }
    &:hover { color: $hero-text; }
  }

  &__sep {
    font-size: 0.78rem;
    color: $hero-muted;
    opacity: 0.4;
    line-height: 1;
    user-select: none;
  }

  &__label {
    font-size: 0.78rem;
    font-weight: 600;
    color: $amber;
    letter-spacing: 0.02em;
  }
}
</style>
