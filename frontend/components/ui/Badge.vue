<template>
  <span :class="['badge', `badge--${variant}`]">
    <slot />
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
