<template>
  <div class="admin-page">
    <AppNav />

    <!-- ── Page Hero ────────────────────────────────────────────── -->
    <section class="page-hero">
      <div class="page-hero__backdrop" aria-hidden="true">
        <div class="page-hero__glow" />
        <div class="page-hero__dots" />
      </div>
      <div class="page-hero__body">
        <AdminBreadcrumb label="Pakete" />
        <div class="page-hero__row">
          <h1 class="page-hero__title">Pakete verwalten</h1>
          <button class="hero-btn" @click="openCreate">
            <span class="icon icon-plus-outline" aria-hidden="true" />
            Paket hinzufügen
          </button>
        </div>
      </div>
    </section>

    <!-- ── Content ──────────────────────────────────────────────── -->
    <div class="admin-content">
      <div class="admin-content__inner">

        <div v-if="loading" class="admin-state">
          <div class="spinner" />
        </div>

        <section v-else class="dash-section">
          <header class="dash-section__header">
            <h2 class="dash-section__title">Alle Pakete</h2>
            <span class="dash-section__count">{{ packages.length }}</span>
          </header>

          <div v-if="!packages.length" class="dash-empty">
            <span class="icon icon-gift-outline dash-empty__icon" aria-hidden="true" />
            <p class="dash-empty__text">Noch keine Pakete vorhanden.</p>
          </div>

          <div v-else class="table-wrap">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Typ</th>
                  <th>Kategorie</th>
                  <th>Spiele</th>
                  <th>Status</th>
                  <th>Aktionen</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="pkg in packages" :key="pkg.id">
                  <td class="dash-table__primary">{{ pkg.name }}</td>
                  <td>
                    <span class="type-badge" :class="pkg.type === 'CURATED' ? 'type-badge--curated' : 'type-badge--category'">
                      {{ pkg.type === 'CURATED' ? 'Kuratiert' : 'Kategorie' }}
                    </span>
                  </td>
                  <td>{{ pkg.category?.name ?? '—' }}</td>
                  <td>{{ pkg.games_count ?? 0 }}</td>
                  <td>
                    <span class="badge" :class="pkg.is_active ? 'badge-success' : ''">
                      {{ pkg.is_active ? 'Aktiv' : 'Inaktiv' }}
                    </span>
                  </td>
                  <td>
                    <div class="action-row">
                      <button class="action-btn" @click="openEdit(pkg)">Bearbeiten</button>
                      <button class="action-btn action-btn--danger" @click="remove(pkg.id)">Löschen</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

      </div>
    </div>

    <!-- ── Formular Modal ───────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="form.open" class="modal-overlay" @click.self="closeForm">
        <div class="dialog dialog--wide">
          <div class="dialog__header">
            <h3 class="dialog__title">{{ form.id ? 'Paket bearbeiten' : 'Paket hinzufügen' }}</h3>
            <button class="dialog__close" aria-label="Schließen" @click="closeForm">
              <span class="icon icon-close-outline" aria-hidden="true" />
            </button>
          </div>

          <div class="dialog__body">
            <div class="form-grid">
              <div class="form-grid__full"><UiInput v-model="form.name" label="Name" required /></div>
              <div class="form-grid__full"><UiInput v-model="form.slug" label="Slug" /></div>
              <div class="form-grid__full"><UiInput v-model="form.description" label="Beschreibung" /></div>

              <div>
                <label class="form-label">Typ</label>
                <select v-model="form.type" class="form-select">
                  <option value="CURATED">Kuratiert</option>
                  <option value="CATEGORY">Kategorie</option>
                </select>
              </div>

              <div>
                <label class="form-label">Kategorie</label>
                <select v-model="form.category_id" class="form-select">
                  <option :value="null">Keine</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
              </div>

              <!-- Spielauswahl -->
              <div class="form-grid__full">
                <label class="form-label">
                  Spiele
                  <span v-if="form.game_ids.length" class="form-label__count">{{ form.game_ids.length }} ausgewählt</span>
                </label>
                <div class="game-search">
                  <input
                    v-model="gameSearch"
                    type="text"
                    class="game-search__input"
                    placeholder="Spiele suchen…"
                  />
                </div>
                <div class="game-picker">
                  <label
                    v-for="game in filteredGames"
                    :key="game.id"
                    class="game-chip"
                    :class="{ 'game-chip--selected': form.game_ids.includes(game.id) }"
                  >
                    <input type="checkbox" :value="game.id" v-model="form.game_ids" class="game-chip__input" />
                    {{ game.title }}
                  </label>
                  <p v-if="!filteredGames.length" class="game-picker__empty">Keine Spiele gefunden.</p>
                </div>
              </div>

              <div class="form-grid__full">
                <label class="form-check">
                  <input v-model="form.is_active" type="checkbox" />
                  <span>Paket aktiv schalten</span>
                </label>
              </div>
            </div>

            <div v-if="formError" class="form-error">{{ formError }}</div>
          </div>

          <div class="dialog__actions">
            <UiButton :loading="saving" @click="save">Speichern</UiButton>
            <button class="action-btn" @click="closeForm">Abbrechen</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ── Footer ──────────────────────────────────────────────── -->
    <footer class="l-footer">
      <div class="l-footer__inner">
        <div class="l-footer__brand">
          <span class="l-footer__hex" aria-hidden="true">⬡</span>
          <span class="l-footer__name">AUA</span>
        </div>
        <p class="l-footer__copy">&copy; {{ year }} AUA</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'

