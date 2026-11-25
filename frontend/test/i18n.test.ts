import { describe, it, expect } from 'vitest'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

describe('de.json', () => {
  it('has no key twice in the same object — JSON would silently drop the first', () => {
    const raw = readFileSync(resolve(__dirname, '../i18n/locales/de.json'), 'utf-8')
    const duplicates: string[] = []
    // Walk the text with a stack of key sets, one per open object. Whole
    // string literals are consumed, so braces inside texts like "{n} Token"
    // never count as objects.
    const stack: Set<string>[] = []
    const token = /"((?:[^"\\]|\\.)*)"(\s*:)?|[{}]/g
    let m: RegExpExecArray | null
    while ((m = token.exec(raw))) {
      if (m[0] === '{') stack.push(new Set())
      else if (m[0] === '}') stack.pop()
      else if (m[2]) {
        const keys = stack[stack.length - 1]!
        if (keys.has(m[1]!)) duplicates.push(m[1]!)
        keys.add(m[1]!)
      }
    }
    expect(duplicates).toEqual([])
  })

  it('has no bare "@", which vue-i18n reads as a linked message', () => {
    const messages = JSON.parse(
      readFileSync(resolve(__dirname, '../i18n/locales/de.json'), 'utf-8')
    ) as Record<string, unknown>
    const offenders: string[] = []
    const walk = (node: unknown, path: string) => {
      if (typeof node === 'string') {
        // "@" starts a linked message and breaks loading the whole file;
        // write it as {'@'} or keep such values (e.g. e-mail addresses) out of here
        if (node.replace(/\{'@'\}/g, '').includes('@')) offenders.push(path)
      } else if (node && typeof node === 'object') {
        for (const [k, v] of Object.entries(node)) walk(v, path ? `${path}.${k}` : k)
      }
    }
    walk(messages, '')
    expect(offenders).toEqual([])
  })
})
