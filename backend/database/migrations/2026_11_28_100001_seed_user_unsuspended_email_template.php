<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

/** Mail to a member whose suspension an admin lifted. */
return new class extends Migration
{
    public function up(): void
    {
        EmailTemplate::firstOrCreate(['key' => 'user_unsuspended'], [
            'key' => 'user_unsuspended',
            'subject' => 'Dein Konto ist wieder freigeschaltet',
            'greeting' => 'Hallo {name},',
            'body' => '<p>dein Konto bei AUA – Alle.Unsere.Abenteuer ist wieder freigeschaltet. Du kannst dich ab sofort wieder anmelden und Spiele ausleihen.</p>',
            'action_text' => 'Jetzt anmelden',
        ]);
    }

    public function down(): void
    {
        EmailTemplate::where('key', 'user_unsuspended')->delete();
    }
};
