import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, type VueWrapper } from '@vue/test-utils'
import EventsPage from '~/pages/events.vue'
import type { ApiEvent } from '~/types/api'

interface CalendarEvent {
  id: string
  title: string
  start: Date
  end: Date
  allDay: boolean
  badge?: string
}

// The basix calendar draws into the DOM on its own; here it only matters
// what the page hands it and what happens on a click
const calendar = vi.hoisted(() => ({
  options: null as null | { events: CalendarEvent[]; onEventClick: (e: { id: string }) => void },
  destroy: vi.fn(),
}))
vi.mock('@dodlhuat/basix/js/calendar', () => ({
  Calendar: class {
    constructor(options: typeof calendar.options) {
      calendar.options = options
    }
    destroy() {
      calendar.destroy()
    }
  },
}))

const fetchEventsMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useEvents', () => () => ({ fetchEvents: fetchEventsMock }))

const notifyMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useDialogs', () => {
  return () => ({ confirmDialog: vi.fn(), notify: notifyMock })
})

function event(id: number, extra: Partial<ApiEvent> = {}) {
  return {
    id,
    title: 'Spieleabend',
    date: '2026-12-12',
    time: '18:30:00',
    end_time: '22:00:00',
    is_all_day: false,
    description: null,
    image_url: null,
    is_pickup: false,
    pickup_location: null,
    max_loans: null,
    bookings_count: 0,
    ...extra,
  } as unknown as ApiEvent
}

let wrapper: VueWrapper | null = null

async function mountPage(events: ApiEvent[]) {
  fetchEventsMock.mockResolvedValue(events)
  wrapper = await mountSuspended(EventsPage)
  await flushPromises()
  await vi.waitFor(() => {
    if (!calendar.options) throw new Error('calendar not built yet')
  })
  return wrapper
}

function handed(id: number) {
  return calendar.options!.events.find((e) => e.id === String(id))!
}

beforeEach(() => {
  calendar.options = null
  calendar.destroy.mockReset()
  fetchEventsMock.mockReset()
  notifyMock.mockReset()
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
})

describe('/events', () => {
  it('puts timed and all-day events at the right hours', async () => {
    await mountPage([
      event(1),
      event(2, { is_all_day: true, time: null, end_time: null }),
      event(3, { end_time: null }),
    ])

    expect(handed(1).start.getHours()).toBe(18)
    expect(handed(1).start.getMinutes()).toBe(30)
    expect(handed(1).end.getHours()).toBe(22)
    expect(handed(2).allDay).toBe(true)
    expect(handed(2).end.getHours()).toBe(23)
    // without an end time an event lasts an hour
    expect(handed(3).end.getHours()).toBe(19)
  })

  it('marks pickup events in the calendar', async () => {
    await mountPage([event(1), event(2, { is_pickup: true })])

    expect(handed(1).badge).toBeUndefined()
    expect(handed(2).badge).toBe('Abholtermin')
  })

  it('opens an event with place and free spots of a pickup', async () => {
    await mountPage([
      event(4, {
        title: 'Abholung bei Andi',
        is_pickup: true,
        pickup_location: { id: 1, name: 'Bei Andi', address: 'Hauptplatz 1' },
        max_loans: 8,
        bookings_count: 5,
      } as Partial<ApiEvent>),
    ])

    calendar.options!.onEventClick({ id: '4' })
    await flushPromises()

    const dialog = wrapper!.find('.modal-overlay')
    expect(dialog.text()).toContain('Abholung bei Andi')
    expect(dialog.find('.event-pickup').text()).toContain('Bei Andi')
    expect(dialog.find('.event-pickup__hint').text()).toContain('Noch 3 Plätze frei')
  })

  it('tells when the events cannot be loaded', async () => {
    fetchEventsMock.mockRejectedValue({ message: 'Server nicht erreichbar.' })
    wrapper = await mountSuspended(EventsPage)
    await flushPromises()

    expect(notifyMock).toHaveBeenCalledWith('Server nicht erreichbar.', 'error')
  })

  it('cleans up the calendar when leaving the page', async () => {
    await mountPage([event(1)])
    wrapper!.unmount()
    wrapper = null

    expect(calendar.destroy).toHaveBeenCalled()
  })
})
