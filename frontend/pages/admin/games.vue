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
        <NuxtLink to="/admin" class="page-hero__back">
          <span class="icon icon-arrow-back-outline" aria-hidden="true" />
          Admin
        </NuxtLink>
        <p class="page-hero__eyebrow">Administration</p>
        <div class="page-hero__row">
          <h1 class="page-hero__title">Spiele verwalten</h1>
          <button class="hero-btn" @click="openCreate">
            <span class="icon icon-plus-outline" aria-hidden="true" />
            Spiel hinzufügen
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
            <h2 class="dash-section__title">Alle Spiele</h2>
            <span class="dash-section__count">{{ games.length }}</span>
          </header>

          <div v-if="!games.length" class="dash-empty">
            <span class="icon icon-book-open-outline dash-empty__icon" aria-hidden="true" />
            <p class="dash-empty__text">Noch keine Spiele vorhanden.</p>
          </div>

          <div v-else class="table-wrap">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>Titel</th>
                  <th>Kategorie</th>
                  <th>Kopien</th>
                  <th>Status</th>
                  <th>Aktionen</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="game in games" :key="game.id">
                  <td class="dash-table__primary">{{ game.title }}</td>
                  <td>{{ game.category?.name ?? '—' }}</td>
                  <td>{{ game.copies_count }}</td>
                  <td>
                    <span class="status-badge" :class="game.is_active ? 'status-badge--active' : 'status-badge--muted'">
                      {{ game.is_active ? 'Aktiv' : 'Inaktiv' }}
                    </span>
                  </td>
                  <td>
                    <div class="action-row">
                      <button class="action-btn" @click="openEdit(game)">Bearbeiten</button>
                      <button class="action-btn action-btn--danger" @click="remove(game.id)">Löschen</button>
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
        <div class="modal modal--wide">
          <div class="modal__header">
            <h3 class="modal__title">{{ form.id ? 'Spiel bearbeiten' : 'Spiel hinzufügen' }}</h3>
            <button class="modal__close" aria-label="Schließen" @click="closeForm">
              <span class="icon icon-close-outline" aria-hidden="true" />
            </button>
          </div>

          <div class="modal__body">
            <div class="form-grid">
              <div class="form-grid__full"><UiInput v-model="form.title" label="Titel" required /></div>
              <div class="form-grid__full"><UiInput v-model="form.slug" label="Slug" required /></div>
              <div class="form-grid__full"><UiInput v-model="form.short_description" label="Kurzbeschreibung (max. 500 Zeichen)" /></div>
              <div class="form-grid__full"><UiInput v-model="form.description" label="Beschreibung (lang)" /></div>

              <div class="form-grid__full">
                <label class="form-label">Kategorie</label>
                <select v-model="form.category_id" class="form-select">
                  <option :value="null">Keine</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
              </div>

              <div><UiInput v-model="form.min_players" label="Min. Spieler" type="number" /></div>
              <div><UiInput v-model="form.max_players" label="Max. Spieler" type="number" /></div>
              <div><UiInput v-model="form.min_age" label="Ab Alter" type="number" /></div>

              <div><UiInput v-model="form.duration_min" label="Spielzeit min. (Min.)" type="number" /></div>
              <div><UiInput v-model="form.duration_max" label="Spielzeit max. (Min.)" type="number" /></div>
              <div><UiInput v-model="form.year" label="Erscheinungsjahr" type="number" /></div>

              <div>
                <label class="form-label">Schwierigkeit</label>
                <select v-model="form.difficulty" class="form-select">
                  <option value="">Keine</option>
                  <option value="EASY">Leicht</option>
                  <option value="MEDIUM">Mittel</option>
                  <option value="HARD">Schwer</option>
                  <option value="EXPERT">Experte</option>
                </select>
              </div>

              <div><UiInput v-model="form.language" label="Sprache" /></div>

              <div class="form-grid__full">
                <label class="form-label">Tags</label>
                <div class="tag-picker">
                  <label
                    v-for="tag in allTags"
                    :key="tag.id"
                    class="tag-chip"
                    :class="{ 'tag-chip--selected': form.tag_ids.includes(tag.id) }"
                  >
                    <input type="checkbox" :value="tag.id" v-model="form.tag_ids" class="tag-chip__input" />
                    {{ tag.name }}
                  </label>
                </div>
                <div class="tag-add">
                  <UiInput v-model="newTagName" placeholder="Neuer Tag…" style="flex:1" />
                  <button class="action-btn" :disabled="!newTagName.trim()" @click="addTag">Hinzufügen</button>
                </div>
              </div>

              <div class="form-grid__full">
                <label class="form-label">Coverbild</label>
                <div v-if="form.existingCoverUrl && !form.coverFile" class="cover-preview">
                  <img :src="form.existingCoverUrl" alt="Aktuelles Coverbild" class="cover-preview__img" />
                  <p class="cover-preview__hint">Neues Bild auswählen um das bestehende zu ersetzen.</p>
                </div>
                <div v-if="form.coverFile" class="cover-preview">
                  <img :src="coverPreviewUrl" alt="Vorschau" class="cover-preview__img" />
                  <button type="button" class="cover-preview__remove" @click="form.coverFile = null">Entfernen</button>
                </div>
                <input type="file" accept="image/*" class="form-file" @change="onFileChange" />
              </div>

              <div class="form-grid__full">
                <label class="form-check">
                  <input v-model="form.is_active" type="checkbox" />
                  <span>Spiel aktiv schalten</span>
                </label>
              </div>
            </div>

            <div v-if="formError" class="form-error">{{ formError }}</div>
          </div>

          <div class="modal__actions">
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

