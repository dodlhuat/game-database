<template>
  <div ref="containerRef" />
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'

interface Option {
  label: string
  value: string | number | null
}

const props = defineProps<{
  modelValue?: string | number | null
  options: Option[]
  placeholder?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string | number | null]
  'change': []
}>()

// Sentinel for null values since VirtualDropdown only accepts string | number
const NULL_SENTINEL = '__null__'

const containerRef = ref<HTMLElement | null>(null)
// eslint-disable-next-line @typescript-eslint/no-explicit-any
let dropdown: any = null
// eslint-disable-next-line @typescript-eslint/no-explicit-any
let VDClass: any = null
let initId = 0

function encode(val: string | number | null): string | number {
  return val === null ? NULL_SENTINEL : val
}

function decode(val: string | number): string | number | null {
  return val === NULL_SENTINEL ? null : val
}

async function init() {
  if (!containerRef.value) return
  const id = ++initId

  if (dropdown) {
    dropdown.destroy()
    dropdown = null
  }

  if (!VDClass) {
    const mod = await import('@dodlhuat/basix/js/virtual-dropdown')
    VDClass = mod.VirtualDropdown
  }

  if (id !== initId) return

  dropdown = new VDClass({
    container: containerRef.value,
    options: props.options.map(o => ({ label: o.label, value: encode(o.value) })),
    placeholder: props.placeholder ?? 'Auswählen...',
    onSelect: (vals: Array<string | number>) => {
      const val = vals.length > 0 ? decode(vals[0]) : null
      emit('update:modelValue', val)
      emit('change')
    },
  })

  syncValue(props.modelValue)
}

function syncValue(val: string | number | null | undefined) {
  if (!dropdown) return
  if (val === undefined) {
    dropdown.clearSelection()
  } else {
    dropdown.setValue([encode(val)])
  }
}

onMounted(init)
onBeforeUnmount(() => { dropdown?.destroy(); dropdown = null })

watch(() => props.modelValue, syncValue)
watch(() => props.options, init, { deep: true })
</script>
