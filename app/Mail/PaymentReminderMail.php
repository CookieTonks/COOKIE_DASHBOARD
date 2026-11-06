<?php

namespace App\Mail;

use App\Models\PaymentDue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaymentReminderMail extends Mailable
{
    public function __construct(public PaymentDue $due) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recordatorio de pago: '.$this->due->plan->concept.' vence el '.$this->due->due_date->translatedFormat('j \d\e F'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-reminder',
            text: 'emails.payment-reminder-text',
        );
    }
}