definePageMeta({ middleware: ['auth', 'admin'] })

const { fetchAdminPackages, createPackage, updatePackage, deletePackage, fetchAdminCategories, fetchAdminGames } = useAdmin()

interface PackageItem {
  id: number
  name: string
  slug: string
  description: string | null
  type: 'CATEGORY' | 'CURATED'
  category: { id: number; name: string } | null
  games_count: number
  games: { id: number; title: string }[]
  is_active: boolean
}

interface GameOption {
  id: number
  title: string
}

const year = new Date().getFullYear()
const loading = ref(true)
const saving = ref(false)
const formError = ref('')
interface CategoryItem { id: number; name: string; is_active: boolean; children: CategoryItem[] }
const packages = ref<PackageItem[]>([])
const rawCategories = ref<CategoryItem[]>([])
const categories = computed(() => {
  const flat: { id: number; name: string }[] = []
  for (const cat of rawCategories.value) {
    flat.push({ id: cat.id, name: cat.name })
    for (const child of cat.children ?? []) {
      flat.push({ id: child.id, name: `${cat.name} › ${child.name}` })
    }
  }
  return flat
})
const allGames = ref<GameOption[]>([])
const gameSearch = ref('')

const filteredGames = computed(() => {
  const q = gameSearch.value.trim().toLowerCase()
  if (!q) return allGames.value
  return allGames.value.filter(g => g.title.toLowerCase().includes(q))
})

const form = reactive({
  open: false,
  id: null as number | null,
  name: '',
  slug: '',
  description: '',
  type: 'CURATED' as 'CATEGORY' | 'CURATED',
  category_id: null as number | null,
  game_ids: [] as number[],
  is_active: true,
})

onMounted(async () => {
  await load()
  const [catData, gameData] = await Promise.all([fetchAdminCategories(), fetchAdminGames()])
  rawCategories.value = catData.data as CategoryItem[]
  allGames.value = (gameData.data as GameOption[]).map(g => ({ id: g.id, title: g.title }))
})

async function load() {
  loading.value = true
  try {
    const data = await fetchAdminPackages()
    packages.value = data.data as PackageItem[]
  } finally {
    loading.value = false
  }
}

function openCreate() {
  Object.assign(form, { open: true, id: null, name: '', slug: '', description: '', type: 'CURATED', category_id: null, game_ids: [], is_active: true })
  gameSearch.value = ''
  formError.value = ''
}

function openEdit(pkg: PackageItem) {
  Object.assign(form, {
    open: true, id: pkg.id,
    name: pkg.name, slug: pkg.slug, description: pkg.description ?? '',
    type: pkg.type, category_id: pkg.category?.id ?? null,
    game_ids: pkg.games?.map(g => g.id) ?? [],
    is_active: pkg.is_active,
  })
  gameSearch.value = ''
  formError.value = ''
}

function closeForm() { form.open = false }

async function save() {
  saving.value = true
  formError.value = ''
  try {
    const payload: Record<string, unknown> = {
      name: form.name,
      type: form.type,
      game_ids: form.game_ids,
      is_active: form.is_active,
    }
    if (form.slug) payload.slug = form.slug
    if (form.description) payload.description = form.description
    if (form.category_id !== null) payload.category_id = form.category_id

    form.id ? await updatePackage(form.id, payload) : await createPackage(payload)
    await load()
    closeForm()
  } catch (err: unknown) {
    formError.value = (err as { message?: string }).message ?? 'Fehler beim Speichern.'
  } finally {
    saving.value = false
  }
}

