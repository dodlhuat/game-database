<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        EmailTemplate::updateOrCreate(
            ['key' => 'inactive_account_notice'],
            [
                'key' => 'inactive_account_notice',
                'subject' => 'Dein Konto wird am {deletion_date} gelöscht',
                'greeting' => 'Hallo {name},',
                'body' => '<p>du hast dich seit drei Jahren nicht mehr bei der AUA Spieleausleihe angemeldet. Wie in unserer Datenschutzerklärung beschrieben, löschen wir inaktive Konten.</p><p>Wenn du nichts unternimmst, werden deine persönlichen Daten (Name, E-Mail-Adresse, Adresse, Geburtsdatum, Bewertungen, Favoriten und Reservierungen) am <strong>{deletion_date}</strong> gelöscht. Ausleih- und Zahlungsbelege bewahren wir nur so lange auf, wie es die gesetzliche Aufbewahrungspflicht verlangt.</p><p>Du möchtest dein Konto behalten? Dann melde dich einfach vor diesem Datum einmal an.</p>',
                'action_text' => 'Jetzt anmelden',
            ]
        );
    }

    public function down(): void
    {
        EmailTemplate::where('key', 'inactive_account_notice')->delete();
    }
};
