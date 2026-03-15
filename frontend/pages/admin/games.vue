<template>
  <main class="content">
    <h1>Spiele verwalten</h1>
    <NuxtLink to="/admin">← Admin</NuxtLink>

    <button class="button-primary" @click="openCreate">Spiel hinzufügen</button>

    <div v-if="loading" class="center"><div class="spinner"></div></div>

    <div v-else class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Titel</th>
            <th>Kategorie</th>
            <th>Kopien</th>
            <th>Aktiv</th>
            <th>Aktionen</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="game in games" :key="game.id">
            <td>{{ game.title }}</td>
            <td>{{ game.category?.name ?? '—' }}</td>
            <td>{{ game.copies_count }}</td>
            <td>{{ game.is_active ? 'Ja' : 'Nein' }}</td>
            <td>
              <button class="button" @click="openEdit(game)">Bearbeiten</button>
              <button class="button-error" @click="remove(game.id)">Löschen</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Formular (Create / Edit) -->
    <div v-if="form.open" class="card">
      <div class="header">
        <h2>{{ form.id ? 'Spiel bearbeiten' : 'Spiel hinzufügen' }}</h2>
      </div>

      <UiInput v-model="form.title" label="Titel" required />
      <UiInput v-model="form.slug" label="Slug" required />
      <UiInput v-model="form.short_description" label="Kurzbeschreibung (max. 500 Zeichen)" />
      <UiInput v-model="form.description" label="Beschreibung (lang)" />

      <label>Kategorie
        <select v-model="form.category_id">
          <option :value="null">Keine</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
      </label>

      <div class="row">
        <div class="column"><UiInput v-model="form.min_players" label="Min. Spieler" type="number" /></div>
        <div class="column"><UiInput v-model="form.max_players" label="Max. Spieler" type="number" /></div>
        <div class="column"><UiInput v-model="form.min_age" label="Ab Alter" type="number" /></div>
      </div>

      <div class="row">
        <div class="column"><UiInput v-model="form.duration_min" label="Spielzeit min. (Min.)" type="number" /></div>
        <div class="column"><UiInput v-model="form.duration_max" label="Spielzeit max. (Min.)" type="number" /></div>
      </div>

      <label>Schwierigkeit
        <select v-model="form.difficulty">
          <option value="">Keine</option>
          <option value="EASY">Leicht</option>
          <option value="MEDIUM">Mittel</option>
          <option value="HARD">Schwer</option>
          <option value="EXPERT">Experte</option>
        </select>
      </label>

      <div class="row">
        <div class="column"><UiInput v-model="form.language" label="Sprache" /></div>
        <div class="column"><UiInput v-model="form.year" label="Jahr" type="number" /></div>
      </div>

      <!-- Tags -->
      <div>
        <label>Tags</label>
        <div class="tag-picker">
          <label
            v-for="tag in allTags"
            :key="tag.id"
            class="tag-chip"
            :class="{ 'tag-chip--selected': form.tag_ids.includes(tag.id) }"
          >
            <input
              type="checkbox"
              :value="tag.id"
              v-model="form.tag_ids"
              class="tag-chip__input"
            />
            {{ tag.name }}
          </label>
        </div>
        <div class="tag-add">
          <UiInput v-model="newTagName" placeholder="Neuer Tag…" style="flex:1" />
          <button class="button" :disabled="!newTagName.trim()" @click="addTag">Hinzufügen</button>
        </div>
      </div>

      <label>Coverbild
        <input type="file" accept="image/*" @change="onFileChange" />
      </label>

      <label>
        <input v-model="form.is_active" type="checkbox" class="styled-checkbox" />
        Aktiv
      </label>

      <div v-if="formError" class="alert alert-error" role="alert">{{ formError }}</div>

      <div class="row spacing-top">
        <UiButton :loading="saving" @click="save">Speichern</UiButton>
        <button class="button" @click="closeForm">Abbrechen</button>
      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'

definePageMeta({ middleware: ['auth', 'admin'] })

const { fetchAdminGames, createGame, updateGame, deleteGame, fetchAdminCategories, fetchAdminTags, createTag } = useAdmin()

interface Game {
  id: number
  title: string
  slug: string
  description: string | null
  short_description: string | null
  category: { id: number; name: string } | null
  copies_count: number
  is_active: boolean
  min_players: number | null
  max_players: number | null
  min_age: number | null
  duration_min: number | null
  duration_max: number | null
  difficulty: string | null
  language: string | null
  year: number | null
  tags: { id: number; name: string }[]
}

