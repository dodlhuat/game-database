<template>
  <div class="user-picker">
    <ul v-if="modelValue.length" class="chips user-picker__chips">
      <li
        v-for="user in modelValue"
        :key="user.id"
        class="chip closeable"
        :class="user.can_borrow ? 'chip-accent' : 'user-picker__chip--stale'"
        :title="user.can_borrow ? user.email : $t('admin.user_picker.no_membership')"
      >
        {{ user.name }}
        <small v-if="!user.can_borrow" class="user-picker__stale">{{
          $t('admin.user_picker.no_membership_short')
        }}</small>
        <button
          type="button"
          :aria-label="$t('admin.user_picker.remove', { name: user.name })"
          @click="remove(user.id)"
        >
          <svg class="icon-svg" aria-hidden="true"><use href="/svg-icons/icons.svg#close" /></svg>
        </button>
      </li>
    </ul>

    <div class="user-picker__search">
      <svg class="icon-svg user-picker__icon" aria-hidden="true">
        <use href="/svg-icons/icons.svg#search" />
      </svg>
      <input
        v-model="query"
        type="text"
        class="user-picker__input"
        autocomplete="off"
        :placeholder="$t('admin.user_picker.placeholder')"
        :aria-label="$t('admin.user_picker.placeholder')"
        role="combobox"
        :aria-expanded="open"
        aria-controls="user-picker-results"
        @keydown.down.prevent="highlight(1)"
        @keydown.up.prevent="highlight(-1)"
        @keydown.enter.prevent="pickHighlighted"
        @keydown.esc="query = ''"
      />
      <span v-if="searching" class="user-picker__spinner" aria-hidden="true" />
    </div>

    <ul v-if="open" id="user-picker-results" class="user-picker__results" role="listbox">
      <li v-if="!results.length" class="user-picker__empty">
        {{ $t('admin.user_picker.no_results') }}
      </li>
      <li
        v-for="(user, i) in results"
        :key="user.id"
        role="option"
        :aria-selected="i === active"
        class="user-picker__result"
        :class="{ 'is-active': i === active }"
        @mousedown.prevent="pick(user)"
        @mouseenter="active = i"
      >
        <span class="user-picker__name">{{ user.name }}</span>
        <span class="user-picker__email">{{ user.email }}</span>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { PickupLocationUser } from '~/composables/usePickupLocations'

/** Searches users by name or e-mail and keeps the picked ones as chips. */
const props = defineProps<{ modelValue: PickupLocationUser[] }>()
const emit = defineEmits<{ 'update:modelValue': [users: PickupLocationUser[]] }>()

interface SearchUser {
  id: number
  name: string
  email: string
  role: string
  is_member?: boolean
}

const api = useApi()
const query = ref('')
const found = ref<SearchUser[]>([])
const searching = ref(false)
const active = ref(0)

const picked = computed(() => new Set(props.modelValue.map((u) => u.id)))
const results = computed(() => found.value.filter((u) => !picked.value.has(u.id)).slice(0, 8))
const open = computed(() => query.value.trim().length >= 2 && !searching.value)

let timer: ReturnType<typeof setTimeout> | undefined
let requestId = 0

watch(query, (value) => {
  clearTimeout(timer)
  const term = value.trim()
  if (term.length < 2) {
    found.value = []
    return
  }
  timer = setTimeout(() => search(term), 250)
})

async function search(term: string) {
  const id = ++requestId
  searching.value = true
  try {
    const res = await api.get<{ data: SearchUser[] }>('/admin/users', { params: { search: term } })
    if (id !== requestId) return
    found.value = res.data
    active.value = 0
  } catch {
    if (id === requestId) found.value = []
  } finally {
    if (id === requestId) searching.value = false
  }
}

function pick(user: SearchUser) {
  emit('update:modelValue', [
    ...props.modelValue,
    {
      id: user.id,
      name: user.name,
      email: user.email,
      can_borrow: user.role === 'ADMIN' || !!user.is_member,
    },
  ])
  query.value = ''
}

function remove(id: number) {
  emit(
    'update:modelValue',
    props.modelValue.filter((u) => u.id !== id)
  )
}

function highlight(delta: number) {
  if (!results.value.length) return
  active.value = (active.value + delta + results.value.length) % results.value.length
}

function pickHighlighted() {
  const user = results.value[active.value]
  if (user) pick(user)
}
</script>

<style lang="scss" scoped>
.user-picker {
  position: relative;

  &__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin: 0 0 0.6rem;
    padding: 0;
    list-style: none;
  }
  &__chip--stale {
    opacity: 0.75;
    border-style: dashed;
  }
  &__stale {
    margin-left: 0.3rem;
    font-size: 0.68rem;
    color: var(--secondary-text);
  }

  &__search {
    position: relative;
    display: flex;
    align-items: center;
  }
  &__icon {
    position: absolute;
    left: 0.75rem;
    width: 1rem;
    height: 1rem;
    color: var(--secondary-text);
    pointer-events: none;
  }
  &__input {
    width: 100%;
    padding: 0.6rem 2.25rem;
    font: inherit;
    font-size: 0.875rem;
    color: var(--primary-text);
    background: var(--background);
    border: 1px solid var(--divider);
    border-radius: 10px;
    transition: border-color 0.15s;
    &:focus {
      outline: none;
      border-color: $amber;
    }
  }
  &__spinner {
    position: absolute;
    right: 0.8rem;
    width: 0.9rem;
    height: 0.9rem;
    border: 2px solid var(--divider);
    border-top-color: $amber;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
  }

  // In the flow, not a popup: the form dialog body scrolls and would clip it
  &__results {
    margin: 0.35rem 0 0;
    padding: 0.3rem;
    list-style: none;
    background: var(--secondary-background);
    border: 1px solid var(--divider);
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    animation: drop-in 0.16s ease-out;
  }
  &__result {
    display: flex;
    flex-direction: column;
    gap: 0.05rem;
    padding: 0.5rem 0.7rem;
    border-radius: 8px;
    cursor: pointer;
    &.is-active {
      background: color-mix(in srgb, #{$amber} 12%, transparent);
    }
  }
  &__name {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--primary-text);
  }
  &__email {
    font-size: 0.75rem;
    color: var(--secondary-text);
  }
  &__empty {
    padding: 0.6rem 0.7rem;
    font-size: 0.8rem;
    color: var(--secondary-text);
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes drop-in {
  from {
    opacity: 0;
    transform: translateY(-4px);
  }
}
</style>
