import { describe, it, expect } from 'vitest'
import { parseLegalDocument, legalSections } from '~/composables/useLegalDocument'

describe('parseLegalDocument', () => {
  it('parses numbered uppercase headings, subheadings, bullets, basis and footer', () => {
    const blocks = parseLegalDocument(
      [
        'DATENSCHUTZERKLÄRUNG',
        '(gem. DSGVO, Stand: Oktober 2026)',
        '',
        '1. VERANTWORTLICHER',
        'Name',
        'Straße 1',
        '',
        '3. VERARBEITETE DATEN',
        '',
        'a) Mitgliedschaftsdaten',
        'Text.',
        'Rechtsgrundlage: Art. 6 DSGVO',
        '',
        '4. WEITERGABE',
        'Nur wenn:',
        '– Gesetz,',
        '– Einwilligung.',
        '',
        'Zuletzt aktualisiert: Oktober 2026',
      ].join('\n')
    )

    expect(blocks[0]).toEqual({ type: 'meta', text: 'gem. DSGVO, Stand: Oktober 2026' })
    expect(blocks.find((b) => b.type === 'h2')).toMatchObject({ no: '1', id: 'sec-1' })
    expect(blocks).toContainEqual({ type: 'p', lines: ['Name', 'Straße 1'] })
    expect(blocks).toContainEqual({ type: 'h3', label: 'a', text: 'Mitgliedschaftsdaten' })
    expect(blocks).toContainEqual({ type: 'basis', text: 'Art. 6 DSGVO' })
    expect(blocks).toContainEqual({ type: 'p', lines: ['Nur wenn:'] })
    expect(blocks).toContainEqual({ type: 'list', items: ['Gesetz,', 'Einwilligung.'] })
    expect(blocks.at(-1)).toEqual({ type: 'footer', text: 'Zuletzt aktualisiert: Oktober 2026' })
    expect(legalSections(blocks).map((s) => s.no)).toEqual(['1', '3', '4'])
  })

  it('parses §-headings of the terms and keeps following lines as one paragraph', () => {
    const blocks = parseLegalDocument(
      '§1 Geltungsbereich\nZeile eins.\nZeile zwei.\n\n§2 Ausleihe\nText.'
    )

    expect(blocks[0]).toMatchObject({ type: 'h2', no: '§1', text: 'Geltungsbereich' })
    expect(blocks[1]).toEqual({ type: 'p', lines: ['Zeile eins.', 'Zeile zwei.'] })
    expect(blocks[2]).toMatchObject({ type: 'h2', no: '§2' })
  })

  it('does not treat numbered mixed-case lines as headings', () => {
    const blocks = parseLegalDocument('2. Soweit möglich ist das so.')

    expect(blocks).toEqual([{ type: 'p', lines: ['2. Soweit möglich ist das so.'] }])
  })
})
