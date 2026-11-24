import type { ApiPickupEvent, ApiPickupLocation } from '~/types/api'

type Translate = (key: string, params?: Record<string, unknown>, plural?: number) => string

/**
 * One option in the borrow dialog: a regular location (picked up on the next
 * shared appointment) or a personal pickup event (place, date and time).
 * Keys look like `l:3` / `e:12`, so both fit one radio group.
 */
export type PickupChoice =
  | { key: string; kind: 'location'; location: ApiPickupLocation }
  | { key: string; kind: 'event'; location: ApiPickupLocation; event: ApiPickupEvent }

export type PickupPayload = { pickup_location_id: number } | { pickup_event_id: number }

const STORAGE_KEY = 'last_pickup_location'

export function pickupChoices(
  locations: ApiPickupLocation[],
  events: ApiPickupEvent[] = []
): PickupChoice[] {
  return [
    ...locations.map((location) => ({
      key: `l:${location.id}`,
      kind: 'location' as const,
      location,
    })),
    ...events
      .filter((event) => event.location)
      .map((event) => ({
        key: `e:${event.id}`,
        kind: 'event' as const,
        location: event.location!,
        event,
      })),
  ]
}

export function pickupPayload(choice: PickupChoice): PickupPayload {
  return choice.kind === 'event'
    ? { pickup_event_id: choice.event.id }
    : { pickup_location_id: choice.location.id }
}

/** Remembers the member's last regular location so the next dialog preselects it. */
export function rememberPickupChoice(choice: PickupChoice) {
  if (choice.kind !== 'location') return // events are one-offs, not a habit
  try {
    localStorage.setItem(STORAGE_KEY, String(choice.location.id))
  } catch {
    // private mode / blocked storage — preselection is only a convenience
  }
}

/** Preselection: the only option, else the last regular location if it is still offered. */
export function initialPickupChoice(choices: PickupChoice[]): string | null {
  if (choices.length === 1) return choices[0]!.key
  try {
    const key = `l:${Number(localStorage.getItem(STORAGE_KEY))}`
    if (choices.some((c) => c.key === key)) return key
  } catch {
    // ignore
  }
  return null
}

/** "Do., 10.12." */
export function formatEventDay(event: ApiPickupEvent): string {
  return new Date(`${event.date}T00:00:00`).toLocaleDateString('de-AT', {
    weekday: 'short',
    day: '2-digit',
    month: '2-digit',
  })
}

/** "18:00–20:00 Uhr" */
export function formatEventTime(event: ApiPickupEvent): string {
  if (!event.time) return ''
  return `${event.time}${event.end_time ? `–${event.end_time}` : ''} Uhr`
}

/** Short date block for the event card: day number and month. */
export function eventDateParts(event: ApiPickupEvent): { day: string; month: string } {
  const date = new Date(`${event.date}T00:00:00`)
  return {
    day: String(date.getDate()),
    month: date.toLocaleDateString('de-AT', { month: 'short' }).replace('.', ''),
  }
}

function esc(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}

function locationBody(location: ApiPickupLocation, t: Translate): string {
  const parts = [`<span class="pickup-option__name">${esc(location.name)}</span>`]
  if (location.address)
    parts.push(`<span class="pickup-option__meta">${esc(location.address)}</span>`)
  if (location.opening_hours) {
    parts.push(`<span class="pickup-option__meta">${esc(location.opening_hours)}</span>`)
  }
  if (location.notes) parts.push(`<span class="pickup-option__note">${esc(location.notes)}</span>`)
  if (location.map_url) {
    parts.push(
      `<a class="pickup-option__map" href="${esc(location.map_url)}" target="_blank" rel="noopener noreferrer">${esc(t('pickup.map_link'))}</a>`
    )
  }
  return `<span class="pickup-option__body">${parts.join('')}</span>`
}

