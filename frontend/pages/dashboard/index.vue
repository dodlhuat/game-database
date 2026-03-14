<template>
  <main>
    <h1>Mein Dashboard</h1>
    <p>Willkommen, {{ auth.user?.name }}</p>

    <div v-if="loading">Lädt...</div>

    <div v-else>
      <!-- Statistiken -->
      <section>
        <h2>Übersicht</h2>
        <ul>
          <li>Aktive Ausleihen: {{ data?.stats.active_loans_count }}</li>
          <li>Überfällig: {{ data?.stats.overdue_count }}</li>
          <li>Reservierungen: {{ data?.stats.reservations_count }}</li>
          <li>Ausleihen gesamt: {{ data?.stats.total_loans }}</li>
        </ul>
      </section>

      <!-- Aktive Ausleihen -->
      <section>
        <h2>Aktive Ausleihen</h2>
        <p v-if="!activeLoans.length">Keine aktiven Ausleihen.</p>
        <ul v-else>
          <li v-for="loan in activeLoans" :key="loan.id">
            <NuxtLink :to="`/games/${loan.game?.slug}`">{{ loan.game?.title }}</NuxtLink>
            <UiBadge :variant="loanStatusVariant(loan)">{{ loanStatusLabel(loan) }}</UiBadge>
            <span>Fällig: {{ formatDate(loan.due_date) }}</span>

            <!-- Verlängerungsantrag -->
            <template v-if="loan.status !== 'RETURNED'">
              <template v-if="pendingExtension(loan)">
                <UiBadge variant="pending">Verlängerung beantragt</UiBadge>
              </template>
              <template v-else>
                <UiButton size="sm" variant="secondary" @click="openExtension(loan)">
                  Verlängerung beantragen
                </UiButton>
              </template>
            </template>

            <UiButton size="sm" @click="openReturn(loan)">Zurückgeben</UiButton>
          </li>
        </ul>
      </section>

      <!-- Reservierungen -->
      <section>
        <h2>Meine Reservierungen</h2>
        <p v-if="!reservations.length">Keine aktiven Reservierungen.</p>
        <ul v-else>
          <li v-for="res in reservations" :key="res.id">
            {{ res.game?.title }} — Position {{ res.position }}
            <UiButton size="sm" variant="danger" @click="cancelReservation(res.id)">Entfernen</UiButton>
          </li>
        </ul>
      </section>

      <!-- Ausleihhistorie -->
      <section>
        <h2>Letzte Ausleihen</h2>
        <p v-if="!loanHistory.length">Noch keine zurückgegebenen Ausleihen.</p>
        <ul v-else>
          <li v-for="loan in loanHistory" :key="loan.id">
            {{ loan.game?.title }} — {{ formatDate(loan.returned_at!) }}
          </li>
        </ul>
      </section>
    </div>

    <!-- Rückgabe-Dialog -->
    <div v-if="returnLoan">
      <h3>Spiel zurückgeben: {{ returnLoan.game?.title }}</h3>
      <select v-model="returnCondition">
        <option value="GOOD">Gut</option>
        <option value="WORN">Abgenutzt</option>
        <option value="DAMAGED">Beschädigt</option>
      </select>
      <UiButton :loading="returning" @click="submitReturn">Bestätigen</UiButton>
      <UiButton variant="secondary" @click="returnLoan = null">Abbrechen</UiButton>
    </div>

    <!-- Verlängerungs-Dialog -->
    <div v-if="extensionLoan">
      <h3>Verlängerung beantragen: {{ extensionLoan.game?.title }}</h3>
      <UiInput v-model="extensionDate" type="date" label="Neues Rückgabedatum" />
      <UiButton :loading="extending" @click="submitExtension">Beantragen</UiButton>
      <UiButton variant="secondary" @click="extensionLoan = null">Abbrechen</UiButton>
    </div>

    <UiButton variant="secondary" @click="handleLogout">Abmelden</UiButton>
  </main>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { Loan, DashboardData } from '~/composables/useLoans'

definePageMeta({ middleware: ['auth'] })

const auth = useAuthStore()
const { logout } = useAuth()
const { fetchDashboard, returnLoan: doReturn, requestExtension, removeReservation } = useLoans()

const loading = ref(true)
const data = ref<DashboardData | null>(null)

const returnLoan = ref<Loan | null>(null)
const returnCondition = ref('GOOD')
const returning = ref(false)

const extensionLoan = ref<Loan | null>(null)
const extensionDate = ref('')
const extending = ref(false)

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
