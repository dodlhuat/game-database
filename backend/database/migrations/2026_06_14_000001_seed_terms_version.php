<?php

use App\Models\TermsVersion;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        TermsVersion::updateOrCreate(
            ['version' => '1.0'],
            [
                'content' => "§1 Geltungsbereich\nDiese Nutzungsbedingungen gelten für alle Mitglieder der Spielothek.\n\n§2 Ausleihe\nSpiele können für 14 Tage ausgeliehen werden. Eine Verlängerung ist auf Anfrage möglich.\n\n§3 Haftung\nBei Beschädigung oder Verlust ist der Zeitwert des Spiels zu ersetzen.\n\n§4 Datenschutz\nWir verarbeiten Ihre Daten gemäß unserer Datenschutzerklärung.",
                'published_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        TermsVersion::where('version', '1.0')->delete();
    }
};