const { fetchAdminGames, createGame, updateGame, deleteGame, fetchAdminCategories, fetchAdminTags, createTag } = useAdmin()

interface Game {
  id: number; title: string; slug: string; description: string | null; short_description: string | null
  category: { id: number; name: string } | null; copies_count: number; is_active: boolean
  min_players: number | null; max_players: number | null; min_age: number | null
  duration_min: number | null; duration_max: number | null; difficulty: string | null
  language: string | null; year: number | null; tags: { id: number; name: string }[]
  cover_image_url: string | null
}

const year = new Date().getFullYear()
const loading = ref(true)
const coverPreviewUrl = computed(() => form.coverFile ? URL.createObjectURL(form.coverFile) : null)
const saving = ref(false)
const formError = ref('')
const games = ref<Game[]>([])
const categories = ref<{ id: number; name: string }[]>([])
const allTags = ref<{ id: number; name: string; slug: string }[]>([])
const newTagName = ref('')

const form = reactive({
  open: false, id: null as number | null,
  title: '', slug: '', description: '', short_description: '',
  category_id: null as number | null,
  min_players: '', max_players: '', min_age: '',
  duration_min: '', duration_max: '',
  difficulty: '', language: '', year: '',
  is_active: true, tag_ids: [] as number[],
  coverFile: null as File | null,
  existingCoverUrl: null as string | null,
})

onMounted(async () => {
  await load()
  const [catData, tagData] = await Promise.all([fetchAdminCategories(), fetchAdminTags()])
  categories.value = catData.data as { id: number; name: string }[]
  allTags.value = tagData.data
})

async function load() {
  loading.value = true
  try { const data = await fetchAdminGames(); games.value = data.data as Game[] }
  finally { loading.value = false }
}

function openCreate() {
  Object.assign(form, { open: true, id: null, title: '', slug: '', description: '', short_description: '', category_id: null, min_players: '', max_players: '', min_age: '', duration_min: '', duration_max: '', difficulty: '', language: '', year: '', is_active: true, tag_ids: [], coverFile: null, existingCoverUrl: null })
  formError.value = ''
}

function openEdit(game: Game) {
  Object.assign(form, { open: true, id: game.id, title: game.title, slug: game.slug, description: game.description ?? '', short_description: game.short_description ?? '', category_id: game.category?.id ?? null, min_players: game.min_players ?? '', max_players: game.max_players ?? '', min_age: game.min_age ?? '', duration_min: game.duration_min ?? '', duration_max: game.duration_max ?? '', difficulty: game.difficulty ?? '', language: game.language ?? '', year: game.year ?? '', is_active: game.is_active, tag_ids: game.tags?.map(t => t.id) ?? [], coverFile: null, existingCoverUrl: game.cover_image_url ?? null })
  formError.value = ''
}

function closeForm() { form.open = false }
function onFileChange(e: Event) { form.coverFile = (e.target as HTMLInputElement).files?.[0] ?? null }

async function addTag() {
  const name = newTagName.value.trim()
  if (!name) return
  try { const res = await createTag(name); allTags.value.push(res.data); form.tag_ids.push(res.data.id); newTagName.value = '' } catch {}
}

async function save() {
  saving.value = true; formError.value = ''
  try {
    const fd = new FormData()
    const fields = ['title', 'slug', 'description', 'short_description', 'category_id', 'min_players', 'max_players', 'min_age', 'duration_min', 'duration_max', 'difficulty', 'language', 'year'] as const
    fields.forEach((f) => { if (form[f] !== '' && form[f] !== null) fd.append(f, String(form[f])) })
    fd.append('is_active', form.is_active ? '1' : '0')
    form.tag_ids.forEach(id => fd.append('tag_ids[]', String(id)))
    if (form.coverFile) fd.append('cover_image', form.coverFile)
    form.id ? await updateGame(form.id, fd) : await createGame(fd)
    await load(); closeForm()
  } catch (err: unknown) {
    formError.value = (err as { message?: string }).message ?? 'Fehler beim Speichern.'
  } finally { saving.value = false }
}

async function remove(id: number) { await deleteGame(id); await load() }
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
  &__back { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.78rem; font-weight: 600; color: $hero-muted; text-decoration: none; margin-bottom: 0.6rem; transition: color 0.2s; .icon { width: 14px; height: 14px; } &:hover { color: $hero-text; } }
  &__eyebrow { display: inline-block; font-size: 0.72rem; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase; color: $amber; margin-bottom: 0.4rem; padding: 0.2rem 0.6rem; border: 1px solid $amber-25; border-radius: 999px; background: $amber-08; }
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

