<template>
  <div class="admin-page">
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <AdminBreadcrumb :label="$t('admin.breadcrumb.users')" />
        <div class="page-hero__row">
          <h1 class="page-hero__title">{{ $t('admin.users.title') }}</h1>
          <button class="hero-btn" @click="openCreate">
            <svg class="icon-svg" aria-hidden="true"><use href="/svg-icons/icons.svg#add" /></svg>
            {{ $t('admin.actions.add_user') }}
          </button>
        </div>
      </div>
    </section>

    <div class="admin-content">
      <div class="admin-content__inner">
        <!-- The header with its filters stays while the list reloads -->
        <section class="dash-section">
          <header class="dash-section__header">
            <h2 class="dash-section__title">
              {{
                showDeleted
                  ? $t('admin.users.deleted_only')
                  : statusFilter
                    ? $t('admin.users.pending_only')
                    : $t('admin.users.all')
              }}
            </h2>
            <span class="dash-section__count">{{ total }}</span>
            <button
              v-if="statusFilter && !showDeleted"
              type="button"
              class="action-btn filter-clear"
              @click="clearStatusFilter"
            >
              {{ $t('admin.users.show_all') }}
            </button>
            <div class="deleted-toggle">
              <UiSwitch v-model="showDeleted" :label="$t('admin.users.show_deleted')" />
            </div>
          </header>

          <div v-if="pending" class="admin-state"><div class="spinner" /></div>

          <div v-else-if="!users.length" class="dash-empty">
            <svg class="icon-svg dash-empty__icon" aria-hidden="true">
              <use href="/svg-icons/icons.svg#person" />
            </svg>
            <p class="dash-empty__text">
              {{ showDeleted ? $t('admin.users.empty_deleted') : $t('admin.empty.users') }}
            </p>
          </div>

          <div
            v-else
            ref="tableWrap"
            class="table-wrap"
            :class="{ 'table-wrap--more': canScrollRight }"
            @scroll.passive="updateScrollHint"
          >
            <table class="dash-table user-table">
              <thead>
                <tr>
                  <th>{{ $t('admin.table.member') }}</th>
                  <th>{{ $t('admin.table.role') }}</th>
                  <th>{{ $t('admin.table.status') }}</th>
                  <th>{{ $t('admin.table.tokens') }}</th>
                  <th>{{ $t('admin.table.membership') }}</th>
                  <th class="user-table__actions-col">
                    <span class="sr-only">{{ $t('admin.table.actions') }}</span>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in users" :key="user.id">
                  <td class="user-table__who">
                    <span class="user-table__name">{{ user.name }}</span>
                    <span class="user-table__email">{{ user.email }}</span>
                  </td>
                  <td class="user-table__role text-muted">{{ roleLabel(user.role) }}</td>
                  <td class="user-table__status">
                    <span v-if="user.anonymized_at" class="badge">{{
                      $t('admin.users.anonymized')
                    }}</span>
                    <span v-else-if="user.deleted_at" class="badge badge-error">{{
                      $t('admin.users.deleted_on', { date: formatDate(user.deleted_at) })
                    }}</span>
                    <span v-else class="badge" :class="statusClass(user.status)">{{
                      statusLabel(user.status)
                    }}</span>
                  </td>
                  <td class="user-table__tokens">
                    <span class="user-table__label">{{ $t('admin.table.tokens') }}</span>
                    <span class="user-table__token-total">{{ spendable(user) }}</span>
                    <span v-if="tokenBreakdown(user)" class="user-table__sub">
                      {{ tokenBreakdown(user) }}
                    </span>
                  </td>
                  <td class="user-table__membership">
                    <span class="user-table__label">{{ $t('admin.table.membership') }}</span>
                    <span
                      v-if="user.membership_expires_at"
                      :class="membershipClass(user.membership_expires_at)"
                    >
                      {{ formatDate(user.membership_expires_at) }}
                    </span>
                    <span v-else class="text-muted">—</span>
                  </td>
                  <td class="user-table__actions">
                    <!-- Deleted: restoring is the only thing to do (anonymized: nothing) -->
                    <div v-if="user.deleted_at" class="action-row">
                      <button
                        v-if="!user.anonymized_at"
                        class="action-btn action-btn--success"
                        @click="restoreUser(user)"
                      >
                        {{ $t('admin.users.restore') }}
                      </button>
                    </div>
                    <div v-else class="action-row">
                      <button
                        v-if="user.status === 'PENDING'"
                        class="action-btn action-btn--success"
                        @click="approve(user.id)"
                      >
                        {{ $t('admin.users.approve') }}
                      </button>
                      <button v-else class="action-btn" @click="openEdit(user)">
                        {{ $t('admin.actions.edit') }}
                      </button>
                      <button
                        class="row-menu-btn"
                        :aria-label="$t('admin.users.more_actions', { name: user.name })"
                        aria-haspopup="menu"
                        @click.stop="openRowMenu($event, user)"
                      >
                        <svg class="icon-svg" aria-hidden="true">
                          <use href="/svg-icons/icons.svg#more_horiz" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <AdminPagination
            :page="page"
            :last-page="lastPage"
            :total="total"
            :loading="pending"
            @change="loadUsers"
          />
        </section>
      </div>
    </div>

    <!-- ── Create Modal ──────────────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="showCreate" class="modal-overlay" @click.self="showCreate = false">
        <div class="modal-box">
          <header class="modal-box__header">
            <h2 class="modal-box__title">{{ $t('admin.users.create_title') }}</h2>
            <button
              class="modal-box__close"
              :aria-label="$t('admin.form.close')"
              @click="showCreate = false"
            >
              <svg class="icon-svg"><use href="/svg-icons/icons.svg#close" /></svg>
            </button>
          </header>
          <form class="modal-box__body" @submit.prevent="submitCreate">
            <div class="form-field">
              <label class="form-label">{{ $t('admin.form.name') }}</label>
              <input v-model="createForm.name" class="form-input" type="text" required />
            </div>
            <div class="form-field">
              <label class="form-label">{{ $t('admin.table.email') }}</label>
              <input v-model="createForm.email" class="form-input" type="email" required />
            </div>
            <div class="form-field">
              <label class="form-label">{{ $t('auth.password') }}</label>
              <input
                v-model="createForm.password"
                class="form-input"
                type="password"
                required
                minlength="8"
              />
            </div>
            <div class="form-field">
              <label class="form-label">{{ $t('admin.table.role') }}</label>
              <UiVirtualDropdown
                v-model="createForm.role"
                class="form-select"
                :options="[
                  { label: $t('admin.users.role_user'), value: 'USER' },
                  { label: $t('admin.users.role_supporter'), value: 'SUPPORTER' },
                  { label: $t('admin.users.role_member'), value: 'MEMBER' },
                  { label: $t('admin.users.role_admin'), value: 'ADMIN' },
                ]"
              />
            </div>
            <div class="form-field">
              <label class="form-label">{{ $t('admin.table.status') }}</label>
              <UiVirtualDropdown
                v-model="createForm.status"
                class="form-select"
                :options="[
                  { label: 'PENDING', value: 'PENDING' },
                  { label: 'ACTIVE', value: 'ACTIVE' },
                ]"
              />
            </div>
            <p v-if="createError" class="form-error">{{ createError }}</p>
            <div class="modal-box__footer">
              <button type="button" class="action-btn" @click="showCreate = false">
                {{ $t('admin.form.cancel') }}
              </button>
              <button
                type="submit"
                class="action-btn action-btn--success"
                :disabled="createLoading"
              >
                {{ createLoading ? $t('admin.users.creating') : $t('admin.users.create_btn') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ── Edit Modal ────────────────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="showEdit" class="modal-overlay" @click.self="showEdit = false">
        <div class="modal-box">
          <header class="modal-box__header">
            <h2 class="modal-box__title">{{ $t('admin.users.edit_title') }}</h2>
            <button
              class="modal-box__close"
              :aria-label="$t('admin.form.close')"
              @click="showEdit = false"
            >
              <svg class="icon-svg"><use href="/svg-icons/icons.svg#close" /></svg>
            </button>
          </header>
          <form class="modal-box__body" @submit.prevent="submitEdit">
            <p v-if="editRegisteredAt" class="edit-meta">
              {{ $t('admin.users.registered_at', { date: formatDate(editRegisteredAt) }) }}
            </p>
            <div class="form-field">
              <label class="form-label">{{ $t('admin.form.name') }}</label>
              <input v-model="editForm.name" class="form-input" type="text" required />
            </div>
            <div class="form-field">
              <label class="form-label">{{ $t('admin.table.email') }}</label>
              <input v-model="editForm.email" class="form-input" type="email" required />
            </div>
            <div class="form-field">
              <label class="form-label">{{ $t('admin.table.role') }}</label>
              <UiVirtualDropdown
                v-model="editForm.role"
                class="form-select"
                :options="[
                  { label: $t('admin.users.role_user'), value: 'USER' },
                  { label: $t('admin.users.role_supporter'), value: 'SUPPORTER' },
                  { label: $t('admin.users.role_member'), value: 'MEMBER' },
                  { label: $t('admin.users.role_admin'), value: 'ADMIN' },
                ]"
              />
            </div>
            <div class="form-field">
              <label class="form-label"
                >{{ $t('auth.password') }}
                <span class="text-muted">{{ $t('admin.users.password_edit_hint') }}</span></label
              >
              <input v-model="editForm.password" class="form-input" type="password" minlength="8" />
            </div>
            <p v-if="editError" class="form-error">{{ editError }}</p>
            <div class="modal-box__footer">
              <button type="button" class="action-btn" @click="showEdit = false">
                {{ $t('admin.form.cancel') }}
              </button>
              <button type="submit" class="action-btn action-btn--success" :disabled="editLoading">
                {{ editLoading ? $t('admin.users.saving') : $t('admin.form.save') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ── Token-Transaktionen Panel ──────────────────────────── -->
    <Teleport to="body">
      <div v-if="txPanel.open" class="modal-overlay" @click.self="txPanel.open = false">
        <div class="modal-box modal-box--wide">
          <div class="modal-box__header">
            <h3>{{ $t('admin.users.token_history_title', { name: txPanel.userName }) }}</h3>
            <button
              class="modal-box__close"
              :aria-label="$t('admin.form.close')"
              @click="txPanel.open = false"
            >
              <svg class="icon-svg" aria-hidden="true">
                <use href="/svg-icons/icons.svg#close" />
              </svg>
            </button>
          </div>
          <div v-if="txPanel.loading" class="modal-box__body"><div class="spinner" /></div>
          <div v-else-if="!txPanel.transactions.length" class="modal-box__body">
            <p class="text-muted">{{ $t('pages.tokens.history_empty') }}</p>
          </div>
          <div v-else class="modal-box__body">
            <table class="dash-table">
              <tbody>
                <tr v-for="tx in txPanel.transactions" :key="tx.id">
                  <td class="text-muted" style="white-space: nowrap">
                    {{ formatTxDate(tx.created_at) }}
                  </td>
                  <td>{{ tx.description ?? tx.type }}</td>
                  <td
                    style="text-align: right; font-weight: 700"
                    :style="
                      tx.amount >= 0 ? 'color:var(--success-text)' : 'color:var(--error-text)'
                    "
                  >
                    {{ tx.amount >= 0 ? '+' : '' }}{{ tx.amount }}
                  </td>
                </tr>
              </tbody>
            </table>
            <div v-if="txPanel.lastPage > 1" class="pagination-row">
              <button
                class="action-btn"
                :disabled="txPanel.page === 1"
                @click="loadTxPanel(txPanel.userId!, txPanel.page - 1)"
              >
                ←
              </button>
              <span>{{ txPanel.page }} / {{ txPanel.lastPage }}</span>
              <button
                class="action-btn"
                :disabled="txPanel.page === txPanel.lastPage"
                @click="loadTxPanel(txPanel.userId!, txPanel.page + 1)"
              >
                →
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <footer class="l-footer">
      <div class="l-footer__inner">
        <div class="l-footer__brand">
          <span class="l-footer__hex" aria-hidden="true">⬡</span
          ><span class="l-footer__name">AUA</span>
        </div>
        <p class="l-footer__copy">{{ $t('common.copyright_short', { year }) }}</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'
import type { PageMeta } from '~/composables/useAdmin'
import { useRowMenu, type RowMenuItem } from '~/composables/useRowMenu'

definePageMeta({ middleware: ['auth', 'admin'] })

interface User {
  id: number
  name: string
  email: string
  role: string
  status: string
  created_at: string
  tokens?: number
  bonus_tokens?: number
  tokens_blocked?: number
  membership_expires_at?: string | null
  deleted_at?: string | null
  anonymized_at?: string | null
}
interface TxItem {
  id: number
  type: string
  amount: number
  description: string | null
  created_at: string
}

const api = useApi()
const { t } = useI18n()
const { confirmDialog, notify } = useDialogs()
const year = new Date().getFullYear()
const users = ref<User[]>([])
const pending = ref(true)

// ── Create ──────────────────────────────────────────────────────
const showCreate = ref(false)
const createLoading = ref(false)
const createError = ref('')
const createForm = ref({ name: '', email: '', password: '', role: 'USER', status: 'PENDING' })

function openCreate() {
  createForm.value = { name: '', email: '', password: '', role: 'USER', status: 'PENDING' }
  createError.value = ''
  showCreate.value = true
}

async function submitCreate() {
  createLoading.value = true
  createError.value = ''
  try {
    const res = await api.post<{ data: User }>('/admin/users', createForm.value)
    users.value.unshift(res.data)
    total.value++
    showCreate.value = false
  } catch (e: unknown) {
    // useApi throws the response body itself: the message sits on top
    const err = e as { message?: string }
    createError.value = err?.message ?? t('admin.users.create_error')
  } finally {
    createLoading.value = false
  }
}

// ── Edit ────────────────────────────────────────────────────────
const showEdit = ref(false)
const editLoading = ref(false)
const editError = ref('')
const editUserId = ref<number | null>(null)
const editForm = ref({ name: '', email: '', role: 'USER', password: '' })

const editRegisteredAt = ref<string | null>(null)

function openEdit(user: User) {
  editUserId.value = user.id
  editRegisteredAt.value = user.created_at
  editForm.value = { name: user.name, email: user.email, role: user.role, password: '' }
  editError.value = ''
  showEdit.value = true
}

async function submitEdit() {
  if (!editUserId.value) return
  editLoading.value = true
  editError.value = ''
  const payload: Record<string, string> = {
    name: editForm.value.name,
    email: editForm.value.email,
    role: editForm.value.role,
  }
  if (editForm.value.password) payload.password = editForm.value.password
  try {
    const res = await api.put<{ data: User }>(`/admin/users/${editUserId.value}`, payload)
    const idx = users.value.findIndex((u) => u.id === editUserId.value)
    if (idx !== -1) users.value[idx] = { ...users.value[idx], ...res.data }
    showEdit.value = false
  } catch (e: unknown) {
    // useApi throws the response body itself: the message sits on top
    const err = e as { message?: string }
    editError.value = err?.message ?? t('admin.users.edit_error')
  } finally {
    editLoading.value = false
  }
}

// ── Token-Transaktionen ──────────────────────────────────────────
const txPanel = ref<{
  open: boolean
  loading: boolean
  userId: number | null
  userName: string
  transactions: TxItem[]
  page: number
  lastPage: number
}>({
  open: false,
  loading: false,
  userId: null,
  userName: '',
  transactions: [],
  page: 1,
  lastPage: 1,
})

async function openTxPanel(user: User) {
  txPanel.value = {
    open: true,
    loading: true,
    userId: user.id,
    userName: user.name,
    transactions: [],
    page: 1,
    lastPage: 1,
  }
  await loadTxPanel(user.id, 1)
}

async function loadTxPanel(userId: number, page: number) {
  txPanel.value.loading = true
  txPanel.value.page = page
  try {
    const data = await api.get<{ data: TxItem[]; meta: { last_page: number } }>(
      `/admin/users/${userId}/token-transactions?page=${page}`
    )
    txPanel.value.transactions = data.data
    txPanel.value.lastPage = data.meta.last_page
  } catch (err: unknown) {
    notify((err as { message?: string }).message ?? t('admin.errors.load'), 'error')
  } finally {
    txPanel.value.loading = false
  }
}

function formatTxDate(iso: string) {
  return new Date(iso).toLocaleDateString('de-AT', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

// ── Horizontal scroll hint ──────────────────────────────────────
// The actions column is pinned to the right edge; while more columns are
// hidden behind it, it casts a shadow so the table visibly continues.
const tableWrap = ref<HTMLElement | null>(null)
const canScrollRight = ref(false)
function updateScrollHint() {
  const el = tableWrap.value
  canScrollRight.value = !!el && el.scrollLeft + el.clientWidth < el.scrollWidth - 1
}
let resizeObserver: ResizeObserver | null = null

// ── Status actions ──────────────────────────────────────────────
// The API pages the list (20 per page)
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)

// The admin start links here with ?status=PENDING to show who waits for approval
const route = useRoute()
const statusFilter = ref(String(route.query.status ?? '') === 'PENDING' ? 'PENDING' : '')

function clearStatusFilter() {
  statusFilter.value = ''
  navigateTo({ query: {} }, { replace: true })
  loadUsers(1)
}

// Deleted users are a separate view; switching starts on page 1
const showDeleted = ref(false)
watch(showDeleted, () => loadUsers(1))

async function loadUsers(target = page.value) {
  pending.value = true
  try {
    const data = await api.get<{ data: User[]; meta?: PageMeta }>('/admin/users', {
      params: {
        page: target,
        ...(showDeleted.value
          ? { deleted: 1 }
          : statusFilter.value
            ? { status: statusFilter.value }
            : {}),
      },
    })
    users.value = data.data
    page.value = data.meta?.current_page ?? 1
    lastPage.value = data.meta?.last_page ?? 1
    total.value = data.meta?.total ?? data.data.length
  } catch (err: unknown) {
    notify((err as { message?: string }).message ?? t('admin.errors.load'), 'error')
  } finally {
    pending.value = false
  }
}

onMounted(async () => {
  await loadUsers()
  await nextTick()
  updateScrollHint()
  if (tableWrap.value && typeof ResizeObserver !== 'undefined') {
    resizeObserver = new ResizeObserver(updateScrollHint)
    resizeObserver.observe(tableWrap.value)
  }
})
onBeforeUnmount(() => resizeObserver?.disconnect())

// ── Row menu ────────────────────────────────────────────────────
// The visible button is the row's main action (approve while pending,
// otherwise edit); everything else lives in the ⋯ menu.
const rowMenu = useRowMenu()
function openRowMenu(event: MouseEvent, user: User) {
  const items: RowMenuItem[] = []
  if (user.status === 'PENDING') {
    items.push({ label: t('admin.actions.edit'), icon: 'edit', action: () => openEdit(user) })
  }
  items.push({
    label: t('admin.actions.token_history'),
    icon: 'receipt_long',
    action: () => openTxPanel(user),
  })
  if (user.status === 'PENDING') {
    items.push({
      label: t('admin.users.reject'),
      icon: 'cancel',
      destructive: true,
      action: () => reject(user.id),
    })
  } else if (user.status === 'ACTIVE') {
    items.push({
      label: t('admin.users.suspend'),
      icon: 'block',
      destructive: true,
      action: () => suspend(user.id),
    })
  }
  // A rejected registration can still be approved later (sends the approval mail)
  if (user.status === 'REJECTED' && !user.anonymized_at) {
    items.push({
      label: t('admin.users.approve'),
      icon: 'lock_open',
      action: () => approve(user.id),
    })
  }
  // Anonymized accounts have no data left — nothing to unlock or delete
  if (user.status === 'SUSPENDED' && !user.anonymized_at) {
    items.push({
      label: t('admin.users.unsuspend'),
      icon: 'lock_open',
      action: () => unsuspend(user),
    })
  }
  if ((user.status === 'SUSPENDED' || user.status === 'REJECTED') && !user.anonymized_at) {
    items.push({
      label: t('admin.users.delete'),
      icon: 'delete',
      destructive: true,
      action: () => removeUser(user),
    })
  }
  rowMenu.open(event.currentTarget as HTMLElement, items)
}

// ── Tokens ──────────────────────────────────────────────────────
// What the member can actually spend — the same number as "Verfügbar" on
// their own dashboard — with bonus/blocked broken out underneath.
function spendable(user: User) {
  return Math.max(0, (user.tokens ?? 0) + (user.bonus_tokens ?? 0) - (user.tokens_blocked ?? 0))
}
function tokenBreakdown(user: User) {
  const parts: string[] = []
  if (user.bonus_tokens) parts.push(t('admin.users.tokens_bonus', { n: user.bonus_tokens }))
  if (user.tokens_blocked) parts.push(t('admin.users.tokens_blocked', { n: user.tokens_blocked }))
  return parts.join(' · ')
}

async function approve(id: number) {
  await api.patch(`/admin/users/${id}/approve`)
  const u = users.value.find((u) => u.id === id)
  if (u) u.status = 'ACTIVE'
}
async function reject(id: number) {
  await api.patch(`/admin/users/${id}/reject`)
  const u = users.value.find((u) => u.id === id)
  if (u) u.status = 'REJECTED'
}
async function suspend(id: number) {
  await api.patch(`/admin/users/${id}/suspend`)
  const u = users.value.find((u) => u.id === id)
  if (u) u.status = 'SUSPENDED'
}

function apiMessage(err: unknown, fallback: string) {
  const message = (err as { message?: unknown })?.message
  return typeof message === 'string' && message ? message : fallback
}

async function unsuspend(user: User) {
  try {
    await api.patch(`/admin/users/${user.id}/unsuspend`)
    user.status = 'ACTIVE'
    notify(t('admin.users.unsuspended', { name: user.name }))
  } catch (err: unknown) {
    notify(apiMessage(err, t('common.error.generic')), 'error')
  }
}

async function removeUser(user: User) {
  const ok = await confirmDialog({
    title: t('admin.users.delete_title'),
    message: t('admin.users.delete_confirm', { name: user.name }),
    confirmLabel: t('admin.users.delete'),
    danger: true,
  })
  if (!ok) return
  try {
    await api.delete(`/admin/users/${user.id}`)
    users.value = users.value.filter((u) => u.id !== user.id)
    total.value = Math.max(0, total.value - 1)
    notify(t('admin.users.deleted', { name: user.name }))
  } catch (err: unknown) {
    // e.g. games still out
    notify(apiMessage(err, t('common.error.generic')), 'error')
  }
}

async function restoreUser(user: User) {
  try {
    await api.patch(`/admin/users/${user.id}/restore`)
    users.value = users.value.filter((u) => u.id !== user.id)
    total.value = Math.max(0, total.value - 1)
    notify(t('admin.users.restored', { name: user.name }))
  } catch (err: unknown) {
    notify(apiMessage(err, t('common.error.generic')), 'error')
  }
}

function statusLabel(s: string) {
  const m: Record<string, string> = {
    ACTIVE: t('admin.users.status_active'),
    PENDING: t('admin.users.status_pending'),
    REJECTED: t('admin.users.status_rejected'),
    SUSPENDED: t('admin.users.status_suspended'),
  }
  return m[s] ?? s
}
function statusClass(s: string) {
  const m: Record<string, string> = {
    ACTIVE: 'badge-success',
    PENDING: 'badge-warning',
    REJECTED: '',
    SUSPENDED: 'badge-error',
  }
  return m[s] ?? ''
}
function roleLabel(r: string) {
  const m: Record<string, string> = {
    USER: t('admin.users.role_label_user'),
    SUPPORTER: t('admin.users.role_label_supporter'),
    MEMBER: t('admin.users.role_label_member'),
    ADMIN: 'Admin',
  }
  return m[r] ?? r
}
function formatDate(iso: string | null | undefined) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('de-DE')
}
function membershipClass(iso: string) {
  const days = (new Date(iso).getTime() - Date.now()) / (1000 * 60 * 60 * 24)
  if (days < 30) return 'text-danger'
  if (days < 90) return 'text-warn'
  return 'text-active'
}
</script>

<style lang="scss" scoped>
$hero-bg: var(--background);
$nav-height: 64px;
$amber-08: rgba(212, 146, 30, 0.08);
$amber-14: rgba(212, 146, 30, 0.14);
$amber-25: rgba(212, 146, 30, 0.25);
$amber-glow: rgba(212, 146, 30, 0.16);
$hero-text: var(--primary-text);
$hero-muted: var(--secondary-text);
$hero-muted-50: color-mix(in srgb, var(--secondary-text) 80%, transparent);
$hero-divider: var(--divider);

.admin-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--background);
}

.page-hero {
  position: relative;
  background: $hero-bg;
  padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem;
  overflow: hidden;
  &__backdrop {
    position: absolute;
    inset: 0;
    pointer-events: none;
  }
  &__glow {
    position: absolute;
    width: 400px;
    height: 400px;
    top: -120px;
    right: -60px;
    border-radius: 50%;
    filter: blur(90px);
    background: $amber-glow;
  }
  &__dots {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
    background-size: 24px 24px;
    mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%);
  }
  &__body {
    position: relative;
    z-index: 1;
    max-width: 1100px;
    margin: 0 auto;
  }
  &__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
  }
  &__title {
    font-size: clamp(1.5rem, 3vw, 2.25rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    color: $hero-text;
    margin: 0;
  }
}

