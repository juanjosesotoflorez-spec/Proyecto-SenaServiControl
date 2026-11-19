<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly array $contact)
    {
    }

    public function envelope(): Envelope
    {
        $nombre = preg_replace('/[\r\n]+/', ' ', $this->contact['nombre']);

        return new Envelope(
            subject: 'Nuevo mensaje de ' . trim($nombre) . ' | Serviteca SENA',
            replyTo: [new Address($this->contact['correo'], $this->contact['nombre'])],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-message',
            text: 'emails.contact-message-text',
        );
    }
}
