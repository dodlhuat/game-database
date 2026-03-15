<template>
  <main class="content">
    <h1>Mein Dashboard</h1>
    <p>Willkommen, {{ auth.user?.name }}</p>

    <div v-if="loading" class="center"><div class="spinner"></div></div>

    <div v-else>
      <!-- Statistiken -->
      <div class="row">
        <div class="card column">
          <strong>Aktive Ausleihen</strong>
          <p>{{ data?.stats.active_loans_count }}</p>
        </div>
        <div class="card column">
          <strong>Überfällig</strong>
          <p>{{ data?.stats.overdue_count }}</p>
        </div>
        <div class="card column">
          <strong>Reservierungen</strong>
          <p>{{ data?.stats.reservations_count }}</p>
        </div>
        <div class="card column">
          <strong>Ausleihen gesamt</strong>
          <p>{{ data?.stats.total_loans }}</p>
        </div>
      </div>

      <!-- Aktive Ausleihen -->
      <div class="card">
        <div class="header"><h2>Aktive Ausleihen</h2></div>
        <p v-if="!activeLoans.length">Keine aktiven Ausleihen.</p>
        <div v-else class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Spiel</th>
                <th>Status</th>
                <th>Fällig</th>
                <th>Aktionen</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="loan in activeLoans" :key="loan.id">
                <td><NuxtLink :to="`/games/${loan.game?.slug}`">{{ loan.game?.title }}</NuxtLink></td>
                <td><UiBadge :variant="loanStatusVariant(loan)">{{ loanStatusLabel(loan) }}</UiBadge></td>
                <td>{{ formatDate(loan.due_date) }}</td>
                <td>
                  <template v-if="loan.status !== 'RETURNED'">
                    <UiBadge v-if="pendingExtension(loan)" variant="pending">Verlängerung beantragt</UiBadge>
                    <button v-else class="button" @click="openExtension(loan)">Verlängerung beantragen</button>
                  </template>
                  <button class="button" @click="openReturn(loan)">Zurückgeben</button>
                  <button class="button-error" @click="openDamage(loan)">Schaden melden</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Reservierungen -->
      <div class="card">
        <div class="header"><h2>Meine Reservierungen</h2></div>
        <p v-if="!reservations.length">Keine aktiven Reservierungen.</p>
        <div v-else class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Spiel</th>
                <th>Position</th>
                <th>Aktionen</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="res in reservations" :key="res.id">
                <td>{{ res.game?.title }}</td>
                <td>{{ res.position }}</td>
                <td><button class="button-error" @click="cancelReservation(res.id)">Entfernen</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Ausleihhistorie -->
      <div class="card">
        <div class="header"><h2>Letzte Ausleihen</h2></div>
        <p v-if="!loanHistory.length">Noch keine zurückgegebenen Ausleihen.</p>
        <div v-else class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Spiel</th>
                <th>Zurückgegeben</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="loan in loanHistory" :key="loan.id">
                <td>{{ loan.game?.title }}</td>
                <td>{{ formatDate(loan.returned_at!) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Rückgabe-Dialog -->
    <div v-if="returnLoan" class="card">
      <div class="header"><h3>Spiel zurückgeben: {{ returnLoan.game?.title }}</h3></div>
      <label>Zustand</label>
      <select v-model="returnCondition">
        <option value="GOOD">Gut</option>
        <option value="WORN">Abgenutzt</option>
        <option value="DAMAGED">Beschädigt</option>
      </select>
      <div class="row spacing-top">
        <UiButton :loading="returning" @click="submitReturn">Bestätigen</UiButton>
        <button class="button" @click="returnLoan = null">Abbrechen</button>
      </div>
    </div>

    <!-- Schadensmeldungs-Dialog -->
    <div v-if="damageLoan" class="card">
      <div class="header"><h3>Schaden melden: {{ damageLoan.game?.title }}</h3></div>
      <UiInput v-model="damageDescription" label="Beschreibung" />
      <UiInput v-model="damagePhotoUrl" label="Foto-URL (optional)" />
      <div class="row spacing-top">
        <UiButton :loading="reporting" @click="submitDamage">Melden</UiButton>
        <button class="button" @click="damageLoan = null">Abbrechen</button>
      </div>
    </div>

    <!-- Verlängerungs-Dialog -->
    <div v-if="extensionLoan" class="card">
      <div class="header"><h3>Verlängerung beantragen: {{ extensionLoan.game?.title }}</h3></div>
      <UiInput v-model="extensionDate" type="date" label="Neues Rückgabedatum" />
      <div class="row spacing-top">
        <UiButton :loading="extending" @click="submitExtension">Beantragen</UiButton>
        <button class="button" @click="extensionLoan = null">Abbrechen</button>
      </div>
    </div>

    <div class="row spacing-top">
      <NuxtLink to="/account">Konto-Einstellungen</NuxtLink>
      <button class="button" @click="handleLogout">Abmelden</button>
    </div>
  </main>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { Loan, DashboardData } from '~/composables/useLoans'

definePageMeta({ middleware: ['auth'] })

const auth = useAuthStore()
const { logout } = useAuth()
const { fetchDashboard, returnLoan: doReturn, requestExtension, removeReservation, reportDamage } = useLoans()

const loading = ref(true)
const data = ref<DashboardData | null>(null)

const returnLoan = ref<Loan | null>(null)
const returnCondition = ref('GOOD')
const returning = ref(false)

const extensionLoan = ref<Loan | null>(null)
const extensionDate = ref('')
const extending = ref(false)

const damageLoan = ref<Loan | null>(null)
const damageDescription = ref('')
const damagePhotoUrl = ref('')
const reporting = ref(false)

const activeLoans = computed(() => data.value?.active_loans.data ?? [])
const loanHistory = computed(() => data.value?.loan_history.data ?? [])
const reservations = computed(() => (data.value?.reservations as { data: { id: number; position: number; game?: { title: string; slug: string } }[] })?.data ?? [])

onMounted(async () => {
  try {
    data.value = await fetchDashboard()
  } finally {
    loading.value = false
  }
})

function openReturn(loan: Loan) {
  returnLoan.value = loan
  returnCondition.value = 'GOOD'
}

async function submitReturn() {
  if (!returnLoan.value) return
  returning.value = true
  try {
    await doReturn(returnLoan.value.id, returnCondition.value)
    data.value = await fetchDashboard()
    returnLoan.value = null
  } finally {
    returning.value = false
  }
}

function openExtension(loan: Loan) {
  extensionLoan.value = loan
  extensionDate.value = ''
}

async function submitExtension() {
  if (!extensionLoan.value || !extensionDate.value) return
  extending.value = true
  try {
    await requestExtension(extensionLoan.value.id, extensionDate.value)
    data.value = await fetchDashboard()
    extensionLoan.value = null
  } finally {
    extending.value = false
  }
}

function openDamage(loan: Loan) {
  damageLoan.value = loan
  damageDescription.value = ''
  damagePhotoUrl.value = ''
}

async function submitDamage() {
  if (!damageLoan.value || !damageDescription.value) return
  reporting.value = true
  try {
    await reportDamage(damageLoan.value.id, damageDescription.value, damagePhotoUrl.value || undefined)
    damageLoan.value = null
  } finally {
    reporting.value = false
  }
}

async function cancelReservation(id: number) {
  await removeReservation(id)
  data.value = await fetchDashboard()
}

async function handleLogout() {
  await logout()
}

function pendingExtension(loan: Loan) {
  return loan.extensions.some((e) => e.status === 'PENDING')
}

function loanStatusVariant(loan: Loan) {
  if (loan.status === 'OVERDUE') return 'loaned'
  if (loan.status === 'EXTENDED') return 'pending'
  return 'available'
}

function loanStatusLabel(loan: Loan) {
  const map: Record<string, string> = {
    ACTIVE: 'Aktiv',
    EXTENDED: 'Verlängert',
    OVERDUE: 'Überfällig',
    RETURNED: 'Zurückgegeben',
  }
  return map[loan.status] ?? loan.status
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString('de-DE')
}
</script>
