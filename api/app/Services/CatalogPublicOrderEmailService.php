<?php

namespace App\Services;

use App\Http\Resources\PlaceOrderMailResource;
use App\Mail\PlaceOrderMail;
use App\Models\Company;
use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class CatalogPublicOrderEmailService
{
    public function sendAfterPlace(Order $order, string $typeCart): void
    {
        $order->loadMissing([
            'company.currencyModel',
            'company.format_numeric',
            'customer',
            'seller.user',
            'address',
            'statusOrder',
            'order_details.product.images',
        ]);

        $company = $order->company;
        if ($company === null) {
            return;
        }

        match ($typeCart) {
            'to_portal_seller' => $this->sendPortalSellerEmails($order, $company),
            'to_customer' => $this->sendCustomerEmail($order),
            default => null,
        };
    }

    private function sendPortalSellerEmails(Order $order, Company $company): void
    {
        if ($company->email_client_on_salesman_order) {
            $this->sendToCustomer($order);
        }

        if (! $company->email_on_salesman_order) {
            return;
        }

        $seller = $order->seller;
        if ($seller === null && $order->seller_id) {
            $seller = Vendor::query()->find((int) $order->seller_id);
        }

        if ($seller !== null && ! empty($seller->email_on_salesman_order) && ! empty($seller->email)) {
            $this->sendOrderMail((string) $seller->email, $order);
        }
    }

    private function sendCustomerEmail(Order $order): void
    {
        $this->sendToCustomer($order);
    }

    private function sendToCustomer(Order $order): void
    {
        $email = (string) ($order->customer?->email ?? '');
        if ($email === '') {
            return;
        }

        $this->sendOrderMail($email, $order);
    }

    private function buildSubject(Order $order): string
    {
        $locale = strtolower((string) request()->header('Accept-Language', request()->header('X-App-Locale', 'en')));
        $file = str_contains($locale, 'es') ? 'es-ES.json' : 'en-EN.json';
        $path = base_path('lang/'.$file);
        $confirmation = (string) $order->order_number;
        if (is_readable($path)) {
            $data = json_decode(file_get_contents($path), true);
            $template = $data['cmp-orders']['order_confirmation'] ?? '%s';
            $confirmation = sprintf($template, (string) $order->order_number);
        }

        $companyName = (string) ($order->company?->name ?? '');

        return trim($companyName.' - '.$confirmation);
    }

    private function sendOrderMail(string $email, Order $order): void
    {
        try {
            /** @var CompanySendTemplateService $templates */
            $templates = app(CompanySendTemplateService::class);
            $rendered = $templates->renderForOrder($order, 'email');
            if ($rendered !== null && trim($rendered['body']) !== '') {
                $subject = trim($rendered['subject']) !== ''
                    ? $rendered['subject']
                    : $this->buildSubject($order);
                Mail::html($rendered['body'], function ($message) use ($email, $subject): void {
                    $message->to($email)->subject($subject);
                });

                return;
            }

            $mailData = (new PlaceOrderMailResource($order))->toArray(new Request());
            $mailData['subject'] = $this->buildSubject($order);

            Mail::to($email)->send(new PlaceOrderMail($mailData));
        } catch (\Throwable $e) {
            Log::error('CatalogPublicOrderEmailService: '.$e->getMessage(), [
                'order_id' => $order->order_id,
                'email' => $email,
            ]);
        }
    }
}
