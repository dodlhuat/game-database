import { Modal } from '@dodlhuat/basix/js/modal'
import { Toast } from '@dodlhuat/basix/js/toast'
import { escapeHtml } from '@dodlhuat/basix/js/utils'

export interface ConfirmOptions {
  title: string
  message: string
  confirmLabel: string
  cancelLabel?: string
  /** Destructive actions get a red confirm button. */
  danger?: boolean
}

/** basix Modal that reports every way it closes (button, ✕, Escape, backdrop). */
class ConfirmModal extends Modal {
  constructor(
    options: ConstructorParameters<typeof Modal>[0],
    private readonly onClose: () => void
  ) {
    super(options)
  }

  override hide(): void {
    // show() calls hide() first to clear an old wrapper — that is not a close
    const wasOpen = this.isVisible()
    super.hide()
    if (wasOpen) this.onClose()
  }
}

/**
 * Styled replacements for window.confirm / window.alert, built on the basix
 * Modal and Toast so they match the rest of the UI.
 */
export function useDialogs() {
  const { t } = useI18n()

  /** Resolves true only when the confirm button was pressed. */
  function confirmDialog(options: ConfirmOptions): Promise<boolean> {
    return new Promise((resolve) => {
      let confirmed = false
      const modal = new ConfirmModal(
        {
          header: escapeHtml(options.title),
          content: `<p class="confirm-dialog__message">${escapeHtml(options.message)}</p>`,
          footer: `<div class="confirm-dialog__actions">
            <button type="button" class="button" data-confirm-cancel>${escapeHtml(options.cancelLabel ?? t('btn.cancel'))}</button>
            <button type="button" class="button ${options.danger ? 'button-error' : 'button-primary'}" data-confirm-ok>${escapeHtml(options.confirmLabel)}</button>
          </div>`,
          closeable: true,
          iconBasePath: '/svg-icons/',
        },
        () => resolve(confirmed)
      )
      modal.show()

      requestAnimationFrame(() => {
        const wrapper = document.querySelector('.modal-wrapper:last-of-type')
        wrapper
          ?.querySelector('[data-confirm-cancel]')
          ?.addEventListener('click', () => modal.hide())
        const ok = wrapper?.querySelector<HTMLButtonElement>('[data-confirm-ok]')
        ok?.addEventListener('click', () => {
          confirmed = true
          modal.hide()
        })
        ok?.focus()
      })
    })
  }

  /** Short message in the corner; errors stay a little longer. */
  function notify(message: string, type: 'success' | 'error' | 'info' = 'success') {
    // Toast escapes its content itself
    new Toast({ content: message, type, iconBasePath: '/svg-icons/' }).show(
      type === 'error' ? 6000 : 3500
    )
  }

  return { confirmDialog, notify }
}
