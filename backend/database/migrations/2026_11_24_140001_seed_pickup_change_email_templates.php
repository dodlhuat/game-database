<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

/** Mails when a pickup booking is cancelled for a no-show, or its event moves. */
return new class extends Migration
{
    private const TEMPLATES = [
        'loan_cancelled_no_show' => [
            'subject' => 'Ausleihe storniert: {game}',
            'greeting' => 'Hallo {name},',
            'body' => '<p>du hast <strong>{game}</strong> beim Abholtermin am {pickup} nicht abgeholt. Wir haben die Ausleihe deshalb storniert.</p><p>Die Leihgebühr und eine allfällige Kaution haben wir dir zurückgebucht – Bonus-Token behalten ihr ursprüngliches Ablaufdatum.</p><p>Du kannst das Spiel jederzeit erneut ausleihen.</p>',
            'action_text' => 'Zum Dashboard',
        ],
        'pickup_event_moved' => [
            'subject' => 'Dein Abholtermin hat sich geändert',
            'greeting' => 'Hallo {name},',
            'body' => '<p>dein Abholtermin wurde verschoben:</p><p><strong>Neu:</strong> {pickup}<br><span>Bisher: {previously}</span></p>{location_details}<p>Das Rückgabedatum deiner Ausleihe verschiebt sich entsprechend mit. Passt dir der neue Termin nicht, melde dich bitte bei uns.</p>',
            'action_text' => 'Zum Dashboard',
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
