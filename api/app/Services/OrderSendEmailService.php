<?php

namespace App\Services;

use App\Enums\OrderStatusCodes;
use App\Http\Resources\PlaceOrderMailResource;
use App\Mail\PlaceOrderMail;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\StatusOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrderSendEmailService
{
    /**
     * @return array{success: bool, message: string, status: int}
     */
    public function send(Request $request, int $authCompanyId, User $user): array
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer'],
            'email' => ['required', 'email', 'max:255'],
            'expiration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'descriptions' => ['nullable', 'string', 'max:5000'],
            'show_information' => ['nullable'],
            'hide_price' => ['nullable'],
        ]);

        $orderId = (int) $validated['order_id'];
        $order = Order::query()
            ->with([
                'company.currencyModel',
                'company.format_numeric',
                'customer',
                'seller.user',
                'address',
                'statusOrder',
                'order_details.product.images',
            ])
            ->where('order_id', $orderId)
            ->first();

        if (! $order) {
            return [
                'success' => false,
                'message' => $this->cmpOrdersMessage('assign_warehouse_order_not_found'),
                'status' => 404,
            ];
        }

        if ((int) $order->company_id !== $authCompanyId) {
            return [
                'success' => false,
                'message' => $this->cmpOrdersMessage('warehouse_assign_forbidden'),
                'status' => 403,
            ];
        }

        $expirationDays = (int) ($validated['expiration_days']
            ?? $order->expiration_days
            ?? $order->company?->ordersend_time
            ?? 15);

        $expiresAt = Carbon::now()->addDays($expirationDays);
        $hadToken = filled($order->token_pass);
        $token = $hadToken ? (string) $order->token_pass : $this->generateToken();

        DB::transaction(function () use ($order, $hadToken, $token, $expiresAt, $expirationDays, $user): void {
            if ($hadToken) {
                $order->date_expired_email = $expiresAt;
                $order->expiration_days = $expirationDays;
            } else {
                $pendingStatusId = StatusOrder::getIdByCode(OrderStatusCodes::PENDING);
                if ($pendingStatusId !== false) {
                    $order->status_id = (int) $pendingStatusId;
                }

                $order->token_pass = $token;
                $order->send_email_order = now();
                $order->date_expired_email = $expiresAt;
                $order->expiration_days = $expirationDays;
            }

            $order->save();

            if (! $hadToken) {
                OrderStatusHistory::query()->create([
                    'order_id' => $order->order_id,
                    'company_id' => $order->company_id,
                    'user_id' => $user->id,
                    'cod_status' => OrderStatusCodes::PENDING->value,
                ]);
            }
        });

        $order->refresh()->load([
            'company.currencyModel',
            'company.format_numeric',
            'customer',
            'seller.user',
            'address',
            'statusOrder',
            'order_details.product.images',
        ]);

        $mailRequest = Request::create('/', 'POST', [
            'descriptions' => $validated['descriptions'] ?? null,
            'show_information' => $request->boolean('show_information'),
            'hide_price' => $request->boolean('hide_price'),
            'expiration_days' => $expirationDays,
            'token' => $token,
        ]);

        $mailData = (new PlaceOrderMailResource($order))->toArray($mailRequest);
        $mailData['subject'] = $this->buildSubject($order);
        $mailData['confirm_url'] = $this->publicUrl('products/order_confirm/'.$token);
        $mailData['reject_url'] = $this->publicUrl('products/order_reject/'.$token);

        try {
            $templateSent = $this->sendUsingCompanyTemplate(
                $order,
                (string) $validated['email'],
                (string) ($validated['descriptions'] ?? ''),
                (string) $mailData['confirm_url'],
                (string) $mailData['reject_url']
            );
            if (! $templateSent) {
                $this->sendMail((string) $validated['email'], $mailData);
            }
        } catch (\Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' => $this->cmpOrdersMessage('email_send_failed'),
                'status' => 503,
            ];
        }

        return [
            'success' => true,
            'message' => $this->cmpOrdersMessage('email_send'),
            'status' => 200,
        ];
    }

    private function generateToken(): string
    {
        return substr(hash('sha256', microtime(true).Str::random(8)), 0, 10);
    }

    private function buildSubject(Order $order): string
    {
        $companyName = (string) ($order->company?->name ?? '');
        $confirmation = sprintf(
            $this->cmpOrdersMessage('order_confirmation'),
            (string) $order->order_number
        );

        return trim($companyName.' - '.$confirmation);
    }

    private function publicUrl(string $path): string
    {
        return rtrim((string) config('fullvendor.web_url'), '/').'/'.ltrim($path, '/');
    }

    /**
     * @param array<string, mixed> $mailData
     */
    private function sendMail(string $toEmail, array $mailData): void
    {
        $mailable = new PlaceOrderMail($mailData);
        $useSmtp = config('mail.default') === 'log'
            && is_string(env('MAIL_HOST'))
            && env('MAIL_HOST') !== ''
            && env('MAIL_HOST') !== 'null';

        if ($useSmtp) {
            Mail::mailer('smtp')->to($toEmail)->send($mailable);
        } else {
            Mail::to($toEmail)->send($mailable);
        }
    }

    /**
     * Usa plantilla order.email (Settings → Plantilla de envíos) si hay cuerpo.
     */
    private function sendUsingCompanyTemplate(
        Order $order,
        string $toEmail,
        string $descriptions,
        string $confirmUrl,
        string $rejectUrl
    ): bool {
        $message = trim($descriptions);
        $links = trim('Confirm: '.$confirmUrl."\nReject: ".$rejectUrl);
        if ($message !== '') {
            $message .= "\n\n".$links;
        } else {
            $message = $links;
        }

        /** @var CompanySendTemplateService $templates */
        $templates = app(CompanySendTemplateService::class);
        $rendered = $templates->renderForOrder($order, 'email', $message);

        if ($rendered === null || trim($rendered['body']) === '') {
            return false;
        }

        $subject = trim($rendered['subject']) !== ''
            ? $rendered['subject']
            : $this->buildSubject($order);

        $mailer = (config('mail.default') === 'log'
            && is_string(env('MAIL_HOST'))
            && env('MAIL_HOST') !== ''
            && env('MAIL_HOST') !== 'null')
            ? Mail::mailer('smtp')
            : Mail::mailer();

        $mailer->html($rendered['body'], function ($message) use ($toEmail, $subject): void {
            $message->to($toEmail)->subject($subject);
        });

        return true;
    }

    private function cmpOrdersMessage(string $key): string
    {
        $locale = strtolower((string) request()->header('Accept-Language', request()->header('X-App-Locale', 'en')));
        $file = str_contains($locale, 'es') ? 'es-ES.json' : 'en-EN.json';
        $path = base_path('lang/'.$file);
        if (! is_readable($path)) {
            return $key;
        }
        $data = json_decode(file_get_contents($path), true);

        return $data['cmp-orders'][$key] ?? $key;
    }
}
