import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, DOMWrapper, type VueWrapper } from '@vue/test-utils'
import AdminEventsPage from '~/pages/admin/events.vue'
import type { ApiEvent, ApiPickupLocation } from '~/types/api'

// The form dialog renders via <Teleport to="body">
function body() {
  return new DOMWrapper(document.body)
}

const ev = vi.hoisted(() => ({
  fetchAdminEvents: vi.fn(),
  createEvent: vi.fn(),
  updateEvent: vi.fn(),
  deleteEvent: vi.fn(),
}))
mockNuxtImport('useEvents', () => () => ev)

const fetchAdminLocationsMock = vi.hoisted(() => vi.fn())
mockNuxtImport('usePickupLocations', () => () => ({ fetchAdminLocations: fetchAdminLocationsMock }))

const confirmMock = vi.hoisted(() => vi.fn())
const notifyMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useDialogs', () => {
  return () => ({ confirmDialog: confirmMock, notify: notifyMock })
})

// Plain inputs in place of the pickers, so the form can be filled like a user would
const stubs = {
  UiDatePicker: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template:
      '<input class="stub-date" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
  },
  UiTimeSpanPicker: {
    props: ['start', 'end'],
    emits: ['update:start', 'update:end'],
    template:
      '<span><input class="stub-start" :value="start" @input="$emit(\'update:start\', $event.target.value)" /><input class="stub-end" :value="end" @input="$emit(\'update:end\', $event.target.value)" /></span>',
  },
  UiVirtualDropdown: {
    props: ['modelValue', 'options'],
    emits: ['update:modelValue'],
    template:
      '<select class="stub-dropdown" :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)"><option value=""></option><option v-for="o in options" :key="o.value" :value="o.value">{{ o.label }}</option></select>',
  },
  UiRichEditor: true,
}

function location(id: number, name: string, extra: Partial<ApiPickupLocation> = {}) {
  return { id, name, is_active: true, is_personal: true, ...extra } as ApiPickupLocation
}

const LOCATIONS = [
  location(1, 'Bei Andi'),
  location(2, 'Bei Sara (pausiert)', { is_active: false }),
  location(3, 'Hauptstandort', { is_personal: false }),
]

