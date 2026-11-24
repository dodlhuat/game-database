<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

/**
 * Mails around a confirmed return: a receipt for the member, a heads-up for
 * the admins and their daily pickup list grouped by location.
 */
return new class extends Migration
{
    private const TEMPLATES = [
        'loan_returned' => [
            'subject' => 'Rückgabe erfasst: {game}',
            'greeting' => 'Hallo {name},',
            'body' => '<p>danke! Wir haben deine Rückgabe von <strong>{game}</strong> am {returned_at} erfasst. Abgegeben hast du es hier:</p>{location_details}<p>Wir holen das Spiel dort ab und prüfen es. Falls für die Ausleihe eine Kaution blockiert ist, wird sie danach freigegeben — darüber bekommst du eine eigene E-Mail.</p>',
            'action_text' => 'Zum Dashboard',
        ],
        'loan_returned_admin' => [
            'subject' => 'Rückgabe bei {location}: {game}',
            'greeting' => 'Hallo,',
            'body' => '<p>{member} hat die Rückgabe von <strong>{game}</strong> bestätigt.</p><p><strong>Abholort:</strong> {location}<br><strong>Zurückgegeben:</strong> {returned_at}<br><strong>Gemeldeter Zustand:</strong> {condition}</p><p>Das Spiel liegt dort zur Abholung und Prüfung bereit.</p>',
            'action_text' => 'Zur Prüfliste',
        ],
        'pickup_digest_admin' => [
            'subject' => 'Abholliste: {count} Spiel(e) warten auf Abholung',
            'greeting' => 'Guten Morgen,',
            'body' => '<p>Stand {date} liegen diese zurückgegebenen Spiele an den Abholorten bereit und warten auf Abholung und Prüfung:</p>{pickup_list}',
            'action_text' => 'Zur Prüfliste',
        ],
    ];

    public function up(): void
    {
        foreach (self::TEMPLATES as $key => $template) {
            EmailTemplate::firstOrCreate(['key' => $key], ['key' => $key, ...$template]);
        }
    }

    public function down(): void
    {
        EmailTemplate::whereIn('key', array_keys(self::TEMPLATES))->delete();
    }
};
