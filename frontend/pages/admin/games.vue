<template>
  <main>
    <h1>Spiele verwalten</h1>
    <NuxtLink to="/admin">← Admin</NuxtLink>

    <UiButton @click="openCreate">Spiel hinzufügen</UiButton>

    <div v-if="loading">Lädt...</div>

    <table v-else>
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
            <UiButton size="sm" variant="secondary" @click="openEdit(game)">Bearbeiten</UiButton>
            <UiButton size="sm" variant="danger" @click="remove(game.id)">Löschen</UiButton>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Formular (Create / Edit) -->
    <div v-if="form.open">
      <h2>{{ form.id ? 'Spiel bearbeiten' : 'Spiel hinzufügen' }}</h2>

      <UiInput v-model="form.title" label="Titel" required />
      <UiInput v-model="form.slug" label="Slug" required />
      <UiInput v-model="form.description" label="Beschreibung" />

      <label>Kategorie
        <select v-model="form.category_id">
          <option :value="null">Keine</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
      </label>

      <UiInput v-model="form.min_players" label="Min. Spieler" type="number" />
      <UiInput v-model="form.max_players" label="Max. Spieler" type="number" />
      <UiInput v-model="form.duration_min" label="Dauer (Min.)" type="number" />

      <label>Schwierigkeit
        <select v-model="form.difficulty">
          <option value="">Keine</option>
          <option value="EASY">Leicht</option>
          <option value="MEDIUM">Mittel</option>
          <option value="HARD">Schwer</option>
          <option value="EXPERT">Experte</option>
        </select>
      </label>

      <UiInput v-model="form.language" label="Sprache" />
      <UiInput v-model="form.year" label="Jahr" type="number" />

      <label>Coverbild
        <input type="file" accept="image/*" @change="onFileChange" />
      </label>

      <label>
        <input v-model="form.is_active" type="checkbox" />
        Aktiv
      </label>

      <p v-if="formError" role="alert">{{ formError }}</p>

      <UiButton :loading="saving" @click="save">Speichern</UiButton>
      <UiButton variant="secondary" @click="closeForm">Abbrechen</UiButton>
    </div>
  </main>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'

definePageMeta({ middleware: ['auth', 'admin'] })

const { fetchAdminGames, createGame, updateGame, deleteGame, fetchAdminCategories } = useAdmin()

interface Game {
  id: number
  title: string
  slug: string
  description: string | null
  category: { id: number; name: string } | null
  copies_count: number
  is_active: boolean
}

const loading = ref(true)
const saving = ref(false)
const formError = ref('')
const games = ref<Game[]>([])
const categories = ref<{ id: number; name: string }[]>([])

const form = reactive({
  open: false,
  id: null as number | null,
  title: '',
  slug: '',
  description: '',
  category_id: null as number | null,
  min_players: '',
  max_players: '',
  duration_min: '',
  difficulty: '',
  language: '',
  year: '',
  is_active: true,
  coverFile: null as File | null,
})

onMounted(async () => {
  await load()
  const catData = await fetchAdminCategories()
  categories.value = catData.data as { id: number; name: string }[]
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
  Object.assign(form, { open: true, id: null, title: '', slug: '', description: '', category_id: null, min_players: '', max_players: '', duration_min: '', difficulty: '', language: '', year: '', is_active: true, coverFile: null })
  formError.value = ''
}

function openEdit(game: Game) {
  Object.assign(form, { open: true, id: game.id, title: game.title, slug: game.slug, description: game.description ?? '', category_id: game.category?.id ?? null, is_active: game.is_active, coverFile: null })
  formError.value = ''
}

function closeForm() {
  form.open = false
}

function onFileChange(e: Event) {
  form.coverFile = (e.target as HTMLInputElement).files?.[0] ?? null
}

async function save() {
  saving.value = true
  formError.value = ''
  try {
    const fd = new FormData()
    const fields = ['title', 'slug', 'description', 'category_id', 'min_players', 'max_players', 'duration_min', 'difficulty', 'language', 'year'] as const
    fields.forEach((f) => { if (form[f] !== '' && form[f] !== null) fd.append(f, String(form[f])) })
    fd.append('is_active', form.is_active ? '1' : '0')
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
