<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

/** Reminder the day before a personal pickup event. */
return new class extends Migration
{
    public function up(): void
    {
        EmailTemplate::firstOrCreate(['key' => 'pickup_event_reminder'], [
            'key' => 'pickup_event_reminder',
            'subject' => 'Morgen: Abholung bei {location}',
            'greeting' => 'Hallo {name},',
            'body' => '<p>morgen, am <strong>{date}</strong>, kannst du deine Spiele persönlich abholen – <strong>{time}</strong> hier:</p>{location_details}<p>Für dich liegt bereit:</p>{games}<p>Kannst du doch nicht kommen? Gib uns bitte rechtzeitig Bescheid.</p>',
            'action_text' => 'Zum Dashboard',
        ]);
    }

    public function down(): void
    {
        EmailTemplate::where('key', 'pickup_event_reminder')->delete();
    }
};
