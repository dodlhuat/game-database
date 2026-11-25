import { describe, it, expect, beforeEach, vi, type Mock } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import UiInput from '~/components/ui/Input.vue'
import UiButton from '~/components/ui/Button.vue'
import UiBadge from '~/components/ui/Badge.vue'
import UiCard from '~/components/ui/Card.vue'
import UiSwitch from '~/components/ui/Switch.vue'
import UiDatePicker from '~/components/ui/DatePicker.vue'
import UiTimeSpanPicker from '~/components/ui/TimeSpanPicker.vue'
import UiVirtualDropdown from '~/components/ui/VirtualDropdown.vue'
import UiRichEditor from '~/components/ui/RichEditor.vue'

// The basix widgets draw their own DOM; these stand-ins record what the
// wrappers hand them and let the tests fire their callbacks.
const basix = vi.hoisted(() => ({
  datepicker: null as null | { onSelect: (d: Date) => void; min?: Date },
  timepicker: null as null | {
    opts: { defaultStart?: string; onChange: (s: string, e: string) => void }
    value: { start: string; end: string }
    setValue: Mock<(...args: unknown[]) => void>
    reset: Mock<(...args: unknown[]) => void>
  },
  dropdown: null as null | {
    opts: { options: { value: string | number }[]; onSelect: (v: (string | number)[]) => void }
    setValue: Mock<(...args: unknown[]) => void>
  },
  destroyed: [] as string[],
}))

vi.mock('@dodlhuat/basix/js/datepicker', () => ({
  DatePicker: class {
    constructor(_el: HTMLElement, opts: { onSelect: (d: Date) => void; min?: Date }) {
      basix.datepicker = opts
    }
    destroy() {
      basix.destroyed.push('datepicker')
    }
  },
}))

vi.mock('@dodlhuat/basix/js/timepicker', () => ({
  TimeSpanPicker: class {
    constructor(
      _el: HTMLElement,
      opts: { defaultStart?: string; defaultEnd?: string; onChange: (s: string, e: string) => void }
    ) {
      basix.timepicker = {
        opts,
        value: { start: opts.defaultStart ?? '', end: opts.defaultEnd ?? '' },
        setValue: vi.fn(),
        reset: vi.fn(),
      }
    }
    getValue() {
      return basix.timepicker!.value
    }
    setValue(s: string, e: string) {
      basix.timepicker!.setValue(s, e)
    }
    reset() {
      basix.timepicker!.reset()
    }
    destroy() {
      basix.destroyed.push('timepicker')
    }
  },
}))

vi.mock('@dodlhuat/basix/js/virtual-dropdown', () => ({
  VirtualDropdown: class {
    constructor(opts: {
      options: { value: string | number }[]
      onSelect: (v: (string | number)[]) => void
    }) {
      basix.dropdown = { opts, setValue: vi.fn() }
    }
    setValue(v: (string | number)[]) {
      basix.dropdown!.setValue(v)
    }
    clearSelection() {}
    destroy() {
      basix.destroyed.push('dropdown')
    }
  },
}))

vi.mock('@dodlhuat/basix/js/editor', () => ({
  Editor: class {
    destroy() {
      basix.destroyed.push('editor')
    }
  },
}))

beforeEach(() => {
  basix.datepicker = null
  basix.timepicker = null
  basix.dropdown = null
  basix.destroyed = []
})

describe('UiInput', () => {
  it('ties the label to the field and shows the error', async () => {
    const w = await mountSuspended(UiInput, {
      props: { label: 'E-Mail', id: 'mail', error: 'Ungültig', required: true },
    })

    expect(w.find('label').attributes('for')).toBe('mail')
    expect(w.find('input').attributes('id')).toBe('mail')
    expect(w.find('[role="alert"]').text()).toBe('Ungültig')
  })

  it('lets a password be shown and hidden again', async () => {
    const w = await mountSuspended(UiInput, { props: { type: 'password' } })
    const toggle = w.find('button[aria-pressed]')

    expect(w.find('input').attributes('type')).toBe('password')
    await toggle.trigger('click')
    expect(w.find('input').attributes('type')).toBe('text')
    expect(toggle.attributes('aria-pressed')).toBe('true')
    await toggle.trigger('click')
    expect(w.find('input').attributes('type')).toBe('password')
  })

  it('reports what is typed', async () => {
    const w = await mountSuspended(UiInput, { props: { modelValue: '' } })
    await w.find('input').setValue('Mia')
    expect(w.emitted('update:modelValue')!.at(-1)).toEqual(['Mia'])
  })
})

describe('small building blocks', () => {
  it('UiButton cannot be clicked while loading', async () => {
    const w = await mountSuspended(UiButton, {
      props: { loading: true },
      slots: { default: 'Senden' },
    })
    expect(w.find('button').attributes('disabled')).toBeDefined()
    expect(w.find('button').attributes('type')).toBe('button')
  })

  it('UiBadge colours by variant', async () => {
    const w = await mountSuspended(UiBadge, {
      props: { variant: 'loaned' },
      slots: { default: 'Verliehen' },
    })
    expect(w.classes()).toContain('badge-error')
  })

  it('UiCard renders header and footer only when given', async () => {
    const plain = await mountSuspended(UiCard, { slots: { default: 'Inhalt' } })
    expect(plain.find('.header').exists()).toBe(false)
    const full = await mountSuspended(UiCard, {
      slots: { default: 'x', header: 'Kopf', footer: 'Fuß' },
    })
    expect(full.find('.header').text()).toBe('Kopf')
    expect(full.find('.footer').text()).toBe('Fuß')
  })

  it('UiSwitch reports on and off', async () => {
    const w = await mountSuspended(UiSwitch, { props: { modelValue: false, label: 'Aktiv' } })
    await w.find('input').setValue(true)
    expect(w.emitted('update:modelValue')).toEqual([[true]])
  })
})

