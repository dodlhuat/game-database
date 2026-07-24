<template>
  <div class="chip-input">
    <label v-if="label" class="form-label">
      {{ label }}
      <span v-if="required" aria-hidden="true">*</span>
    </label>
    <div class="chip-input__field" :class="{ 'chip-input__field--error': !!error }" @click="focus">
      <ul v-if="modelValue.length" class="chips">
        <li v-for="(item, i) in modelValue" :key="i" class="chip chip-accent closeable">
          {{ item }}
          <button type="button" :aria-label="`${item} entfernen`" @click.stop="remove(i)">
            <svg class="icon-svg" aria-hidden="true"><use href="/svg-icons/icons.svg#close" /></svg>
          </button>
        </li>
      </ul>
      <input
        ref="inputRef"
        v-model="draft"
        type="text"
        :placeholder="modelValue.length ? '' : placeholder"
        @keydown="onKeydown"
        @blur="commit"
      />
    </div>
    <p v-if="error" role="alert" class="error-text">{{ error }}</p>
    <p v-else-if="hint" class="hint-text">{{ hint }}</p>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{
  modelValue: string[]
  label?: string
  placeholder?: string
  hint?: string
  error?: string
  required?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string[]]
}>()

const draft = ref('')
const inputRef = ref<HTMLInputElement | null>(null)

function commit() {
  const value = draft.value.trim()
  if (value) {
    emit('update:modelValue', [...props.modelValue, value])
  }
  draft.value = ''
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter' || e.key === ',') {
    e.preventDefault()
    commit()
  } else if (e.key === 'Backspace' && !draft.value && props.modelValue.length) {
    emit('update:modelValue', props.modelValue.slice(0, -1))
  }
}

function remove(i: number) {
  emit(
    'update:modelValue',
    props.modelValue.filter((_, idx) => idx !== i)
  )
}

function focus() {
  inputRef.value?.focus()
}
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

.chip-input__field {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.4rem;
  width: 100%;
  min-height: 2.75rem;
  border: 1.5px solid var(--divider);
  border-radius: 12px;
  padding: 0.4rem 0.65rem;
  background: var(--background);
  cursor: text;
  box-sizing: border-box;
  transition:
    border-color 0.16s ease,
    box-shadow 0.22s ease;

  &--error {
    border-color: var(--error);
  }

  &:focus-within {
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3.5px color-mix(in srgb, var(--accent-color) 13%, transparent);
  }

  input {
    flex: 1;
    min-width: 120px;
    border: none;
    box-shadow: none;
    padding: 0.2rem 0;
    min-height: unset;
    background: transparent;
  }
}

.chips {
  margin: 0;
  padding: 0;
  list-style: none;
  align-items: center;

  .chip {
    /* Reset typography's global `li + li { margin-top }` bleed (a rich-text
       list reset that otherwise leaks into these chips and, combined with
       flex's default align-items: stretch, makes the first chip stretch
       taller than the rest to fill the line). */
    margin: 0;
  }
}

.error-text {
  color: var(--error);
  font-size: 0.875rem;
  margin-top: 0.25rem;
  padding-bottom: 0;
}

.hint-text {
  color: var(--secondary-text);
  font-size: 0.875rem;
  margin-top: 0.25rem;
  padding-bottom: 0;
}
</style>
