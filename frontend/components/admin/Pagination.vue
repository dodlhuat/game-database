<template>
  <nav v-if="lastPage > 1" class="admin-pagination" :aria-label="$t('admin.pagination.label')">
    <button
      type="button"
      class="admin-pagination__btn"
      :disabled="page <= 1 || loading"
      :aria-label="$t('admin.pagination.prev')"
      @click="emit('change', page - 1)"
    >
      <svg class="icon-svg" aria-hidden="true">
        <use href="/svg-icons/icons.svg#chevron_left" />
      </svg>
    </button>
    <span class="admin-pagination__info">
      {{ $t('admin.pagination.page', { page, last: lastPage }) }}
      <span v-if="total !== undefined" class="admin-pagination__total">
        · {{ $t('admin.pagination.total', { n: total }, total) }}
      </span>
    </span>
    <button
      type="button"
      class="admin-pagination__btn"
      :disabled="page >= lastPage || loading"
      :aria-label="$t('admin.pagination.next')"
      @click="emit('change', page + 1)"
    >
      <svg class="icon-svg" aria-hidden="true">
        <use href="/svg-icons/icons.svg#chevron_right" />
      </svg>
    </button>
  </nav>
</template>

<script setup lang="ts">
/**
 * Page controls for the paginated admin lists. Hidden while everything fits
 * on one page, so short lists look exactly as before.
 */
withDefaults(defineProps<{ page: number; lastPage: number; total?: number; loading?: boolean }>(), {
  total: undefined,
  loading: false,
})

const emit = defineEmits<{ change: [page: number] }>()
</script>

<style lang="scss" scoped>
.admin-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 1rem;
  border-top: 1px solid var(--divider);

  &__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    padding: 0;
    border-radius: 8px;
    color: var(--primary-text);
    background: var(--background);
    border: 1px solid var(--divider);
    cursor: pointer;
    transition: border-color 0.15s;
    .icon-svg {
      width: 1.1rem;
      height: 1.1rem;
    }
    &:hover:not(:disabled) {
      border-color: $amber;
    }
    &:disabled {
      opacity: 0.35;
      cursor: default;
    }
  }
  &__info {
    font-size: 0.85rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--primary-text);
  }
  &__total {
    font-weight: 400;
    color: var(--secondary-text);
  }
}
</style>
