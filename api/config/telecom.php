<?php

/**
 * Home Value / Telecom messaging API (email, SMS, WhatsApp).
 * Docs: http://telecom.birdviewmall.com/swagger/ui/index
 */
return [
    'base_url' => rtrim((string) env('TELECOM_API_BASE_URL', 'http://telecom.birdviewmall.com'), '/'),
    'api_key' => (string) env('TELECOM_API_KEY', ''),
    'timeout' => (int) env('TELECOM_API_TIMEOUT', 30),
    // En XAMPP/Windows a menudo falta cacert.pem → cURL error 60. Local: false.
    'verify_ssl' => filter_var(env('TELECOM_HTTP_VERIFY', true), FILTER_VALIDATE_BOOLEAN),
];
