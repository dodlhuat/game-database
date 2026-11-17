<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    /** @var array<string, array<string, mixed>> */
    private array $defaults = [
        'user_approved' => [
            'key' => 'user_approved',
            'subject' => 'Dein Konto wurde freigeschaltet!',
            'greeting' => 'Willkommen, {name}!',
            'body' => '<p>Dein Konto wurde von einem Administrator freigeschaltet.</p><p>Viel Spaß beim Ausleihen!</p>',
            'action_text' => 'Jetzt einloggen',
        ],
        'user_rejected' => [
            'key' => 'user_rejected',
            'subject' => 'Deine Registrierung wurde abgelehnt',
            'greeting' => 'Hallo {name},',
            'body' => '<p>Leider wurde deine Registrierung abgelehnt.</p><p>Bei Fragen wende dich an uns.</p>',
            'action_text' => null,
        ],
        'new_user_registered' => [
            'key' => 'new_user_registered',
            'subject' => 'Neue Registrierung: {name}',
            'greeting' => 'Hallo Admin,',
            'body' => '<p>Ein neues Mitglied hat sich registriert und wartet auf Freischaltung.</p><p><strong>Name:</strong> {name}<br><strong>E-Mail:</strong> {email}</p><p>Bitte prüfe die Anfrage im Admin-Bereich.</p>',
            'action_text' => 'Mitglied freischalten',
        ],
        'loan_confirmation' => [
            'key' => 'loan_confirmation',
            'subject' => 'Ausleihe bestätigt: {game}',
            'greeting' => 'Hallo {name},',
            'body' => '<p>du hast <strong>{game}</strong> ausgeliehen.</p><p><strong>Abholtermin:</strong> {start_date}<br><strong>Rückgabe bis:</strong> {due_date}</p><p>Abholen und wieder zurückbringen kannst du es hier:</p>{location_details}<p>Viel Spaß beim Spielen!</p>',
            'action_text' => 'Zum Dashboard',
        ],
        'loan_due_soon' => [
            'key' => 'loan_due_soon',
            'subject' => 'Erinnerung: Rückgabe von „{game}" am {due_date}',
            'greeting' => 'Hallo {name},',
            'body' => '<p>Deine Ausleihe von <strong>{game}</strong> läuft am <strong>{due_date}</strong> ab. Bitte bring das Spiel bis dahin dorthin zurück, wo du es abgeholt hast:</p>{location_details}<p>Falls du das Spiel länger behalten möchtest, kannst du eine Verlängerung beantragen.</p>',
            'action_text' => 'Zum Dashboard',
        ],
        'loan_overdue_reminder' => [
            'key' => 'loan_overdue_reminder',
            'subject' => 'Erinnerung: Rückgabe von „{game}" ist überfällig',
            'greeting' => 'Hallo {name},',
            'body' => '<p>Deine Ausleihe von <strong>{game}</strong> war am <strong>{due_date}</strong> zur Rückgabe fällig und ist derzeit überfällig.</p><p>Bitte bring das Spiel so bald wie möglich dorthin zurück, wo du es abgeholt hast:</p>{location_details}<p>Bei Fragen wende dich an uns.</p>',
            'action_text' => 'Zum Dashboard',
        ],
        'reservation_available' => [
            'key' => 'reservation_available',
            'subject' => '„{game}" ist jetzt verfügbar!',
            'greeting' => 'Gute Neuigkeiten, {name}!',
            'body' => '<p>Das Spiel <strong>{game}</strong> ist jetzt wieder verfügbar.</p><p>Bitte leihe es aus, bevor jemand anderes zugreift.</p>',
            'action_text' => 'Jetzt ausleihen',
        ],
        'email_verification' => [
            'key' => 'email_verification',
            'subject' => 'Bitte bestätige deine E-Mail-Adresse',
            'greeting' => 'Hallo {name}!',
            'body' => '<p>Danke für deine Registrierung! Bitte klicke auf den Button, um deine E-Mail-Adresse zu bestätigen und deinen Account zu aktivieren.</p><p>Der Link ist 60 Minuten gültig.</p>',
            'action_text' => 'E-Mail-Adresse bestätigen',
        ],
        'welcome_member' => [
            'key' => 'welcome_member',
            'subject' => 'Willkommen als Mitglied!',
            'greeting' => 'Willkommen, {name}!',
            'body' => '<p>Deine Mitgliedschaft wurde aktiviert. Du hast <strong>20 Bonus-Token</strong> erhalten und kannst jetzt Spiele ausleihen.</p><p>Viel Spaß beim Entdecken unserer Spielesammlung!</p>',
            'action_text' => 'Zum Dashboard',
        ],
        'membership_renewal_reminder' => [
            'key' => 'membership_renewal_reminder',
            'subject' => 'Deine Mitgliedschaft läuft bald ab',
            'greeting' => 'Hallo {name},',
            'body' => '<p>Deine Mitgliedschaft läuft am <strong>{expiry_date}</strong> ab.</p><p>Du kannst deine Mitgliedschaft bereits jetzt verlängern und erhältst dabei wieder 20 Token.</p>',
            'action_text' => 'Mitgliedschaft verlängern',
        ],
        'welcome_supporter' => [
            'key' => 'welcome_supporter',
            'subject' => 'Danke für deine Unterstützung!',
            'greeting' => 'Willkommen, {name}!',
            'body' => '<p>Danke, dass du die AUA Spieleausleihe als außerordentliches Mitglied unterstützt!</p><p>Als außerordentliches Mitglied erhältst du keine Token und kannst aktuell keine Spiele ausleihen. Du kannst dich aber jederzeit zum Vollmitglied hochstufen lassen, um Token zu erhalten und Spiele aus unserer Sammlung auszuleihen.</p>',
            'action_text' => 'Zum Dashboard',
        ],
        'password_reset' => [
            'key' => 'password_reset',
            'subject' => 'Passwort zurücksetzen',
            'greeting' => 'Hallo {name},',
            'body' => '<p>Du hast eine Anfrage zum Zurücksetzen deines Passworts gestellt.</p><p>Klicke auf den Button unten, um ein neues Passwort zu vergeben. Der Link ist <strong>60 Minuten</strong> gültig.</p><p>Falls du diese Anfrage nicht gestellt hast, kannst du diese E-Mail ignorieren.</p>',
            'action_text' => 'Passwort zurücksetzen',
        ],
        'deposit_released' => [
            'key' => 'deposit_released',
            'subject' => 'Deine Kaution wurde freigegeben – {game}',
            'greeting' => 'Hallo {name},',
            'body' => '<p>Deine Kaution von <strong>{deposit} Token(s)</strong> für das Spiel <strong>{game}</strong> wurde freigegeben und deinem Konto gutgeschrieben.</p>',
            'action_text' => 'Zum Dashboard',
        ],
        'deposit_forfeited' => [
            'key' => 'deposit_forfeited',
            'subject' => 'Deine Kaution wurde einbehalten – {game}',
            'greeting' => 'Hallo {name},',
            'body' => '<p>Leider musste deine Kaution von <strong>{deposit} Token(s)</strong> für das Spiel <strong>{game}</strong> einbehalten werden.</p><p>{notes}</p>',
            'action_text' => 'Zum Dashboard',
        ],
        'membership_cancellation_user' => [
            'key' => 'membership_cancellation_user',
            'subject' => 'Bestätigung deiner Kündigung',
            'greeting' => 'Hallo {name},',
            'body' => '<p>wir haben deine Kündigung der Mitgliedschaft erhalten.</p><p>{refund_text}</p><p>Geschenkte Bonus-Token ({bonus_tokens}) verfallen mit der Kündigung.</p><p>Danke, dass du dabei warst!</p>',
            'action_text' => null,
        ],
        'membership_cancellation_admin' => [
            'key' => 'membership_cancellation_admin',
            'subject' => 'Kündigung: {name}',
            'greeting' => 'Hallo,',
            'body' => '<p>{name} ({email}) hat die Mitgliedschaft gekündigt.</p><p>Rückzuüberweisen: <strong>{refund}</strong> ({refund_tokens} Token, abzüglich {fee} Bearbeitungsabzug). Verfallene Bonus-Token: {bonus_tokens}.</p><p>Kontoinhaber: {account_holder}<br>IBAN: {iban}</p><p>Grund: {reason}</p><p>Die Rücküberweisung erfolgt manuell.</p>',
            'action_text' => 'Kündigungen ansehen',
        ],
    ];

    /**
     * Every template key a notification can send. A key missing here is
     * still mailed from the database row, but never shows up in the admin.
     *
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys((new self)->defaults);
    }

    public function index(): JsonResponse
    {
        $templates = collect($this->defaults)->map(function (array $default) {
            return EmailTemplate::firstOrCreate(['key' => $default['key']], $default);
        })->values();

        return response()->json(['data' => $templates]);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $template = EmailTemplate::where('key', $key)->firstOrFail();

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'greeting' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'action_text' => ['nullable', 'string', 'max:255'],
        ]);

        $template->update($validated);

        return response()->json(['data' => $template]);
    }

    public function reset(string $key): JsonResponse
    {
        $template = EmailTemplate::where('key', $key)->firstOrFail();

        if (isset($this->defaults[$key])) {
            $default = $this->defaults[$key];
            $template->update([
                'subject' => $default['subject'],
                'greeting' => $default['greeting'],
                'body' => $default['body'],
                'action_text' => $default['action_text'],
            ]);
        }

        return response()->json(['data' => $template]);
    }
}
