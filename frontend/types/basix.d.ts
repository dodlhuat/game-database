// Ambient declarations for @dodlhuat/basix JS modules (no bundled type definitions)

declare module '@dodlhuat/basix/js/virtual-dropdown' {
  export class VirtualDropdown {
    constructor(options: {
      container: HTMLElement
      options: { label: string; value: string | number }[]
      placeholder?: string
      searchable?: boolean
      multiSelect?: boolean
      onSelect?: (values: Array<string | number>) => void
    })
    setValue(values: Array<string | number>): void
    clearSelection(): void
    destroy(): void
  }
}

declare module '@dodlhuat/basix/js/push-menu' {
  export namespace PushMenu {
    function init(): void
    function destroy(): void
    function openPanel(el: HTMLElement): void
    function close(): void
  }
}

declare module '@dodlhuat/basix/js/group-picker' {
  export class GroupPicker {
    constructor(
      el: HTMLElement,
      data: Array<{
        id: string
        label: string
        subgroups?: Array<{ id: string; label: string }>
      }>,
      options?: {
        searchPlaceholder?: string
        selectAllLabel?: string
        deselectLabel?: string
        emptyLabel?: string
        onSelectionChange?: (selection: {
          parentGroups: string[]
          subgroups: Array<{ groupId: string; subgroupId: string }>
        }) => void
      }
    )
    setSelection(selection: {
      parentGroups: string[]
      subgroups: Array<{ groupId: string; subgroupId: string }>
    }): void
    destroy(): void
  }
}

declare module '@dodlhuat/basix/js/datepicker' {
  export class Datepicker {
    constructor(el: HTMLElement, options?: Record<string, unknown>)
    destroy(): void
  }
  export { Datepicker as DatePicker }
}

declare module '@dodlhuat/basix/js/editor' {
  export class Editor {
    constructor(options: { root: HTMLElement; [key: string]: unknown })
    getHTML(): string
    setHTML(html: string): void
    destroy(): void
  }
}

declare module '@dodlhuat/basix/js/modal' {
  export class Modal {
    constructor(options: {
      content: string | HTMLElement
      header?: string
      footer?: string | HTMLElement
      closeable?: boolean
      [key: string]: unknown
    })
    show(): void
    hide(): void
    destroy(): void
  }
}

declare module '@dodlhuat/basix/js/lightbox' {
  export class Lightbox {
    constructor(options: {
      images: Array<{ src: string; alt?: string }>
      startIndex?: number
      [key: string]: unknown
    })
    show(): void
    destroy(): void
  }
}

declare module '@dodlhuat/basix/js/calendar' {
  export class Calendar {
    constructor(options: { container: HTMLElement; events?: unknown[]; [key: string]: unknown })
    destroy(): void
  }
}
