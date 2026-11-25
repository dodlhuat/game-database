import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, DOMWrapper, type VueWrapper } from '@vue/test-utils'
import PickupLocationsPage from '~/pages/admin/pickup-locations.vue'
import type { ApiPickupLocation } from '~/types/api'

// The form dialog renders via <Teleport to="body">
function body() {
  return new DOMWrapper(document.body)
}

const api = vi.hoisted(() => ({
  fetchAdminLocations: vi.fn(),
  createLocation: vi.fn(),
  updateLocation: vi.fn(),
  toggleLocation: vi.fn(),
  reorderLocations: vi.fn(),
  deleteLocation: vi.fn(),
}))
mockNuxtImport('usePickupLocations', () => () => api)

const confirmMock = vi.hoisted(() => vi.fn())
const notifyMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useDialogs', () => {
  return () => ({ confirmDialog: confirmMock, notify: notifyMock })
})

function place(
  id: number,
  name: string,
  extra: Partial<ApiPickupLocation> = {}
): ApiPickupLocation {
  return {
    id,
    name,
    address: null,
    opening_hours: null,
    notes: null,
    map_url: null,
    is_active: true,
    is_personal: false,
    open_loans_count: 0,
    users: [],
    ...extra,
  } as ApiPickupLocation
}

let wrapper: VueWrapper | null = null

async function mountPage(list: ApiPickupLocation[]) {
  api.fetchAdminLocations.mockResolvedValue(list)
  wrapper = await mountSuspended(PickupLocationsPage, {
    // picking users is the picker's own business
    global: { stubs: { AdminUserPicker: true } },
  })
  await flushPromises()
  return wrapper
}

function row(name: string) {
  return wrapper!
    .findAll('.location-row')
    .find((r) => r.find('.location-cell__name').text() === name)!
}

function activeSwitch(name: string) {
  return row(name).find('.switch-wrap input')
}

function deleteButton(name: string) {
  return row(name).find('.action-btn--danger')
}

/** Switches in the form, in order: personal, restricted, active */
function formSwitches() {
  return body().findAll('.modal-overlay .ui-switch__input')
}

async function save() {
  await body()
    .findAll('.modal-overlay button')
    .find((b) => b.text() === 'Speichern')!
    .trigger('click')
  await flushPromises()
}

beforeEach(() => {
  Object.values(api).forEach((fn) => fn.mockReset())
  confirmMock.mockReset()
  notifyMock.mockReset()
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
})

describe('admin/pickup-locations.vue — what may be switched off', () => {
  it('keeps the last active regular location on', async () => {
    await mountPage([
      place(1, 'Linz'),
      place(2, 'Wels', { is_active: false }),
      // personal locations only work through events, so they never count
      place(3, 'Bei Andi', { is_personal: true }),
    ])

    expect(activeSwitch('Linz').attributes('disabled')).toBeDefined()
    expect(deleteButton('Linz').attributes('disabled')).toBeDefined()
    expect(activeSwitch('Bei Andi').attributes('disabled')).toBeUndefined()
  })

  it('blocks switching off and deleting while games are still out', async () => {
    await mountPage([place(1, 'Linz', { open_loans_count: 2 }), place(2, 'Wels')])

    expect(activeSwitch('Linz').attributes('disabled')).toBeDefined()
    expect(deleteButton('Linz').attributes('disabled')).toBeDefined()
    expect(row('Linz').find('.switch-wrap').attributes('title')).toContain('2')
    expect(activeSwitch('Wels').attributes('disabled')).toBeUndefined()
  })

  it('switches a location off and shows a refusal', async () => {
    // three, so Linz is not the last active one once Wels is off
    await mountPage([place(1, 'Linz'), place(2, 'Wels'), place(3, 'Steyr')])
    api.toggleLocation.mockResolvedValueOnce({ ...place(2, 'Wels'), is_active: false })

    await activeSwitch('Wels').setValue(false)
    await flushPromises()
    expect(api.toggleLocation).toHaveBeenCalledWith(2)
    expect((activeSwitch('Wels').element as HTMLInputElement).checked).toBe(false)

    api.toggleLocation.mockRejectedValueOnce({ message: 'Noch offene Ausleihen.' })
    await activeSwitch('Linz').setValue(false)
    await flushPromises()
    expect(notifyMock).toHaveBeenCalledWith('Noch offene Ausleihen.', 'error')
  })
})

