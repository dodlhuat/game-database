<template>
  <main class="content">
    <h1>Mitgliederverwaltung</h1>
    <NuxtLink to="/admin">← Admin</NuxtLink>

    <div v-if="pending" class="center"><div class="spinner"></div></div>

    <div v-else class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>E-Mail</th>
            <th>Status</th>
            <th>Registriert</th>
            <th>Aktionen</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id">
            <td>{{ user.name }}</td>
            <td>{{ user.email }}</td>
            <td>
              <UiBadge :variant="statusVariant(user.status)">{{ user.status }}</UiBadge>
            </td>
            <td>{{ formatDate(user.created_at) }}</td>
            <td>
              <template v-if="user.status === 'PENDING'">
                <button class="button-primary" @click="approve(user.id)">Freischalten</button>
                <button class="button-error" @click="reject(user.id)">Ablehnen</button>
              </template>
              <template v-else-if="user.status === 'ACTIVE'">
                <button class="button-error" @click="suspend(user.id)">Sperren</button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </main>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

definePageMeta({ middleware: ['auth', 'admin'] })

interface User {
  id: number
  name: string
  email: string
  role: string
  status: string
  created_at: string
}

const api = useApi()
const users = ref<User[]>([])
const pending = ref(true)

onMounted(async () => {
  try {
    const data = await api.get<{ data: User[] }>('/admin/users')
    users.value = data.data
  } finally {
    pending.value = false
  }
})

async function approve(id: number) {
  await api.patch(`/admin/users/${id}/approve`)
  const user = users.value.find((u) => u.id === id)
  if (user) user.status = 'ACTIVE'
}

async function reject(id: number) {
  await api.patch(`/admin/users/${id}/reject`)
  const user = users.value.find((u) => u.id === id)
  if (user) user.status = 'REJECTED'
}

async function suspend(id: number) {
  await api.patch(`/admin/users/${id}/suspend`)
  const user = users.value.find((u) => u.id === id)
  if (user) user.status = 'SUSPENDED'
}

function statusVariant(status: string) {
  const map: Record<string, 'available' | 'loaned' | 'pending' | 'default'> = {
    ACTIVE: 'available',
    SUSPENDED: 'loaned',
    REJECTED: 'loaned',
    PENDING: 'pending',
  }
  return map[status] ?? 'default'
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString('de-DE')
}
</script>
