<template>
  <div
    ref="container"
    class="paypal-buttons"
    :class="{ 'paypal-buttons--loading': loading, 'paypal-buttons--disabled': props.disabled }"
  >
    <div v-if="loading" class="paypal-buttons__spinner"><div class="spinner" /></div>
  </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import type { PayPalButtonsInstance } from '~/types/paypal'

const props = defineProps<{ amount: number; disabled?: boolean }>()

const emit = defineEmits<{
  success: [payload: { message: string; user: unknown }]
  error: [message: string]
}>()

const api = useApi()
const { load } = usePayPalSdk()
const container = ref<HTMLElement | null>(null)
const loading = ref(true)
let instance: PayPalButtonsInstance | null = null

async function createOrder(): Promise<string> {
  const data = await api.post<{ orderID: string }>('/tokens/checkout', { amount: props.amount })
  return data.orderID
}

async function onApprove(data: { orderID: string }): Promise<void> {
  try {
    const result = await api.post<{ message: string; user: unknown }>(
      `/tokens/capture/${data.orderID}`
    )
    emit('success', result)
  } catch (err: unknown) {
    const e = err as { message?: string }
    emit('error', e.message ?? 'Zahlung konnte nicht abgeschlossen werden.')
  }
}

onMounted(async () => {
  try {
    await load()
    if (!window.paypal || !container.value) return

    instance = window.paypal.Buttons({
      style: { layout: 'horizontal', height: 40, label: 'pay', tagline: false },
      createOrder,
      onApprove,
      onError: () => emit('error', 'Bei PayPal ist ein Fehler aufgetreten.'),
    })
    await instance.render(container.value)
  } catch (err: unknown) {
    const e = err as { message?: string }
    emit('error', e.message ?? 'PayPal konnte nicht geladen werden.')
  } finally {
    loading.value = false
  }
})

onBeforeUnmount(() => {
  instance?.close?.()
})
</script>

<style lang="scss" scoped>
.paypal-buttons {
  min-height: 40px;
  width: 100%;
}
.paypal-buttons__spinner {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 40px;
}
.paypal-buttons--disabled {
  opacity: 0.5;
  pointer-events: none;
}
</style>
