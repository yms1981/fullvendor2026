<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sendtemplate extends Company_Controller
{
    private const KINDS = ['catalog', 'order', 'message'];
    private const CHANNELS = ['whatsapp', 'email', 'sms'];

    public function index()
    {
        $texts = $this->texts();
        $stored = $this->loadStored();
        $defaults = $this->defaults($texts);
        $samples = $this->sampleMap($texts);
        $items = [];
        foreach (self::KINDS as $kind) {
            foreach (self::CHANNELS as $channel) {
                $key = $kind . '.' . $channel;
                $row = $stored[$key] ?? $defaults[$key];
                $subject = (string) ($row['subject'] ?? '');
                $body = (string) ($row['body'] ?? '');
                $items[] = [
                    'kind' => $kind,
                    'channel' => $channel,
                    'subject' => $subject,
                    'body' => $body,
                    'preview_subject' => $this->applySamples($subject, $samples),
                    'preview_body' => $this->applySamples($body, $samples),
                    'custom' => isset($stored[$key]),
                ];
            }
        }

        $this->showContent('company/sendtemplate/index', [
            'texts' => $texts,
            'items' => $items,
            'page' => 'settings',
        ]);
    }

    public function edit($kind = '', $channel = '')
    {
        $kind = (string) $kind;
        $channel = (string) $channel;
        if (!$this->isAllowed($kind, $channel)) {
            show_404();
            return;
        }

        $texts = $this->texts();
        $key = $kind . '.' . $channel;
        $stored = $this->loadStored();
        $defaults = $this->defaults($texts);
        $row = $stored[$key] ?? $defaults[$key];
        $samples = $this->sampleMap($texts);
        $subject = (string) ($row['subject'] ?? '');
        $body = (string) ($row['body'] ?? '');

        $this->showContent('company/sendtemplate/edit', [
            'texts' => $texts,
            'kind' => $kind,
            'channel' => $channel,
            'subject' => $subject,
            'body' => $body,
            'preview_subject' => $this->applySamples($subject, $samples),
            'preview_body' => $this->applySamples($body, $samples),
            'sampleMap' => $samples,
            'page' => 'settings',
        ]);
    }

    public function save()
    {
        $kind = (string) $this->input->post('kind');
        $channel = (string) $this->input->post('channel');
        if (!$this->isAllowed($kind, $channel)) {
            show_404();
            return;
        }

        $texts = $this->texts();
        $key = $kind . '.' . $channel;
        $stored = $this->loadStored();
        $stored[$key] = [
            'subject' => trim((string) $this->input->post('subject', false)),
            'body' => (string) $this->input->post('body', false),
        ];
        $this->writeStored($stored);

        $this->session->set_flashdata('fv_sendtemplate_notice', $texts['saved'] ?? '');
        redirect(company_url('sendtemplate/edit/' . $kind . '/' . $channel));
    }

    public function reset($kind = '', $channel = '')
    {
        $kind = (string) $kind;
        $channel = (string) $channel;
        if (!$this->isAllowed($kind, $channel)) {
            show_404();
            return;
        }

        $stored = $this->loadStored();
        unset($stored[$kind . '.' . $channel]);
        $this->writeStored($stored);

        $texts = $this->texts();
        $this->session->set_flashdata('fv_sendtemplate_notice', $texts['reset_done'] ?? '');
        redirect(company_url('sendtemplate/edit/' . $kind . '/' . $channel));
    }

    private function isAllowed(string $kind, string $channel): bool
    {
        return in_array($kind, self::KINDS, true) && in_array($channel, self::CHANNELS, true);
    }

    private function texts(): array
    {
        $section = $this->getLangJsonSection('cmp-send-templates');

        return is_array($section) ? $section : [];
    }

    private function storagePath(): string
    {
        $dir = APPPATH . 'data/send_templates';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir . '/' . (int) $this->getCompanyId() . '.json';
    }

    private function loadStored(): array
    {
        $path = $this->storagePath();
        if (!is_readable($path)) {
            return [];
        }
        $raw = file_get_contents($path);
        $data = json_decode((string) $raw, true);

        return is_array($data) ? $data : [];
    }

    private function writeStored(array $data): void
    {
        file_put_contents(
            $this->storagePath(),
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    private function sampleMap(array $t): array
    {
        return [
            '{{customer_name}}' => (string) ($t['sample_customer'] ?? 'Alex Rivera'),
            '{{company_name}}' => (string) ($t['sample_company_name'] ?? ($t['sample_company'] ?? 'FullVendor')),
            '{{catalog_name}}' => (string) ($t['sample_catalog'] ?? 'Spring catalog'),
            '{{catalog_link}}' => (string) ($t['sample_catalog_link'] ?? 'https://example.com/catalog'),
            '{{order_number}}' => (string) ($t['sample_order_number'] ?? 'ORD-1042'),
            '{{order_total}}' => (string) ($t['sample_order_total'] ?? '$250.00'),
            '{{order_status}}' => (string) ($t['sample_order_status'] ?? 'Approved'),
            '{{message}}' => (string) ($t['sample_message'] ?? 'Thank you for your purchase.'),
        ];
    }

    private function applySamples(string $text, array $samples): string
    {
        return str_replace(array_keys($samples), array_values($samples), $text);
    }

    private function defaults(array $t): array
    {
        $company = '{{company_name}}';

        return [
            'catalog.whatsapp' => [
                'subject' => '',
                'body' => $t['tpl_catalog_whatsapp'] ?? '',
            ],
            'catalog.sms' => [
                'subject' => '',
                'body' => $t['tpl_catalog_sms'] ?? '',
            ],
            'catalog.email' => [
                'subject' => $t['tpl_catalog_email_subject'] ?? '',
                'body' => $this->emailHtml(
                    $t['tpl_catalog_email_subject'] ?? '',
                    $t['tpl_catalog_email_html'] ?? '',
                    $company
                ),
            ],
            'order.whatsapp' => [
                'subject' => '',
                'body' => $t['tpl_order_whatsapp'] ?? '',
            ],
            'order.sms' => [
                'subject' => '',
                'body' => $t['tpl_order_sms'] ?? '',
            ],
            'order.email' => [
                'subject' => $t['tpl_order_email_subject'] ?? '',
                'body' => $this->emailHtml(
                    $t['tpl_order_email_subject'] ?? '',
                    $t['tpl_order_email_html'] ?? '',
                    $company
                ),
            ],
            'message.whatsapp' => [
                'subject' => '',
                'body' => $t['tpl_message_whatsapp'] ?? '',
            ],
            'message.sms' => [
                'subject' => '',
                'body' => $t['tpl_message_sms'] ?? '',
            ],
            'message.email' => [
                'subject' => $t['tpl_message_email_subject'] ?? '',
                'body' => $this->emailHtml(
                    $t['tpl_message_email_subject'] ?? '',
                    $t['tpl_message_email_html'] ?? '',
                    $company
                ),
            ],
        ];
    }

    private function emailHtml(string $title, string $innerHtml, string $company): string
    {
        $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $company = $company !== '' ? $company : '{{company_name}}';

        return '<!DOCTYPE html>
<html lang="und">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>' . $title . '</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;">
<tr><td style="background:#dc2626;padding:18px 24px;color:#ffffff;font-size:18px;font-weight:700;">' . $company . '</td></tr>
<tr><td style="padding:24px;font-size:15px;line-height:1.55;">' . $innerHtml . '</td></tr>
<tr><td style="padding:16px 24px;background:#f8fafc;color:#6b7280;font-size:12px;">{{company_name}}</td></tr>
</table>
</td></tr>
</table>
</body>
</html>';
    }
}
