<template>
  <main class="content">
    <h1>Newsletter</h1>
    <NuxtLink to="/admin">← Admin</NuxtLink>

    <!-- Neuer Newsletter -->
    <div class="card">
      <div class="header"><h2>Neuen Newsletter senden</h2></div>
      <UiInput v-model="form.subject" label="Betreff" />
      <label>Inhalt</label>
      <textarea v-model="form.body" rows="8"></textarea>
      <div v-if="successMsg" class="alert alert-success">{{ successMsg }}</div>
      <button class="button-primary" :disabled="sending" @click="send">Senden</button>
    </div>

    <!-- Versandhistorie -->
    <div class="card">
      <div class="header"><h2>Versandhistorie</h2></div>
      <div v-if="loading" class="center"><div class="spinner"></div></div>
      <div v-else-if="!newsletters.length" class="alert alert-default">Noch keine Newsletter versandt.</div>
      <div v-else class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Betreff</th>
              <th>Versandt am</th>
              <th>Empfänger</th>
              <th>Von</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="nl in newsletters" :key="nl.id">
              <td>{{ nl.subject }}</td>
              <td>{{ formatDate(nl.sent_at) }}</td>
              <td>{{ nl.recipient_count }}</td>
              <td>{{ nl.sender?.name ?? '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

definePageMeta({ middleware: ['auth', 'admin'] })

const { fetchNewsletters, sendNewsletter } = useAdmin()

interface Newsletter {
  id: number
  subject: string
  sent_at: string
  recipient_count: number
  sender: { name: string } | null
}

const loading = ref(true)
const sending = ref(false)
const newsletters = ref<Newsletter[]>([])
const successMsg = ref('')
const form = ref({ subject: '', body: '' })

onMounted(load)

async function load() {
  loading.value = true
  try {
    const data = await fetchNewsletters()
    newsletters.value = data.data as Newsletter[]
  } finally {
    loading.value = false
  }
}

async function send() {
  if (!form.value.subject || !form.value.body) return
  sending.value = true
  successMsg.value = ''
  try {
    await sendNewsletter(form.value.subject, form.value.body)
    form.value = { subject: '', body: '' }
    successMsg.value = 'Newsletter wurde in die Warteschlange eingereiht.'
    await load()
  } finally {
    sending.value = false
  }
}

function formatDate(iso?: string) {
  return iso ? new Date(iso).toLocaleDateString('de-DE', { dateStyle: 'medium' }) : '—'
}
</script>
