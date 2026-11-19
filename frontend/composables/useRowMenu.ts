import { onBeforeUnmount } from 'vue'
import type { ContextMenu, ContextMenuItemDef } from '@dodlhuat/basix/js/context-menu'

export type RowMenuItem = Pick<ContextMenuItemDef, 'label'> &
  Partial<Pick<ContextMenuItemDef, 'icon' | 'destructive'>> & {
    action: () => unknown
  }

/**
 * A "⋯" row-action menu for admin tables, built on basix's ContextMenu.
 *
 * Why ContextMenu and not basix's Dropdown: Dropdown positions its menu
 * absolutely inside its container, so inside a horizontally scrolling table
 * wrapper it gets clipped (worst on the last rows). ContextMenu renders a
 * fixed-position menu into <body> and already flips at the viewport edges,
 * supports icons and red "destructive" items, and handles Esc/arrow keys.
 *
 * It only opens on a `contextmenu` event, so the trigger button replays one
 * at its own bottom-right corner. The trigger's click must not reach the
 * document (ContextMenu closes on any document click) — use @click.stop.
 */
export function useRowMenu() {
  let menu: ContextMenu | null = null

  async function open(trigger: HTMLElement, items: RowMenuItem[]) {
    const { ContextMenu } = await import('@dodlhuat/basix/js/context-menu')
    menu?.destroy()
    menu = new ContextMenu(
      trigger,
      items.map((item) => ({ ...item, action: () => item.action() })),
      { spritePath: '/svg-icons/icons.svg' }
    )
    const rect = trigger.getBoundingClientRect()
    trigger.dispatchEvent(
      new MouseEvent('contextmenu', {
        bubbles: true,
        cancelable: true,
        clientX: rect.right,
        clientY: rect.bottom + 4,
      })
    )
    // ContextMenu opens rightwards from the point (it's built for a mouse
    // cursor); right-align it under the button instead. It is appended to
    // <body> synchronously by the dispatch above.
    const el = document.body.lastElementChild
    if (el instanceof HTMLElement && el.classList.contains('context-menu')) {
      el.style.left = `${Math.max(8, rect.right - el.offsetWidth)}px`
      el.style.transformOrigin = el.style.transformOrigin.replace('left', 'right')
    }
  }

  onBeforeUnmount(() => {
    menu?.destroy()
    menu = null
  })

  return { open }
}
