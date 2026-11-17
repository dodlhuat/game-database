<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

/**
 * Pickup locations: a confirmation mail for new loans, and the return place in
 * both reminder mails. Reminder texts are only replaced while they are still
 * the untouched defaults — texts edited in the admin are left alone.
 */
return new class extends Migration
{
    private const OLD_DUE_SOON = '<p>Deine Ausleihe von <strong>{game}</strong> läuft am <strong>{due_date}</strong> ab.</p><p>Falls du das Spiel länger behalten möchtest, kannst du eine Verlängerung beantragen.</p>';

    private const NEW_DUE_SOON = '<p>Deine Ausleihe von <strong>{game}</strong> läuft am <strong>{due_date}</strong> ab. Bitte bring das Spiel bis dahin dorthin zurück, wo du es abgeholt hast:</p>{location_details}<p>Falls du das Spiel länger behalten möchtest, kannst du eine Verlängerung beantragen.</p>';

    private const OLD_OVERDUE = '<p>Deine Ausleihe von <strong>{game}</strong> war am <strong>{due_date}</strong> zur Rückgabe fällig und ist derzeit überfällig.</p><p>Bitte bringe das Spiel so bald wie möglich zurück. Bei Fragen wende dich an uns.</p>';

    private const NEW_OVERDUE = '<p>Deine Ausleihe von <strong>{game}</strong> war am <strong>{due_date}</strong> zur Rückgabe fällig und ist derzeit überfällig.</p><p>Bitte bring das Spiel so bald wie möglich dorthin zurück, wo du es abgeholt hast:</p>{location_details}<p>Bei Fragen wende dich an uns.</p>';

    public function up(): void
    {
        EmailTemplate::firstOrCreate(
            ['key' => 'loan_confirmation'],
            [
                'subject' => 'Ausleihe bestätigt: {game}',
                'greeting' => 'Hallo {name},',
                'body' => '<p>du hast <strong>{game}</strong> ausgeliehen.</p><p><strong>Abholtermin:</strong> {start_date}<br><strong>Rückgabe bis:</strong> {due_date}</p><p>Abholen und wieder zurückbringen kannst du es hier:</p>{location_details}<p>Viel Spaß beim Spielen!</p>',
                'action_text' => 'Zum Dashboard',
            ]
        );

        EmailTemplate::where('key', 'loan_due_soon')->where('body', self::OLD_DUE_SOON)->update(['body' => self::NEW_DUE_SOON]);
        EmailTemplate::where('key', 'loan_overdue_reminder')->where('body', self::OLD_OVERDUE)->update(['body' => self::NEW_OVERDUE]);
    }

    public function down(): void
    {
        EmailTemplate::where('key', 'loan_confirmation')->delete();
        EmailTemplate::where('key', 'loan_due_soon')->where('body', self::NEW_DUE_SOON)->update(['body' => self::OLD_DUE_SOON]);
        EmailTemplate::where('key', 'loan_overdue_reminder')->where('body', self::NEW_OVERDUE)->update(['body' => self::OLD_OVERDUE]);
    }
};