.hero-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1rem;
  font-size: 0.85rem;
  font-weight: 600;
  font-family: inherit;
  color: #0f0e0c;
  background: $amber;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
  white-space: nowrap;
  .icon {
    font-size: 1rem;
  }
  &:hover {
    background: color.adjust($amber, $lightness: -8%);
  }
}

.admin-content {
  flex: 1;
  padding: 2rem 1.5rem 4rem;
  &__inner {
    max-width: 1100px;
    margin: 0 auto;
  }
}
.admin-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 200px;
}

.dash-section {
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 14px;
  overflow: hidden;
}
.dash-section__header {
  display: flex;
  flex-wrap: wrap; // the deleted toggle moves below the title on narrow screens
  align-items: center;
  gap: 0.75rem;
  padding: 1.1rem 1.5rem;
  border-bottom: 1px solid var(--divider);
}
// "Show all" sits at the far end of the header
.filter-clear {
  margin-left: auto;
}
.dash-section__title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--primary-text);
  margin: 0;
  letter-spacing: -0.02em;
}
.dash-section__count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 22px;
  height: 22px;
  padding: 0 6px;
  font-size: 0.75rem;
  font-weight: 700;
  color: $amber;
  background: $amber-08;
  border: 1px solid $amber-25;
  border-radius: 999px;
}

