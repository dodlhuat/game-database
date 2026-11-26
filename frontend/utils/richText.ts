const BLOCK_TAGS = new Set([
  'DIV',
  'P',
  'H1',
  'H2',
  'H3',
  'H4',
  'H5',
  'H6',
  'UL',
  'OL',
  'LI',
  'BLOCKQUOTE',
  'PRE',
  'TABLE',
  'HR',
])

const isLineBreak = (node: Node) => node.nodeName === 'BR'
const isBlock = (node: Node) => node.nodeType === Node.ELEMENT_NODE && BLOCK_TAGS.has(node.nodeName)

/**
 * Browsers leave text typed after a list (or at the very start) loose in the
 * editable root, outside any block. The basix editor's heading/paragraph
 * buttons replace "the block around the cursor" and silently do nothing
 * there — so a line meant as heading stayed plain text. This wraps the loose
 * line around the cursor (the run of text and inline elements between blocks
 * or line breaks) in a <div>, giving those buttons a block to turn into a
 * heading. Lines that already sit in a block are left alone.
 *
 * Returns true when it wrapped something.
 */
export function wrapLooseLineAtCursor(editable: HTMLElement): boolean {
  const sel = window.getSelection()
  if (!sel || sel.rangeCount === 0) return false
  const range = sel.getRangeAt(0)
  if (!editable.contains(range.startContainer)) return false

  // The node directly under the editable root that holds the cursor
  let top: Node | null = range.startContainer
  if (top === editable) top = editable.childNodes[range.startOffset] ?? editable.lastChild
  while (top && top.parentNode !== editable) top = top.parentNode
  if (!top || isBlock(top) || isLineBreak(top)) return false

  // Extend to the whole line: inline siblings up to the next block or <br>
  let first: Node = top
  while (
    first.previousSibling &&
    !isBlock(first.previousSibling) &&
    !isLineBreak(first.previousSibling)
  ) {
    first = first.previousSibling
  }
  const line: Node[] = []
  for (let n: Node | null = first; n && !isBlock(n) && !isLineBreak(n); n = n.nextSibling) {
    line.push(n)
  }
  if (!line.some((n) => (n.textContent ?? '').trim())) return false

  // Moving nodes would drop the selection; remember where the cursor was
  const { startContainer, startOffset, endContainer, endOffset } = range
  const block = document.createElement('div')
  editable.insertBefore(block, first)
  line.forEach((n) => block.appendChild(n))
  // A <br> that ended the loose line now only adds an empty line
  if (block.nextSibling && isLineBreak(block.nextSibling)) block.nextSibling.remove()

  const restored = document.createRange()
  restored.setStart(startContainer, startOffset)
  restored.setEnd(endContainer, endOffset)
  sel.removeAllRanges()
  sel.addRange(restored)
  return true
}
