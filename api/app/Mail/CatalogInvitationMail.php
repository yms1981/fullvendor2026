<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Vendor;

class CatalogInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $token;
    public Company $company;
    public Customer $customer;
    public ?Vendor $seller;
    public string $emailText;
    public int $catalogId;
    public string $fecha_ex;
//    public int $expirationDays;
//    public string $catalogTitle;
//    public string $fechaExpired;
//    public string $formatDate;

    public function __construct(
        string $token,
        Company $company,
        Customer $customer,
        ?Vendor $seller = null,
        string $emailText,
        int $catalogId,
        string $fecha_ex
//        int $expirationDays,
//        string $catalogTitle,
//        string $fechaExpired,
//        string $formatDate

    ) {
        $this->token          = $token;
        $this->company        = $company;
        $this->customer       = $customer;
        $this->seller         = $seller;
        $this->emailText      = $emailText;
        $this->catalogId      = $catalogId;
        $this->fecha_ex       = $fecha_ex;
//        $this->expirationDays = $expirationDays;
//        $this->catalogTitle    = $catalogTitle;
//        $this->fechaExpired   = $fechaExpired;
//        $this->formatDate     = $formatDate;
    }

    public function build()
    {
        $this->subject("{$this->company->name} Product Catalog")
            ->view('emails.catalog_invitation');

        $host = (string) config('mail.mailers.smtp.host', '');
        if ($host === 'smtp.sendgrid.net' || str_contains($host, 'sendgrid')) {
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
}


