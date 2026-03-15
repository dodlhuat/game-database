<template>
  <main>
    <h1>{{ error.statusCode === 404 ? 'Seite nicht gefunden' : 'Ein Fehler ist aufgetreten' }}</h1>
    <p v-if="error.statusCode === 404">Die aufgerufene Seite existiert nicht.</p>
    <p v-else>Bitte versuche es später erneut.</p>
    <NuxtLink to="/">Zur Startseite</NuxtLink>
  </main>
</template>

<script setup lang="ts">
import type { NuxtError } from '#app'

defineProps<{ error: NuxtError }>()
</script>
