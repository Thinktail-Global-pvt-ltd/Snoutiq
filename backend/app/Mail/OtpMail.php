<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;

    public function __construct($otp)
    {
        $this->otp = $otp; // public property -> view me auto available
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your OTP Code',
            from: new Address(config('mail.from.address'), config('mail.from.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            with: ['otp' => $this->otp], // optional, public property se bhi chal jayega
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
