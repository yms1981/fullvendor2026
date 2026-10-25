<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Order;

/**
 * Plantillas de envío por empresa (email / SMS / WhatsApp).
 * Fuente: storage/app/send_templates/{companyId}.json (mismo archivo que edita el MVC).
 */
class CompanySendTemplateService
{
    public const KINDS = ['catalog', 'order', 'message'];

    public const CHANNELS = ['whatsapp', 'email', 'sms'];

    /**
     * @return array{subject: string, body: string}|null
     */
    public function get(int $companyId, string $kind, string $channel, ?int $languageId = null): ?array
    {
        if (! in_array($kind, self::KINDS, true) || ! in_array($channel, self::CHANNELS, true)) {
            return null;
        }

        $key = $kind.'.'.$channel;
        $stored = $this->loadStored($companyId);
        if (isset($stored[$key]) && is_array($stored[$key])) {
            return [
                'subject' => (string) ($stored[$key]['subject'] ?? ''),
                'body' => (string) ($stored[$key]['body'] ?? ''),
            ];
        }

        return $this->defaultTemplate($kind, $channel, $languageId);
    }

    /**
     * @param  array<string, string>  $vars  claves con llaves, p. ej. '{{customer_name}}' => 'Alex'
     * @return array{subject: string, body: string}|null
     */
    public function render(int $companyId, string $kind, string $channel, array $vars, ?int $languageId = null): ?array
    {
        $tpl = $this->get($companyId, $kind, $channel, $languageId);
        if ($tpl === null) {
            return null;
        }

        $keys = array_keys($vars);
        $values = array_values($vars);

        return [
            'subject' => str_replace($keys, $values, $tpl['subject']),
            'body' => str_replace($keys, $values, $tpl['body']),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function catalogVars(
        Company $company,
        string $customerName,
        string $catalogName,
        string $catalogLink,
        string $message = ''
    ): array {
        return [
            '{{customer_name}}' => $customerName,
            '{{company_name}}' => trim((string) ($company->name ?? '')),
            '{{catalog_name}}' => $catalogName,
            '{{catalog_link}}' => $catalogLink,
            '{{order_number}}' => '',
            '{{order_total}}' => '',
            '{{order_status}}' => '',
            '{{message}}' => $message,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function orderVars(
        Company $company,
        string $customerName,
        string $orderNumber,
        string $orderTotal,
        string $orderStatus,
        string $message = ''
    ): array {
        return [
            '{{customer_name}}' => $customerName,
            '{{company_name}}' => trim((string) ($company->name ?? '')),
            '{{catalog_name}}' => '',
            '{{catalog_link}}' => '',
            '{{order_number}}' => $orderNumber,
            '{{order_total}}' => $orderTotal,
            '{{order_status}}' => $orderStatus,
            '{{message}}' => $message,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messageVars(Company $company, string $customerName, string $message = ''): array
    {
        return [
            '{{customer_name}}' => $customerName,
            '{{company_name}}' => trim((string) ($company->name ?? '')),
            '{{catalog_name}}' => '',
            '{{catalog_link}}' => '',
            '{{order_number}}' => '',
            '{{order_total}}' => '',
            '{{order_status}}' => '',
            '{{message}}' => $message,
        ];
    }

    /**
     * Renderiza plantilla de orden (email / sms / whatsapp) a partir del modelo Order.
     *
     * @return array{subject: string, body: string}|null
     */
    public function renderForOrder(Order $order, string $channel, string $message = ''): ?array
    {
        $company = $order->company;
        if ($company === null) {
            return null;
        }

        $order->loadMissing(['customer', 'statusOrder']);

        $customerName = trim((string) ($order->customer?->name ?? ''));
        if ($customerName === '') {
            $customerName = trim((string) ($order->customer?->business_name ?? ''));
        }

        $statusLabel = trim((string) (
            $order->statusOrder?->name_status_english
            ?? $order->statusOrder?->name_status_spanish
            ?? $order->statusOrder?->name
            ?? ''
        ));

        return $this->render(
            (int) $company->company_id,
            'order',
            $channel,
            $this->orderVars(
                $company,
                $customerName,
                (string) ($order->order_number ?? ''),
                (string) ($order->total_amount ?? $order->total ?? ''),
                $statusLabel,
                $message
            ),
            (int) ($company->language_id ?? 0) ?: null
        );
    }

    /**
     * @return array<string, array{subject?: string, body?: string}>
     */
    private function loadStored(int $companyId): array
    {
        if ($companyId < 1) {
            return [];
        }

        foreach ($this->candidatePaths($companyId) as $path) {
            if (! is_readable($path)) {
                continue;
            }
            $raw = file_get_contents($path);
            $data = json_decode((string) $raw, true);
            if (is_array($data)) {
                return $data;
            }
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function candidatePaths(int $companyId): array
    {
        $file = $companyId.'.json';

        return array_values(array_unique(array_filter([
            storage_path('app/send_templates/'.$file),
            base_path('../mvc/application/data/send_templates/'.$file),
            '/var/www/mvc_send_templates/'.$file,
        ])));
    }

    /**
     * @return array{subject: string, body: string}|null
     */
    private function defaultTemplate(string $kind, string $channel, ?int $languageId): ?array
    {
        $section = $this->loadLangSection('cmp-send-templates', $languageId);
        if ($section === []) {
            return null;
        }

        if ($channel === 'email') {
            $subject = (string) ($section['tpl_'.$kind.'_email_subject'] ?? '');
            $inner = (string) ($section['tpl_'.$kind.'_email_html'] ?? '');
            if ($subject === '' && $inner === '') {
                return null;
            }

            return [
                'subject' => $subject,
                'body' => $this->wrapEmailHtml($subject, $inner),
            ];
        }

        $body = (string) ($section['tpl_'.$kind.'_'.$channel] ?? '');
        if ($body === '') {
            return null;
        }

        return ['subject' => '', 'body' => $body];
    }

    /**
     * @return array<string, string>
     */
    private function loadLangSection(string $section, ?int $languageId): array
    {
        $map = [
            1 => 'en-EN.json',
            2 => 'es-ES.json',
            3 => 'pt-PT.json',
            4 => 'it-IT.json',
            5 => 'ru-RU.json',
            6 => 'de-DE.json',
            7 => 'zh-CN.json',
        ];
        $file = $map[(int) $languageId] ?? 'en-EN.json';
        $en = $this->readLangFile('en-EN.json', $section);
        if ($file === 'en-EN.json') {
            return $en;
        }
        $loc = $this->readLangFile($file, $section);

        return array_merge($en, $loc);
    }

    /**
     * @return array<string, string>
     */
    private function readLangFile(string $basename, string $section): array
    {
        $path = base_path('lang/'.$basename);
        if (! is_readable($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data) || ! isset($data[$section]) || ! is_array($data[$section])) {
            return [];
        }
        $out = [];
        foreach ($data[$section] as $k => $v) {
            if (is_string($k) && (is_string($v) || is_numeric($v))) {
                $out[$k] = (string) $v;
            }
        }

        return $out;
    }

    private function wrapEmailHtml(string $title, string $innerHtml): string
    {
        $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

        return '<!DOCTYPE html>
<html lang="und">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>'.$titleEsc.'</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;">
<tr><td style="background:#dc2626;padding:18px 24px;color:#ffffff;font-size:18px;font-weight:700;">{{company_name}}</td></tr>
<tr><td style="padding:24px;font-size:15px;line-height:1.55;">'.$innerHtml.'</td></tr>
<tr><td style="padding:16px 24px;background:#f8fafc;color:#6b7280;font-size:12px;">{{company_name}}</td></tr>
</table>
</td></tr>
</table>
</body>
</html>';
    }
}
