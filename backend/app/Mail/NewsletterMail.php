<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class NewsletterMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string $subjectText,
        public readonly string $body,
        public readonly User $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectText);
    }

    /** Lets mail clients show their own one-click "unsubscribe" (RFC 8058). */
    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.$this->signedUnsubscribeUrl().'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.newsletter', with: [
            'unsubscribeUrl' => $this->unsubscribePageUrl(),
        ]);
    }

    /** API endpoint the mail client posts to. Valid until used, no expiry. */
    public function signedUnsubscribeUrl(): string
    {
        return URL::signedRoute('newsletter.unsubscribe', ['user' => $this->recipient->id]);
    }

    /**
     * The footer link: the frontend page that asks before unsubscribing, so a
     * link scanner opening it does not sign anyone out of the newsletter.
     */
    public function unsubscribePageUrl(): string
    {
        parse_str((string) parse_url($this->signedUnsubscribeUrl(), PHP_URL_QUERY), $query);

        return config()->string('frontend.url').'/newsletter/abmelden?'.http_build_query([
            'user' => $this->recipient->id,
            'signature' => $query['signature'] ?? '',
        ]);
    }
}
