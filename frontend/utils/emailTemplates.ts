/**
 * Every mail the app sends, as the admin sees it: a readable name, who gets
 * it, an icon and the placeholders its template may use. Shared by the
 * template editor and the mail log filter. A backend test
 * (EmailTemplateConsistencyTest) checks that each mail has an entry here.
 */
export interface TemplateMeta {
  label: string
  recipient: string
  icon: string
  vars: string[]
}

export const EMAIL_TEMPLATE_META: Record<string, TemplateMeta> = {
  user_approved: {
    label: 'Konto freigeschaltet',
    recipient: 'Mitglied',
    icon: 'check_circle',
    vars: ['name'],
  },
  user_rejected: {
    label: 'Registrierung abgelehnt',
    recipient: 'Mitglied',
    icon: 'close',
    vars: ['name'],
  },
  new_user_registered: {
    label: 'Neue Registrierung',
    recipient: 'Admin',
    icon: 'person',
    vars: ['name', 'email'],
  },
  loan_confirmation: {
    label: 'Ausleihe bestätigt',
    recipient: 'Mitglied',
    icon: 'event_available',
    vars: ['name', 'game', 'start_date', 'due_date', 'location', 'location_details'],
  },
  loan_due_soon: {
    label: 'Rückgabe-Erinnerung',
    recipient: 'Mitglied',
    icon: 'schedule',
    vars: ['name', 'game', 'due_date', 'location', 'location_details'],
  },
  loan_returned: {
    label: 'Rückgabe erfasst',
    recipient: 'Mitglied',
    icon: 'assignment_return',
    vars: ['name', 'game', 'returned_at', 'location', 'location_details'],
  },
  loan_returned_admin: {
    label: 'Rückgabe bestätigt',
    recipient: 'Admin',
    icon: 'location_on',
    vars: ['member', 'game', 'location', 'condition', 'returned_at'],
  },
  pickup_digest_admin: {
    label: 'Tägliche Abholliste',
    recipient: 'Admin',
    icon: 'checklist',
    vars: ['count', 'date', 'pickup_list'],
  },
  membership_payment_duplicate: {
    label: 'Doppelte Zahlung – wird erstattet',
    recipient: 'Mitglied',
    icon: 'undo',
    vars: ['name', 'amount', 'date'],
  },
  membership_payment_duplicate_admin: {
    label: 'Doppelte Zahlung – bitte erstatten',
    recipient: 'Admin',
    icon: 'undo',
    vars: ['name', 'email', 'amount', 'date', 'payment_id'],
  },
  pickup_event_reminder: {
    label: 'Abholtermin morgen',
    recipient: 'Mitglied',
    icon: 'event',
    vars: ['name', 'date', 'time', 'games', 'location', 'location_details'],
  },
  loan_cancelled_no_show: {
    label: 'Nicht abgeholt – storniert',
    recipient: 'Mitglied',
    icon: 'event_busy',
    vars: ['name', 'game', 'pickup'],
  },
  pickup_event_moved: {
    label: 'Abholtermin verschoben',
    recipient: 'Mitglied',
    icon: 'update',
    vars: ['name', 'pickup', 'previously', 'location', 'location_details'],
  },
  reservation_available: {
    label: 'Spiel wieder verfügbar',
    recipient: 'Mitglied',
    icon: 'notifications_active',
    vars: ['name', 'game'],
  },
  email_verification: {
    label: 'E-Mail-Verifizierung',
    recipient: 'Neuer User',
    icon: 'check_circle',
    vars: ['name', 'verification_link'],
  },
  welcome_member: {
    label: 'Willkommen als Mitglied',
    recipient: 'Neues Mitglied',
    icon: 'stars',
    vars: ['name'],
  },
  membership_renewal_reminder: {
    label: 'Ablauf-Erinnerung',
    recipient: 'Mitglied',
    icon: 'calendar_today',
    vars: ['name', 'expiry_date', 'renewal_link'],
  },
  loan_overdue_reminder: {
    label: 'Rückgabe überfällig',
    recipient: 'Mitglied',
    icon: 'warning',
    vars: ['name', 'game', 'due_date', 'location', 'location_details'],
  },
  welcome_supporter: {
    label: 'Willkommen als außerordentliches Mitglied',
    recipient: 'Außerordentliches Mitglied',
    icon: 'volunteer_activism',
    vars: ['name'],
  },
  password_reset: {
    label: 'Passwort zurücksetzen',
    recipient: 'Mitglied',
    icon: 'lock_reset',
    vars: ['name', 'reset_link'],
  },
  deposit_released: {
    label: 'Kaution freigegeben',
    recipient: 'Mitglied',
    icon: 'lock_open',
    vars: ['name', 'game', 'deposit'],
  },
  deposit_forfeited: {
    label: 'Kaution einbehalten',
    recipient: 'Mitglied',
    icon: 'money_off',
    vars: ['name', 'game', 'deposit', 'notes'],
  },
  membership_cancellation_user: {
    label: 'Kündigungsbestätigung',
    recipient: 'Mitglied',
    icon: 'logout',
    vars: ['name', 'refund_text', 'bonus_tokens'],
  },
  membership_cancellation_admin: {
    label: 'Neue Kündigung',
    recipient: 'Admin',
    icon: 'person_remove',
    vars: [
      'name',
      'email',
      'refund',
      'refund_tokens',
      'fee',
      'bonus_tokens',
      'account_holder',
      'iban',
      'reason',
    ],
  },
  inactive_account_notice: {
    label: 'Löschung wegen Inaktivität',
    recipient: 'Mitglied',
    icon: 'hourglass_bottom',
    vars: ['name', 'deletion_date', 'login_link'],
  },
}
