<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Cliente HTTP para Home Value / Telecom (email, SMS, WhatsApp).
 * No usa Twilio ni el mailer interno de FullVendor.
 */
class TelecomMessagingClient
{
    public function __construct(
        protected ?CompanyMessagingStatsService $stats = null
    ) {
    }

    public function isConfigured(): bool
    {
        return rtrim((string) config('telecom.base_url'), '/') !== '';
    }

    /**
     * @param  list<string>  $destinatary
     */
    public function sendEmail(
        array $destinatary,
        string $subject,
        string $body,
        bool $isHtml = true,
        ?int $companyId = null
    ): void {
        $emails = array_values(array_filter(array_map(
            static fn ($e) => trim((string) $e),
            $destinatary
        ), static fn ($e) => $e !== ''));

        if ($emails === []) {
            throw new RuntimeException('Telecom email: no recipients');
        }

        $this->postJson('/api/Email/SendEmail', [
            'Destinatary' => $emails,
            'Subject' => $subject,
            'Cuerpo' => $body,
            'isHTML' => $isHtml,
        ]);

        $this->recordStat($companyId, CompanyMessagingStatsService::CHANNEL_EMAIL, count($emails));
    }

    public function sendSms(string $to, string $message, ?int $companyId = null): void
    {
        $to = $this->normalizePhone($to);
        if ($to === '') {
            throw new RuntimeException('Telecom SMS: empty destination');
        }

        $this->postJson('/api/SMS/Send', [
            'to' => $to,
            'message' => $message,
        ]);

        $this->recordStat($companyId, CompanyMessagingStatsService::CHANNEL_SMS, 1);
    }

    public function sendWhatsApp(string $to, string $message, ?int $companyId = null): void
    {
        $to = $this->normalizePhone($to);
        if ($to === '') {
            throw new RuntimeException('Telecom WhatsApp: empty destination');
        }

        $this->postJson('/api/WhatsApp/Send', [
            'to' => $to,
            'message' => $message,
        ]);

        $this->recordStat($companyId, CompanyMessagingStatsService::CHANNEL_WHATSAPP, 1);
    }

    /**
     * Solo dígitos (sin + ni espacios).
     */
    public function normalizePhone(string $raw): string
    {
        return preg_replace('/\D+/', '', $raw) ?? '';
    }

    /**
     * Número nacional + prefijo de país (p. ej. 51 + 900947238 → 51900947238).
     * Si el número ya empieza por el prefijo, no lo duplica.
     */
    public function withCountryPrefix(string $phone, string|int|null $phonecode): string
    {
        $digits = $this->normalizePhone($phone);
        $code = $this->normalizePhone((string) ($phonecode ?? ''));
        if ($digits === '') {
            return '';
        }
        if ($code === '') {
            return $digits;
        }
        if (str_starts_with($digits, $code) && strlen($digits) > strlen($code)) {
            return $digits;
        }

        return $code . $digits;
    }

    private function recordStat(?int $companyId, string $channel, int $quantity): void
    {
        if ($companyId === null || $companyId <= 0 || $quantity <= 0) {
            return;
        }

        try {
            $stats = $this->stats ?? app(CompanyMessagingStatsService::class);
            $stats->record($companyId, $channel, $quantity);
        } catch (\Throwable $e) {
            Log::warning('TelecomMessagingClient: stats record failed', [
                'company_id' => $companyId,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postJson(string $path, array $payload): void
    {
        $base = rtrim((string) config('telecom.base_url'), '/');
        if ($base === '') {
            throw new RuntimeException('Telecom API base URL is not configured (TELECOM_API_BASE_URL)');
        }

        $url = $base . $path;
        $request = Http::timeout((int) config('telecom.timeout', 30))
            ->acceptJson()
            ->asJson();

        $apiKey = trim((string) config('telecom.api_key', ''));
        if ($apiKey !== '') {
            $request = $request->withHeaders([
                'api_key' => $apiKey,
                'Authorization' => 'Bearer ' . $apiKey,
            ]);
        }

        $response = $request->post($url, $payload);

        if ($response->successful()) {
            return;
        }

        $body = $response->body();
        Log::error('TelecomMessagingClient request failed', [
            'url' => $url,
            'status' => $response->status(),
            'body' => mb_substr((string) $body, 0, 1000),
        ]);

        throw new RuntimeException(
            'Telecom API error HTTP ' . $response->status()
            . ($body !== '' ? (': ' . mb_substr(trim(strip_tags((string) $body)), 0, 200)) : '')
        );
    }
}
