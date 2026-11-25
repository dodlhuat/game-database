import { describe, it, expect } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import PickupLocationPicker from '~/components/PickupLocationPicker.vue'
import Breadcrumb from '~/components/admin/Breadcrumb.vue'
import type { PickupChoice } from '~/utils/pickupLocations'
import type { ApiPickupEvent, ApiPickupLocation } from '~/types/api'

const loc = (id: number, name: string, extra: Partial<ApiPickupLocation> = {}) =>
  ({
    id,
    name,
    address: null,
    opening_hours: null,
    notes: null,
    map_url: null,
    ...extra,
  }) as ApiPickupLocation

const place = (id: number, name: string, extra: Partial<ApiPickupLocation> = {}): PickupChoice => ({
  key: `location-${id}`,
  kind: 'location',
  location: loc(id, name, extra),
})

const appointment = (id: number, spots: number | null): PickupChoice => ({
  key: `event-${id}`,
  kind: 'event',
  location: loc(9, 'Bei Andi', { address: 'Hauptplatz 1' }),
  event: {
    id,
    date: '2026-12-03',
    time: '18:00',
    end_time: '19:00',
    spots_left: spots,
  } as unknown as ApiPickupEvent,
})

describe('PickupLocationPicker', () => {
  it('lists regular places first and pickup appointments under their own heading', async () => {
    const w = await mountSuspended(PickupLocationPicker, {
      props: { choices: [appointment(1, 3), place(1, 'Linz'), place(2, 'Wels')], modelValue: null },
    })

    const names = w.findAll('.pickup-option__name').map((n) => n.text())
    expect(names).toEqual(['Linz', 'Wels', 'Bei Andi'])
    expect(w.find('.pickup-picker__group').exists()).toBe(true)
  })

  it('reports the chosen key', async () => {
    const w = await mountSuspended(PickupLocationPicker, {
      props: { choices: [place(1, 'Linz'), appointment(5, 2)], modelValue: 'location-1' },
    })

    expect((w.findAll('input[type="radio"]')[0]!.element as HTMLInputElement).checked).toBe(true)
    await w.findAll('input[type="radio"]')[1]!.setValue(true)

    expect(w.emitted('update:modelValue')).toEqual([['event-5']])
  })

  it('shows a single place as information, without a choice to make', async () => {
    const w = await mountSuspended(PickupLocationPicker, {
      props: { choices: [place(1, 'Linz', { opening_hours: 'Mo–Fr 9–17' })], modelValue: null },
    })

    expect(w.find('input[type="radio"]').exists()).toBe(false)
    expect(w.text()).toContain('Mo–Fr 9–17')
  })

  it('shows date, time, free spots and a map link of an appointment', async () => {
    const choice = appointment(1, 1)
    ;(choice.location as ApiPickupLocation).map_url = 'https://maps.example/andi'
    const w = await mountSuspended(PickupLocationPicker, {
      props: { choices: [choice, place(1, 'Linz')], modelValue: null },
    })

    const card = w.find('.pickup-option--event')
    expect(card.find('.pickup-option__date strong').text()).toBe('3')
    expect(card.text()).toContain('18:00–19:00 Uhr')
    expect(card.text()).toContain('Noch 1 Platz frei')
    expect(card.find('a').attributes('target')).toBe('_blank')
  })

  it('asks for a choice when one is missing', async () => {
    const w = await mountSuspended(PickupLocationPicker, {
      props: { choices: [place(1, 'Linz'), place(2, 'Wels')], modelValue: null, showError: true },
    })

    expect(w.find('[role="alert"]').exists()).toBe(true)
  })
})

describe('AdminBreadcrumb', () => {
  it('leads back to the admin start and names the current page', async () => {
    const w = await mountSuspended(Breadcrumb, { props: { label: 'Kündigungen' } })

    expect(w.find('a').attributes('href')).toBe('/admin')
    expect(w.find('[aria-current="page"]').text()).toBe('Kündigungen')
  })
})
