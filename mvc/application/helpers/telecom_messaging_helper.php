<?php

/**
 * Cliente HTTP para Home Value / Telecom (email, SMS, WhatsApp).
 * No usa Twiliosms ni PHPMailer / SMTP interno de FullVendor.
 * Tras un envío exitoso, acumula contadores en company_messaging_daily.
 */
class TelecomMessagingHelper
{
    public static function baseUrl(): string
    {
        return rtrim((string) (getenv('TELECOM_API_BASE_URL') ?: 'http://telecom.birdviewmall.com'), '/');
    }

    public static function apiKey(): string
    {
        return trim((string) (getenv('TELECOM_API_KEY') ?: ''));
    }

    public static function sendEmail(
        array $destinatary,
        string $subject,
        string $body,
        bool $isHtml = true,
        $companyId = null
    ): bool {
        $emails = array_values(array_filter(array_map('trim', $destinatary)));
        if ($emails === []) {
            return false;
        }

        $ok = self::postJson('/api/Email/SendEmail', [
            'Destinatary' => $emails,
            'Subject' => $subject,
            'Cuerpo' => $body,
            'isHTML' => $isHtml,
        ]);
        if ($ok) {
            self::recordStat($companyId, 'email', count($emails));
        }

        return $ok;
    }

    public static function normalizePhone(string $raw): string
    {
        return (string) preg_replace('/\D+/', '', $raw);
    }

    /**
     * Prefijo país + número nacional (sin duplicar si ya viene el prefijo).
     */
    public static function withCountryPrefix(string $phone, $phonecode): string
    {
        $digits = self::normalizePhone($phone);
        $code = self::normalizePhone((string) $phonecode);
        if ($digits === '') {
            return '';
        }
        if ($code === '') {
            return $digits;
        }
        if (strpos($digits, $code) === 0 && strlen($digits) > strlen($code)) {
            return $digits;
        }

        return $code . $digits;
    }

    public static function sendSms(string $to, string $message, $phonecode = null, $companyId = null): bool
    {
        $to = self::withCountryPrefix($to, $phonecode);
        if ($to === '') {
            return false;
        }

        $ok = self::postJson('/api/SMS/Send', [
            'to' => $to,
            'message' => $message,
        ]);
        if ($ok) {
            self::recordStat($companyId, 'sms', 1);
        }

        return $ok;
    }

    public static function sendWhatsApp(string $to, string $message, $phonecode = null, $companyId = null): bool
    {
        $to = self::withCountryPrefix($to, $phonecode);
        if ($to === '') {
            return false;
        }

        $ok = self::postJson('/api/WhatsApp/Send', [
            'to' => $to,
            'message' => $message,
        ]);
        if ($ok) {
            self::recordStat($companyId, 'whatsapp', 1);
        }

        return $ok;
    }

    /**
     * Incrementa contadores diarios por company_id (email / sms / whatsapp).
     */
    public static function recordStat($companyId, string $channel, int $quantity = 1): void
    {
        $companyId = (int) $companyId;
        if ($companyId <= 0 || $quantity <= 0) {
            return;
        }

            $channelKey = strtolower(trim($channel));
            if ($channelKey === 'email') {
                $column = 'email_count';
            } elseif ($channelKey === 'sms') {
                $column = 'sms_count';
            } elseif ($channelKey === 'whatsapp') {
                $column = 'whatsapp_count';
            } else {
                return;
            }

        try {
            $CI = function_exists('get_instance') ? get_instance() : null;
            if ($CI === null || !isset($CI->db)) {
                return;
            }

            if (!$CI->db->table_exists('company_messaging_daily')) {
                return;
            }

            $date = date('Y-m-d');
            $now = date('Y-m-d H:i:s');
            $row = $CI->db
                ->where('company_id', $companyId)
                ->where('stat_date', $date)
                ->get('company_messaging_daily')
                ->row_array();

            if ($row) {
                $CI->db
                    ->set($column, $column . ' + ' . (int) $quantity, false)
                    ->set('updated_at', $now)
                    ->where('id', (int) $row['id'])
                    ->update('company_messaging_daily');
            } else {
                $CI->db->insert('company_messaging_daily', [
                    'company_id' => $companyId,
                    'stat_date' => $date,
                    'email_count' => $column === 'email_count' ? $quantity : 0,
                    'sms_count' => $column === 'sms_count' ? $quantity : 0,
                    'whatsapp_count' => $column === 'whatsapp_count' ? $quantity : 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        } catch (Throwable $e) {
            if (function_exists('log_message')) {
                log_message('error', 'TelecomMessagingHelper::recordStat failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function postJson(string $path, array $payload): bool
    {
        $url = self::baseUrl() . $path;
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        $apiKey = self::apiKey();
        if ($apiKey !== '') {
            $headers[] = 'api_key: ' . $apiKey;
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int) (getenv('TELECOM_API_TIMEOUT') ?: 30),
        ]);

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $status < 200 || $status >= 300) {
            log_message('error', 'TelecomMessagingHelper failed url=' . $url . ' status=' . $status . ' err=' . $err . ' body=' . substr((string) $raw, 0, 500));

            return false;
        }

        return true;
    }
}
