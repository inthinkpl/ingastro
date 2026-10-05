<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;

class DynamicSaaSMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectLine;
    public $htmlBody;
    public $attachmentsPaths;

    public function __construct(string $subjectLine, string $htmlBody, array $attachmentsPaths = [])
    {
        $this->subjectLine = $subjectLine;
        $this->htmlBody = $htmlBody;
        $this->attachmentsPaths = $attachmentsPaths;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        // Budujemy prosty, estetyczny globalny szablon HTML dla wszystkich maili InGastro
        $wrappedBody = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333;">
            <div style="text-align: center; padding: 20px 0;">
                <h2 style="color: #f59e0b; margin: 0;">InGastro</h2>
                <p style="color: #64748b; font-size: 12px; margin: 0;">Twój system gastronomiczny</p>
            </div>
            <div style="background: #ffffff; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px;">
                ' . $this->htmlBody . '
            </div>
            <div style="text-align: center; padding: 20px; font-size: 11px; color: #94a3b8;">
                &copy; ' . date('Y') . ' InGastro.pl<br>Wiadomość wygenerowana automatycznie.
            </div>
        </div>';

        return new Content(
            htmlString: $wrappedBody,
        );
    }

    public function attachments(): array
    {
        $files = [];
        foreach ($this->attachmentsPaths as $path) {
            if (file_exists($path)) {
                $files[] = Attachment::fromPath($path);
            }
        }
        return $files;
    }
}