async function remove(id: number) {
  await deletePackage(id)
  await load()
}
</script>

<style lang="scss" scoped>
$hero-bg:     #0F0E0C;
$amber:       #D4921E;
$nav-height:  64px;
$amber-08:    rgba(212, 146, 30, 0.08);
$amber-14:    rgba(212, 146, 30, 0.14);
$amber-25:    rgba(212, 146, 30, 0.25);
$amber-glow:  rgba(212, 146, 30, 0.16);
$hero-text:   #EEE8DF;
$hero-muted:  rgba(238, 232, 223, 0.55);
$hero-muted-50: rgba(238, 232, 223, 0.50);
$hero-divider:  rgba(238, 232, 223, 0.10);

.admin-page { min-height: 100vh; display: flex; flex-direction: column; background: var(--background); }

.page-hero {
  position: relative; background: $hero-bg;
  padding: calc(#{$nav-height} + 1.75rem) 1.5rem 1.75rem; overflow: hidden;
  &__backdrop { position: absolute; inset: 0; pointer-events: none; }
  &__glow { position: absolute; width: 400px; height: 400px; top: -120px; right: -60px; border-radius: 50%; filter: blur(90px); background: $amber-glow; }
  &__dots { position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px); background-size: 24px 24px; mask-image: radial-gradient(ellipse 80% 100% at 70% 50%, black 20%, transparent 100%); }
  &__body { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; }
  &__row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
  &__title { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 800; letter-spacing: -0.04em; color: $hero-text; margin: 0; }
}

.hero-btn {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600; font-family: inherit;
  background: $amber; color: #0F0E0C; border: none; border-radius: 8px; cursor: pointer;
  transition: opacity 0.2s;
  .icon { width: 16px; height: 16px; }
  &:hover { opacity: 0.88; }
}

.admin-content { flex: 1; padding: 2rem 1.5rem 4rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; } }
.admin-state { display: flex; justify-content: center; align-items: center; min-height: 200px; }

.dash-section { background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 14px; overflow: hidden; }
.dash-section__header { display: flex; align-items: center; gap: 0.75rem; padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--divider); }
.dash-section__title { font-size: 0.95rem; font-weight: 700; color: var(--primary-text); margin: 0; letter-spacing: -0.02em; }
.dash-section__count { display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 22px; padding: 0 6px; font-size: 0.75rem; font-weight: 700; color: $amber; background: $amber-08; border: 1px solid $amber-25; border-radius: 999px; }

.dash-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; padding: 3rem 1.5rem; color: var(--secondary-text); &__icon { width: 2rem; height: 2rem; opacity: 0.35; } &__text { font-size: 0.9rem; padding-bottom: 0; } }

.table-wrap { overflow-x: auto; }
.dash-table {
  width: 100%; border-collapse: collapse; font-size: 0.875rem;
  th { padding: 0.7rem 1.5rem; text-align: left; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--secondary-text); background: var(--background); border-bottom: 1px solid var(--divider); white-space: nowrap; }
  td { padding: 0.9rem 1.5rem; color: var(--primary-text); border-bottom: 1px solid var(--divider); vertical-align: middle; }
  tbody tr:last-child td { border-bottom: none; }
  tbody tr { transition: background 0.15s; &:hover { background: var(--background); } }
  &__primary { font-weight: 600; }
}


