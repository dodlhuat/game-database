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
$hero-muted: rgba(238, 232, 223, 0.72);
$hero-text:  #EEE8DF;

// Override basix breadcrumb colors for the dark admin hero background
.breadcrumb {
  margin-bottom: 0.75rem;

  :deep(li + li::before) {
    color: rgba(238, 232, 223, 0.2);
  }

  :deep(a) {
    color: $hero-muted;

    &:hover {
      color: $hero-text;
      background: rgba(255, 255, 255, 0.06);
    }
  }

  :deep(li[aria-current="page"] > span) {
    color: $amber;
    font-weight: 600;
  }
}
</style>
