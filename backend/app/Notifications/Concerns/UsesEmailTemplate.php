<?php

namespace App\Notifications\Concerns;

use App\Models\EmailTemplate;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

trait UsesEmailTemplate
{
    protected function buildFromTemplate(string $key, array $vars, string $actionUrl): MailMessage
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

        $message = (new MailMessage)
            ->subject($replace($subject))
            ->greeting($replace($greeting))
            ->line(new HtmlString($replace($body)));

        if ($actionText && $actionUrl) {
            $message->action($replace($actionText), $actionUrl);
        }

        return $message;
    }
}
