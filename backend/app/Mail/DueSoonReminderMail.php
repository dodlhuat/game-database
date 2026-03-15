<?php

namespace App\Mail;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DueSoonReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Loan $loan) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Erinnerung: Rückgabe in 2 Tagen fällig');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.due-soon');
    }
}
