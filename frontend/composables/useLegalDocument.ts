export type LegalBlock =
  | { type: 'meta'; text: string }
  | { type: 'h2'; id: string; no: string; text: string }
  | { type: 'h3'; label: string; text: string }
  | { type: 'p'; lines: string[] }
  | { type: 'list'; items: string[] }
  | { type: 'basis'; text: string }
  | { type: 'footer'; text: string }

export interface LegalSection {
  id: string
  no: string
  text: string
}

const SECTION_SIGN = /^§\s*(\d+)\s+(.+)$/
const NUMBERED = /^(\d+)\.\s+(\S.*)$/
const SUB = /^([a-z])\)\s+(.+)$/
const BULLET = /^[–•-]\s+(.+)$/
const BASIS = /^Rechtsgrundlage:\s*(.+)$/
const FOOTER = /^Zuletzt aktualisiert:/

function isUppercase(text: string): boolean {
  return /[A-ZÄÖÜ]/.test(text) && text === text.toUpperCase()
}

/**
 * Turns the plain-text legal documents (terms, privacy, cookies), which are
 * stored as versioned text in the database, into renderable blocks. Detects
 * "§1 Title" and "1. UPPERCASE TITLE" headings, "a) Subheadings", "– bullets",
 * "Rechtsgrundlage:" notes and the "Zuletzt aktualisiert" footer. The first
 * uppercase line is dropped, because the page hero already shows the title.
 */
export function parseLegalDocument(content: string): LegalBlock[] {
  const blocks: LegalBlock[] = []
  let paragraph: string[] = []
  let list: string[] = []
  let seenSection = false
  let seenTitle = false

  const flush = () => {
    if (paragraph.length) blocks.push({ type: 'p', lines: paragraph })
    if (list.length) blocks.push({ type: 'list', items: list })
    paragraph = []
    list = []
  }

  for (const raw of content.replace(/\r\n?/g, '\n').split('\n')) {
    const line = raw.trim()

    if (!line) {
      flush()
      continue
    }

    const sign = SECTION_SIGN.exec(line)
    const numbered = NUMBERED.exec(line)
    if (sign || (numbered && isUppercase(numbered[2]!))) {
      flush()
      const no = sign ? `§${sign[1]}` : numbered![1]!
      const text = (sign ? sign[2] : numbered![2])!
      blocks.push({ type: 'h2', id: `sec-${sign ? sign[1] : numbered![1]}`, no, text })
      seenSection = true
      continue
    }

    const sub = SUB.exec(line)
    if (sub) {
      flush()
      blocks.push({ type: 'h3', label: sub[1]!, text: sub[2]! })
      continue
    }

    const bullet = BULLET.exec(line)
    if (bullet) {
      if (paragraph.length) flush()
      list.push(bullet[1]!)
      continue
    }

    const basis = BASIS.exec(line)
    if (basis) {
      flush()
      blocks.push({ type: 'basis', text: basis[1]! })
      continue
    }

    if (FOOTER.test(line)) {
      flush()
      blocks.push({ type: 'footer', text: line })
      continue
    }

    if (!seenSection) {
      if (!seenTitle && isUppercase(line)) {
        seenTitle = true
        continue
      }
      if (line.startsWith('(')) {
        flush()
        blocks.push({ type: 'meta', text: line.replace(/^\(|\)$/g, '') })
        continue
      }
    }

    if (list.length) flush()
    paragraph.push(line)
  }

  flush()
  return blocks
}

export function legalSections(blocks: LegalBlock[]): LegalSection[] {
  return blocks.flatMap((b) => (b.type === 'h2' ? [{ id: b.id, no: b.no, text: b.text }] : []))
}
