<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="open" class="modal-overlay" @click.self="close">
        <div class="dialog" role="dialog" aria-modal="true">
          <div class="dialog__header">
            <h3 class="dialog__title">{{ $t('account.cancel.title') }}</h3>
            <button class="dialog__close" :aria-label="$t('btn.close')" @click="close">
              <svg class="icon-svg" aria-hidden="true">
                <use href="/svg-icons/icons.svg#close" />
              </svg>
            </button>
          </div>

          <div class="dialog__body">
            <div v-if="loading" class="cancel__spinner"><div class="spinner" /></div>

            <div v-else-if="done" class="cancel__done">
              <p class="alert alert-success">{{ $t('account.cancel.done') }}</p>
              <p v-if="done.refund_cents > 0">
                {{
                  $t('account.cancel.done_refund', {
                    amount: euro(done.refund_cents),
                    tokens: done.refund_tokens,
                  })
                }}
              </p>
              <UiButton @click="finish">{{ $t('account.cancel.close') }}</UiButton>
            </div>

            <div v-else-if="preview && preview.blockers.length" class="cancel__blocked">
              <p class="alert alert-error">{{ $t('account.cancel.blocked') }}</p>
              <ul>
                <li v-for="b in preview.blockers" :key="b">
                  {{ $t(`account.cancel.blocker_${b}`) }}
                </li>
              </ul>
            </div>

            <form v-else-if="preview" @submit.prevent="submit">
              <ul class="cancel__summary">
                <li class="cancel__summary-main">
                  <template v-if="preview.refund_cents > 0">
                    {{
                      $t('account.cancel.refund_info', {
                        tokens: preview.refund_tokens,
                        amount: euro(preview.refund_cents),
                      })
                    }}
                    <span class="cancel__muted">{{
                      $t('account.cancel.fee_info', { fee: euro(preview.fee_cents) })
                    }}</span>
                  </template>
                  <template v-else>{{ $t('account.cancel.no_refund') }}</template>
                </li>
                <li v-if="preview.bonus_tokens > 0">
                  {{ $t('account.cancel.bonus_forfeited', { bonus: preview.bonus_tokens }) }}
                </li>
                <li v-if="preview.non_refundable_tokens > 0">
                  {{
                    $t('account.cancel.non_refundable', {
                      tokens: preview.non_refundable_tokens,
                      days: 30,
                    })
                  }}
                </li>
              </ul>

              <template v-if="preview.refund_cents > 0">
                <UiInput
                  v-model="form.account_holder"
                  :label="$t('account.cancel.account_holder')"
                  :error="errors.account_holder"
                  autocomplete="name"
                />
                <UiInput
                  v-model="form.iban"
                  :label="$t('account.cancel.iban')"
                  :error="errors.iban"
                  placeholder="AT00 0000 0000 0000 0000"
                  autocomplete="off"
                />
              </template>

              <div class="input-wrapper">
                <label for="cancel-reason">{{ $t('account.cancel.reason') }}</label>
                <textarea id="cancel-reason" v-model="form.reason" rows="3" maxlength="1000" />
              </div>

              <div class="cancel__confirm">
                <input
                  id="cancel-confirm"
                  v-model="form.confirm"
                  type="checkbox"
                  class="styled-checkbox"
                />
                <label for="cancel-confirm">{{ $t('account.cancel.confirm') }}</label>
              </div>
              <p v-if="errors.confirm" class="error-text" role="alert">{{ errors.confirm }}</p>
              <p v-if="formError" class="alert alert-error">{{ formError }}</p>

              <UiButton
                type="submit"
                class="cancel__submit"
                :loading="submitting"
                :disabled="!form.confirm"
              >
                {{ $t('account.cancel.submit') }}
              </UiButton>
            </form>

            <p v-else-if="loadError" class="alert alert-error">{{ loadError }}</p>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { reactive, ref, watch } from 'vue'

interface Preview {
  tokens: number
  bonus_tokens: number
  refund_tokens: number
  gross_cents: number
  fee_cents: number
  refund_cents: number
  non_refundable_tokens: number
  blockers: string[]
}

const props = defineProps<{ open: boolean }>()
const emit = defineEmits<{ close: []; cancelled: [user: unknown] }>()

