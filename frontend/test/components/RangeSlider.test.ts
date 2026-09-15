import { describe, it, expect } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import RangeSlider from '~/components/ui/RangeSlider.vue'

function mountSlider(modelValue: [number, number] = [20, 80]) {
  return mountSuspended(RangeSlider, { props: { modelValue, min: 0, max: 100 } })
}

function inputs(wrapper: Awaited<ReturnType<typeof mountSlider>>) {
  const els = wrapper.findAll('input[type="range"]')
  return { start: els[0]!, end: els[1]! }
}

describe('RangeSlider', () => {
  it('emits update:modelValue as the start handle moves, clamped to the end value', async () => {
    const wrapper = await mountSlider([20, 80])
    const { start } = inputs(wrapper)

    await start.setValue('90')

    expect(wrapper.emitted('update:modelValue')?.[0]?.[0]).toEqual([80, 80])
  })

  it('emits update:modelValue as the end handle moves, clamped to the start value', async () => {
    const wrapper = await mountSlider([20, 80])
    const { end } = inputs(wrapper)

    await end.setValue('10')

    expect(wrapper.emitted('update:modelValue')?.[0]?.[0]).toEqual([20, 20])
  })

  it('moves the start handle freely below the end value', async () => {
    const wrapper = await mountSlider([20, 80])
    const { start } = inputs(wrapper)

    await start.setValue('50')

    expect(wrapper.emitted('update:modelValue')?.[0]?.[0]).toEqual([50, 80])
  })

  it('only emits "change" on the native change event, not on every input', async () => {
    const wrapper = await mountSlider([20, 80])
    const { start } = inputs(wrapper)

    // VTU's setValue() fires both input and change — drive the raw DOM event
    // directly here since the point of this test is telling them apart.
    ;(start.element as HTMLInputElement).value = '50'
    await start.trigger('input')
    expect(wrapper.emitted('change')).toBeUndefined()

    await start.trigger('change')
    expect(wrapper.emitted('change')?.[0]?.[0]).toEqual([50, 80])
  })

  it('syncs internal state when modelValue changes externally', async () => {
    const wrapper = await mountSlider([20, 80])

    await wrapper.setProps({ modelValue: [30, 70] })
    const { start, end } = inputs(wrapper)

    expect((start.element as HTMLInputElement).value).toBe('30')
    expect((end.element as HTMLInputElement).value).toBe('70')
  })

  it('formats the displayed values with the default formatter', async () => {
    const wrapper = await mountSlider([20, 80])
    const values = wrapper.findAll('.ui-range-slider__value')

    expect(values[0]!.text()).toBe('20')
    expect(values[1]!.text()).toBe('80')
  })

  it('formats the displayed values with a custom formatter', async () => {
    const wrapper = await mountSuspended(RangeSlider, {
      props: {
        modelValue: [20, 80],
        min: 0,
        max: 100,
        formatLabel: (v: number) => `${v}€`,
      },
    })
    const values = wrapper.findAll('.ui-range-slider__value')

    expect(values[0]!.text()).toBe('20€')
    expect(values[1]!.text()).toBe('80€')
  })
})
