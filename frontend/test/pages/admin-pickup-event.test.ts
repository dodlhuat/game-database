import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, type VueWrapper } from '@vue/test-utils'
import PickupEventPage from '~/pages/admin/pickup-events/[id].vue'

const getMock = vi.hoisted(() => vi.fn())
const postMock = vi.hoisted(() => vi.fn())
const patchMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useApi', () => {
  return () => ({ get: getMock, post: postMock, put: vi.fn(), patch: patchMock, delete: vi.fn() })
})

const confirmMock = vi.hoisted(() => vi.fn())
const notifyMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useDialogs', () => {
  return () => ({ confirmDialog: confirmMock, notify: notifyMock })
})

function packingList() {
  return {
    event: {
      id: 12,
      title: 'Abholung Linz',
      date: '2026-12-03',
      time: '18:00',
      end_time: '19:00',
      max_loans: null,
      location: { id: 1, name: 'Linz', address: 'Hauptplatz 1' },
    },
    bookings: [
      {
        type: 'loan',
        id: 5,
        member: { id: 2, name: 'Mia', email: 'mia@example.at' },
        package_name: null,
        handed_out_at: null,
        items: [{ loan_id: 5, game_title: 'Azul', qr_code: 'QR-5' }],
      },
      {
        type: 'package',
        id: 9,
        member: { id: 3, name: 'Tom', email: 'tom@example.at' },
        package_name: 'Familienabend',
        handed_out_at: null,
        items: [
          { loan_id: 7, game_title: 'Catan', qr_code: 'QR-7' },
          { loan_id: 8, game_title: 'Dixit', qr_code: 'QR-8' },
        ],
      },
    ],
    returns: [],
  }
}

let wrapper: VueWrapper | null = null

async function mountPage() {
  // the auth/admin middleware reads the login from localStorage
  localStorage.setItem('auth_token', 'test-token')
  localStorage.setItem('auth_user', JSON.stringify({ id: 1, role: 'ADMIN' }))
  wrapper = await mountSuspended(PickupEventPage, { route: '/admin/pickup-events/12' })
  await flushPromises()
  return wrapper
}

function booking(name: string) {
  return wrapper!.findAll('.booking').find((b) => b.text().includes(name))!
}

beforeEach(() => {
  getMock.mockReset().mockResolvedValue(packingList())
  postMock.mockReset()
  patchMock.mockReset()
  confirmMock.mockReset()
  notifyMock.mockReset()
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
  localStorage.clear()
})

describe('admin/pickup-events/[id] — handing out', () => {
  it('loads the packing list of this event and counts what is to hand out', async () => {
    await mountPage()

    expect(getMock).toHaveBeenCalledWith('/admin/events/12/packing-list')
    expect(wrapper!.find('.tally__count').text()).toBe('0/ 2')
    expect(wrapper!.find('.tally__games').text()).toContain('3')
  })

  it('ticks off a single loan and a package at their own endpoints', async () => {
    patchMock.mockResolvedValue({ handed_out_at: '2026-12-03T18:05:00Z' })
    await mountPage()

    await booking('Mia').find('.booking__toggle').trigger('click')
    await booking('Tom').find('.booking__toggle').trigger('click')
    await flushPromises()

    expect(patchMock).toHaveBeenCalledWith('/admin/loans/5/hand-out', { handed_out: true })
    expect(patchMock).toHaveBeenCalledWith('/admin/package-loans/9/hand-out', { handed_out: true })
    expect(booking('Mia').classes()).toContain('is-out')
    expect(wrapper!.find('.tally__count').text()).toBe('2/ 2')
  })

  it('takes a hand-out back', async () => {
    const list = packingList()
    list.bookings[0]!.handed_out_at = '2026-12-03T18:05:00Z' as unknown as null
    getMock.mockResolvedValue(list)
    patchMock.mockResolvedValue({ handed_out_at: null })
    await mountPage()

    await booking('Mia').find('.booking__toggle').trigger('click')
    await flushPromises()

    expect(patchMock).toHaveBeenCalledWith('/admin/loans/5/hand-out', { handed_out: false })
    expect(booking('Mia').classes()).not.toContain('is-out')
  })

  it('leaves the booking unticked when the server refuses', async () => {
    patchMock.mockRejectedValue({ message: 'Ausleihe ist bereits storniert.' })
    await mountPage()

    await booking('Mia').find('.booking__toggle').trigger('click')
    await flushPromises()

    expect(notifyMock).toHaveBeenCalledWith('Ausleihe ist bereits storniert.', 'error')
    expect(booking('Mia').classes()).not.toContain('is-out')
  })

  it('shows why the list cannot be loaded', async () => {
    getMock.mockRejectedValue({ message: 'Kein Abholtermin.' })
    await mountPage()

    expect(wrapper!.find('.alert-error').text()).toBe('Kein Abholtermin.')
  })
})

