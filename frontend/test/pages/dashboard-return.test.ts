import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, type VueWrapper } from '@vue/test-utils'
import { useAuthStore } from '~/stores/auth'
import DashboardPage from '~/pages/dashboard/index.vue'
import type { DashboardData, Loan } from '~/composables/useLoans'

const loans = vi.hoisted(() => ({
  fetchDashboard: vi.fn(),
  returnLoan: vi.fn(),
  returnPackageLoan: vi.fn(),
  fetchReturnOptions: vi.fn(),
  requestExtension: vi.fn(),
  removeReservation: vi.fn(),
  reportDamage: vi.fn(),
}))
mockNuxtImport('useLoans', () => () => loans)

const notifyMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useDialogs', () => {
  return () => ({ confirmDialog: vi.fn(), notify: notifyMock })
})

// The condition dropdown is a basix widget; a plain select stands in for it
const stubs = {
  UiVirtualDropdown: {
    props: ['modelValue', 'options'],
    emits: ['update:modelValue'],
    template:
      '<select class="stub-condition" :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)"><option v-for="o in options" :key="o.value" :value="o.value">{{ o.label }}</option></select>',
  },
}

function loan(overrides: Partial<Loan> = {}): Loan {
  return {
    id: 1,
    game: { title: 'Catan', slug: 'catan' },
    start_date: '2026-11-01',
    due_date: '2026-11-29',
    returned_at: null,
    return_condition: null,
    deposit_tokens: 0,
    status: 'ACTIVE',
    is_overdue: false,
    extensions: [],
    can_extend: false,
    extension_blocked_reason: null,
    next_extension_cost: 0,
    extensions_used: 0,
    max_extensions: 2,
    package_loan_id: null,
    ...overrides,
  } as Loan
}

function dashboard(active: Loan[], reservations: unknown[] = []): DashboardData {
  return {
    active_loans: active,
    loan_history: [],
    reservations,
    stats: {
      total_loans: 1,
      active_loans_count: active.length,
      overdue_count: 0,
      reservations_count: 0,
      tokens_blocked: 0,
    },
  } as unknown as DashboardData
}

let wrapper: VueWrapper | null = null

async function mountDashboard(active: Loan[], reservations: unknown[] = []) {
  const auth = useAuthStore()
  auth.token = 'test-token'
  auth.user = { id: 1, name: 'Mia', role: 'MEMBER', tokens: 5, bonus_tokens: 0 } as typeof auth.user
  loans.fetchDashboard.mockResolvedValue(dashboard(active, reservations))
  wrapper = await mountSuspended(DashboardPage, { route: '/dashboard', global: { stubs } })
  await flushPromises()
  return wrapper
}

async function openReturn(title: string) {
  const card = wrapper!
    .findAll('.action-btn')
    .filter((b) => b.text() === 'Zurückgeben' || b.text() === 'Paket zurückgeben')
  expect(card.length, `no return button for ${title}`).toBeGreaterThan(0)
  await card[0]!.trigger('click')
  await flushPromises()
}

async function confirmReturn() {
  await wrapper!.find('.dialog__actions button').trigger('click')
  await flushPromises()
}

beforeEach(() => {
  Object.values(loans).forEach((fn) => fn.mockReset())
  notifyMock.mockReset()
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
})

describe('dashboard — returning', () => {
  it('returns a loan from a regular place with the chosen condition', async () => {
    loans.returnLoan.mockResolvedValue({})
    await mountDashboard([loan()])

    await openReturn('Catan')
    await wrapper!.find('.stub-condition').setValue('WORN')
    loans.fetchDashboard.mockResolvedValue(dashboard([]))
    await confirmReturn()

    expect(loans.returnLoan).toHaveBeenCalledWith(1, 'WORN', {})
    expect(loans.fetchReturnOptions).not.toHaveBeenCalled() // a regular place needs no choice
    expect(wrapper!.find('.modal-overlay').exists()).toBe(false)
  })

  it('returns a whole package through the package', async () => {
    loans.returnPackageLoan.mockResolvedValue({})
    await mountDashboard([loan({ package_loan_id: 7 } as Partial<Loan>)])

    await openReturn('Catan')
    await confirmReturn()

    expect(loans.returnPackageLoan).toHaveBeenCalledWith(7, 'GOOD', {})
    expect(loans.returnLoan).not.toHaveBeenCalled()
  })

  it('after a personal pickup, offers the latest appointment and asks for a place', async () => {
    loans.fetchReturnOptions.mockResolvedValue({
      locations: [
        { id: 1, name: 'Linz', address: null, opening_hours: null, notes: null, map_url: null },
      ],
      events: [
        {
          id: 9,
          date: '2026-12-03',
          time: '18:00',
          end_time: '19:00',
          location: {
            id: 5,
            name: 'Bei Andi',
            address: null,
            opening_hours: null,
            notes: null,
            map_url: null,
          },
        },
      ],
    })
    loans.returnLoan.mockResolvedValue({})
    await mountDashboard([loan({ pickup_event: { id: 3 } } as Partial<Loan>)])

    await openReturn('Catan')
    // the appointment is preselected as the likeliest place
    await confirmReturn()
    expect(loans.returnLoan).toHaveBeenLastCalledWith(1, 'GOOD', { return_event_id: 9 })
  })

  it('keeps the dialog with the reason when the return is refused', async () => {
    loans.returnLoan.mockRejectedValue({ message: 'Diese Ausleihe ist bereits zurückgegeben.' })
    await mountDashboard([loan()])

    await openReturn('Catan')
    await confirmReturn()

    expect(wrapper!.find('.modal-overlay .alert-error').text()).toBe(
      'Diese Ausleihe ist bereits zurückgegeben.'
    )
  })
})

describe('dashboard — reservations', () => {
  const reservation = {
    id: 4,
    game: { title: 'Azul', slug: 'azul' },
    created_at: '2026-11-20T10:00:00Z',
  }

  it('cancels a reservation and reloads', async () => {
    loans.removeReservation.mockResolvedValue({})
    await mountDashboard([], [reservation])

    loans.fetchDashboard.mockResolvedValue(dashboard([]))
    await wrapper!.find('.queue .action-btn--danger, .action-btn--danger').trigger('click')
    await flushPromises()

    expect(loans.removeReservation).toHaveBeenCalledWith(4)
    expect(loans.fetchDashboard).toHaveBeenCalledTimes(2)
  })

  it('says so when cancelling fails', async () => {
    loans.removeReservation.mockRejectedValue({ message: 'Server nicht erreichbar.' })
    await mountDashboard([], [reservation])

    await wrapper!.find('.action-btn--danger').trigger('click')
    await flushPromises()

    expect(notifyMock).toHaveBeenCalledWith('Server nicht erreichbar.', 'error')
  })
})
