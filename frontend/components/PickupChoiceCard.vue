<template>
  <template v-if="choice.kind === 'event'">
    <span class="pickup-option__date" aria-hidden="true">
      <strong>{{ dateParts.day }}</strong>{{ dateParts.month }}
    </span>
    <span class="pickup-option__body">
      <span class="pickup-option__when"
        >{{ formatEventDay(choice.event) }} · {{ formatEventTime(choice.event) }}</span
      >
      <span class="pickup-option__name">{{ choice.location.name }}</span>
      <span v-if="choice.location.address" class="pickup-option__meta">{{
        choice.location.address
      }}</span>
      <span v-if="choice.event.spots_left != null" class="pickup-option__spots">{{
        $t('pickup.spots_left', { n: choice.event.spots_left }, choice.event.spots_left)
      }}</span>
      <a
        v-if="choice.location.map_url"
        class="pickup-option__map"
        :href="choice.location.map_url"
        target="_blank"
        rel="noopener noreferrer"
        >{{ $t('pickup.map_link') }}</a
      >
    </span>
  </template>
  <template v-else>
    <span class="pickup-option__pin" aria-hidden="true">
      <svg class="icon-svg"><use href="/svg-icons/icons.svg#location_on" /></svg>
    </span>
    <PickupLocationDetails :location="choice.location" />
  </template>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  eventDateParts,
  formatEventDay,
  formatEventTime,
  type PickupChoice,
} from '~/utils/pickupLocations'

/** Card content of one borrow-dialog option — regular location or pickup event. */
const props = defineProps<{ choice: PickupChoice }>()

const dateParts = computed(() =>
  props.choice.kind === 'event' ? eventDateParts(props.choice.event) : { day: '', month: '' }
)
</script>
