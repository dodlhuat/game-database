<template>
  <div class="ui-timespan">
    <label v-if="label" class="form-label">{{ label }}</label>
    <div ref="containerRef" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import type { TimeSpanPicker as TimeSpanPickerClass } from '@dodlhuat/basix/js/timepicker'

const props = defineProps<{
  start?: string
  end?: string
  label?: string
  fromLabel?: string
  toLabel?: string
}>()

const emit = defineEmits<{
  'update:start': [value: string]
  'update:end': [value: string]
}>()

const containerRef = ref<HTMLElement | null>(null)
let picker: InstanceType<typeof TimeSpanPickerClass> | null = null

onMounted(async () => {
  if (!containerRef.value) return

  const { TimeSpanPicker } = await import('@dodlhuat/basix/js/timepicker')

  picker = new TimeSpanPicker(containerRef.value, {
    defaultStart: props.start || undefined,
    defaultEnd: props.end || undefined,
    fromString: props.fromLabel ?? 'Von',
    toLabel: props.toLabel ?? 'Bis',
    onChange: (start, end) => {
      emit('update:start', start)
      emit('update:end', end)
    },
  })
})

onBeforeUnmount(() => {
  picker?.destroy()
  picker = null
})

// Reflect external resets (e.g. opening the edit dialog for a different
// event) into the picker without feeding back its own onChange emissions.
watch(
  () => [props.start, props.end] as const,
  ([start, end]) => {
    if (!picker) return
    const current = picker.getValue()
    if (start && end && (current.start !== start || current.end !== end)) {
      picker.setValue(start, end)
    } else if (!start && !end && (current.start || current.end)) {
      picker.reset()
    }
  }
)
</script>

<style scoped>
.form-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--secondary-text);
  margin-bottom: 0.4rem;
  letter-spacing: 0.03em;
}
</style>
