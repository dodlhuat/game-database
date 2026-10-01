<?php

namespace App\Notifications;

use App\Models\MembershipCancellation;
use App\Models\User;
use App\Notifications\Concerns\UsesEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Confirmation to the member who cancelled. */
class MembershipCancellationConfirmation extends Notification
{
    use Queueable, UsesEmailTemplate;

    public function __construct(private MembershipCancellation $cancellation) {}

    /** @return array<int, string> */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $c = $this->cancellation;

        $refundText = $c->refund_cents > 0
            ? 'Du erhältst '.MembershipCancellationRequested::euro($c->refund_cents)." für {$c->refund_tokens} Token zurück (nach Abzug von "
                .MembershipCancellationRequested::euro($c->fee_cents).' Bearbeitungsgebühr). Die Rücküberweisung erfolgt manuell auf das angegebene Konto.'
            : 'Es gibt keine Token, die zurückerstattet werden können.';

        return $this->buildFromTemplate('membership_cancellation_user', [
            'name' => e($notifiable->name),
            'refund_text' => $refundText,
            'bonus_tokens' => $c->bonus_tokens_forfeited,
        ], '', $notifiable);
    }
}
