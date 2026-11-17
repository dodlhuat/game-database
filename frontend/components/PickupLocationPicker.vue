<template>
  <fieldset class="pickup-picker">
    <legend class="pickup-picker__legend">
      {{ locations.length === 1 ? $t('pickup.legend_single') : $t('pickup.legend') }}
    </legend>
    <p class="pickup-picker__hint">{{ $t('pickup.hint') }}</p>

    <div class="pickup-picker__options">
      <template v-if="locations.length === 1">
        <div class="pickup-option pickup-option--static">
          <span class="pickup-option__card">
            <span class="pickup-option__pin" aria-hidden="true">
              <svg class="icon-svg"><use href="/svg-icons/icons.svg#location_on" /></svg>
            </span>
            <PickupLocationDetails :location="locations[0]!" />
          </span>
        </div>
      </template>

      <label v-for="location in locations" v-else :key="location.id" class="pickup-option">
        <input
          class="pickup-option__input"
          type="radio"
          :name="name"
          :value="location.id"
          :checked="modelValue === location.id"
          @change="emit('update:modelValue', location.id)"
        />
        <span class="pickup-option__card">
          <span class="pickup-option__pin" aria-hidden="true">
            <svg class="icon-svg"><use href="/svg-icons/icons.svg#location_on" /></svg>
          </span>
          <PickupLocationDetails :location="location" />
          <span class="pickup-option__check" aria-hidden="true" />
        </span>
      </label>
    </div>

    <p v-if="showError" class="pickup-picker__error" role="alert">{{ $t('pickup.required') }}</p>
  </fieldset>
</template>

<script setup lang="ts">
import type { ApiPickupLocation } from '~/types/api'

defineProps<{
  locations: ApiPickupLocation[]
  modelValue: number | null
  showError?: boolean
}>()

const emit = defineEmits<{ 'update:modelValue': [id: number] }>()

const name = `pickup-${useId()}`
</script>
