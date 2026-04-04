<template>
  <main class="content">
    <NuxtLink to="/" class="back-link">← Zurück zur Startseite</NuxtLink>
    <div class="card">
      <div class="header">
        <h1>Nutzungsbedingungen</h1>
      </div>
      <div class="body">
        <div v-if="loading" class="spinner"></div>
        <div v-else-if="!terms" class="alert alert-default">
          <p>Nutzungsbedingungen sind derzeit nicht verfügbar.</p>
        </div>
        <div v-else class="terms-content">
          <small v-if="terms">Version {{ terms.version }} — veröffentlicht am {{ formatDate(terms.published_at) }}</small>
          {{ terms.content }}
        </div>
      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

const api = useApi()

interface TermsVersion {
  version: string
  content: string
  published_at: string
}

const loading = ref(true)
const terms = ref<TermsVersion | null>(null)

onMounted(async () => {
  try {
    terms.value = await api.get<TermsVersion>('/terms')
  } catch {
    terms.value = null
  } finally {
    loading.value = false
  }
})

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString('de-DE', { dateStyle: 'long' })
}
</script>

<style scoped>
.terms-content {
  white-space: pre-line;
}
</style>
