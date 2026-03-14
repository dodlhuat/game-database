<template>
  <main>
    <h1>Admin Dashboard</h1>

    <div v-if="loading">Lädt...</div>

    <div v-else-if="stats">
      <section>
        <h2>Mitglieder</h2>
        <ul>
          <li>Gesamt: {{ stats.users.total }}</li>
          <li>
            Ausstehend:
            <NuxtLink to="/admin/users?status=PENDING">
              {{ stats.users.pending }}
            </NuxtLink>
            <UiBadge v-if="stats.users.pending > 0" variant="pending">!</UiBadge>
          </li>
          <li>Aktiv: {{ stats.users.active }}</li>
        </ul>
      </section>

      <section>
        <h2>Ausleihen</h2>
        <ul>
          <li>Aktiv: {{ stats.loans.active }}</li>
          <li>
            Überfällig:
            <NuxtLink to="/admin/loans?status=OVERDUE">{{ stats.loans.overdue }}</NuxtLink>
            <UiBadge v-if="stats.loans.overdue > 0" variant="loaned">!</UiBadge>
          </li>
          <li>Heute zurückgegeben: {{ stats.loans.returned_today }}</li>
        </ul>
      </section>

      <section>
        <h2>Verlängerungsanträge</h2>
        <ul>
          <li>
            Offen:
            <NuxtLink to="/admin/extensions">{{ stats.extensions.pending }}</NuxtLink>
            <UiBadge v-if="stats.extensions.pending > 0" variant="pending">!</UiBadge>
          </li>
        </ul>
      </section>

      <nav>
        <NuxtLink to="/admin/users">Mitglieder verwalten</NuxtLink>
        <NuxtLink to="/admin/games">Spiele verwalten</NuxtLink>
        <NuxtLink to="/admin/copies">Kopien verwalten</NuxtLink>
        <NuxtLink to="/admin/loans">Ausleihen</NuxtLink>
        <NuxtLink to="/admin/extensions">Verlängerungsanträge</NuxtLink>
      </nav>
    </div>
  </main>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import type { AdminStats } from '~/composables/useAdmin'

definePageMeta({ middleware: ['auth', 'admin'] })

const { fetchStats } = useAdmin()
const stats = ref<AdminStats | null>(null)
const loading = ref(true)

onMounted(async () => {
  try {
    stats.value = await fetchStats()
  } finally {
    loading.value = false
  }
})
</script>
