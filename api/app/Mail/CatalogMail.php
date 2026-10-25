<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CatalogMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $htmlBody;

    public string $companyName;

    /**
     * Create a new message instance.
     *
     * @param string      $htmlBody   HTML ya renderizado del correo (desde el MVC).
     * @param string|null $companyName Nombre de la empresa para el asunto.
     */
    public function __construct(string $htmlBody, ?string $companyName = null)
    {
        $this->htmlBody = $htmlBody;
        $this->companyName = ($companyName !== null && $companyName !== '') ? $companyName : (string) config('app.name', 'Catalog');
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $name = $this->companyName !== '' ? $this->companyName : config('app.name', 'Catalog');
        $subject = (string) __("cmp-clientes.subjet_email_send_catalog");
        $subject = str_replace('%s', $name, $subject);
        if ($subject === '' || $subject === 'cmp-clientes.subjet_email_send_catalog') {
            $subject = "{$name} - Product Catalog";
        }

        $this->subject($subject)
            ->view('emails.catalog_send')
            ->with([
                'htmlBody' => $this->htmlBody,
            ]);

        if ($this->isSendGridSmtp()) {
            $this->withSymfonyMessage(function (\Symfony\Component\Mime\Email $message) {
                $message->getHeaders()->addTextHeader(
                    'X-SMTPAPI',
                    json_encode([
                        'filters' => [
                            'clicktrack' => ['settings' => ['enable' => 0]],
                            'opentrack' => ['settings' => ['enable' => 0]],
                        ],
                    ])
                );
            });
        }

        return $this;
    }

    private function isSendGridSmtp(): bool
    {
        $host = (string) config('mail.mailers.smtp.host', '');

        return $host === 'smtp.sendgrid.net' || str_contains($host, 'sendgrid');
    }
}
