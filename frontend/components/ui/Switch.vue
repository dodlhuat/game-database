<template>
  <label class="ui-switch">
    <input
      type="checkbox"
      :checked="modelValue"
      :disabled="disabled"
      class="ui-switch__input"
      @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
    />
    <span class="ui-switch__track" aria-hidden="true">
      <span class="ui-switch__thumb" />
    </span>
    <span v-if="label" class="ui-switch__label">{{ label }}</span>
  </label>
</template>

<script setup lang="ts">
defineProps<{
  modelValue?: boolean
  label?: string
  disabled?: boolean
}>()

defineEmits<{
  'update:modelValue': [value: boolean]
}>()
</script>

<style scoped>
.ui-switch {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  cursor: pointer;
  user-select: none;
}

.ui-switch__input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.ui-switch__track {
  position: relative;
  display: flex;
  align-items: center;
  width: 36px;
  height: 20px;
  border-radius: 999px;
  background: var(--divider);
  border: 1px solid rgba(255, 255, 255, 0.08);
  transition:
    background 0.2s,
    border-color 0.2s;
  flex-shrink: 0;
}

.ui-switch__thumb {
  position: absolute;
  left: 2px;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: var(--secondary-text);
  transition:
    transform 0.2s,
    background 0.2s;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.ui-switch__input:checked + .ui-switch__track {
  background: var(--accent-color);
  border-color: var(--accent-color);
}

.ui-switch__input:checked + .ui-switch__track .ui-switch__thumb {
  transform: translateX(16px);
  background: #0f0e0c;
}

.ui-switch__input:disabled + .ui-switch__track {
  opacity: 0.4;
  cursor: not-allowed;
}

.ui-switch__label {
  font-size: 0.875rem;
  color: var(--primary-text);
}
</style>
