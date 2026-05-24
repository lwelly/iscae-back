<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $fullName  = 'Étudiant',
        public string $type      = 'registration',
        public int    $expiresIn = 10
    ) {}

    public function envelope(): Envelope
    {
        $subject = match($this->type) {
            'admin_2fa'    => 'Code de connexion 2FA — ISCAE',
            'registration' => 'Code de vérification — Inscription ISCAE',
            default        => 'Code de vérification — ISCAE',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            with: [
                'otpCode'     => $this->code,
                'studentName' => $this->fullName,
                'type'        => $this->type,
                'expiresIn'   => $this->expiresIn,
            ]
        );
    }
}
