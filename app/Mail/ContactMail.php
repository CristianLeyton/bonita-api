<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailSubject;
    public $mailMessage;

    public function __construct($subject, $message)
    {
        $this->mailSubject = $subject;
        $this->mailMessage = $message;
    }

    public function build()
    {
        return $this->subject($this->mailSubject)
            ->view('emails.contact');
    }
}
