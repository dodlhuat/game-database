import { describe, it, expect } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import ChipInput from '~/components/ui/ChipInput.vue'

function mountChips(modelValue: string[] = []) {
  return mountSuspended(ChipInput, { props: { modelValue } })
}

describe('ChipInput', () => {
  it('commits the draft as a new chip on Enter and clears the input', async () => {
    const wrapper = await mountChips(['existing'])
    const input = wrapper.find('input')

    await input.setValue('new-chip')
    await input.trigger('keydown', { key: 'Enter' })

    expect(wrapper.emitted('update:modelValue')?.[0]?.[0]).toEqual(['existing', 'new-chip'])
    expect((input.element as HTMLInputElement).value).toBe('')
  })

  it('also commits on comma', async () => {
    const wrapper = await mountChips([])
    const input = wrapper.find('input')

    await input.setValue('tag')
    await input.trigger('keydown', { key: ',' })

    expect(wrapper.emitted('update:modelValue')?.[0]?.[0]).toEqual(['tag'])
  })

  it('does not add an empty or whitespace-only chip', async () => {
    const wrapper = await mountChips(['existing'])
    const input = wrapper.find('input')

    await input.setValue('   ')
    await input.trigger('keydown', { key: 'Enter' })

    expect(wrapper.emitted('update:modelValue')).toBeUndefined()
  })

  it('removes the last chip on Backspace when the input is empty', async () => {
    const wrapper = await mountChips(['a', 'b'])
    const input = wrapper.find('input')

    await input.trigger('keydown', { key: 'Backspace' })

    expect(wrapper.emitted('update:modelValue')?.[0]?.[0]).toEqual(['a'])
  })

  it('does not remove a chip on Backspace while the input has text', async () => {
    const wrapper = await mountChips(['a', 'b'])
    const input = wrapper.find('input')

    await input.setValue('typing')
    await input.trigger('keydown', { key: 'Backspace' })

    expect(wrapper.emitted('update:modelValue')).toBeUndefined()
  })

  it('removes a specific chip via its remove button', async () => {
    const wrapper = await mountChips(['a', 'b', 'c'])

    await wrapper.findAll('button')[1]!.trigger('click')

    expect(wrapper.emitted('update:modelValue')?.[0]?.[0]).toEqual(['a', 'c'])
  })

  it('commits the draft on blur', async () => {
    const wrapper = await mountChips([])
    const input = wrapper.find('input')

    await input.setValue('blurred-chip')
    await input.trigger('blur')

    expect(wrapper.emitted('update:modelValue')?.[0]?.[0]).toEqual(['blurred-chip'])
  })
})
