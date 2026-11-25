<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

/** A membership paid twice: tell the member, and ask the admins to refund by hand. */
return new class extends Migration
{
    public function up(): void
    {
        EmailTemplate::firstOrCreate(['key' => 'membership_payment_duplicate'], [
            'key' => 'membership_payment_duplicate',
            'subject' => 'Doppelte Zahlung – wir erstatten dir den Betrag',
            'greeting' => 'Hallo {name},',
            'body' => '<p>deine Zahlung über <strong>{amount}</strong> vom {date} ist bei uns eingegangen, als deine Mitgliedschaft bereits bezahlt war – du hast also doppelt gezahlt.</p><p>Wir erstatten dir diesen Betrag in den nächsten Tagen. Je nach Zahlungsart dauert es danach noch ein paar Tage, bis das Geld wieder auf deinem Konto ist.</p><p>An deiner Mitgliedschaft ändert sich nichts.</p>',
            'action_text' => 'Zum Dashboard',
        ]);
        EmailTemplate::firstOrCreate(['key' => 'membership_payment_duplicate_admin'], [
            'key' => 'membership_payment_duplicate_admin',
            'subject' => 'Doppelte Mitgliedszahlung: {name} – bitte erstatten',
            'greeting' => 'Hallo,',
            'body' => '<p><strong>{name}</strong> ({email}) hat die Mitgliedschaft doppelt bezahlt. Die zweite Zahlung über <strong>{amount}</strong> vom {date} wurde nicht verbucht.</p><p>Bitte erstatte sie im Mollie-Dashboard (Zahlung <strong>{payment_id}</strong>). Sobald Mollie die Erstattung meldet, wird sie in der Liste automatisch abgehakt – du kannst sie dort auch selbst als erstattet markieren.</p>',
            'action_text' => 'Zur Erstattungsliste',
        ]);
    }

    public function down(): void
    {
        EmailTemplate::whereIn('key', ['membership_payment_duplicate', 'membership_payment_duplicate_admin'])->delete();
    }
};
