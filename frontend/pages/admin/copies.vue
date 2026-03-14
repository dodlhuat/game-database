<template>
  <main>
    <h1>Kopien verwalten</h1>
    <NuxtLink to="/admin">← Admin</NuxtLink>

    <UiButton @click="openCreate">Kopie hinzufügen</UiButton>

    <div v-if="loading">Lädt...</div>

    <table v-else>
      <thead>
        <tr>
          <th>Spiel</th>
          <th>Zustand</th>
          <th>QR-Code</th>
          <th>Verfügbar</th>
          <th>Aktionen</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="copy in copies" :key="copy.id">
          <td>{{ copy.game?.title }}</td>
          <td>{{ copy.condition }}</td>
          <td>{{ copy.qr_code ?? '—' }}</td>
          <td>
            <UiBadge :variant="copy.is_available ? 'available' : 'loaned'">
              {{ copy.is_available ? 'Ja' : 'Nein' }}
            </UiBadge>
          </td>
          <td>
            <UiButton size="sm" variant="secondary" @click="openEdit(copy)">Bearbeiten</UiButton>
            <UiButton size="sm" variant="danger" @click="remove(copy.id)">Löschen</UiButton>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="form.open">
      <h2>{{ form.id ? 'Kopie bearbeiten' : 'Kopie hinzufügen' }}</h2>

      <label>Spiel
        <select v-model="form.game_id" required>
          <option :value="null">Bitte wählen</option>
          <option v-for="game in games" :key="game.id" :value="game.id">{{ game.title }}</option>
        </select>
      </label>

      <label>Zustand
        <select v-model="form.condition">
          <option value="GOOD">Gut</option>
          <option value="WORN">Abgenutzt</option>
          <option value="DAMAGED">Beschädigt</option>
          <option value="LOCKED">Gesperrt</option>
        </select>
      </label>

      <UiInput v-model="form.qr_code" label="QR-Code (optional, wird automatisch generiert)" />
      <UiInput v-model="form.notes" label="Notizen" />

      <p v-if="formError" role="alert">{{ formError }}</p>
      <UiButton :loading="saving" @click="save">Speichern</UiButton>
      <UiButton variant="secondary" @click="form.open = false">Abbrechen</UiButton>
    </div>
  </main>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'

definePageMeta({ middleware: ['auth', 'admin'] })

const { fetchCopies, createCopy, updateCopy, deleteCopy, fetchAdminGames } = useAdmin()

interface Copy {
  id: number
  game: { id: number; title: string } | null
  condition: string
  qr_code: string | null
  notes: string | null
  is_available: boolean
}

const loading = ref(true)
const saving = ref(false)
const formError = ref('')
const copies = ref<Copy[]>([])
const games = ref<{ id: number; title: string }[]>([])

const form = reactive({
  open: false,
  id: null as number | null,
  game_id: null as number | null,
  condition: 'GOOD',
  qr_code: '',
  notes: '',
})

onMounted(async () => {
  await load()
  const g = await fetchAdminGames()
  games.value = (g.data as { id: number; title: string }[])
})

async function load() {
  loading.value = true
  try {
    const data = await fetchCopies()
    copies.value = data.data as Copy[]
  } finally {
    loading.value = false
  }
}

function openCreate() {
  Object.assign(form, { open: true, id: null, game_id: null, condition: 'GOOD', qr_code: '', notes: '' })
  formError.value = ''
}

function openEdit(copy: Copy) {
  Object.assign(form, { open: true, id: copy.id, game_id: copy.game?.id ?? null, condition: copy.condition, qr_code: copy.qr_code ?? '', notes: copy.notes ?? '' })
  formError.value = ''
}

async function save() {
  saving.value = true
  formError.value = ''
  try {
    const payload = { game_id: form.game_id, condition: form.condition, qr_code: form.qr_code || undefined, notes: form.notes || undefined }
    form.id ? await updateCopy(form.id, payload) : await createCopy(payload)
    await load()
    form.open = false
  } catch (err: unknown) {
    formError.value = (err as { message?: string }).message ?? 'Fehler.'
  } finally {
    saving.value = false
  }
}

async function remove(id: number) {
  try {
    await deleteCopy(id)
    await load()
  } catch (err: unknown) {
    alert((err as { message?: string }).message ?? 'Fehler.')
  }
}
</script>