.dash-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 3rem 1.5rem;
  color: var(--secondary-text);
  &__icon {
    width: 2rem;
    height: 2rem;
    opacity: 0.35;
  }
  &__text {
    font-size: 0.9rem;
    padding-bottom: 0;
  }
}

.table-wrap {
  overflow-x: auto;
}
.dash-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
  th {
    padding: 0.7rem 1.5rem;
    text-align: left;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--secondary-text);
    background: var(--background);
    border-bottom: 1px solid var(--divider);
    white-space: nowrap;
  }
  td {
    padding: 0.9rem 1.5rem;
    color: var(--primary-text);
    border-bottom: 1px solid var(--divider);
    vertical-align: middle;
  }
  tbody tr:last-child td {
    border-bottom: none;
  }
  tbody tr {
    transition: background 0.15s;
    &:hover {
      background: var(--background);
    }
  }
  &__primary {
    font-weight: 600;
  }
}

// ── User table ─────────────────────────────────────────────────────
.user-table {
  // Stays a real table cell (display: flex here would break the row's
  // borders/height); name and email stack as blocks instead.
  &__who {
    min-width: 12rem;
  }
  &__name,
  &__email {
    display: block;
  }
  &__name {
    font-weight: 600;
    margin-bottom: 0.15rem;
  }
  &__email,
  &__sub {
    font-size: 0.78rem;
    color: var(--secondary-text);
  }
  &__sub {
    display: block;
    white-space: nowrap;
  }
  &__token-total {
    font-weight: 700;
    font-variant-numeric: tabular-nums;
  }
  &__role,
  &__membership {
    white-space: nowrap;
  }
  // Only shown in the phone card layout, where there are no column headers.
  &__label {
    display: none;
  }

  // Pinned to the right edge so the row actions are always reachable, no
  // matter how far the table is scrolled sideways (they used to sit at the
  // far end of a horizontal scroll nobody noticed). Needs an opaque
  // background so scrolled cells pass underneath it.
  &__actions-col,
  &__actions {
    position: sticky;
    right: 0;
    z-index: 1;
    width: 1%;
    transition: box-shadow 0.2s ease;
  }
  &__actions-col {
    background: var(--background);
  }
  // The pinned cell must always match its row, including the hover tint
  // (and its transition), so it inherits an explicit row background.
  tbody tr {
    background: var(--secondary-background);
  }
  &__actions {
    background: inherit;
  }
  .action-row {
    flex-wrap: nowrap;
    justify-content: flex-end;
  }
}
// While columns are hidden behind the pinned actions, cast a shadow so the
// table visibly continues.
.table-wrap--more .user-table__actions-col,
.table-wrap--more .user-table__actions {
  box-shadow: -14px 0 14px -12px rgba(0, 0, 0, 0.35);
}

