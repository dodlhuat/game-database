import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import PickupLocationSelect from '~/components/admin/PickupLocationSelect.vue'
import type { ApiPickupLocation } from '~/types/api'

const patchMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useApi', () => {
  return () => ({ get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: patchMock, delete: vi.fn() })
})

const fetchUserLocationsMock = vi.hoisted(() => vi.fn())
mockNuxtImport('usePickupLocations', () => () => ({ fetchUserLocations: fetchUserLocationsMock }))

const loc = (id: number, name: string) => ({ id, name }) as ApiPickupLocation
const LINZ = loc(1, 'Linz')
const WELS = loc(2, 'Wels')
const STEYR = loc(3, 'Steyr')

function mountSelect(props: Record<string, unknown> = {}) {
  return mountSuspended(PickupLocationSelect, {
    props: {
      current: LINZ,
      locations: [LINZ, WELS, STEYR],
      editable: true,
      endpoint: '/admin/loans/5/pickup-location',
      ...props,
    },
  })
}

const optionNames = (w: Awaited<ReturnType<typeof mountSelect>>) =>
  w.findAll('option').map((o) => o.text())

beforeEach(() => {
  patchMock.mockReset()
  fetchUserLocationsMock.mockReset()
})

describe('AdminPickupLocationSelect', () => {
  it('only shows the place when it may not be changed', async () => {
    const w = await mountSelect({ editable: false, lockedHint: 'über das Paket' })

    expect(w.find('select').exists()).toBe(false)
    expect(w.text()).toContain('Linz')
    expect(w.text()).toContain('über das Paket')
  })

  it('moves the loan and reports the new place', async () => {
    patchMock.mockResolvedValue({ data: { pickup_location: WELS } })
    const w = await mountSelect()

    await w.find('select').setValue('2')
    await flushPromises()

    expect(patchMock).toHaveBeenCalledWith('/admin/loans/5/pickup-location', {
      pickup_location_id: 2,
    })
    expect(w.emitted('updated')).toEqual([[WELS]])
  })

  it('jumps back and says why when the move is refused', async () => {
    patchMock.mockRejectedValue({ message: 'Dieser Ort ist für Mia nicht freigegeben.' })
    const w = await mountSelect()

    await w.find('select').setValue('3')
    await flushPromises()

    expect((w.find('select').element as HTMLSelectElement).value).toBe('1')
    expect(w.find('[role="alert"]').text()).toBe('Dieser Ort ist für Mia nicht freigegeben.')
    expect(w.emitted('updated')).toBeUndefined()
  })

  it('offers only the places open to the borrower, asked once on opening', async () => {
    fetchUserLocationsMock.mockResolvedValue([LINZ, WELS])
    const w = await mountSelect({ userId: 7 })

    expect(fetchUserLocationsMock).not.toHaveBeenCalled() // no request per row up front
    await w.find('select').trigger('focus')
    await w.find('select').trigger('pointerdown')
    await flushPromises()

    expect(fetchUserLocationsMock).toHaveBeenCalledTimes(1)
    expect(fetchUserLocationsMock).toHaveBeenCalledWith(7)
    expect(optionNames(w)).toEqual(['Linz', 'Wels'])
  })

  it('keeps the current place selectable even if it is no longer open to the borrower', async () => {
    fetchUserLocationsMock.mockResolvedValue([WELS])
    const w = await mountSelect({ userId: 7 })

    await w.find('select').trigger('focus')
    await flushPromises()

    expect(optionNames(w)).toEqual(['Linz', 'Wels'])
  })
})
