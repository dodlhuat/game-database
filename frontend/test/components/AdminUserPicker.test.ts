import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { mockNuxtImport, mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import UserPicker from '~/components/admin/UserPicker.vue'
import type { PickupLocationUser } from '~/composables/usePickupLocations'

const getMock = vi.hoisted(() => vi.fn())
mockNuxtImport('useApi', () => {
  return () => ({ get: getMock, post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() })
})

const MIA = { id: 7, name: 'Mia', email: 'mia@example.at', role: 'MEMBER', is_member: true }
const TOM = { id: 8, name: 'Tom', email: 'tom@example.at', role: 'USER', is_member: false }

function mountPicker(modelValue: PickupLocationUser[] = []) {
  return mountSuspended(UserPicker, { props: { modelValue } })
}

async function type(w: Awaited<ReturnType<typeof mountPicker>>, text: string) {
  await w.find('.user-picker__input').setValue(text)
  await vi.advanceTimersByTimeAsync(300) // past the 250 ms debounce
  await flushPromises()
}

beforeEach(() => {
  getMock.mockReset()
  vi.useFakeTimers({ shouldAdvanceTime: true })
})

afterEach(() => {
  vi.useRealTimers()
})

describe('AdminUserPicker', () => {
  it('waits for two letters and a pause before searching', async () => {
    getMock.mockResolvedValue({ data: [MIA] })
    const w = await mountPicker()

    await type(w, 'm')
    expect(getMock).not.toHaveBeenCalled()

    await w.find('.user-picker__input').setValue('mi')
    await w.find('.user-picker__input').setValue('mia')
    await vi.advanceTimersByTimeAsync(300)
    await flushPromises()

    expect(getMock).toHaveBeenCalledTimes(1)
    expect(getMock).toHaveBeenCalledWith('/admin/users', { params: { search: 'mia' } })
  })

  it('picks a user with the keyboard and marks who cannot borrow', async () => {
    getMock.mockResolvedValue({ data: [MIA, TOM] })
    const w = await mountPicker()

    await type(w, 'example')
    await w.find('.user-picker__input').trigger('keydown', { key: 'ArrowDown' })
    await w.find('.user-picker__input').trigger('keydown', { key: 'Enter' })

    expect(w.emitted('update:modelValue')).toEqual([
      [[{ id: 8, name: 'Tom', email: 'tom@example.at', can_borrow: false }]],
    ])
  })

  it('hides users already picked and removes a chip', async () => {
    getMock.mockResolvedValue({ data: [MIA, TOM] })
    const picked = [{ id: 7, name: 'Mia', email: 'mia@example.at', can_borrow: true }]
    const w = await mountPicker(picked)

    await type(w, 'example')
    expect(
      w.findAll('.user-picker__result').map((r) => r.find('.user-picker__name').text())
    ).toEqual(['Tom'])

    await w.find('.user-picker__chips button').trigger('click')
    expect(w.emitted('update:modelValue')!.at(-1)).toEqual([[]])
  })

  it('ignores an answer that arrives after a newer search', async () => {
    let answerSlow!: (v: unknown) => void
    getMock
      .mockImplementationOnce(() => new Promise((r) => (answerSlow = r)))
      .mockResolvedValueOnce({ data: [TOM] })
    const w = await mountPicker()

    await type(w, 'mi')
    await type(w, 'tom')
    answerSlow({ data: [MIA] })
    await flushPromises()

    expect(w.findAll('.user-picker__name').map((n) => n.text())).toEqual(['Tom'])
  })
})
