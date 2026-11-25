import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, type VueWrapper } from '@vue/test-utils'
import DonationsPage from '~/pages/donations.vue'

const submitDonationMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useDonations', () => () => ({ submitDonation: submitDonationMock }))

let wrapper: VueWrapper | null = null

async function mountPage() {
  wrapper = await mountSuspended(DonationsPage)
  await flushPromises()
  return wrapper
}

async function addGame(title: string) {
  const input = wrapper!.find('.donation-form input[type="text"]')
  await input.setValue(title)
  await input.trigger('keydown', { key: 'Enter' })
}

async function addFiles(files: File[]) {
  const input = wrapper!.find('input[type="file"]')
  Object.defineProperty(input.element, 'files', { value: files, configurable: true })
  await input.trigger('change')
}

async function submit() {
  await wrapper!.find('.donation-form').trigger('submit')
  await flushPromises()
}

const image = (name: string) => new File(['x'], name, { type: 'image/jpeg' })

beforeEach(() => {
  submitDonationMock.mockReset()
  URL.createObjectURL = vi.fn(() => 'blob:preview')
  URL.revokeObjectURL = vi.fn()
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
})

describe('/donations', () => {
  it('sends games, confirmation, photos and the bot guards', async () => {
    submitDonationMock.mockResolvedValue({ message: 'Danke für deine Spende!' })
    await mountPage()

    await addGame('Azul')
    await addGame('Catan')
    await wrapper!.find('input[type="checkbox"]').setValue(true)
    await addFiles([image('box.jpg')])
    await submit()

    const fd = submitDonationMock.mock.calls[0]![0] as FormData
    expect(fd.getAll('games[]')).toEqual(['Azul', 'Catan'])
    expect(fd.get('confirmed_complete')).toBe('1')
    expect(fd.getAll('images[]')).toHaveLength(1)
    // the honeypot stays empty and the load time goes along for the timing check
    expect(fd.get('website')).toBe('')
    expect(Number(fd.get('form_loaded_at'))).toBeGreaterThan(0)

    expect(wrapper!.find('.alert-success').text()).toContain('Danke für deine Spende!')
    expect(wrapper!.find('.donation-form').exists()).toBe(false)
  })

  it('takes at most three photos and skips files that are not images', async () => {
    await mountPage()

    await addFiles([
      image('1.jpg'),
      new File(['x'], 'liste.pdf', { type: 'application/pdf' }),
      image('2.jpg'),
      image('3.jpg'),
      image('4.jpg'),
    ])

    expect(wrapper!.findAll('.file-list li, .file-list .file-item')).toHaveLength(3)
  })

  it('puts server errors next to their fields, also for list fields like games.0', async () => {
    submitDonationMock.mockRejectedValue({
      status: 422,
      errors: {
        'games.0': ['Bitte einen Spieltitel angeben.'],
        confirmed_complete: ['Bitte bestätige die Vollständigkeit.'],
      },
    })
    await mountPage()

    await submit()

    expect(wrapper!.text()).toContain('Bitte einen Spieltitel angeben.')
    expect(wrapper!.text()).toContain('Bitte bestätige die Vollständigkeit.')
    expect(wrapper!.find('.donation-form').exists()).toBe(true)
  })

  it('shows a general error when the server is unreachable', async () => {
    submitDonationMock.mockRejectedValue({ status: 500, message: 'Server nicht erreichbar.' })
    await mountPage()

    await submit()

    expect(wrapper!.find('.alert-error').text()).toBe('Server nicht erreichbar.')
  })
})
