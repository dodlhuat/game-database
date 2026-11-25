import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, DOMWrapper, type VueWrapper } from '@vue/test-utils'
import AdminEmailsPage from '~/pages/admin/emails.vue'

// The edit dialog renders via <Teleport to="body">
function dialog() {
  return new DOMWrapper(document.body).find('.modal-overlay')
}

const adm = vi.hoisted(() => ({
  fetchEmailTemplates: vi.fn(),
  updateEmailTemplate: vi.fn(),
  resetEmailTemplate: vi.fn(),
}))
mockNuxtImport('useAdmin', () => () => adm)

const confirmMock = vi.hoisted(() => vi.fn())
const notifyMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useDialogs', () => {
  return () => ({ confirmDialog: confirmMock, notify: notifyMock })
})

const TEMPLATE = {
  id: 1,
  key: 'pickup_event_reminder',
  subject: 'Morgen: Abholung bei {location}',
  greeting: 'Hallo {name},',
  body: '<p>Eigener Text</p>',
  action_text: 'Zum Dashboard',
}

let wrapper: VueWrapper | null = null

async function openTemplate() {
  adm.fetchEmailTemplates.mockResolvedValue({ data: [{ ...TEMPLATE }] })
  wrapper = await mountSuspended(AdminEmailsPage, { global: { stubs: { UiRichEditor: true } } })
  await flushPromises()
  await wrapper.find('.tpl-card').trigger('click')
  await flushPromises()
}

function button(text: string) {
  return dialog()
    .findAll('button')
    .find((b) => b.text() === text)!
}

beforeEach(() => {
  Object.values(adm).forEach((fn) => fn.mockReset())
  confirmMock.mockReset()
  notifyMock.mockReset()
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
})

describe('admin/emails.vue', () => {
  it('shows each template with its name and placeholders', async () => {
    await openTemplate()

    const card = wrapper!.find('.tpl-card')
    expect(card.text()).toContain('Abholtermin morgen')
    expect(card.findAll('.tpl-card__var').map((v) => v.text())).toContain('{location}')
  })

  it('saves the edited texts and an empty button text as none', async () => {
    adm.updateEmailTemplate.mockResolvedValue({ data: { ...TEMPLATE, subject: 'Neu' } })
    await openTemplate()

    const [subject, , action] = dialog().findAll('input')
    await subject!.setValue('Neu')
    await action!.setValue('')
    await button('Speichern').trigger('click')
    await flushPromises()

    expect(adm.updateEmailTemplate).toHaveBeenCalledWith('pickup_event_reminder', {
      subject: 'Neu',
      greeting: 'Hallo {name},',
      body: '<p>Eigener Text</p>',
      action_text: null,
    })
    expect(wrapper!.find('.tpl-card__subject').text()).toBe('Neu')
  })

  it('resets to the standard text only after asking', async () => {
    adm.resetEmailTemplate.mockResolvedValue({ data: { ...TEMPLATE, body: '<p>Standard</p>' } })
    await openTemplate()

    confirmMock.mockResolvedValueOnce(false)
    await dialog().find('.action-btn--muted').trigger('click')
    await flushPromises()
    expect(adm.resetEmailTemplate).not.toHaveBeenCalled()

    confirmMock.mockResolvedValueOnce(true)
    await dialog().find('.action-btn--muted').trigger('click')
    await flushPromises()
    expect(confirmMock).toHaveBeenLastCalledWith(expect.objectContaining({ danger: true }))
    expect(adm.resetEmailTemplate).toHaveBeenCalledWith('pickup_event_reminder')
  })

  it('keeps the dialog open with the server message when saving fails', async () => {
    adm.updateEmailTemplate.mockRejectedValue({ status: 422, message: 'Der Betreff ist zu lang.' })
    await openTemplate()

    await button('Speichern').trigger('click')
    await flushPromises()

    expect(dialog().find('.form-error').text()).toBe('Der Betreff ist zu lang.')
  })
})