function event(id: number, extra: Partial<ApiEvent> = {}) {
  return {
    id,
    title: 'Spieleabend',
    date: '2026-12-12',
    time: '18:00:00',
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

async function mountPage(events: ApiEvent[] = []) {
  ev.fetchAdminEvents.mockResolvedValue(events)
  fetchAdminLocationsMock.mockResolvedValue(LOCATIONS)
  wrapper = await mountSuspended(AdminEventsPage, { global: { stubs } })
  await flushPromises()
  return wrapper
}

function dialog() {
  return body().find('.modal-overlay')
}

/** "All day" is hidden on pickup events (a pickup always has times) */
function allDaySwitch() {
  return dialog().findAll('.ui-switch__input')[0]!
}

function pickupSwitch() {
  return dialog().findAll('.ui-switch__input').at(-1)!
}

async function fill(fields: { title?: string; date?: string; start?: string; end?: string }) {
  if (fields.title !== undefined) await dialog().find('input').setValue(fields.title)
  if (fields.date !== undefined) await dialog().find('.stub-date').setValue(fields.date)
  if (fields.start !== undefined) await dialog().find('.stub-start').setValue(fields.start)
  if (fields.end !== undefined) await dialog().find('.stub-end').setValue(fields.end)
}

async function save() {
  await dialog()
    .findAll('button')
    .find((b) => b.text() === 'Speichern')!
    .trigger('click')
  await flushPromises()
}

const sent = (mock: ReturnType<typeof vi.fn>, arg = 0) =>
  Object.fromEntries((mock.mock.calls[0]![arg] as FormData).entries())

beforeEach(() => {
  Object.values(ev).forEach((fn) => fn.mockReset())
  fetchAdminLocationsMock.mockReset()
  confirmMock.mockReset()
  notifyMock.mockReset()
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
})

describe('admin/events.vue — regular events', () => {
  it('sends a timed event without any pickup fields', async () => {
    ev.createEvent.mockResolvedValue(event(1))
    await mountPage()
    await wrapper!.find('.hero-btn').trigger('click')

    await fill({ title: 'Spieleabend', date: '2026-12-12', start: '18:00', end: '22:00' })
    await save()

    expect(sent(ev.createEvent)).toEqual({
      title: 'Spieleabend',
      date: '2026-12-12',
      is_all_day: '0',
      time: '18:00',
      end_time: '22:00',
      pickup_location_id: '',
      max_loans: '',
    })
    expect(dialog().exists()).toBe(false)
  })

  it('leaves the times out of an all-day event', async () => {
    ev.createEvent.mockResolvedValue(event(1, { is_all_day: true }))
    await mountPage()
    await wrapper!.find('.hero-btn').trigger('click')

    await fill({ title: 'Flohmarkt', date: '2026-12-12' })
    await allDaySwitch().setValue(true)
    await save()

    const data = sent(ev.createEvent)
    expect(data.is_all_day).toBe('1')
    expect(data).not.toHaveProperty('time')
    expect(data).not.toHaveProperty('end_time')
  })

  it('refuses an end time before the start', async () => {
    await mountPage()
    await wrapper!.find('.hero-btn').trigger('click')

    await fill({ title: 'Spieleabend', date: '2026-12-12', start: '20:00', end: '18:00' })
    await save()

    expect(dialog().find('.form-error').text()).toBe('Die Endzeit muss nach der Startzeit liegen.')
    expect(ev.createEvent).not.toHaveBeenCalled()
  })
})

describe('admin/events.vue — pickup events', () => {
  it('offers only active personal locations and requires one', async () => {
    await mountPage()
    await wrapper!.find('.hero-btn').trigger('click')
    await fill({ title: 'Abholung', date: '2026-12-12', start: '18:00', end: '19:00' })
    await pickupSwitch().setValue(true)

    const offered = dialog()
      .findAll('.stub-dropdown option')
      .map((o) => o.text())
      .filter(Boolean)
    expect(offered).toEqual(['Bei Andi'])

    await save()
    expect(dialog().find('.form-error').exists()).toBe(true)
    expect(ev.createEvent).not.toHaveBeenCalled()
  })

  it('sends place and capacity of a pickup event', async () => {
    ev.createEvent.mockResolvedValue(event(1, { is_pickup: true }))
    await mountPage()
    await wrapper!.find('.hero-btn').trigger('click')
    await fill({ title: 'Abholung', date: '2026-12-12', start: '18:00', end: '19:00' })
    await pickupSwitch().setValue(true)

    await dialog().find('.stub-dropdown').setValue('1')
    await dialog()
      .findAll('input')
      .filter((i) => i.attributes('type') === 'number')
      .at(0)
      ?.setValue('8')
    await save()

    expect(sent(ev.createEvent)).toMatchObject({ pickup_location_id: '1', max_loans: '8' })
  })

  it('keeps a paused location selectable on an event that already uses it', async () => {
    await mountPage([
      event(5, {
        is_pickup: true,
        pickup_location: location(2, 'Bei Sara (pausiert)', { is_active: false }),
      }),
    ])

    await wrapper!.findAll('tbody button.action-btn')[0]!.trigger('click')

    const offered = dialog()
      .findAll('.stub-dropdown option')
      .map((o) => o.text())
      .filter(Boolean)
    expect(offered).toEqual(['Bei Andi', 'Bei Sara (pausiert)'])
  })

  it('turns a pickup event back into a regular one', async () => {
    ev.updateEvent.mockResolvedValue(event(5))
    await mountPage([
      event(5, { is_pickup: true, pickup_location: location(1, 'Bei Andi'), max_loans: 8 }),
    ])

    await wrapper!.findAll('tbody button.action-btn')[0]!.trigger('click')
    await pickupSwitch().setValue(false)
    await save()

    expect(ev.updateEvent.mock.calls[0]![0]).toBe(5)
    expect(sent(ev.updateEvent, 1)).toMatchObject({ pickup_location_id: '', max_loans: '' })
  })
})

describe('admin/events.vue — list', () => {
  it('deletes only after asking', async () => {
    confirmMock.mockResolvedValue(true)
    ev.deleteEvent.mockResolvedValue({})
    await mountPage([event(5)])

    await wrapper!.find('tbody .action-btn--danger').trigger('click')
    await flushPromises()

    expect(ev.deleteEvent).toHaveBeenCalledWith(5)
    expect(wrapper!.findAll('tbody tr')).toHaveLength(0)
  })

  it('says so when the events cannot be loaded', async () => {
    ev.fetchAdminEvents.mockRejectedValue({ message: 'Server nicht erreichbar.' })
    fetchAdminLocationsMock.mockResolvedValue([])
    wrapper = await mountSuspended(AdminEventsPage, { global: { stubs } })
    await flushPromises()

    expect(notifyMock).toHaveBeenCalledWith('Server nicht erreichbar.', 'error')
  })
})
