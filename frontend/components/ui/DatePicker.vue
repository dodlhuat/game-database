<template>
  <div class="input-wrapper">
    <label v-if="label" :for="inputId">{{ label }}</label>
    <input
      :id="inputId"
      ref="inputRef"
      type="text"
      :placeholder="placeholder ?? 'Datum wählen'"
      :required="required"
      readonly
      class="datepicker-trigger"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue'

interface Props {
  modelValue?: string   // ISO date string YYYY-MM-DD
  label?: string
  placeholder?: string
  required?: boolean
  id?: string
  minDate?: Date
  maxDate?: Date
}

const props = defineProps<Props>()
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const inputRef = ref<HTMLInputElement | null>(null)
const inputId = computed(() => props.id || `dp-${Math.random().toString(36).slice(2, 9)}`)

let picker: unknown = null

onMounted(async () => {
  if (!inputRef.value) return

  const { DatePicker } = await import('@dodlhuat/basix/js/datepicker')

  picker = new DatePicker(inputRef.value, {
    mode: 'single',
    startDay: 1,
    locales: {
      days: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
      months: [
        'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
        'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember',
      ],
    },
    format: (date: Date) => {
      const d = String(date.getDate()).padStart(2, '0')
      const m = String(date.getMonth() + 1).padStart(2, '0')
      return `${d}.${m}.${date.getFullYear()}`
    },
    onSelect: (date: Date | { start: Date | null; end: Date | null }) => {
      const d = date instanceof Date ? date : date.start
      if (!d) return
      if (props.maxDate && d > props.maxDate) {
        if (inputRef.value) inputRef.value.value = ''
        emit('update:modelValue', '')
        return
      }
      if (props.minDate && d < props.minDate) {
        if (inputRef.value) inputRef.value.value = ''
        emit('update:modelValue', '')
        return
      }
      const iso = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
      emit('update:modelValue', iso)
    },
  })

  // Reflect initial value into the display input
  if (props.modelValue) setDisplay(props.modelValue)
})

onBeforeUnmount(() => {
  picker = null
})

watch(() => props.modelValue, (val) => {
  if (val) setDisplay(val)
  else if (inputRef.value) inputRef.value.value = ''
})

function setDisplay(iso: string) {
  if (!inputRef.value) return
  const [y, m, d] = iso.split('-')
  inputRef.value.value = `${d}.${m}.${y}`
}
</script>

<style scoped>
.datepicker-trigger {
  cursor: pointer;
}
</style>
