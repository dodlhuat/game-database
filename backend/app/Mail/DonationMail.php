<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DonationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string[]  $games
     * @param  array<int, array{filename: string, mime: string, contents: string}>  $images
     */
    public function __construct(
        public readonly string $donorName,
        public readonly string $donorEmail,
        public readonly array $games,
        public readonly array $images = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Sachspende von {$this->donorName}: Spiele verschenken",
            replyTo: [$this->donorEmail],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.donation');
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return collect($this->images)
            ->map(fn (array $image) => Attachment::fromData(
                fn () => base64_decode($image['contents']),
                $image['filename']
            )->withMime($image['mime']))
            ->all();
    }
}
