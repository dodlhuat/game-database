<template>
  <button
    :class="['button', variantClass]"
    :disabled="disabled || loading"
    :type="type"
    v-bind="$attrs"
  >
    <slot />
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost'
  size?: 'sm' | 'md' | 'lg'
  type?: 'button' | 'submit' | 'reset'
  disabled?: boolean
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  type: 'button',
  disabled: false,
  loading: false,
})

const variantClass = computed(() => {
  const map: Record<string, string> = {
    primary: 'button-primary',
    secondary: '',
    danger: 'button-error',
    ghost: '',
  }
  return map[props.variant] ?? ''
})
</script>
