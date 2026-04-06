<template>
  <span class="badge" :class="variantClass"><slot /></span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  variant?: 'available' | 'loaned' | 'pending' | 'info' | 'default'
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
})

const variantClass = computed(() => ({
  'badge-success': props.variant === 'available',
  'badge-error':   props.variant === 'loaned',
  'badge-warning': props.variant === 'pending',
  'badge-info':    props.variant === 'info',
}))
</script>