const loading = ref(true)
const saving = ref(false)
const formError = ref('')
const games = ref<Game[]>([])
const categories = ref<{ id: number; name: string }[]>([])
const allTags = ref<{ id: number; name: string; slug: string }[]>([])
const newTagName = ref('')

const form = reactive({
  open: false,
  id: null as number | null,
  title: '',
  slug: '',
  description: '',
  short_description: '',
  category_id: null as number | null,
  min_players: '',
  max_players: '',
  min_age: '',
  duration_min: '',
  duration_max: '',
  difficulty: '',
  language: '',
  year: '',
  is_active: true,
  tag_ids: [] as number[],
  coverFile: null as File | null,
})

onMounted(async () => {
  await load()
  const [catData, tagData] = await Promise.all([fetchAdminCategories(), fetchAdminTags()])
  categories.value = catData.data as { id: number; name: string }[]
  allTags.value = tagData.data
})

async function load() {
  loading.value = true
  try {
    const data = await fetchAdminGames()
    games.value = data.data as Game[]
  } finally {
    loading.value = false
  }
}

function openCreate() {
  Object.assign(form, {
    open: true, id: null, title: '', slug: '', description: '', short_description: '',
    category_id: null, min_players: '', max_players: '', min_age: '',
    duration_min: '', duration_max: '', difficulty: '', language: '', year: '',
    is_active: true, tag_ids: [], coverFile: null,
  })
  formError.value = ''
}

function openEdit(game: Game) {
  Object.assign(form, {
    open: true,
    id: game.id,
    title: game.title,
    slug: game.slug,
    description: game.description ?? '',
    short_description: game.short_description ?? '',
    category_id: game.category?.id ?? null,
    min_players: game.min_players ?? '',
    max_players: game.max_players ?? '',
    min_age: game.min_age ?? '',
    duration_min: game.duration_min ?? '',
    duration_max: game.duration_max ?? '',
    difficulty: game.difficulty ?? '',
    language: game.language ?? '',
    year: game.year ?? '',
    is_active: game.is_active,
    tag_ids: game.tags?.map(t => t.id) ?? [],
    coverFile: null,
  })
  formError.value = ''
}

function closeForm() {
  form.open = false
}

function onFileChange(e: Event) {
  form.coverFile = (e.target as HTMLInputElement).files?.[0] ?? null
}

async function addTag() {
  const name = newTagName.value.trim()
  if (!name) return
  try {
    const res = await createTag(name)
    allTags.value.push(res.data)
    form.tag_ids.push(res.data.id)
    newTagName.value = ''
  } catch {
    // tag may already exist — just ignore
  }
}

async function save() {
  saving.value = true
  formError.value = ''
  try {
    const fd = new FormData()
    const fields = [
      'title', 'slug', 'description', 'short_description', 'category_id',
      'min_players', 'max_players', 'min_age', 'duration_min', 'duration_max',
      'difficulty', 'language', 'year',
    ] as const
    fields.forEach((f) => { if (form[f] !== '' && form[f] !== null) fd.append(f, String(form[f])) })
    fd.append('is_active', form.is_active ? '1' : '0')
    form.tag_ids.forEach(id => fd.append('tag_ids[]', String(id)))
    if (form.coverFile) fd.append('cover_image', form.coverFile)

    if (form.id) {
      await updateGame(form.id, fd)
    } else {
      await createGame(fd)
    }
    await load()
    closeForm()
  } catch (err: unknown) {
    const e = err as { message?: string }
    formError.value = e.message ?? 'Fehler beim Speichern.'
  } finally {
    saving.value = false
  }
}

async function remove(id: number) {
  await deleteGame(id)
  await load()
}
</script>

<style scoped>
.tag-picker {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin: 0.5rem 0;
}

.tag-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.3rem 0.65rem;
  border: 1px solid var(--divider);
  border-radius: 999px;
  font-size: 0.85rem;
  cursor: pointer;
  user-select: none;
  transition: border-color 0.15s, background 0.15s;

  &--selected {
    border-color: var(--accent-color);
    background: var(--accent-color-muted);
    color: var(--accent-text);
  }

  &__input {
    display: none;
  }
}

.tag-add {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  margin-top: 0.5rem;
}
</style>
