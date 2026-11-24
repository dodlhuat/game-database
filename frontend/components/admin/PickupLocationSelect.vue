<template>
  <span v-if="!editable || !current" class="loc-static">
    <svg class="icon-svg" aria-hidden="true"><use href="/svg-icons/icons.svg#location_on" /></svg>
    <span>{{ current?.name ?? '—' }}</span>
    <span v-if="lockedHint" class="loc-static__hint">{{ lockedHint }}</span>
  </span>

  <span v-else class="loc-select" :class="{ 'is-saving': saving, 'is-saved': saved }">
    <svg class="icon-svg loc-select__pin" aria-hidden="true">
      <use href="/svg-icons/icons.svg#location_on" />
    </svg>
    <select
      :value="current.id"
      :disabled="saving"
      :aria-label="$t('admin.loans.change_location')"
      @focus="loadAllowed"
      @pointerdown="loadAllowed"
      @change="onChange"
    >
      <option v-for="location in options" :key="location.id" :value="location.id">
        {{ location.name }}
      </option>
    </select>
    <svg class="icon-svg loc-select__caret" aria-hidden="true">
      <use href="/svg-icons/icons.svg#keyboard_arrow_down" />
    </svg>
    <span v-if="error" class="loc-select__error" role="alert">{{ error }}</span>
  </span>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import type { ApiPickupLocation } from '~/types/api'

/**
 * Where a running loan has to come back to — admins can move it to another
 * active location (e.g. when a member asks). Saves on change.
 */
const props = defineProps<{
  current: ApiPickupLocation | null | undefined
  locations: ApiPickupLocation[]
  editable: boolean
  endpoint: string
  lockedHint?: string
  /** Borrower: when given, only locations open to this user are offered. */
  userId?: number | null
}>()

const emit = defineEmits<{ updated: [location: ApiPickupLocation] }>()

const api = useApi()
const { t } = useI18n()
const saving = ref(false)
const saved = ref(false)
const error = ref('')
const { fetchUserLocations } = usePickupLocations()
const allowed = ref<ApiPickupLocation[] | null>(null)

// Restricted locations are only for their users — ask once the admin opens
// the menu, so a long loan list does not fire a request per row.
async function loadAllowed() {
  if (!props.userId || allowed.value) return
  try {
    allowed.value = await fetchUserLocations(props.userId)
  } catch {
    // Fall back to the full list; the backend still refuses a closed location
  }
}

// The current location is always offered, even if the list is still loading.
const options = computed(() => {
  const list = [...(allowed.value ?? props.locations)]
  if (props.current && !list.some((l) => l.id === props.current!.id)) list.unshift(props.current)
  return list
})

async function onChange(e: Event) {
  const select = e.target as HTMLSelectElement
  const id = Number(select.value)
  if (!props.current || id === props.current.id) return
  saving.value = true
  error.value = ''
  try {
    const res = await api.patch<{ data: { pickup_location: ApiPickupLocation } }>(props.endpoint, {
      pickup_location_id: id,
    })
    emit('updated', res.data.pickup_location)
    saved.value = true
    setTimeout(() => (saved.value = false), 1600)
  } catch (err) {
    select.value = String(props.current.id)
    const message = (err as { message?: unknown })?.message
    error.value = typeof message === 'string' && message ? message : t('admin.loans.location_error')
  } finally {
    saving.value = false
  }
}
</script>

<style lang="scss" scoped>
.loc-static {
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.3rem;
  font-size: 0.85rem;
  color: var(--secondary-text);
  .icon-svg {
    width: 0.95rem;
    height: 0.95rem;
  }
  &__hint {
    flex-basis: 100%;
    font-size: 0.72rem;
    opacity: 0.8;
  }
}

.loc-select {
  position: relative;
  display: inline-flex;
  flex-wrap: wrap;
  align-items: center;

  select {
    appearance: none;
    min-height: 0;
    height: 32px;
    width: auto;
    max-width: 200px;
    padding: 0 1.9rem 0 1.85rem;
    font-family: inherit;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--primary-text);
    background: var(--background);
    border: 1px solid var(--divider);
    border-radius: 8px;
    box-shadow: none;
    cursor: pointer;
    text-overflow: ellipsis;
    transition:
      border-color 0.2s ease,
      box-shadow 0.3s ease;
    &:hover,
    &:focus-visible {
      border-color: $amber;
    }
    &:disabled {
      opacity: 0.6;
      cursor: progress;
    }
  }

  &__pin,
  &__caret {
    position: absolute;
    top: 16px;
    transform: translateY(-50%);
    width: 0.95rem;
    height: 0.95rem;
    pointer-events: none;
  }
  &__pin {
    left: 0.55rem;
    color: $amber;
  }
  &__caret {
    right: 0.5rem;
    color: var(--secondary-text);
  }

  // Brief confirmation pulse after a successful move
  &.is-saved select {
    border-color: var(--success, #3fb950);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--success, #3fb950) 22%, transparent);
  }

  &__error {
    flex-basis: 100%;
    margin-top: 0.3rem;
    max-width: 220px;
    font-size: 0.72rem;
    color: var(--error-text, var(--error));
  }
}
</style>
