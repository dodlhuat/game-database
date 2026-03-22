<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key'         => 'user_approved',
                'subject'     => 'Dein Konto wurde freigeschaltet!',
                'greeting'    => 'Willkommen, {name}!',
                'body'        => '<p>Dein Konto wurde von einem Administrator freigeschaltet.</p><p>Viel Spaß beim Ausleihen!</p>',
                'action_text' => 'Jetzt einloggen',
            ],
            [
                'key'         => 'user_rejected',
                'subject'     => 'Deine Registrierung wurde abgelehnt',
                'greeting'    => 'Hallo {name},',
                'body'        => '<p>Leider wurde deine Registrierung abgelehnt.</p><p>Bei Fragen wende dich an uns.</p>',
                'action_text' => null,
            ],
            [
                'key'         => 'new_user_registered',
                'subject'     => 'Neue Registrierung: {name}',
                'greeting'    => 'Hallo Admin,',
                'body'        => '<p>Ein neues Mitglied hat sich registriert und wartet auf Freischaltung.</p><p><strong>Name:</strong> {name}<br><strong>E-Mail:</strong> {email}</p><p>Bitte prüfe die Anfrage im Admin-Bereich.</p>',
                'action_text' => 'Mitglied freischalten',
            ],
            [
                'key'         => 'loan_due_soon',
                'subject'     => 'Erinnerung: Rückgabe von „{game}" am {due_date}',
                'greeting'    => 'Hallo {name},',
                'body'        => '<p>Deine Ausleihe von <strong>{game}</strong> läuft am <strong>{due_date}</strong> ab.</p><p>Falls du das Spiel länger behalten möchtest, kannst du eine Verlängerung beantragen.</p>',
                'action_text' => 'Zum Dashboard',
            ],
            [
                'key'         => 'reservation_available',
                'subject'     => '„{game}" ist jetzt verfügbar!',
                'greeting'    => 'Gute Neuigkeiten, {name}!',
                'body'        => '<p>Das Spiel <strong>{game}</strong> ist jetzt wieder verfügbar.</p><p>Bitte leihe es aus, bevor jemand anderes zugreift.</p>',
                'action_text' => 'Jetzt ausleihen',
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }
}