describe('admin/pickup-events/[id] — no-shows', () => {
  it('asks, cancels with refund and drops the booking', async () => {
    confirmMock.mockResolvedValue(true)
    postMock.mockResolvedValue({})
    await mountPage()

    await booking('Tom').find('.booking__noshow').trigger('click')
    await flushPromises()

    expect(confirmMock).toHaveBeenCalledWith(expect.objectContaining({ danger: true }))
    expect(postMock).toHaveBeenCalledWith('/admin/package-loans/9/cancel-pickup')
    expect(wrapper!.findAll('.booking')).toHaveLength(1)
  })

  it('does nothing when the question is declined', async () => {
    confirmMock.mockResolvedValue(false)
    await mountPage()

    await booking('Mia').find('.booking__noshow').trigger('click')
    await flushPromises()

    expect(postMock).not.toHaveBeenCalled()
    expect(wrapper!.findAll('.booking')).toHaveLength(2)
  })

  it('keeps the booking when the cancellation fails', async () => {
    confirmMock.mockResolvedValue(true)
    postMock.mockRejectedValue({ message: 'Schon ausgegeben.' })
    await mountPage()

    await booking('Mia').find('.booking__noshow').trigger('click')
    await flushPromises()

    expect(notifyMock).toHaveBeenCalledWith('Schon ausgegeben.', 'error')
    expect(wrapper!.findAll('.booking')).toHaveLength(2)
  })
})

describe('admin/pickup-events/[id] — returns at the event', () => {
  async function takeBack(code: string, condition?: string) {
    await wrapper!.find('.intake__form input').setValue(code)
    if (condition) {
      await wrapper!
        .findAll('[role="radio"]')
        .find((r) => r.text() === condition)!
        .trigger('click')
    }
    await wrapper!.find('.intake__form').trigger('submit')
    await flushPromises()
  }

  it('books a return with the chosen condition and lists it', async () => {
    postMock.mockResolvedValue({
      message: 'Catan zurückgenommen.',
      returns: [{ loan_id: 7, game_title: 'Catan', member: 'Tom', condition: 'WORN' }],
    })
    await mountPage()

    await takeBack(' QR-7 ', 'Abgenutzt')

    expect(postMock).toHaveBeenCalledWith('/admin/events/12/returns', {
      qr_code: 'QR-7',
      return_condition: 'WORN',
    })
    expect(wrapper!.find('.intake__message').text()).toBe('Catan zurückgenommen.')
    expect(wrapper!.findAll('.intake__item')).toHaveLength(1)
    expect((wrapper!.find('.intake__form input').element as HTMLInputElement).value).toBe('')
  })

  it('keeps the code to correct it when the return is refused', async () => {
    postMock.mockRejectedValue({ message: 'Dieses Spiel ist nicht ausgeliehen.' })
    await mountPage()

    await takeBack('QR-99')

    expect(wrapper!.find('.intake__message').classes()).toContain('is-error')
    expect((wrapper!.find('.intake__form input').element as HTMLInputElement).value).toBe('QR-99')
  })
})
