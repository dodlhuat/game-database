<template>
  <main class="content">
    <h1>Schadensmeldungen</h1>
    <NuxtLink to="/admin">← Admin</NuxtLink>

    <div v-if="loading" class="center"><div class="spinner"></div></div>

    <div v-else-if="!reports.length" class="alert alert-default">Keine Schadensmeldungen vorhanden.</div>

    <div v-else class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Mitglied</th>
            <th>Spiel</th>
            <th>Beschreibung</th>
            <th>Foto</th>
            <th>Gemeldet am</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="report in reports" :key="report.id">
            <td>{{ report.user?.name }}</td>
            <td>{{ report.loan?.copy?.game?.title ?? '—' }}</td>
            <td>{{ report.description }}</td>
            <td>
              <a v-if="report.photo_url" :href="report.photo_url" target="_blank" rel="noopener">Foto</a>
              <span v-else>—</span>
            </td>
            <td>{{ formatDate(report.created_at) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </main>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

definePageMeta({ middleware: ['auth', 'admin'] })

const { fetchDamageReports } = useAdmin()

interface DamageReport {
  id: number
  description: string
  photo_url: string | null
  created_at: string
  user: { name: string } | null
  loan: { copy: { game: { title: string } | null } | null } | null
}

const loading = ref(true)
const reports = ref<DamageReport[]>([])

onMounted(async () => {
  try {
    const data = await fetchDamageReports()
    reports.value = data.data as DamageReport[]
  } finally {
    loading.value = false
  }
})

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString('de-DE', { dateStyle: 'medium' })
}
</script>