.row-menu-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  padding: 0;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 7px;
  color: var(--secondary-text);
  cursor: pointer;
  transition:
    background 0.15s,
    border-color 0.15s,
    color 0.15s;
  .icon-svg {
    width: 1.15rem;
    height: 1.15rem;
  }
  &:hover,
  &:focus-visible {
    background: var(--background);
    border-color: var(--divider);
    color: var(--primary-text);
  }
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.edit-meta {
  font-size: 0.8rem;
  color: var(--secondary-text);
  margin: 0 0 1rem;
  padding-bottom: 0;
}

// ── Phone: one card per user instead of a table row ────────────────
// basix already turns every table into stacked label/value cards below
// $bp-md (td as space-between flex, right-aligned, ::before labels). Keep
// its card frame for consistency with the other admin tables, but lay the
// cells out as a small grid instead of one label/value line each.
@media (max-width: $bp-md) {
  .table-wrap {
    overflow: visible;
  }
  .user-table {
    tbody tr {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      grid-template-areas:
        'who status'
        'tokens membership'
        'role actions';
      gap: 0.85rem 1rem;
      align-items: start;
      // keep basix's card surface (the desktop rule above sets the row bg)
      background: var(--background);
    }
    td {
      display: block;
      padding: 0;
      border: none;
      text-align: left;
      &::before {
        content: none;
      }
    }
    &__who {
      grid-area: who;
      min-width: 0;
    }
    &__name,
    &__email {
      max-width: 100%;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    &__status {
      grid-area: status;
      justify-self: end;
    }
    &__tokens {
      grid-area: tokens;
    }
    &__membership {
      grid-area: membership;
      text-align: right;
    }
    &__role {
      grid-area: role;
      align-self: center;
      font-size: 0.8rem;
    }
    &__label {
      display: block;
      font-size: 0.68rem;
      font-weight: 600;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--secondary-text);
      margin-bottom: 0.2rem;
    }
    &__actions {
      grid-area: actions;
      position: static;
      width: auto;
      background: transparent;
      box-shadow: none !important;
    }
  }
}

