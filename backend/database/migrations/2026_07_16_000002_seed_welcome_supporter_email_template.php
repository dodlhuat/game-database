<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        EmailTemplate::updateOrCreate(
            ['key' => 'welcome_supporter'],
            [
                'key' => 'welcome_supporter',
                'subject' => 'Danke für deine Unterstützung!',
                'greeting' => 'Willkommen, {name}!',
                'body' => '<p>Danke, dass du die AUA Spieleausleihe als außerordentliches Mitglied unterstützt!</p><p>Als außerordentliches Mitglied erhältst du keine Token und kannst aktuell keine Spiele ausleihen. Du kannst dich aber jederzeit zum Vollmitglied hochstufen lassen, um Token zu erhalten und Spiele aus unserer Sammlung auszuleihen.</p>',
                'action_text' => 'Zum Dashboard',
            ]
        );
    }

    public function down(): void
    {
        EmailTemplate::where('key', 'welcome_supporter')->delete();
    }
};
