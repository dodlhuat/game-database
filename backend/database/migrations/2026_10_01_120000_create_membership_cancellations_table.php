<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('previous_role', 12);
            $table->unsignedInteger('tokens_total');
            $table->unsignedInteger('bonus_tokens_forfeited');
            $table->unsignedInteger('refund_tokens');
            $table->unsignedInteger('gross_cents');
            $table->unsignedInteger('fee_cents');
            $table->unsignedInteger('refund_cents');
            $table->string('account_holder')->nullable();
            $table->text('iban')->nullable(); // encrypted
            $table->text('reason')->nullable();
            $table->string('status', 12)->default('REQUESTED'); // REQUESTED | REFUNDED | NO_REFUND
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });

        EmailTemplate::updateOrCreate(
            ['key' => 'membership_cancellation_admin'],
            [
                'key' => 'membership_cancellation_admin',
                'subject' => 'Kündigung: {name}',
                'greeting' => 'Hallo,',
                'body' => '<p>{name} ({email}) hat die Mitgliedschaft gekündigt.</p><p>Rückzuüberweisen: <strong>{refund}</strong> ({refund_tokens} Token, abzüglich {fee} Bearbeitungsabzug). Verfallene Bonus-Token: {bonus_tokens}.</p><p>Kontoinhaber: {account_holder}<br>IBAN: {iban}</p><p>Grund: {reason}</p><p>Die Rücküberweisung erfolgt manuell.</p>',
                'action_text' => 'Kündigungen ansehen',
            ]
        );

        EmailTemplate::updateOrCreate(
            ['key' => 'membership_cancellation_user'],
            [
                'key' => 'membership_cancellation_user',
                'subject' => 'Bestätigung deiner Kündigung',
                'greeting' => 'Hallo {name},',
                'body' => '<p>wir haben deine Kündigung der Mitgliedschaft erhalten.</p><p>{refund_text}</p><p>Geschenkte Bonus-Token ({bonus_tokens}) verfallen mit der Kündigung.</p><p>Danke, dass du dabei warst!</p>',
                'action_text' => null,
            ]
        );
    }

    public function down(): void
    {
        EmailTemplate::whereIn('key', ['membership_cancellation_admin', 'membership_cancellation_user'])->delete();
        Schema::dropIfExists('membership_cancellations');
    }
};
