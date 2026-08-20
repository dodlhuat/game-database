<template>
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <ol>
      <li>
        <NuxtLink to="/admin">Admin</NuxtLink>
      </li>
      <li aria-current="page">
        <span>{{ label }}</span>
      </li>
    </ol>
  </nav>
</template>

<script setup lang="ts">
defineProps<{ label: string }>()
</script>

<style lang="scss" scoped>
// Used to be hardcoded cream/white, assuming the admin hero was always
// dark — that hero is theme-adaptive now (see .page-hero), so this must
// follow the theme too.
$hero-muted: var(--secondary-text);
$hero-text: var(--primary-text);

// Override basix breadcrumb colors for the admin hero
.breadcrumb {
  margin-bottom: 0.75rem;

  :deep(li + li::before) {
    color: color-mix(in srgb, var(--secondary-text) 30%, transparent);
  }

  :deep(a) {
    color: $hero-muted;

    &:hover {
      color: $hero-text;
      background: color-mix(in srgb, var(--primary-text) 6%, transparent);
    }
  }

  :deep(li[aria-current='page'] > span) {
    color: $amber;
    font-weight: 600;
  }
}
</style>
