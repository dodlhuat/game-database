<?php

use App\Models\PrivacyVersion;
use Illuminate\Database\Migrations\Migration;

/**
 * The Mollie note added to privacy policy 1.3 said "du"; the rest of the
 * policy says "Sie". Same text, consistent form of address.
 */
return new class extends Migration
{
    private const DU = 'Zum Bezahlen wirst du auf eine Seite von Mollie weitergeleitet; Zahlungsdaten wie Kartennummern gibst du direkt dort ein, sie werden von uns nicht gespeichert.';

    private const SIE = 'Zum Bezahlen werden Sie auf eine Seite von Mollie weitergeleitet; Zahlungsdaten wie Kartennummern geben Sie direkt dort ein, sie werden von uns nicht gespeichert.';

    public function up(): void
    {
        $this->swap(self::DU, self::SIE);
    }

    public function down(): void
    {
        $this->swap(self::SIE, self::DU);
    }

    private function swap(string $from, string $to): void
    {
        $privacy = PrivacyVersion::where('version', '1.3')->first();
        $privacy?->update(['content' => str_replace($from, $to, $privacy->content)]);
    }
};
