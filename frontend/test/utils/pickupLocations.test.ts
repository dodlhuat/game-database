import { describe, it, expect, beforeEach, vi } from 'vitest'
import {
  pickupChoices,
  pickupPayload,
  rememberPickupChoice,
  initialPickupChoice,
  renderPickupPickerHtml,
  readPickupPickerChoice,
} from '~/utils/pickupLocations'
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

const ev = (id: number, location: ApiPickupLocation | null, spots: number | null = null) =>
  ({
    id,
    date: '2026-12-03',
    time: '18:00',
    end_time: '19:00',
    spots_left: spots,
    location,
  }) as unknown as ApiPickupEvent

// Stand-in for vue-i18n's t(): returns the key, with n where given
const t = ((key: string, values?: { n?: number }) =>
  values?.n !== undefined ? `${key}:${values.n}` : key) as Parameters<
  typeof renderPickupPickerHtml
>[2]

beforeEach(() => {
  localStorage.clear()
  document.body.innerHTML = ''
})

describe('pickup choices', () => {
  it('lists places, then appointments, skipping appointments without a place', () => {
    const choices = pickupChoices([loc(1, 'Linz')], [ev(5, loc(9, 'Bei Andi')), ev(6, null)])

    expect(choices.map((c) => c.key)).toEqual(['l:1', 'e:5'])
  })

  it('books a place by location and an appointment by event', () => {
    const [place, appointment] = pickupChoices([loc(1, 'Linz')], [ev(5, loc(9, 'Bei Andi'))])

    expect(pickupPayload(place!)).toEqual({ pickup_location_id: 1 })
    expect(pickupPayload(appointment!)).toEqual({ pickup_event_id: 5 })
  })
})

describe('preselection', () => {
  it('preselects the only option', () => {
    expect(initialPickupChoice(pickupChoices([loc(1, 'Linz')]))).toBe('l:1')
  })

  it('remembers the last regular place, but not a one-off appointment', () => {
    const choices = pickupChoices([loc(1, 'Linz'), loc(2, 'Wels')], [ev(5, loc(9, 'Bei Andi'))])

    rememberPickupChoice(choices[1]!)
    expect(initialPickupChoice(choices)).toBe('l:2')

    rememberPickupChoice(choices[2]!) // the appointment
    expect(initialPickupChoice(choices)).toBe('l:2')
  })

  it('forgets a remembered place that is no longer offered', () => {
    rememberPickupChoice(pickupChoices([loc(7, 'Steyr')])[0]!)

    expect(initialPickupChoice(pickupChoices([loc(1, 'Linz'), loc(2, 'Wels')]))).toBeNull()
  })

  it('works without storage, e.g. in private mode', () => {
    vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
      throw new Error('blocked')
    })
    expect(() => rememberPickupChoice(pickupChoices([loc(1, 'Linz')])[0]!)).not.toThrow()
    vi.restoreAllMocks()
  })
})

describe('picker HTML for the game page dialog', () => {
  it('escapes what admins typed, so a name cannot inject markup', () => {
    const html = renderPickupPickerHtml(
      pickupChoices([
        loc(1, '<img src=x onerror=alert(1)>', { notes: 'A & B "C"' }),
        loc(2, 'Wels'),
      ]),
      null,
      t
    )

    expect(html).not.toContain('<img')
    expect(html).toContain('&lt;img src=x onerror=alert(1)&gt;')
    expect(html).toContain('A &amp; B &quot;C&quot;')
  })

  it('marks the preselected option and groups appointments with their hint', () => {
    const choices = pickupChoices([loc(1, 'Linz'), loc(2, 'Wels')], [ev(5, loc(9, 'Bei Andi'), 2)])
    document.body.innerHTML = renderPickupPickerHtml(choices, 'l:2', t)

    expect(document.querySelector<HTMLInputElement>('input[value="l:2"]')!.checked).toBe(true)
    expect(document.querySelector('.pickup-picker__group')!.textContent).toBe(
      'pickup.personal_group'
    )
    expect(document.querySelector('.pickup-option--event .pickup-option__spots')!.textContent).toBe(
      'pickup.spots_left:2'
    )
  })

  it('reads the choice back from the dialog', () => {
    const choices = pickupChoices([loc(1, 'Linz'), loc(2, 'Wels')])
    document.body.innerHTML = renderPickupPickerHtml(choices, null, t)

    expect(readPickupPickerChoice(choices)).toBeNull()
    document.querySelector<HTMLInputElement>('input[value="l:2"]')!.checked = true
    expect(readPickupPickerChoice(choices)?.location.name).toBe('Wels')
  })

  it('shows a single place as information without a radio', () => {
    const choices = pickupChoices([loc(1, 'Linz')])
    document.body.innerHTML = renderPickupPickerHtml(choices, null, t)

    expect(document.querySelector('input[type="radio"]')).toBeNull()
    expect(document.querySelector('legend')!.textContent).toBe('pickup.legend_single')
    expect(readPickupPickerChoice(choices)?.location.name).toBe('Linz')
  })
})