const api = useApi()
const { t } = useI18n()

const loading = ref(false)
const submitting = ref(false)
const loadError = ref('')
const formError = ref('')
const preview = ref<Preview | null>(null)
const done = ref<{ refund_cents: number; refund_tokens: number; user: unknown } | null>(null)
const form = reactive({ account_holder: '', iban: '', reason: '', confirm: false })
const errors = reactive({ account_holder: '', iban: '', confirm: '' })

function euro(cents: number) {
  return new Intl.NumberFormat('de-AT', { style: 'currency', currency: 'EUR' }).format(cents / 100)
}

function reset() {
  preview.value = null
  done.value = null
  loadError.value = ''
  formError.value = ''
  Object.assign(form, { account_holder: '', iban: '', reason: '', confirm: false })
  Object.assign(errors, { account_holder: '', iban: '', confirm: '' })
}

watch(
  () => props.open,
  async (isOpen) => {
    if (!isOpen) return
    reset()
    loading.value = true
    try {
      preview.value = await api.get<Preview>('/membership/cancel/preview')
    } catch (err: unknown) {
      loadError.value = (err as { message?: string }).message ?? t('common.error.generic')
    } finally {
      loading.value = false
    }
  },
  { immediate: true }
)

function close() {
  if (!submitting.value) emit('close')
}

function finish() {
  emit('cancelled', done.value?.user)
}

async function submit() {
  Object.assign(errors, { account_holder: '', iban: '', confirm: '' })
  formError.value = ''
  submitting.value = true
  try {
    done.value = await api.post<{ refund_cents: number; refund_tokens: number; user: unknown }>(
      '/membership/cancel',
      {
        confirm: form.confirm,
        reason: form.reason || null,
        account_holder: form.account_holder || null,
        iban: form.iban || null,
      }
    )
  } catch (err: unknown) {
    const e = err as { errors?: Record<string, string[]>; message?: string }
    if (e.errors) {
      errors.account_holder = e.errors.account_holder?.[0] ?? ''
      errors.iban = e.errors.iban?.[0] ?? ''
      errors.confirm = e.errors.confirm?.[0] ?? ''
    } else {
      formError.value = e.message ?? t('common.error.generic')
    }
  } finally {
    submitting.value = false
  }
}
</script>

<style lang="scss" scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 200;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}
.dialog {
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 16px;
  padding: 1.75rem;
  width: 100%;
  max-width: 480px;
  max-height: 100%;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
  &__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1.25rem;
    flex-shrink: 0;
  }
  &__title {
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--primary-text);
  }
  &__close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: transparent;
    border: none;
    border-radius: 6px;
    color: var(--secondary-text);
    cursor: pointer;
    transition:
      background 0.15s,
      color 0.15s;
    .icon-svg {
      width: 1rem;
      height: 1rem;
    }
    &:hover {
      background: var(--background);
      color: var(--primary-text);
    }
  }
  &__body {
    overflow-y: auto;
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
}

.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
  .dialog {
    transition:
      opacity 0.2s ease,
      transform 0.2s ease;
  }
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  .dialog {
    opacity: 0;
    transform: translateY(8px) scale(0.98);
  }
}

form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.cancel {
  &__spinner {
    display: flex;
    justify-content: center;
    min-height: 6rem;
    align-items: center;
  }
  &__summary {
    list-style: none;
    margin: 0;
    padding: 1rem 1.1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    border: 1px solid var(--divider);
    border-left: 3px solid var(--accent-color);
    border-radius: 10px;
    background: var(--background);
    font-size: 0.9rem;
    color: var(--secondary-text);
  }
  &__summary-main {
    color: var(--primary-text);
    font-weight: 600;
  }
  &__muted {
    display: block;
    font-weight: 400;
    font-size: 0.8rem;
    color: var(--secondary-text);
  }
  &__confirm {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.875rem;
  }
  &__submit {
    width: 100%;
  }
  &__blocked ul {
    margin: 0.75rem 0 0 1.25rem;
    color: var(--secondary-text);
  }
  &__done {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
}
textarea {
  width: 100%;
  resize: vertical;
}
</style>
