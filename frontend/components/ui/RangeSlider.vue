<template>
  <div class="ui-range-slider">
    <div class="ui-range-slider__values">
      <span class="ui-range-slider__value">{{ formatLabel(local[0]) }}</span>
      <span class="ui-range-slider__value">{{ formatLabel(local[1]) }}</span>
    </div>
    <div class="range-slider range-slider--range" :style="fillVars">
      <div class="range-slider-fill" />
      <input
        type="range"
        :min="min"
        :max="max"
        :step="step"
        :value="local[0]"
        :aria-label="startLabel"
        @input="onStartInput"
        @change="emitChange"
      />
      <input
        type="range"
        :min="min"
        :max="max"
        :step="step"
        :value="local[1]"
        :aria-label="endLabel"
        @input="onEndInput"
        @change="emitChange"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue: [number, number]
    min: number
    max: number
    step?: number
    formatLabel?: (value: number) => string
    startLabel?: string
    endLabel?: string
  }>(),
  {
    step: 1,
    formatLabel: (value: number) => String(value),
    startLabel: 'Minimum',
    endLabel: 'Maximum',
  }
)

const emit = defineEmits<{
  'update:modelValue': [value: [number, number]]
  change: [value: [number, number]]
}>()

// Tracked locally rather than read back off `props.modelValue`: the parent's
// prop update after `emit('update:modelValue', ...)` lands asynchronously,
// but a native range input fires `input` then `change` back-to-back in the
// same tick on drag release — reading the prop from `change` would see the
// value from before the last `input`.
const local = ref<[number, number]>([...props.modelValue])

watch(
  () => props.modelValue,
  (v) => {
    local.value = [...v]
  }
)

const pct = (value: number) => ((value - props.min) / (props.max - props.min)) * 100

const fillVars = computed(() => ({
  '--range-start': `${pct(local.value[0])}%`,
  '--range-end': `${pct(local.value[1])}%`,
}))

function onStartInput(e: Event) {
  const value = Math.min(Number((e.target as HTMLInputElement).value), local.value[1])
  local.value = [value, local.value[1]]
  emit('update:modelValue', local.value)
}

function onEndInput(e: Event) {
  const value = Math.max(Number((e.target as HTMLInputElement).value), local.value[0])
  local.value = [local.value[0], value]
  emit('update:modelValue', local.value)
}

function emitChange() {
  emit('change', local.value)
}
</script>

<style scoped lang="scss">
.ui-range-slider {
  width: 100%;

  &__values {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--accent-text);
    margin-bottom: 0.5rem;
  }
}
</style>
