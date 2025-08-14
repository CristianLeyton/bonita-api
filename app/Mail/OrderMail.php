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
    public $couponInfo;

    public function __construct($subject, $message, $couponInfo = null)
    {
        $this->mailSubject = $subject;
        $this->mailMessage = $message;
        $this->couponInfo = $couponInfo;
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
                'couponInfo' => $this->couponInfo,
            ],
        );
    }
}