.type-badge { display: inline-block; padding: 0.2rem 0.6rem; font-size: 0.72rem; font-weight: 600; border-radius: 999px; white-space: nowrap; }
.type-badge--curated  { background: $amber-08; color: $amber; border: 1px solid $amber-25; }
.type-badge--category { background: rgba(99,102,241,0.1); color: #818cf8; border: 1px solid rgba(99,102,241,0.25); }

.action-row { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.action-btn {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 600; font-family: inherit;
  color: var(--primary-text); background: var(--background); border: 1px solid var(--divider); border-radius: 7px; cursor: pointer; transition: border-color 0.2s, color 0.2s; white-space: nowrap;
  &:hover { border-color: var(--accent-color); color: var(--accent-text); }
  &--danger { color: #f87171; border-color: rgba(239,68,68,0.25); background: rgba(239,68,68,0.05); &:hover { border-color: rgba(239,68,68,0.5); color: #fca5a5; } }
}

// ─── Modal ────────────────────────────────────────────────────────
.modal-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 1.5rem; overflow-y: auto; }
.dialog {
  background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 16px; padding: 1.75rem; width: 100%; max-width: 480px; box-shadow: 0 25px 60px rgba(0,0,0,0.4);
  &--wide { max-width: 660px; }
  &__header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.5rem; }
  &__title { font-size: 1.05rem; font-weight: 700; letter-spacing: -0.02em; color: var(--primary-text); }
  &__close { display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: transparent; border: none; border-radius: 6px; color: var(--secondary-text); cursor: pointer; transition: background 0.15s, color 0.15s; .icon { width: 18px; height: 18px; } &:hover { background: var(--background); color: var(--primary-text); } }
  &__body { margin-bottom: 1.5rem; max-height: 65vh; overflow-y: auto; }
  &__actions { display: flex; gap: 0.75rem; }
}

.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; .dialog { transition: opacity 0.2s ease, transform 0.2s ease; } }
.modal-enter-from, .modal-leave-to { opacity: 0; .dialog { opacity: 0; transform: translateY(8px) scale(0.98); } }

// ─── Form Elements ────────────────────────────────────────────────
.form-grid {
  display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem 1rem;
  @media (max-width: 500px) { grid-template-columns: 1fr; }
  &__full { grid-column: 1 / -1; }
}

.form-label {
  display: flex; align-items: center; gap: 0.4rem;
  font-size: 0.8rem; font-weight: 600; color: var(--secondary-text); margin-bottom: 0.4rem; letter-spacing: 0.03em;

  &__count {
    font-size: 0.72rem; font-weight: 700;
    color: $amber; background: $amber-08; border: 1px solid $amber-25;
    border-radius: 999px; padding: 0.1rem 0.45rem;
  }
}

.form-select { display: block; width: 100%; height: 40px; padding: 0 0.75rem; border: 1px solid var(--divider); border-radius: 8px; background: var(--background); color: var(--primary-text); font-size: 0.875rem; font-family: inherit; cursor: pointer; transition: border-color 0.2s; &:focus { outline: none; border-color: var(--accent-color); } }

.form-check { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--primary-text); cursor: pointer; user-select: none; input { accent-color: var(--accent-color); width: 15px; height: 15px; cursor: pointer; } }

.form-error { margin-top: 1rem; padding: 0.75rem 1rem; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); border-radius: 8px; color: #f87171; font-size: 0.875rem; }

// ─── Game Picker ──────────────────────────────────────────────────
.game-search {
  margin-bottom: 0.5rem;

  &__input {
    display: block; width: 100%; height: 36px; padding: 0 0.75rem;
    border: 1px solid var(--divider); border-radius: 8px;
    background: var(--background); color: var(--primary-text);
    font-size: 0.875rem; font-family: inherit;
    transition: border-color 0.2s;
    &:focus { outline: none; border-color: var(--accent-color); }
  }
}

.game-picker {
  max-height: 200px;
  overflow-y: auto;
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  padding: 0.6rem;
  border: 1px solid var(--divider);
  border-radius: 8px;
  background: var(--background);

  &__empty { font-size: 0.82rem; color: var(--secondary-text); padding-bottom: 0; width: 100%; }
}

.game-chip {
  display: inline-flex; align-items: center; gap: 0.3rem;
  padding: 0.25rem 0.6rem;
  border: 1px solid var(--divider); border-radius: 999px;
  font-size: 0.8rem; cursor: pointer; user-select: none;
  transition: border-color 0.15s, background 0.15s;

  &--selected { border-color: var(--accent-color); background: var(--accent-color-muted); color: var(--accent-text); }
  &__input { display: none; }
}

// ─── Footer ───────────────────────────────────────────────────────
.l-footer { background: $hero-bg; border-top: 1px solid $hero-divider; padding: 1.75rem 1.5rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; } &__brand { display: flex; align-items: center; gap: 0.4rem; } &__hex { font-size: 1.1rem; color: $amber; } &__name { font-size: 0.9rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em; } &__copy { font-size: 0.8rem; color: $hero-muted-50; padding-bottom: 0; } }
</style>
