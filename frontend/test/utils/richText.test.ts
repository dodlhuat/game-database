import { describe, it, expect, beforeEach } from 'vitest'
import { wrapLooseLineAtCursor } from '~/utils/richText'

let editable: HTMLElement

function setup(html: string) {
  editable = document.createElement('div')
  editable.innerHTML = html
  document.body.replaceChildren(editable)
}

/** Puts the cursor into the first text node containing `text`. */
function cursorIn(text: string, offset = 2) {
  const walker = document.createTreeWalker(editable, NodeFilter.SHOW_TEXT)
  for (let n = walker.nextNode(); n; n = walker.nextNode()) {
    if (n.textContent?.includes(text)) {
      const range = document.createRange()
      range.setStart(n, offset)
      range.collapse(true)
      window.getSelection()!.removeAllRanges()
      window.getSelection()!.addRange(range)
      return n
    }
  }
  throw new Error(`no text ${text}`)
}

beforeEach(() => {
  document.body.innerHTML = ''
})

describe('wrapLooseLineAtCursor', () => {
  it('wraps a loose line after a list, as the browser leaves it', () => {
    setup('<ul><li>2–4 Personen</li></ul>Gut zu wissen<br><div>Calavera stammt von …</div>')
    cursorIn('Gut zu wissen')

    expect(wrapLooseLineAtCursor(editable)).toBe(true)
    expect(editable.innerHTML).toBe(
      '<ul><li>2–4 Personen</li></ul><div>Gut zu wissen</div><div>Calavera stammt von …</div>'
    )
  })

  it('takes inline formatting along and keeps the cursor where it was', () => {
    setup('Bunte Blumen und <strong>Calavera</strong> heute<h2>So geht es</h2>')
    const node = cursorIn('Bunte', 3)

    wrapLooseLineAtCursor(editable)

    expect(editable.firstElementChild!.outerHTML).toBe(
      '<div>Bunte Blumen und <strong>Calavera</strong> heute</div>'
    )
    const range = window.getSelection()!.getRangeAt(0)
    expect(range.startContainer).toBe(node)
    expect(range.startOffset).toBe(3)
  })

  it('wraps only the line of the cursor when loose lines are separated by <br>', () => {
    setup('erste Zeile<br>zweite Zeile')
    cursorIn('zweite')

    wrapLooseLineAtCursor(editable)

    expect(editable.innerHTML).toBe('erste Zeile<br><div>zweite Zeile</div>')
  })

  it('leaves lines that already sit in a block alone', () => {
    setup('<div>Schon ein Block</div><h2>Überschrift</h2>')
    cursorIn('Schon')

    expect(wrapLooseLineAtCursor(editable)).toBe(false)
    expect(editable.innerHTML).toBe('<div>Schon ein Block</div><h2>Überschrift</h2>')
  })
})
