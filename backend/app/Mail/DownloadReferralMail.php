<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DownloadReferralMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $code,
        public readonly ?string $clinicName = null,
        public readonly ?string $vetSlug = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your SnoutIQ download link & referral code',
            from: new Address(config('mail.from.address'), config('mail.from.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.download-referral',
            with: [
                'user'       => $this->user,
                'code'       => $this->code,
                'clinicName' => $this->clinicName,
                'vetSlug'    => $this->vetSlug,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
