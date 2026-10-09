<template>
  <div class="input-wrapper">
    <label v-if="label" :for="inputId">
      {{ label }}
      <span v-if="required" aria-hidden="true">*</span>
    </label>
    <div class="input-field">
      <input
        :id="inputId"
        v-bind="$attrs"
        :type="inputType"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :class="{ 'input-error': !!error }"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      />
      <button
        v-if="type === 'password'"
        type="button"
        class="input-toggle"
        :aria-label="revealed ? $t('auth.hide_password') : $t('auth.show_password')"
        :aria-pressed="revealed"
        @click="revealed = !revealed"
      >
        <svg class="icon-svg" aria-hidden="true">
          <use :href="`/svg-icons/icons.svg#${revealed ? 'hide' : 'visibility'}`" />
        </svg>
      </button>
    </div>
    <p v-if="error" role="alert" class="error-text">{{ error }}</p>
    <p v-else-if="hint" class="hint-text">{{ hint }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

interface Props {
  modelValue?: string
  label?: string
  type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'date' | 'time'
  placeholder?: string
  error?: string
  hint?: string
  disabled?: boolean
  required?: boolean
  id?: string
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  disabled: false,
  required: false,
})

defineEmits<{
  'update:modelValue': [value: string]
}>()

defineOptions({ inheritAttrs: false })

const revealed = ref(false)
const inputType = computed(() =>
  props.type === 'password' && revealed.value ? 'text' : props.type
)

const inputId = computed(() => props.id || `input-${Math.random().toString(36).slice(2, 9)}`)
</script>

<style scoped>
.input-field {
  position: relative;
}
.input-field input {
  width: 100%;
}
.input-toggle {
  position: absolute;
  top: 50%;
  right: 0.5rem;
  transform: translateY(-50%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.35rem;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--secondary-text);
  transition: color 0.15s;
}
.input-toggle:hover,
.input-toggle:focus-visible {
  color: var(--accent-color);
}
.input-field:has(.input-toggle) input {
  padding-right: 2.75rem;
}
.input-error {
  border-color: var(--error) !important;
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
