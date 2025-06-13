<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailSubject;
    public $mailMessage;

    public function __construct($subject, $message)
    {
        $this->mailSubject = $subject;
        $this->mailMessage = $message;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order',
            with: [
                'mailMessage' => $this->mailMessage,
            ],
        );
    }
}
