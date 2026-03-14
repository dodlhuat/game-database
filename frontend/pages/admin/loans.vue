<template>
  <main>
    <h1>Ausleihen</h1>
    <NuxtLink to="/admin">← Admin</NuxtLink>

    <div>
      <label>Status
        <select v-model="statusFilter" @change="load">
          <option value="">Alle</option>
          <option value="ACTIVE">Aktiv</option>
          <option value="EXTENDED">Verlängert</option>
          <option value="OVERDUE">Überfällig</option>
          <option value="RETURNED">Zurückgegeben</option>
        </select>
      </label>
    </div>

    <div v-if="loading">Lädt...</div>

    <table v-else>
      <thead>
        <tr>
          <th>Mitglied</th>
          <th>Spiel</th>
          <th>Ausleihdatum</th>
          <th>Fällig</th>
          <th>Status</th>
          <th>Aktionen</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="loan in loans" :key="loan.id">
          <td>{{ loan.user?.name }}</td>
          <td>{{ loan.game?.title }}</td>
          <td>{{ formatDate(loan.start_date) }}</td>
          <td>{{ formatDate(loan.due_date) }}</td>
          <td>
            <UiBadge :variant="statusVariant(loan.status)">{{ loan.status }}</UiBadge>
          </td>
          <td>
            <UiButton
              v-if="['ACTIVE', 'EXTENDED'].includes(loan.status)"
              size="sm"
              variant="danger"
              @click="setOverdue(loan.id)"
            >
              Als überfällig markieren
            </UiButton>
          </td>
        </tr>
      </tbody>
    </table>
  </main>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

definePageMeta({ middleware: ['auth', 'admin'] })

const { fetchAdminLoans, markOverdue } = useAdmin()

const loading = ref(true)
const statusFilter = ref('')
const loans = ref<{ id: number; user: { name: string } | null; game: { title: string } | null; start_date: string; due_date: string; status: string }[]>([])

onMounted(load)

async function load() {
  loading.value = true
  try {
    const data = await fetchAdminLoans(statusFilter.value ? { status: statusFilter.value } : undefined)
    loans.value = data.data as typeof loans.value
  } finally {
    loading.value = false
  }
}

async function setOverdue(id: number) {
  await markOverdue(id)
  await load()
}

function statusVariant(status: string) {
  const map: Record<string, 'available' | 'loaned' | 'pending' | 'default'> = {
    ACTIVE: 'available', EXTENDED: 'pending', OVERDUE: 'loaned', RETURNED: 'default',
  }
  return map[status] ?? 'default'
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString('de-DE')
}
</script>
