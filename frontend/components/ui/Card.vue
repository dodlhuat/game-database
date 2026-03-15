<template>
  <div :class="['card', hoverable ? 'card-hover' : '']">
    <div v-if="$slots.header" class="header">
      <slot name="header" />
    </div>
    <div class="body">
      <slot />
    </div>
    <div v-if="$slots.footer" class="footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  hoverable?: boolean
}

withDefaults(defineProps<Props>(), {
  hoverable: false,
})
</script>
