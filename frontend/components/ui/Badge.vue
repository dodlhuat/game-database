<template>
  <span class="chips">
    <span :class="['chip', `chip-${variant}`]"><slot /></span>
  </span>
</template>

<script setup lang="ts">
interface Props {
  variant?: 'available' | 'loaned' | 'pending' | 'info' | 'default'
}

withDefaults(defineProps<Props>(), {
  variant: 'default',
})
</script>

<style scoped>
.chip-available { background: var(--success); color: #fff; }
.chip-loaned    { background: var(--error);   color: #fff; }
.chip-pending   { background: var(--warning); color: #fff; }
.chip-info      { background: var(--accent-color); color: var(--accent-color-text); }
</style>
