import type { ApiPickupLocation } from '~/types/api'

type Translate = (key: string) => string

const STORAGE_KEY = 'last_pickup_location'

/** Remembers the member's last choice so the next borrow dialog preselects it. */
export function rememberPickupLocation(id: number) {
  try {
    localStorage.setItem(STORAGE_KEY, String(id))
  } catch {
    // private mode / blocked storage — preselection is only a convenience
  }
}

/** Preselection: the only location, else the last one used if it is still offered. */
export function initialPickupLocationId(locations: ApiPickupLocation[]): number | null {
  if (locations.length === 1) return locations[0]!.id
  try {
    const stored = Number(localStorage.getItem(STORAGE_KEY))
    if (locations.some((l) => l.id === stored)) return stored
  } catch {
    // ignore
  }
  return null
}

function esc(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}

function cardBody(location: ApiPickupLocation, t: Translate): string {
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

const PIN =
  '<span class="pickup-option__pin" aria-hidden="true"><svg class="icon-svg"><use href="/svg-icons/icons.svg#location_on" /></svg></span>'

/**
 * HTML for the basix Modal on the game page, which only takes markup strings.
 * Mirrors <PickupLocationPicker>; both share the global `.pickup-picker` styles.
 */
export function renderPickupPickerHtml(
  locations: ApiPickupLocation[],
  selectedId: number | null,
  t: Translate
): string {
  const single = locations.length === 1
  const options = locations
    .map((location) =>
      single
        ? `<div class="pickup-option pickup-option--static"><span class="pickup-option__card">${PIN}${cardBody(location, t)}</span></div>`
        : `<label class="pickup-option">
            <input class="pickup-option__input" type="radio" name="pickup_location" value="${location.id}"${location.id === selectedId ? ' checked' : ''} />
            <span class="pickup-option__card">${PIN}${cardBody(location, t)}<span class="pickup-option__check" aria-hidden="true"></span></span>
          </label>`
    )
    .join('')

  return `<fieldset class="pickup-picker">
    <legend class="pickup-picker__legend">${esc(t(single ? 'pickup.legend_single' : 'pickup.legend'))}</legend>
    <p class="pickup-picker__hint">${esc(t('pickup.hint'))}</p>
    <div class="pickup-picker__options">${options}</div>
    <p id="pickup-error" class="pickup-picker__error" role="alert" hidden>${esc(t('pickup.required'))}</p>
  </fieldset>`
}

/** Reads the choice back out of the rendered HTML picker. */
export function readPickupPickerChoice(locations: ApiPickupLocation[]): number | null {
  if (locations.length === 1) return locations[0]!.id
  const checked = document.querySelector<HTMLInputElement>('input[name="pickup_location"]:checked')
  return checked ? Number(checked.value) : null
}
