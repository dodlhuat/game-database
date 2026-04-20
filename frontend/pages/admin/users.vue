<template>
  <div class="admin-page">
    <AppNav />

    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" /><div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <AdminBreadcrumb :label="$t('admin.breadcrumb.users')" />
        <div class="page-hero__row">
          <h1 class="page-hero__title">{{ $t('admin.users.title') }}</h1>
          <button class="hero-btn" @click="openCreate">
            <span class="icon icon-add" aria-hidden="true" />
            {{ $t('admin.actions.add_user') }}
          </button>
        </div>
      </div>
    </section>

    <div class="admin-content">
      <div class="admin-content__inner">

        <div v-if="pending" class="admin-state"><div class="spinner" /></div>

        <section v-else class="dash-section">
          <header class="dash-section__header">
            <h2 class="dash-section__title">{{ $t('admin.users.all') }}</h2>
            <span class="dash-section__count">{{ users.length }}</span>
          </header>

          <div v-if="!users.length" class="dash-empty">
            <span class="icon icon-person dash-empty__icon" aria-hidden="true" />
            <p class="dash-empty__text">{{ $t('admin.empty.users') }}</p>
          </div>

          <div v-else class="table-wrap">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>{{ $t('admin.table.name') }}</th>
                  <th>{{ $t('admin.table.email') }}</th>
                  <th>{{ $t('admin.table.role') }}</th>
                  <th>{{ $t('admin.table.status') }}</th>
                  <th>{{ $t('admin.table.tokens') }}</th>
                  <th>{{ $t('admin.table.membership') }}</th>
                  <th>{{ $t('admin.table.registered') }}</th>
                  <th>{{ $t('admin.table.actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in users" :key="user.id">
                  <td class="dash-table__primary">{{ user.name }}</td>
                  <td class="text-muted">{{ user.email }}</td>
                  <td class="text-muted">{{ roleLabel(user.role) }}</td>
                  <td>
                    <span class="badge" :class="statusClass(user.status)">{{ statusLabel(user.status) }}</span>
                  </td>
                  <td class="text-muted">{{ user.tokens ?? 0 }}</td>
                  <td class="text-muted">
                    <span v-if="user.membership_expires_at" :class="membershipClass(user.membership_expires_at)">
                      {{ formatDate(user.membership_expires_at) }}
                    </span>
                    <span v-else class="text-muted">—</span>
                  </td>
                  <td class="text-muted">{{ formatDate(user.created_at) }}</td>
                  <td>
                    <div class="action-row">
                      <button class="action-btn" @click="openEdit(user)">{{ $t('admin.actions.edit') }}</button>
                      <template v-if="user.status === 'PENDING'">
                        <button class="action-btn action-btn--success" @click="approve(user.id)">{{ $t('admin.users.approve') }}</button>
                        <button class="action-btn action-btn--danger" @click="reject(user.id)">{{ $t('admin.users.reject') }}</button>
                      </template>
                      <template v-else-if="user.status === 'ACTIVE'">
                        <button class="action-btn action-btn--danger" @click="suspend(user.id)">{{ $t('admin.users.suspend') }}</button>
                      </template>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

      </div>
    </div>

    <!-- ── Create Modal ──────────────────────────────────────────── -->
    <div v-if="showCreate" class="modal-overlay" @click.self="showCreate = false">
      <div class="modal-box">
        <header class="modal-box__header">
          <h2 class="modal-box__title">{{ $t('admin.users.create_title') }}</h2>
          <button class="modal-box__close" @click="showCreate = false" :aria-label="$t('admin.form.close')">
            <span class="icon icon-close" />
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
            <input v-model="createForm.password" class="form-input" type="password" required minlength="8" />
          </div>
          <div class="form-field">
            <label class="form-label">{{ $t('admin.table.role') }}</label>
            <select v-model="createForm.role" class="form-input">
              <option value="USER">{{ $t('admin.users.role_user') }}</option>
              <option value="MEMBER">{{ $t('admin.users.role_member') }}</option>
              <option value="ADMIN">{{ $t('admin.users.role_admin') }}</option>
            </select>
          </div>
          <div class="form-field">
            <label class="form-label">{{ $t('admin.table.status') }}</label>
            <select v-model="createForm.status" class="form-input">
              <option value="PENDING">PENDING</option>
              <option value="ACTIVE">ACTIVE</option>
            </select>
          </div>
          <p v-if="createError" class="form-error">{{ createError }}</p>
          <div class="modal-box__footer">
            <button type="button" class="action-btn" @click="showCreate = false">{{ $t('admin.form.cancel') }}</button>
            <button type="submit" class="action-btn action-btn--success" :disabled="createLoading">
              {{ createLoading ? $t('admin.users.creating') : $t('admin.users.create_btn') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ── Edit Modal ────────────────────────────────────────────── -->
    <div v-if="showEdit" class="modal-overlay" @click.self="showEdit = false">
      <div class="modal-box">
        <header class="modal-box__header">
          <h2 class="modal-box__title">{{ $t('admin.users.edit_title') }}</h2>
          <button class="modal-box__close" @click="showEdit = false" :aria-label="$t('admin.form.close')">
            <span class="icon icon-close" />
          </button>
        </header>
        <form class="modal-box__body" @submit.prevent="submitEdit">
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
            <select v-model="editForm.role" class="form-input">
              <option value="USER">{{ $t('admin.users.role_user') }}</option>
              <option value="MEMBER">{{ $t('admin.users.role_member') }}</option>
              <option value="ADMIN">{{ $t('admin.users.role_admin') }}</option>
            </select>
          </div>
          <div class="form-field">
            <label class="form-label">{{ $t('auth.password') }} <span class="text-muted">{{ $t('admin.users.password_edit_hint') }}</span></label>
            <input v-model="editForm.password" class="form-input" type="password" minlength="8" />
          </div>
          <p v-if="editError" class="form-error">{{ editError }}</p>
          <div class="modal-box__footer">
            <button type="button" class="action-btn" @click="showEdit = false">{{ $t('admin.form.cancel') }}</button>
            <button type="submit" class="action-btn action-btn--success" :disabled="editLoading">
              {{ editLoading ? $t('admin.users.saving') : $t('admin.form.save') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <footer class="l-footer">
      <div class="l-footer__inner">
        <div class="l-footer__brand"><span class="l-footer__hex" aria-hidden="true">⬡</span><span class="l-footer__name">AUA</span></div>
        <p class="l-footer__copy">{{ $t('common.copyright_short', { year }) }}</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

definePageMeta({ middleware: ['auth', 'admin'] })

interface User { id: number; name: string; email: string; role: string; status: string; created_at: string; tokens?: number; membership_expires_at?: string | null }

const api = useApi()
const { t } = useI18n()
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
    showCreate.value = false
  } catch (e: unknown) {
    const err = e as { data?: { message?: string } }
    createError.value = err?.data?.message ?? t('admin.users.create_error')
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

function openEdit(user: User) {
  editUserId.value = user.id
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
    const idx = users.value.findIndex(u => u.id === editUserId.value)
    if (idx !== -1) users.value[idx] = { ...users.value[idx], ...res.data }
    showEdit.value = false
  } catch (e: unknown) {
    const err = e as { data?: { message?: string } }
    editError.value = err?.data?.message ?? t('admin.users.edit_error')
  } finally {
    editLoading.value = false
  }
}

// ── Status actions ──────────────────────────────────────────────
onMounted(async () => { try { const data = await api.get<{ data: User[] }>('/admin/users'); users.value = data.data } finally { pending.value = false } })

async function approve(id: number) { await api.patch(`/admin/users/${id}/approve`); const u = users.value.find(u => u.id === id); if (u) u.status = 'ACTIVE' }
async function reject(id: number)  { await api.patch(`/admin/users/${id}/reject`);  const u = users.value.find(u => u.id === id); if (u) u.status = 'REJECTED' }
async function suspend(id: number) { await api.patch(`/admin/users/${id}/suspend`); const u = users.value.find(u => u.id === id); if (u) u.status = 'SUSPENDED' }

function statusLabel(s: string) { const m: Record<string, string> = { ACTIVE: t('admin.users.status_active'), PENDING: t('admin.users.status_pending'), REJECTED: t('admin.users.status_rejected'), SUSPENDED: t('admin.users.status_suspended') }; return m[s] ?? s }
function statusClass(s: string) { const m: Record<string, string> = { ACTIVE: 'badge-success', PENDING: 'badge-warning', REJECTED: '', SUSPENDED: 'badge-error' }; return m[s] ?? '' }
function roleLabel(r: string) { const m: Record<string, string> = { USER: t('admin.users.role_label_user'), MEMBER: t('admin.users.role_label_member'), ADMIN: 'Admin' }; return m[r] ?? r }
function formatDate(iso: string | null | undefined) { if (!iso) return '—'; return new Date(iso).toLocaleDateString('de-DE') }
function membershipClass(iso: string) {
  const days = (new Date(iso).getTime() - Date.now()) / (1000 * 60 * 60 * 24)
  if (days < 30) return 'text-danger'
  if (days < 90) return 'text-warn'
  return 'text-active'
}
</script>

<style lang="scss" scoped>
$hero-bg: #0F0E0C; $nav-height: 64px;
$amber-08: rgba(212,146,30,0.08); $amber-14: rgba(212,146,30,0.14); $amber-25: rgba(212,146,30,0.25); $amber-glow: rgba(212,146,30,0.16);
$hero-text: #EEE8DF; $hero-muted: rgba(238,232,223,0.72); $hero-muted-50: rgba(238,232,223,0.65); $hero-divider: rgba(238,232,223,0.10);

.admin-page { min-height: 100vh; display: flex; flex-direction: column; background: var(--background); }

.page-hero { position: relative; background: $hero-bg; padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem; overflow: hidden; &__backdrop { position: absolute; inset: 0; pointer-events: none; } &__glow { position: absolute; width: 400px; height: 400px; top: -120px; right: -60px; border-radius: 50%; filter: blur(90px); background: $amber-glow; } &__dots { position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 24px 24px; mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%); } &__body { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; } &__row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; } &__title { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0; } }

.hero-btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 600; font-family: inherit; color: #0F0E0C; background: $amber; border: none; border-radius: 8px; cursor: pointer; transition: background 0.2s; white-space: nowrap; .icon { font-size: 1rem; } &:hover { background: color.adjust($amber, $lightness: -8%); } }

.admin-content { flex: 1; padding: 2rem 1.5rem 4rem; &__inner { max-width: 1100px; margin: 0 auto; } }
.admin-state { display: flex; justify-content: center; align-items: center; min-height: 200px; }

.dash-section { background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 14px; overflow: hidden; }
.dash-section__header { display: flex; align-items: center; gap: 0.75rem; padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--divider); }
.dash-section__title { font-size: 0.95rem; font-weight: 700; color: var(--primary-text); margin: 0; letter-spacing: -0.02em; }
.dash-section__count { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 22px; padding: 0 6px; font-size: 0.75rem; font-weight: 700; color: $amber; background: $amber-08; border: 1px solid $amber-25; border-radius: 999px; }

.dash-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; padding: 3rem 1.5rem; color: var(--secondary-text); &__icon { width: 2rem; height: 2rem; opacity: 0.35; } &__text { font-size: 0.9rem; padding-bottom: 0; } }

.table-wrap { overflow-x: auto; }
.dash-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; th { padding: 0.7rem 1.5rem; text-align: left; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--secondary-text); background: var(--background); border-bottom: 1px solid var(--divider); white-space: nowrap; } td { padding: 0.9rem 1.5rem; color: var(--primary-text); border-bottom: 1px solid var(--divider); vertical-align: middle; } tbody tr:last-child td { border-bottom: none; } tbody tr { transition: background 0.15s; &:hover { background: var(--background); } } &__primary { font-weight: 600; } }


