import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises, type VueWrapper } from '@vue/test-utils'
import LoanSettingsPage from '~/pages/admin/loan-settings.vue'

const getMock = vi.hoisted(() => vi.fn())
const patchMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useApi', () => {
  return () => ({ get: getMock, post: vi.fn(), put: vi.fn(), patch: patchMock, delete: vi.fn() })
})

const SETTINGS = {
  start_date: '2026-12-01T00:00:00.000000Z',
  interval_days: 14,
  grace_days: 3,
  pickup_event_lead_hours: 24,
  loan_duration_weeks: 2,
  max_extensions: 2,
  loan_cost_small: 3,
  loan_cost_medium: 6,
  loan_cost_large: 10,
  package_loan_cost: 3,
  extension_cost: 2,
  reservation_cost: 1,
  condition_very_good_after: 5,
  condition_good_after: 50,
  deposit_pct_very_good: 90,
  deposit_pct_good: 80,
}

let wrapper: VueWrapper | null = null

async function mountPage() {
  wrapper = await mountSuspended(LoanSettingsPage)
  await flushPromises()
  return wrapper
}

beforeEach(() => {
  getMock.mockReset().mockResolvedValue({ ...SETTINGS })
  patchMock.mockReset()
  vi.useFakeTimers({ shouldAdvanceTime: true })
  vi.setSystemTime(new Date('2026-12-01T10:00:00'))
})

afterEach(() => {
  wrapper?.unmount()
  wrapper = null
  vi.useRealTimers()
})

describe('admin/loan-settings.vue', () => {
  it('previews the next pickup dates after the grace period', async () => {
    await mountPage()

    // 1.12. is within the 3 grace days, so the first open date is 15.12.
    const items = wrapper!.findAll('.preview-item').map((i) => i.text())
    expect(items).toHaveLength(4)
    expect(items[0]).toContain('15.12.2026')
    expect(items[0]).toContain('29.12.2026')
    expect(items[3]).toContain('26.01.2027')
  })

  it('saves the settings as plain dates and numbers', async () => {
    patchMock.mockResolvedValue({ ...SETTINGS, loan_cost_large: 12 })
    await mountPage()

    await wrapper!.find('form').trigger('submit')
    await flushPromises()

    expect(patchMock).toHaveBeenCalledWith(
      '/admin/loan-settings',
      expect.objectContaining({ start_date: '2026-12-01', loan_cost_large: 10 })
    )
    expect(wrapper!.find('.save-success').exists()).toBe(true)
  })

  it('shows why the server refused the settings', async () => {
    patchMock.mockRejectedValue({
      status: 422,
      message: 'Die Grenze für "gut" muss über der für "sehr gut" liegen.',
    })
    await mountPage()

    await wrapper!.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper!.find('.form-error').text()).toBe(
      'Die Grenze für "gut" muss über der für "sehr gut" liegen.'
    )
    expect(wrapper!.find('.save-success').exists()).toBe(false)
  })

  it('shows a load error instead of a form with made-up values', async () => {
    getMock.mockRejectedValue({ message: 'Server nicht erreichbar.' })
    await mountPage()

    expect(wrapper!.text()).toContain('Server nicht erreichbar.')
    expect(wrapper!.find('form').exists()).toBe(false)
  })
})