function eventBody(choice: Extract<PickupChoice, { kind: 'event' }>, t: Translate): string {
  const { event, location } = choice
  const parts = [
    `<span class="pickup-option__when">${esc(formatEventDay(event))} · ${esc(formatEventTime(event))}</span>`,
    `<span class="pickup-option__name">${esc(location.name)}</span>`,
  ]
  if (location.address)
    parts.push(`<span class="pickup-option__meta">${esc(location.address)}</span>`)
  if (event.spots_left !== null && event.spots_left !== undefined) {
    parts.push(
      `<span class="pickup-option__spots">${esc(t('pickup.spots_left', { n: event.spots_left }, event.spots_left))}</span>`
    )
  }
  if (location.map_url) {
    parts.push(
      `<a class="pickup-option__map" href="${esc(location.map_url)}" target="_blank" rel="noopener noreferrer">${esc(t('pickup.map_link'))}</a>`
    )
  }
  return `<span class="pickup-option__body">${parts.join('')}</span>`
}

const PIN =
  '<span class="pickup-option__pin" aria-hidden="true"><svg class="icon-svg"><use href="/svg-icons/icons.svg#location_on" /></svg></span>'

function dateBlock(event: ApiPickupEvent): string {
  const { day, month } = eventDateParts(event)
  return `<span class="pickup-option__date" aria-hidden="true"><strong>${esc(day)}</strong>${esc(month)}</span>`
}

function cardHtml(choice: PickupChoice, t: Translate): string {
  return choice.kind === 'event'
    ? `${dateBlock(choice.event)}${eventBody(choice, t)}`
    : `${PIN}${locationBody(choice.location, t)}`
}

function optionHtml(
  choice: PickupChoice,
  selectedKey: string | null,
  t: Translate,
  single: boolean
) {
  const eventClass = choice.kind === 'event' ? ' pickup-option--event' : ''
  if (single) {
    return `<div class="pickup-option pickup-option--static${eventClass}"><span class="pickup-option__card">${cardHtml(choice, t)}</span></div>`
  }
  return `<label class="pickup-option${eventClass}">
    <input class="pickup-option__input" type="radio" name="pickup_choice" value="${esc(choice.key)}"${choice.key === selectedKey ? ' checked' : ''} />
    <span class="pickup-option__card">${cardHtml(choice, t)}<span class="pickup-option__check" aria-hidden="true"></span></span>
  </label>`
}

/**
 * HTML for the basix Modal on the game page, which only takes markup strings.
 * Mirrors <PickupLocationPicker>; both share the global `.pickup-picker` styles.
 */
export function renderPickupPickerHtml(
  choices: PickupChoice[],
  selectedKey: string | null,
  t: Translate
): string {
  const single = choices.length === 1
  const regular = choices.filter((c) => c.kind === 'location')
  const events = choices.filter((c) => c.kind === 'event')
  const group = (list: PickupChoice[]) =>
    list.map((c) => optionHtml(c, selectedKey, t, single)).join('')

  const eventsHtml = events.length
    ? `${regular.length ? `<p class="pickup-picker__group">${esc(t('pickup.personal_group'))}</p>` : ''}${group(events)}`
    : ''

  return `<fieldset class="pickup-picker">
    <legend class="pickup-picker__legend">${esc(t(single ? 'pickup.legend_single' : 'pickup.legend'))}</legend>
    <p class="pickup-picker__hint">${esc(t('pickup.hint'))}</p>
    <div class="pickup-picker__options">${group(regular)}${eventsHtml}</div>
    <p id="pickup-error" class="pickup-picker__error" role="alert" hidden>${esc(t('pickup.required'))}</p>
  </fieldset>`
}

/** Reads the choice back out of the rendered HTML picker. */
export function readPickupPickerChoice(choices: PickupChoice[]): PickupChoice | null {
  if (choices.length === 1) return choices[0]!
  const checked = document.querySelector<HTMLInputElement>('input[name="pickup_choice"]:checked')
  return choices.find((c) => c.key === checked?.value) ?? null
}
