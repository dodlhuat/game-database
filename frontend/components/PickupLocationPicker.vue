<template>
  <fieldset class="pickup-picker">
    <legend class="pickup-picker__legend">
      {{ choices.length === 1 ? $t('pickup.legend_single') : $t('pickup.legend') }}
    </legend>
    <p class="pickup-picker__hint">{{ $t('pickup.hint') }}</p>

    <div class="pickup-picker__options">
      <template v-for="(choice, i) in ordered" :key="choice.key">
        <p
          v-if="choice.kind === 'event' && i === firstEventIndex && firstEventIndex > 0"
          class="pickup-picker__group"
        >
          {{ $t('pickup.personal_group') }}
        </p>

        <div
          v-if="choices.length === 1"
          class="pickup-option pickup-option--static"
          :class="{ 'pickup-option--event': choice.kind === 'event' }"
        >
          <span class="pickup-option__card">
            <PickupChoiceCard :choice="choice" />
          </span>
        </div>

        <label
          v-else
          class="pickup-option"
          :class="{ 'pickup-option--event': choice.kind === 'event' }"
        >
          <input
            class="pickup-option__input"
            type="radio"
            :name="name"
            :value="choice.key"
            :checked="modelValue === choice.key"
            @change="emit('update:modelValue', choice.key)"
          />
          <span class="pickup-option__card">
            <PickupChoiceCard :choice="choice" />
            <span class="pickup-option__check" aria-hidden="true" />
          </span>
        </label>
      </template>
    </div>

    <p v-if="showError" class="pickup-picker__error" role="alert">{{ $t('pickup.required') }}</p>
  </fieldset>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { PickupChoice } from '~/utils/pickupLocations'

const props = defineProps<{
  choices: PickupChoice[]
  modelValue: string | null
  showError?: boolean
}>()

const emit = defineEmits<{ 'update:modelValue': [key: string] }>()

const name = `pickup-${useId()}`

// Regular locations first, then the dated pickup events under their own heading
const ordered = computed(() => [
  ...props.choices.filter((c) => c.kind === 'location'),
  ...props.choices.filter((c) => c.kind === 'event'),
])
const firstEventIndex = computed(() => ordered.value.findIndex((c) => c.kind === 'event'))
</script>
