<template>
  <span class="pickup-option__body">
    <span class="pickup-option__name">{{ location.name }}</span>
    <span v-if="location.address" class="pickup-option__meta">{{ location.address }}</span>
    <span v-if="location.opening_hours" class="pickup-option__meta">{{
      location.opening_hours
    }}</span>
    <span v-if="location.notes" class="pickup-option__note">{{ location.notes }}</span>
    <a
      v-if="location.map_url"
      class="pickup-option__map"
      :href="location.map_url"
      target="_blank"
      rel="noopener noreferrer"
      >{{ $t('pickup.map_link') }}</a
    >
  </span>
</template>

<script setup lang="ts">
import type { ApiPickupLocation } from '~/types/api'

defineProps<{ location: ApiPickupLocation }>()
</script>