.action-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.75rem;
  font-size: 0.8rem;
  font-weight: 600;
  font-family: inherit;
  color: var(--primary-text);
  background: var(--background);
  border: 1px solid var(--divider);
  border-radius: 7px;
  cursor: pointer;
  transition:
    border-color 0.2s,
    color 0.2s;
  white-space: nowrap;
  &:hover {
    border-color: var(--accent-color);
    color: var(--accent-text);
  }
  &--success {
    color: var(--success-text);
    border-color: rgba(34, 197, 94, 0.25);
    background: rgba(34, 197, 94, 0.06);
    &:hover {
      border-color: rgba(34, 197, 94, 0.5);
    }
  }
  &--danger {
    color: var(--error-text);
    border-color: rgba(239, 68, 68, 0.25);
    background: rgba(239, 68, 68, 0.05);
    &:hover {
      border-color: rgba(239, 68, 68, 0.5);
      color: var(--error-text-hover);
    }
  }
  &:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
}

.text-muted {
  color: var(--secondary-text);
}
.text-sm {
  font-size: 0.8rem;
}

// ── Modal ──────────────────────────────────────────────────────────
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}
.modal-box {
  background: var(--secondary-background);
  border: 1px solid var(--divider);
  border-radius: 14px;
  width: 100%;
  max-width: 480px;
  overflow: hidden;
  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.1rem 1.5rem;
    border-bottom: 1px solid var(--divider);
  }
  &__title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--primary-text);
    margin: 0;
    letter-spacing: -0.02em;
  }
  &__close {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--secondary-text);
    display: flex;
    align-items: center;
    .icon {
      font-size: 1.25rem;
    }
    &:hover {
      color: var(--primary-text);
    }
  }
  &__body {
    padding: 1.25rem 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }
  &__footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding-top: 0.5rem;
  }
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}
.form-label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--secondary-text);
}
.form-input {
  padding: 0.55rem 0.75rem;
  background: var(--background);
  border: 1px solid var(--divider);
  border-radius: 8px;
  font-size: 0.9rem;
  font-family: inherit;
  color: var(--primary-text);
  outline: none;
  transition: border-color 0.2s;
  &:focus {
    border-color: $amber;
  }
}
.form-error {
  font-size: 0.85rem;
  color: var(--error-text);
  margin: 0;
  padding-bottom: 0;
}
// UiVirtualDropdown draws its own complete box (background/border/shadow) —
// unlike .form-input, this must NOT add a second one, just size it.
.form-select {
  display: block;
  width: 100%;
}

.l-footer {
  background: $hero-bg;
  border-top: 1px solid $hero-divider;
  padding: 1.75rem 1.5rem;
  &__inner {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
  }
  &__brand {
    display: flex;
    align-items: center;
    gap: 0.4rem;
  }
  &__hex {
    font-size: 1.1rem;
    color: $amber;
  }
  &__name {
    font-size: 0.9rem;
    font-weight: 700;
    color: $hero-text;
    letter-spacing: -0.02em;
  }
  &__copy {
    font-size: 0.8rem;
    color: $hero-muted-50;
    padding-bottom: 0;
  }
}

// "Show deleted" sits at the far end of the header
.deleted-toggle {
  margin-left: auto;
  font-size: 0.82rem;
}
.filter-clear + .deleted-toggle {
  margin-left: 0;
}
</style>
