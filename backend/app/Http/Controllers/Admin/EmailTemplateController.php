<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    private array $defaults = [
        'user_approved' => [
            'key'         => 'user_approved',
            'subject'     => 'Dein Konto wurde freigeschaltet!',
            'greeting'    => 'Willkommen, {name}!',
            'body'        => '<p>Dein Konto wurde von einem Administrator freigeschaltet.</p><p>Viel Spaß beim Ausleihen!</p>',
            'action_text' => 'Jetzt einloggen',
        ],
        'user_rejected' => [
            'key'         => 'user_rejected',
            'subject'     => 'Deine Registrierung wurde abgelehnt',
            'greeting'    => 'Hallo {name},',
            'body'        => '<p>Leider wurde deine Registrierung abgelehnt.</p><p>Bei Fragen wende dich an uns.</p>',
            'action_text' => null,
        ],
        'new_user_registered' => [
            'key'         => 'new_user_registered',
            'subject'     => 'Neue Registrierung: {name}',
            'greeting'    => 'Hallo Admin,',
            'body'        => '<p>Ein neues Mitglied hat sich registriert und wartet auf Freischaltung.</p><p><strong>Name:</strong> {name}<br><strong>E-Mail:</strong> {email}</p><p>Bitte prüfe die Anfrage im Admin-Bereich.</p>',
            'action_text' => 'Mitglied freischalten',
        ],
        'loan_due_soon' => [
            'key'         => 'loan_due_soon',
            'subject'     => 'Erinnerung: Rückgabe von „{game}" am {due_date}',
            'greeting'    => 'Hallo {name},',
            'body'        => '<p>Deine Ausleihe von <strong>{game}</strong> läuft am <strong>{due_date}</strong> ab.</p><p>Falls du das Spiel länger behalten möchtest, kannst du eine Verlängerung beantragen.</p>',
            'action_text' => 'Zum Dashboard',
        ],
        'reservation_available' => [
            'key'         => 'reservation_available',
            'subject'     => '„{game}" ist jetzt verfügbar!',
            'greeting'    => 'Gute Neuigkeiten, {name}!',
            'body'        => '<p>Das Spiel <strong>{game}</strong> ist jetzt wieder verfügbar.</p><p>Bitte leihe es aus, bevor jemand anderes zugreift.</p>',
            'action_text' => 'Jetzt ausleihen',
        ],
    ];

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
            'subject'     => ['required', 'string', 'max:255'],
            'greeting'    => ['required', 'string', 'max:255'],
            'body'        => ['required', 'string'],
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
                'subject'     => $default['subject'],
                'greeting'    => $default['greeting'],
                'body'        => $default['body'],
                'action_text' => $default['action_text'],
            ]);
        }

        return response()->json(['data' => $template]);
    }
}
