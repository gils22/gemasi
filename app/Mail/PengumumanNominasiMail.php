<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PengumumanNominasiMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public array $data)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pengumuman Nominasi GEMASI ' . ($this->data['edition_year'] ?? ''),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pengumuman-nominasi',
            with: [
                'data' => $this->data,
            ],
        );
    }
}