describe('UiDatePicker', () => {
  it('hands back ISO dates and shows the Austrian format', async () => {
    const w = await mountSuspended(UiDatePicker, { props: { modelValue: '2026-12-03' } })
    await vi.waitFor(() => {
      if (!basix.datepicker) throw new Error('not built yet')
    })

    expect((w.find('input').element as HTMLInputElement).value).toBe('03.12.2026')
    basix.datepicker!.onSelect(new Date(2027, 0, 9))
    expect(w.emitted('update:modelValue')).toEqual([['2027-01-09']])
  })

  it('clears the field when the value is reset and cleans up', async () => {
    const w = await mountSuspended(UiDatePicker, { props: { modelValue: '2026-12-03' } })
    await vi.waitFor(() => {
      if (!basix.datepicker) throw new Error('not built yet')
    })

    await w.setProps({ modelValue: '' })
    expect((w.find('input').element as HTMLInputElement).value).toBe('')
    w.unmount()
    expect(basix.destroyed).toContain('datepicker')
  })
})

describe('UiTimeSpanPicker', () => {
  it('reports start and end together', async () => {
    const w = await mountSuspended(UiTimeSpanPicker, { props: { start: '18:00', end: '22:00' } })
    await vi.waitFor(() => {
      if (!basix.timepicker) throw new Error('not built yet')
    })

    expect(basix.timepicker!.opts.defaultStart).toBe('18:00')
    basix.timepicker!.opts.onChange('19:00', '23:00')
    expect(w.emitted('update:start')).toEqual([['19:00']])
    expect(w.emitted('update:end')).toEqual([['23:00']])
  })

  it('follows outside changes without feeding its own back', async () => {
    const w = await mountSuspended(UiTimeSpanPicker, { props: { start: '18:00', end: '22:00' } })
    await vi.waitFor(() => {
      if (!basix.timepicker) throw new Error('not built yet')
    })

    // the same value the picker already holds: nothing to do
    await w.setProps({ start: '18:00', end: '22:00' })
    expect(basix.timepicker!.setValue).not.toHaveBeenCalled()

    await w.setProps({ start: '10:00', end: '12:00' })
    expect(basix.timepicker!.setValue).toHaveBeenCalledWith('10:00', '12:00')

    await w.setProps({ start: '', end: '' })
    expect(basix.timepicker!.reset).toHaveBeenCalled()
  })
})

describe('UiVirtualDropdown', () => {
  const options = [
    { label: 'Keine', value: null },
    { label: 'Linz', value: 1 },
    { label: 'Wels', value: 2 },
  ]

  it('passes null through as a real choice', async () => {
    const w = await mountSuspended(UiVirtualDropdown, { props: { modelValue: null, options } })
    await vi.waitFor(() => {
      if (!basix.dropdown) throw new Error('not built yet')
    })

    expect(basix.dropdown!.opts.options.map((o) => o.value)).toEqual(['__null__', 1, 2])
    expect(basix.dropdown!.setValue).toHaveBeenLastCalledWith(['__null__'])

    basix.dropdown!.opts.onSelect(['__null__'])
    basix.dropdown!.opts.onSelect([2])
    expect(w.emitted('update:modelValue')).toEqual([[null], [2]])
    expect(w.emitted('change')).toHaveLength(2)
  })

  it('returns a list in multiple mode, without the null entry', async () => {
    const w = await mountSuspended(UiVirtualDropdown, {
      props: { modelValue: [], options, multiple: true },
    })
    await vi.waitFor(() => {
      if (!basix.dropdown) throw new Error('not built yet')
    })

    basix.dropdown!.opts.onSelect(['__null__', 1, 2])
    expect(w.emitted('update:modelValue')).toEqual([[[1, 2]]])
  })

  it('rebuilds when the options change', async () => {
    const w = await mountSuspended(UiVirtualDropdown, { props: { modelValue: 1, options } })
    await vi.waitFor(() => {
      if (!basix.dropdown) throw new Error('not built yet')
    })

    await w.setProps({ options: [...options, { label: 'Steyr', value: 3 }] })
    await flushPromises()

    expect(basix.destroyed).toContain('dropdown')
    expect(basix.dropdown!.opts.options).toHaveLength(4)
  })
})

describe('UiRichEditor', () => {
  it('reports edits but not the value it was handed', async () => {
    const w = await mountSuspended(UiRichEditor, { props: { modelValue: '<p>Start</p>' } })
    const editable = w.find('[data-editor="editable"]').element as HTMLElement
    expect(editable.innerHTML).toBe('<p>Start</p>')

    // a new value from outside must not come back as an edit
    await w.setProps({ modelValue: '<p>Von außen</p>' })
    await flushPromises()
    expect(w.emitted('update:modelValue')).toBeUndefined()

    editable.innerHTML = '<p>Getippt</p>'
    await vi.waitFor(() => {
      if (!w.emitted('update:modelValue')) throw new Error('no edit yet')
    })
    expect(w.emitted('update:modelValue')!.at(-1)).toEqual(['<p>Getippt</p>'])
  })
})
