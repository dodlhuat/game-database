import { describe, it, expect } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import UiRichEditor from '~/components/ui/RichEditor.vue'

// The real basix editor here (UiWrappers.test.ts mocks it): this is about
// how its heading button behaves on what browsers actually produce.
const CALAVERA =
  'Bunte Blumen und <strong>Calavera</strong>.<h2>Für wen ist das Spiel?<br></h2>' +
  '<ul><li>2–4 Personen ab 8 Jahren</li></ul>Gut zu wissen<br>' +
  '<div>Calavera stammt von Klaus-Jürgen Wrede.<br></div>'

function putCursorIn(root: Element, text: string) {
  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT)
  for (let n = walker.nextNode(); n; n = walker.nextNode()) {
    if (n.textContent?.includes(text)) {
      const range = document.createRange()
      range.setStart(n, 1)
      range.collapse(true)
      window.getSelection()!.removeAllRanges()
      window.getSelection()!.addRange(range)
      return
    }
  }
  throw new Error(`no text ${text}`)
}

async function makeHeading(text: string) {
  const w = await mountSuspended(UiRichEditor, {
    props: { modelValue: CALAVERA },
    attachTo: document.body,
  })
  const editable = w.find('[data-editor="editable"]').element
  putCursorIn(editable, text)
  await w.find('[data-cmd="formatBlock"][data-value="h2"]').trigger('click')
  await flushPromises()
  await new Promise((r) => setTimeout(r, 0)) // let the MutationObserver report
  return { w, html: editable.innerHTML }
}

describe('UiRichEditor — H2 on text the browser left outside a block', () => {
  it('turns the line after a list into a heading', async () => {
    const { w, html } = await makeHeading('Gut zu wissen')

    expect(html).toContain('</ul><h2>Gut zu wissen</h2>')
    expect(w.emitted('update:modelValue')!.at(-1)![0]).toContain('<h2>Gut zu wissen</h2>')
    w.unmount()
  })

  it('turns the very first line into a heading without swallowing the rest', async () => {
    const { w, html } = await makeHeading('Bunte Blumen')

    expect(html.startsWith('<h2>Bunte Blumen und <strong>Calavera</strong>.</h2><h2>Für wen')).toBe(
      true
    )
    w.unmount()
  })
})
