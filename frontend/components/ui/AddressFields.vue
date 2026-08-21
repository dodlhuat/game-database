<template>
  <div class="address-fields">
    <UiInput
      :model-value="street"
      :label="$t('account.address_street')"
      :error="streetError"
      autocomplete="address-line1"
      :placeholder="$t('account.address_street_placeholder')"
      @update:model-value="$emit('update:street', $event)"
    />
    <div class="address-fields__row">
      <UiInput
        :model-value="postalCode"
        :label="$t('account.address_postal_code')"
        :error="postalCodeError"
        placeholder="1010"
        autocomplete="postal-code"
        inputmode="numeric"
        maxlength="4"
        class="address-fields__plz"
        @update:model-value="$emit('update:postalCode', $event)"
      />
      <UiInput
        :model-value="city"
        :label="$t('account.address_city')"
        :error="cityError"
        placeholder="Wien"
        autocomplete="address-level2"
        @update:model-value="$emit('update:city', $event)"
        @blur="checkAddress"
      />
    </div>
    <Transition name="fade">
      <p
        v-if="status !== 'idle'"
        class="address-fields__status"
        :class="`address-fields__status--${status}`"
      >
        {{ statusText }}
      </p>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useAddress } from '~/composables/useAddress'

interface Props {
  street?: string
  postalCode?: string
  city?: string
  streetError?: string
  postalCodeError?: string
  cityError?: string
}

const props = defineProps<Props>()
defineEmits<{
  'update:street': [value: string]
  'update:postalCode': [value: string]
  'update:city': [value: string]
}>()

const { t } = useI18n()
const { validateAddress } = useAddress()

type Status = 'idle' | 'checking' | 'verified' | 'not-found'
const status = ref<Status>('idle')

const statusText = computed(() => {
  if (status.value === 'checking') return t('account.address_check_pending')
  if (status.value === 'verified') return t('account.address_check_verified')
  if (status.value === 'not-found') return t('account.address_check_not_found')
  return ''
})

const isPlzValid = computed(() => /^\d{4}$/.test(props.postalCode ?? ''))
const allFilled = computed(() => !!props.street?.trim() && isPlzValid.value && !!props.city?.trim())

// A failed/unreachable check must never block the form — this is a soft
// hint only, never treated as a validation error.
async function checkAddress() {
  if (!allFilled.value) {
    status.value = 'idle'
    return
  }
  status.value = 'checking'
  try {
    const { verified } = await validateAddress(props.street!, props.postalCode!, props.city!)
    status.value = verified ? 'verified' : 'not-found'
  } catch {
    status.value = 'idle'
  }
}

// Once the address is edited again after a check, the old result no longer
// applies — reset until the next blur re-checks it.
watch([() => props.street, () => props.postalCode, () => props.city], () => {
  status.value = 'idle'
})
</script>

<style scoped>
.address-fields {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.address-fields__row {
  display: flex;
  gap: 1rem;
}

.address-fields__plz {
  flex: 0 0 6.5rem;
}

.address-fields__row > :last-child {
  flex: 1;
  min-width: 0;
}

.address-fields__status {
  font-size: 0.85rem;
  margin: -0.5rem 0 0;
  padding-bottom: 0;
}

.address-fields__status--checking {
  color: var(--secondary-text);
}

.address-fields__status--verified {
  color: var(--success-text);
}

.address-fields__status--not-found {
  color: var(--warning-text);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
