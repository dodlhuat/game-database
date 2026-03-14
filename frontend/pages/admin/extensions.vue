<template>
  <main>
    <h1>Verlängerungsanträge</h1>
    <NuxtLink to="/admin">← Admin</NuxtLink>

    <div v-if="loading">Lädt...</div>

    <p v-else-if="!extensions.length">Keine offenen Anträge.</p>

    <table v-else>
      <thead>
        <tr>
          <th>Mitglied</th>
          <th>Spiel</th>
          <th>Aktuell fällig</th>
          <th>Beantragt bis</th>
          <th>Beantragt am</th>
          <th>Aktionen</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="ext in extensions" :key="ext.id">
          <td>{{ ext.loan?.user?.name }}</td>
          <td>{{ ext.loan?.copy?.game?.title }}</td>
          <td>{{ formatDate(ext.loan?.due_date) }}</td>
          <td>{{ formatDate(ext.requested_due_date) }}</td>
          <td>{{ formatDate(ext.requested_at) }}</td>
          <td>
            <UiButton size="sm" @click="approve(ext.id)">Genehmigen</UiButton>
            <UiButton size="sm" variant="danger" @click="openReject(ext)">Ablehnen</UiButton>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Ablehnungs-Dialog -->
    <div v-if="rejectTarget">
      <h3>Ablehnen: {{ rejectTarget.loan?.copy?.game?.title }}</h3>
      <UiInput v-model="rejectNote" label="Begründung (optional)" />
      <UiButton variant="danger" :loading="processing" @click="submitReject">Ablehnen</UiButton>
      <UiButton variant="secondary" @click="rejectTarget = null">Abbrechen</UiButton>
    </div>
  </main>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

definePageMeta({ middleware: ['auth', 'admin'] })

const { fetchExtensions, approveExtension, rejectExtension } = useAdmin()

interface Extension {
  id: number
  requested_due_date: string
  requested_at: string
  loan: {
    due_date: string
    user: { name: string } | null
    copy: { game: { title: string } | null } | null
  } | null
}

const loading = ref(true)
const processing = ref(false)
const extensions = ref<Extension[]>([])
const rejectTarget = ref<Extension | null>(null)
const rejectNote = ref('')

onMounted(load)

async function load() {
  loading.value = true
  try {
    const data = await fetchExtensions('PENDING')
    extensions.value = data.data as Extension[]
  } finally {
    loading.value = false
  }
}

async function approve(id: number) {
  await approveExtension(id)
  await load()
}

function openReject(ext: Extension) {
  rejectTarget.value = ext
  rejectNote.value = ''
}

async function submitReject() {
  if (!rejectTarget.value) return
  processing.value = true
  try {
    await rejectExtension(rejectTarget.value.id, rejectNote.value || undefined)
    rejectTarget.value = null
    await load()
  } finally {
    processing.value = false
  }
}

function formatDate(iso?: string) {
  return iso ? new Date(iso).toLocaleDateString('de-DE') : '—'
}
</script>
