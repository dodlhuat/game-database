<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        EmailTemplate::updateOrCreate(
            ['key' => 'password_reset'],
            [
                'subject'     => 'Passwort zurücksetzen',
                'greeting'    => 'Hallo {name},',
                'body'        => '<p>Du hast eine Anfrage zum Zurücksetzen deines Passworts gestellt.</p><p>Klicke auf den Button unten, um ein neues Passwort zu vergeben. Der Link ist <strong>60 Minuten</strong> gültig.</p><p>Falls du diese Anfrage nicht gestellt hast, kannst du diese E-Mail ignorieren.</p>',
                'action_text' => 'Passwort zurücksetzen',
            ]
        );
    }

    public function down(): void
    {
        EmailTemplate::where('key', 'password_reset')->delete();
    }
};