describe('admin/pickup-locations.vue — the form', () => {
  it('requires a name and a real link before saving', async () => {
    await mountPage([place(1, 'Linz')])
    await wrapper!.find('.hero-btn').trigger('click')

    await save()
    expect(body().find('.modal-overlay .form-error').text()).toBe('Bitte einen Namen angeben.')

    await body().find('.modal-overlay input').setValue('Wels')
    await body().find('.modal-overlay input[type="url"]').setValue('maps-link')
    await save()
    expect(body().find('.modal-overlay .form-error').text()).toContain('http')

    expect(api.createLocation).not.toHaveBeenCalled()
  })

  it('creates a location open to everyone with trimmed, nulled fields', async () => {
    await mountPage([place(1, 'Linz')])
    api.createLocation.mockResolvedValue(place(2, 'Wels'))
    await wrapper!.find('.hero-btn').trigger('click')

    await body().find('.modal-overlay input').setValue('  Wels  ')
    await save()

    expect(api.createLocation).toHaveBeenCalledWith({
      name: 'Wels',
      address: null,
      opening_hours: null,
      notes: null,
      map_url: null,
      is_active: true,
      is_personal: false,
      user_ids: [],
    })
    expect(body().find('.modal-overlay').exists()).toBe(false)
    expect(wrapper!.findAll('.location-row')).toHaveLength(2)
  })

  it('keeps the chosen members of a restricted location', async () => {
    const restricted = place(1, 'Linz', {
      users: [
        { id: 7, name: 'Mia', email: 'mia@example.at' },
        { id: 8, name: 'Tom', email: 'tom@example.at' },
      ],
    } as Partial<ApiPickupLocation>)
    await mountPage([restricted, place(2, 'Wels')])
    api.updateLocation.mockResolvedValue(restricted)

    await row('Linz').find('.action-btn').trigger('click')
    await save()

    expect(api.updateLocation).toHaveBeenCalledWith(
      1,
      expect.objectContaining({ user_ids: [7, 8] })
    )
  })

  it('opens a location up to everyone when the restriction is switched off', async () => {
    await mountPage([
      place(1, 'Linz', {
        users: [{ id: 7, name: 'Mia', email: 'mia@example.at' }],
      } as Partial<ApiPickupLocation>),
      place(2, 'Wels'),
    ])
    api.updateLocation.mockResolvedValue(place(1, 'Linz'))

    await row('Linz').find('.action-btn').trigger('click')
    await formSwitches()[1]!.setValue(false)
    await save()

    expect(api.updateLocation).toHaveBeenCalledWith(1, expect.objectContaining({ user_ids: [] }))
  })

  it('keeps the form open with the server message when saving fails', async () => {
    await mountPage([place(1, 'Linz')])
    api.createLocation.mockRejectedValue({ message: 'Name bereits vergeben.' })
    await wrapper!.find('.hero-btn').trigger('click')

    await body().find('.modal-overlay input').setValue('Linz')
    await save()

    expect(body().find('.modal-overlay .form-error').text()).toBe('Name bereits vergeben.')
  })
})

describe('admin/pickup-locations.vue — order and delete', () => {
  it('puts the order back when saving it fails', async () => {
    await mountPage([place(1, 'Linz'), place(2, 'Wels')])
    api.reorderLocations.mockRejectedValue({})

    // the "down" arrow of the first row
    const arrows = wrapper!.findAll('.location-row')[0]!.findAll('button[aria-label]')
    await arrows.at(1)!.trigger('click')
    await flushPromises()

    expect(api.reorderLocations).toHaveBeenCalledWith([2, 1])
    expect(wrapper!.findAll('.location-cell__name').map((n) => n.text())).toEqual(['Linz', 'Wels'])
    expect(notifyMock).toHaveBeenCalledWith(expect.any(String), 'error')
  })

  it('deletes only after asking', async () => {
    await mountPage([place(1, 'Linz'), place(2, 'Wels')])
    confirmMock.mockResolvedValueOnce(false).mockResolvedValueOnce(true)
    api.deleteLocation.mockResolvedValue({})

    await deleteButton('Wels').trigger('click')
    await flushPromises()
    expect(api.deleteLocation).not.toHaveBeenCalled()

    await deleteButton('Wels').trigger('click')
    await flushPromises()
    expect(api.deleteLocation).toHaveBeenCalledWith(2)
    expect(wrapper!.findAll('.location-row')).toHaveLength(1)
  })

  it('says so when the list cannot be loaded', async () => {
    api.fetchAdminLocations.mockRejectedValue({ message: 'Server nicht erreichbar.' })
    wrapper = await mountSuspended(PickupLocationsPage, {
      global: { stubs: { AdminUserPicker: true } },
    })
    await flushPromises()

    expect(notifyMock).toHaveBeenCalledWith('Server nicht erreichbar.', 'error')
  })
})