.action-row { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.action-btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 600; font-family: inherit; color: var(--primary-text); background: var(--background); border: 1px solid var(--divider); border-radius: 7px; cursor: pointer; transition: border-color 0.2s, color 0.2s; white-space: nowrap; &:hover { border-color: var(--accent-color); color: var(--accent-text); } &--success { color: #4ade80; border-color: rgba(34,197,94,0.25); background: rgba(34,197,94,0.06); &:hover { border-color: rgba(34,197,94,0.5); } } &--danger { color: #f87171; border-color: rgba(239,68,68,0.25); background: rgba(239,68,68,0.05); &:hover { border-color: rgba(239,68,68,0.5); color: #fca5a5; } } &:disabled { opacity: 0.5; cursor: not-allowed; } }

.text-muted { color: var(--secondary-text); }
.text-sm { font-size: 0.8rem; }

// ── Modal ──────────────────────────────────────────────────────────
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem; }
.modal-box { background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 14px; width: 100%; max-width: 480px; overflow: hidden; &__header { display: flex; align-items: center; justify-content: space-between; padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--divider); } &__title { font-size: 1rem; font-weight: 700; color: var(--primary-text); margin: 0; letter-spacing: -0.02em; } &__close { background: none; border: none; cursor: pointer; color: var(--secondary-text); display: flex; align-items: center; .icon { font-size: 1.25rem; } &:hover { color: var(--primary-text); } } &__body { padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 1rem; } &__footer { display: flex; justify-content: flex-end; gap: 0.5rem; padding-top: 0.5rem; } }

.form-field { display: flex; flex-direction: column; gap: 0.35rem; }
.form-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--secondary-text); }
.form-input { padding: 0.55rem 0.75rem; background: var(--background); border: 1px solid var(--divider); border-radius: 8px; font-size: 0.9rem; font-family: inherit; color: var(--primary-text); outline: none; transition: border-color 0.2s; &:focus { border-color: $amber; } }
.form-error { font-size: 0.85rem; color: #f87171; margin: 0; padding-bottom: 0; }

.l-footer { background: $hero-bg; border-top: 1px solid $hero-divider; padding: 1.75rem 1.5rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; } &__brand { display: flex; align-items: center; gap: 0.4rem; } &__hex { font-size: 1.1rem; color: $amber; } &__name { font-size: 0.9rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em; } &__copy { font-size: 0.8rem; color: $hero-muted-50; padding-bottom: 0; } }
</style>
