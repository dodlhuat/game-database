<?php

use App\Models\PrivacyVersion;
use Illuminate\Database\Migrations\Migration;

/**
 * Payments moved from Stripe to Mollie: privacy policy 1.3 names the new
 * processor. Not live yet, so 1.3 is amended in place; the seed migration
 * carries the same text for fresh installs.
 */
return new class extends Migration
{
    private const STRIPE_TO_MOLLIE = [
        'Online-Zahlungen (Mitgliedsbeitrag, Token-Pakete) werden über den Zahlungsdienstleister Stripe (Stripe Payments Europe, Ltd., Irland) abgewickelt. Zahlungsdaten wie Kartennummern werden direkt an Stripe übermittelt und von uns nicht gespeichert; wir erhalten lediglich eine Bestätigung über die erfolgte Zahlung samt Referenz.' => 'Online-Zahlungen (Mitgliedsbeitrag, Token-Pakete) werden über den Zahlungsdienstleister Mollie (Mollie B.V., Niederlande) abgewickelt. Zum Bezahlen wirst du auf eine Seite von Mollie weitergeleitet; Zahlungsdaten wie Kartennummern gibst du direkt dort ein, sie werden von uns nicht gespeichert. Wir erhalten lediglich eine Bestätigung über die erfolgte Zahlung samt Referenz.',
        '– Zahlungsabwicklung: Stripe Payments Europe, Ltd., Irland.' => '– Zahlungsabwicklung: Mollie B.V., Niederlande.',
        'Übermittlung in Drittstaaten: Beim E-Mail-Versand über Resend sowie bei der Zahlungsabwicklung durch Stripe können Daten in den USA verarbeitet werden.' => 'Übermittlung in Drittstaaten: Beim E-Mail-Versand über Resend können Daten in den USA verarbeitet werden.',
    ];

    public function up(): void
    {
        $this->amend(self::STRIPE_TO_MOLLIE);
    }

    public function down(): void
    {
        $this->amend(array_flip(self::STRIPE_TO_MOLLIE));
    }

    /** @param  array<string, string>  $replacements */
    private function amend(array $replacements): void
    {
        $privacy = PrivacyVersion::where('version', '1.3')->first();
        if ($privacy === null) {
            return;
        }

        $privacy->update(['content' => str_replace(array_keys($replacements), array_values($replacements), $privacy->content)]);
    }
};