.status-badge { display: inline-block; padding: 0.2rem 0.6rem; font-size: 0.72rem; font-weight: 600; border-radius: 999px; white-space: nowrap; }
.status-badge--active { background: rgba(34,197,94,0.12); color: #4ade80; border: 1px solid rgba(34,197,94,0.25); }
.status-badge--muted  { background: var(--background); color: var(--secondary-text); border: 1px solid var(--divider); }

.action-row { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.action-btn {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 600; font-family: inherit;
  color: var(--primary-text); background: var(--background); border: 1px solid var(--divider); border-radius: 7px; cursor: pointer; transition: border-color 0.2s, color 0.2s; white-space: nowrap;
  &:hover { border-color: var(--accent-color); color: var(--accent-text); }
  &--danger { color: #f87171; border-color: rgba(239,68,68,0.25); background: rgba(239,68,68,0.05); &:hover { border-color: rgba(239,68,68,0.5); color: #fca5a5; } }
  &:disabled { opacity: 0.4; cursor: not-allowed; }
}

// ─── Modal ────────────────────────────────────────────────────────
.modal-overlay { position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 1.5rem; overflow-y: auto; }
.modal {
  background: var(--secondary-background); border: 1px solid var(--divider); border-radius: 16px; padding: 1.75rem; width: 100%; max-width: 480px; box-shadow: 0 25px 60px rgba(0,0,0,0.4);
  &--wide { max-width: 700px; }
  &__header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.5rem; }
  &__title { font-size: 1.05rem; font-weight: 700; letter-spacing: -0.02em; color: var(--primary-text); }
  &__close { display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: transparent; border: none; border-radius: 6px; color: var(--secondary-text); cursor: pointer; transition: background 0.15s, color 0.15s; .icon { width: 18px; height: 18px; } &:hover { background: var(--background); color: var(--primary-text); } }
  &__body { margin-bottom: 1.5rem; max-height: 65vh; overflow-y: auto; }
  &__actions { display: flex; gap: 0.75rem; }
}

.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; .modal { transition: opacity 0.2s ease, transform 0.2s ease; } }
.modal-enter-from, .modal-leave-to { opacity: 0; .modal { opacity: 0; transform: translateY(8px) scale(0.98); } }

// ─── Form Elements ────────────────────────────────────────────────
.form-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem 1rem;
  @media (max-width: 600px) { grid-template-columns: 1fr; }
  &__full { grid-column: 1 / -1; }
}

.form-label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--secondary-text); margin-bottom: 0.4rem; letter-spacing: 0.03em; }

.form-select { display: block; width: 100%; height: 40px; padding: 0 0.75rem; border: 1px solid var(--divider); border-radius: 8px; background: var(--background); color: var(--primary-text); font-size: 0.875rem; font-family: inherit; cursor: pointer; transition: border-color 0.2s; &:focus { outline: none; border-color: var(--accent-color); } }

.form-file { display: block; width: 100%; font-size: 0.875rem; color: var(--secondary-text); padding: 0.4rem 0; }

.cover-preview {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.75rem;
  padding: 0.75rem;
  background: var(--background);
  border: 1px solid var(--divider);
  border-radius: 8px;

  &__img {
    width: 64px;
    height: 64px;
    object-fit: cover;
    border-radius: 6px;
    flex-shrink: 0;
  }

  &__hint {
    font-size: 0.8rem;
    color: var(--secondary-text);
    padding-bottom: 0;
  }

  &__remove {
    font-size: 0.78rem;
    font-weight: 600;
    font-family: inherit;
    color: #f87171;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
    &:hover { text-decoration: underline; }
  }
}

.form-check { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--primary-text); cursor: pointer; user-select: none; input { accent-color: var(--accent-color); width: 15px; height: 15px; cursor: pointer; } }

.form-error { margin-top: 1rem; padding: 0.75rem 1rem; background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25); border-radius: 8px; color: #f87171; font-size: 0.875rem; }

.tag-picker { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.5rem; }
.tag-chip { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.3rem 0.65rem; border: 1px solid var(--divider); border-radius: 999px; font-size: 0.8rem; cursor: pointer; user-select: none; transition: border-color 0.15s, background 0.15s; &--selected { border-color: var(--accent-color); background: var(--accent-color-muted); color: var(--accent-text); } &__input { display: none; } }
.tag-add { display: flex; gap: 0.5rem; align-items: center; margin-top: 0.5rem; }

// ─── Footer ───────────────────────────────────────────────────────
.l-footer { background: $hero-bg; border-top: 1px solid $hero-divider; padding: 1.75rem 1.5rem; &__inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; } &__brand { display: flex; align-items: center; gap: 0.4rem; } &__hex { font-size: 1.1rem; color: $amber; } &__name { font-size: 0.9rem; font-weight: 700; color: $hero-text; letter-spacing: -0.02em; } &__copy { font-size: 0.8rem; color: $hero-muted-50; padding-bottom: 0; } }
</style>
