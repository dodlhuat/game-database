<template>
  <main class="content">
    <h1>Admin Dashboard</h1>

    <div v-if="loading" class="center"><div class="spinner"></div></div>

    <div v-else-if="stats">
      <!-- Stats -->
      <div class="row">
        <div class="card column">
          <strong>Mitglieder</strong>
          <p>Gesamt: {{ stats.users.total }}</p>
          <p>
            Aktiv: {{ stats.users.active }} &nbsp;
            <NuxtLink to="/admin/users?status=PENDING">
              Ausstehend: {{ stats.users.pending }}
            </NuxtLink>
            <UiBadge v-if="stats.users.pending > 0" variant="pending">!</UiBadge>
          </p>
        </div>

        <div class="card column">
          <strong>Ausleihen</strong>
          <p>Aktiv: {{ stats.loans.active }}</p>
          <p>
            <NuxtLink to="/admin/loans?status=OVERDUE">Überfällig: {{ stats.loans.overdue }}</NuxtLink>
            <UiBadge v-if="stats.loans.overdue > 0" variant="loaned">!</UiBadge>
          </p>
          <p>Heute zurückgegeben: {{ stats.loans.returned_today }}</p>
        </div>

        <div class="card column">
          <strong>Verlängerungsanträge</strong>
          <p>
            <NuxtLink to="/admin/extensions">Offen: {{ stats.extensions.pending }}</NuxtLink>
            <UiBadge v-if="stats.extensions.pending > 0" variant="pending">!</UiBadge>
          </p>
        </div>
      </div>

      <!-- Navigation -->
      <div class="card">
        <div class="header"><h2>Verwaltung</h2></div>
        <nav class="row">
          <NuxtLink class="button column" to="/admin/users">Mitglieder</NuxtLink>
          <NuxtLink class="button column" to="/admin/games">Spiele</NuxtLink>
          <NuxtLink class="button column" to="/admin/copies">Kopien</NuxtLink>
          <NuxtLink class="button column" to="/admin/loans">Ausleihen</NuxtLink>
          <NuxtLink class="button column" to="/admin/extensions">Verlängerungsanträge</NuxtLink>
          <NuxtLink class="button column" to="/admin/newsletters">Newsletter</NuxtLink>
          <NuxtLink class="button column" to="/admin/damage-reports">Schadensmeldungen</NuxtLink>
        </nav>
      </div>
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
