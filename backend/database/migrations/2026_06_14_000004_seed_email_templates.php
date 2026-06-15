<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** @var array<string, array<string, mixed>> */
    private array $templates = [
        [
            'key' => 'email_verification',
            'subject' => 'Bitte bestätige deine E-Mail-Adresse',
            'greeting' => 'Hallo {name},',
            'body' => '<p>Danke für deine Registrierung! Bitte klicke auf den Button, um deine E-Mail-Adresse zu bestätigen.</p>',
            'action_text' => 'E-Mail-Adresse bestätigen',
        ],
        [
            'key' => 'user_approved',
            'subject' => 'Dein Konto wurde freigeschaltet!',
            'greeting' => 'Willkommen, {name}!',
            'body' => '<p>Dein Konto wurde von einem Administrator freigeschaltet.</p><p>Viel Spaß beim Ausleihen!</p>',
            'action_text' => 'Jetzt einloggen',
        ],
        [
            'key' => 'user_rejected',
            'subject' => 'Deine Registrierung wurde abgelehnt',
            'greeting' => 'Hallo {name},',
            'body' => '<p>Leider wurde deine Registrierung abgelehnt.</p><p>Bei Fragen wende dich an uns.</p>',
            'action_text' => null,
        ],
        [
            'key' => 'new_user_registered',
            'subject' => 'Neue Registrierung: {name}',
            'greeting' => 'Hallo Admin,',
            'body' => '<p>Ein neues Mitglied hat sich registriert und wartet auf Freischaltung.</p><p><strong>Name:</strong> {name}<br><strong>E-Mail:</strong> {email}</p><p>Bitte prüfe die Anfrage im Admin-Bereich.</p>',
            'action_text' => 'Mitglied freischalten',
        ],
        [
            'key' => 'loan_due_soon',
            'subject' => 'Erinnerung: Rückgabe von „{game}" am {due_date}',
            'greeting' => 'Hallo {name},',
            'body' => '<p>Deine Ausleihe von <strong>{game}</strong> läuft am <strong>{due_date}</strong> ab.</p><p>Falls du das Spiel länger behalten möchtest, kannst du eine Verlängerung beantragen.</p>',
            'action_text' => 'Zum Dashboard',
        ],
        [
            'key' => 'reservation_available',
            'subject' => '„{game}" ist jetzt verfügbar!',
            'greeting' => 'Gute Neuigkeiten, {name}!',
            'body' => '<p>Das Spiel <strong>{game}</strong> ist jetzt wieder verfügbar.</p><p>Bitte leihe es aus, bevor jemand anderes zugreift.</p>',
            'action_text' => 'Jetzt ausleihen',
        ],
        [
            'key' => 'welcome_member',
            'subject' => 'Willkommen bei AUA Spieleausleihe!',
            'greeting' => 'Willkommen, {name}!',
            'body' => '<p>Deine Mitgliedschaft ist jetzt aktiv. Du kannst ab sofort Spiele ausleihen und alle Funktionen der Plattform nutzen.</p><p>Viel Spaß beim Entdecken unserer Spielesammlung!</p>',
            'action_text' => 'Jetzt Spiele entdecken',
        ],
        [
            'key' => 'deposit_forfeited',
            'subject' => 'Deine Kaution wurde einbehalten – {game}',
            'greeting' => 'Hallo {name},',
            'body' => '<p>Leider musste deine Kaution von <strong>{deposit} Token(s)</strong> für das Spiel <strong>{game}</strong> einbehalten werden.</p><p>{notes}</p>',
            'action_text' => 'Zum Dashboard',
        ],
        [
            'key' => 'deposit_released',
            'subject' => 'Deine Kaution wurde freigegeben – {game}',
            'greeting' => 'Hallo {name},',
            'body' => '<p>Deine Kaution von <strong>{deposit} Token(s)</strong> für das Spiel <strong>{game}</strong> wurde freigegeben und deinem Konto gutgeschrieben.</p>',
            'action_text' => 'Zum Dashboard',
        ],
        [
            'key' => 'membership_renewal_reminder',
            'subject' => 'Deine Mitgliedschaft läuft bald ab',
            'greeting' => 'Hallo {name},',
            'body' => '<p>Deine Mitgliedschaft läuft am <strong>{expiry_date}</strong> ab. Bitte erneuere sie rechtzeitig, damit du weiterhin Spiele ausleihen kannst.</p>',
            'action_text' => 'Mitgliedschaft erneuern',
        ],
        [
            'key' => 'loan_overdue_reminder',
            'subject' => 'Erinnerung: Rückgabe von „{game}" ist überfällig',
            'greeting' => 'Hallo {name},',
            'body' => '<p>Deine Ausleihe von <strong>{game}</strong> war am <strong>{due_date}</strong> zur Rückgabe fällig und ist derzeit überfällig.</p><p>Bitte bringe das Spiel so bald wie möglich zurück. Bei Fragen wende dich an uns.</p>',
            'action_text' => 'Zum Dashboard',
        ],
    ];

    public function up(): void
    {
        foreach ($this->templates as $template) {
            EmailTemplate::updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }

    public function down(): void
    {
        EmailTemplate::whereIn('key', array_column($this->templates, 'key'))->delete();
    }
};
