<?php

namespace App\Notifications\Concerns;

use App\Models\EmailLog;
use App\Models\EmailTemplate;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

trait UsesEmailTemplate
{
    protected function buildFromTemplate(string $key, array $vars, string $actionUrl, ?object $notifiable = null): MailMessage
    {
        $tpl = EmailTemplate::where('key', $key)->first();

        $subject    = $tpl?->subject    ?? '';
        $greeting   = $tpl?->greeting   ?? 'Hallo,';
        $body       = $tpl?->body       ?? '';
        $actionText = $tpl?->action_text ?? null;

        $replace = fn(string $s): string => str_replace(
            array_map(fn($k) => '{' . $k . '}', array_keys($vars)),
            array_values($vars),
            $s
        );

        $resolvedSubject = $replace($subject);

        $message = (new MailMessage)
            ->subject($resolvedSubject)
            ->greeting($replace($greeting))
            ->line(new HtmlString($replace($body)));

        if ($actionText && $actionUrl) {
            $message->action($replace($actionText), $actionUrl);
        }

        if ($notifiable !== null) {
            $this->logEmail($notifiable, $key, $resolvedSubject);
        }

        return $message;
    }

    private function logEmail(object $notifiable, string $key, string $subject): void
    {
        try {
            EmailLog::create([
                'user_id'         => $notifiable->id ?? null,
                'recipient_email' => $notifiable->email ?? '',
                'template_key'    => $key,
                'subject'         => $subject,
                'sent_at'         => now(),
            ]);
        } catch (\Throwable) {
            // Never block mail delivery due to logging failure
        }
    }
}
