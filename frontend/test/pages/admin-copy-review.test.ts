import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, DOMWrapper, type VueWrapper } from '@vue/test-utils'
import CopyReviewPage from '~/pages/admin/copy-review.vue'
import type { AdminCopy } from '~/composables/useAdmin'

// The detail dialog renders via <Teleport to="body">
function body() {
  return new DOMWrapper(document.body)
}

const fetchCopiesMock = vi.hoisted(() => vi.fn())
const lookupCopyMock = vi.hoisted(() => vi.fn())
const approveCopyMock = vi.hoisted(() => vi.fn())
const markCopyDamagedMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useAdmin', () => {
  return () => ({
    fetchCopies: fetchCopiesMock,
    lookupCopy: lookupCopyMock,
    approveCopy: approveCopyMock,
    markCopyDamaged: markCopyDamagedMock,
  })
})

const notifyMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useDialogs', () => {
  return () => ({ confirmDialog: vi.fn(), notify: notifyMock })
})

const daysAgo = (n: number) => new Date(Date.now() - n * 86_400_000).toISOString()

function copy(
  id: number,
  title: string,
  location: { id: number; name: string } | null,
  returned: number
): AdminCopy {
  return {
    id,
    game_id: id,
    game: { id, title },
    condition: 'REVIEW',
    borrow_count: 1,
    qr_code: `QR-${id}`,
    notes: null,
    last_return: {
      return_condition: 'GOOD',
      returned_at: daysAgo(returned),
      user_name: 'Mia',
      pickup_location: location,
    },
  }
}

const linz = { id: 1, name: 'Linz' }
const wels = { id: 2, name: 'Wels' }

let wrapper: VueWrapper | null = null

async function mountPage() {
  wrapper = await mountSuspended(CopyReviewPage, {
    global: { stubs: { UiVirtualDropdown: true } },
  })
  await flushPromises()
  return wrapper
}

function queueTitles() {
  return wrapper!.findAll('.queue-card__title').map((t) => t.text())
}

async function openCard(title: string) {
  await wrapper!
    .findAll('.queue-card')
    .find((c) => c.text().includes(title))!
    .trigger('click')
  await flushPromises()
}

function dialogButton(text: string) {
  return body()
    .findAll('.modal-overlay button')
    .filter((b) => b.text() === text)
    .at(-1)!
}

beforeEach(() => {
  fetchCopiesMock.mockReset()
  lookupCopyMock.mockReset()
  approveCopyMock.mockReset()
  markCopyDamagedMock.mockReset()
  notifyMock.mockReset()
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
})

describe('admin/copy-review.vue — the queue', () => {
  it('loads every page and groups by location, longest waiting first', async () => {
    fetchCopiesMock
      .mockResolvedValueOnce({
        data: [copy(1, 'Azul', linz, 1)],
        meta: { current_page: 1, last_page: 2, total: 2 },
      })
      .mockResolvedValueOnce({
        data: [copy(2, 'Catan', wels, 9)],
        meta: { current_page: 2, last_page: 2, total: 2 },
      })
    await mountPage()

    expect(fetchCopiesMock).toHaveBeenCalledTimes(2)
    expect(fetchCopiesMock).toHaveBeenLastCalledWith({
      condition: 'REVIEW',
      per_page: 200,
      page: 2,
    })
    // Wels waited longer, so it comes first
    expect(wrapper!.findAll('.location-group').map((g) => g.text().includes('Wels'))).toEqual([
      true,
      false,
    ])
    expect(queueTitles()).toEqual(['Catan', 'Azul'])
  })

  it('narrows the list to one location', async () => {
    fetchCopiesMock.mockResolvedValue({
      data: [copy(1, 'Azul', linz, 1), copy(2, 'Catan', wels, 2)],
      meta: { current_page: 1, last_page: 1, total: 2 },
    })
    await mountPage()

    await wrapper!
      .findAll('.location-filter__chip')
      .find((c) => c.text().startsWith('Linz'))!
      .trigger('click')

    expect(queueTitles()).toEqual(['Azul'])
  })

  it('says so when the queue cannot be loaded', async () => {
    fetchCopiesMock.mockRejectedValue({ message: 'Server nicht erreichbar.' })
    await mountPage()

    expect(notifyMock).toHaveBeenCalledWith('Server nicht erreichbar.', 'error')
  })
})

describe('admin/copy-review.vue — checking a copy', () => {
  beforeEach(() => {
    fetchCopiesMock.mockResolvedValue({
      data: [copy(1, 'Azul', linz, 1), copy(2, 'Catan', linz, 2)],
      meta: { current_page: 1, last_page: 1, total: 2 },
    })
  })

  it('approves a copy and takes it off the queue', async () => {
    approveCopyMock.mockResolvedValue({})
    await mountPage()

    await openCard('Azul')
    await dialogButton('Freigeben').trigger('click')
    await flushPromises()

    expect(approveCopyMock).toHaveBeenCalledWith(1, undefined)
    expect(body().find('.modal-overlay').exists()).toBe(false)
    expect(queueTitles()).toEqual(['Catan'])
  })

  it('keeps the copy and the dialog when approving fails', async () => {
    approveCopyMock.mockRejectedValue({ message: 'Exemplar wurde bereits geprüft.' })
    await mountPage()

    await openCard('Azul')
    await dialogButton('Freigeben').trigger('click')
    await flushPromises()

    expect(body().find('.modal-overlay .form-error').text()).toBe('Exemplar wurde bereits geprüft.')
    expect(queueTitles()).toContain('Azul')
  })

  it('marks a copy as damaged with a note', async () => {
    markCopyDamagedMock.mockResolvedValue({})
    await mountPage()

    await openCard('Catan')
    await dialogButton('Als beschädigt markieren').trigger('click') // opens the notes
    await body().find('.detail-damaged input').setValue('Karten fehlen')
    await body().find('.detail-damaged button').trigger('click')
    await flushPromises()

    expect(markCopyDamagedMock).toHaveBeenCalledWith(2, 'Karten fehlen')
    expect(queueTitles()).toEqual(['Azul'])
  })
})

describe('admin/copy-review.vue — scanning', () => {
  beforeEach(() => {
    fetchCopiesMock.mockResolvedValue({
      data: [],
      meta: { current_page: 1, last_page: 1, total: 0 },
    })
  })

  async function scan(code: string) {
    await wrapper!.find('.scan-console__form input').setValue(code)
    await wrapper!.find('.scan-console__form').trigger('submit')
    await flushPromises()
  }

  it('opens the scanned copy', async () => {
    lookupCopyMock.mockResolvedValue({ data: copy(7, 'Brass', linz, 0) })
    await mountPage()

    await scan(' QR-7 ')

    expect(lookupCopyMock).toHaveBeenCalledWith('QR-7')
    expect(body().find('.modal-overlay').text()).toContain('Brass')
  })

  it('tells when a code is unknown', async () => {
    lookupCopyMock.mockRejectedValue({ status: 404 })
    await mountPage()

    await scan('QR-unbekannt')

    expect(body().find('.modal-overlay').exists()).toBe(false)
    expect(wrapper!.text()).toContain('Kein Spiel mit diesem Code gefunden.')
  })

  it('shows a copy that is not waiting for review without the review actions', async () => {
    lookupCopyMock.mockResolvedValue({ data: { ...copy(7, 'Brass', null, 0), condition: 'GOOD' } })
    await mountPage()

    await scan('QR-7')

    expect(body().find('.modal-overlay .form-hint').exists()).toBe(true)
    expect(body().find('.detail-actions').exists()).toBe(false)
  })
})
