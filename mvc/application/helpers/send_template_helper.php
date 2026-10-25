<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Lee plantillas de envío por empresa (mismas que Settings → Plantilla de envíos).
 * Prioridad: JSON guardado de la empresa → defaults de api/lang (cmp-send-templates).
 */
if (! function_exists('fv_send_template_load')) {
    /**
     * @return array{subject: string, body: string}|null
     */
    function fv_send_template_load(int $companyId, string $kind, string $channel): ?array
    {
        $kinds = ['catalog', 'order', 'message'];
        $channels = ['whatsapp', 'email', 'sms'];
        if (! in_array($kind, $kinds, true) || ! in_array($channel, $channels, true)) {
            return null;
        }

        if ($companyId >= 1) {
            $path = APPPATH . 'data/send_templates/' . $companyId . '.json';
            if (is_readable($path)) {
                $data = json_decode((string) file_get_contents($path), true);
                $key = $kind . '.' . $channel;
                if (is_array($data) && isset($data[$key]) && is_array($data[$key])) {
                    return [
                        'subject' => (string) ($data[$key]['subject'] ?? ''),
                        'body' => (string) ($data[$key]['body'] ?? ''),
                    ];
                }
            }
        }

        return fv_send_template_defaults($kind, $channel);
    }
}

if (! function_exists('fv_send_template_defaults')) {
    /**
     * @return array{subject: string, body: string}|null
     */
    function fv_send_template_defaults(string $kind, string $channel): ?array
    {
        $section = fv_send_template_lang_section();
        if ($section === []) {
            return null;
        }

        if ($channel === 'email') {
            $subject = (string) ($section['tpl_' . $kind . '_email_subject'] ?? '');
            $inner = (string) ($section['tpl_' . $kind . '_email_html'] ?? '');
            if ($subject === '' && $inner === '') {
                return null;
            }

            return [
                'subject' => $subject,
                'body' => fv_send_template_wrap_email($subject, $inner),
            ];
        }

        $body = (string) ($section['tpl_' . $kind . '_' . $channel] ?? '');
        if ($body === '') {
            return null;
        }

        return ['subject' => '', 'body' => $body];
    }
}

if (! function_exists('fv_send_template_lang_section')) {
    /**
     * @return array<string, string>
     */
    function fv_send_template_lang_section(): array
    {
        $candidates = [
            FCPATH . '../api/lang/es-ES.json',
            FCPATH . '../../api/lang/es-ES.json',
            dirname(APPPATH) . '/../api/lang/es-ES.json',
            '/var/www/html/lang/es-ES.json',
        ];
        $enCandidates = [
            FCPATH . '../api/lang/en-EN.json',
            FCPATH . '../../api/lang/en-EN.json',
            dirname(APPPATH) . '/../api/lang/en-EN.json',
            '/var/www/html/lang/en-EN.json',
        ];

        $en = fv_send_template_read_lang_file($enCandidates);
        $loc = fv_send_template_read_lang_file($candidates);

        return array_merge($en, $loc);
    }
}

if (! function_exists('fv_send_template_read_lang_file')) {
    /**
     * @param list<string> $paths
     * @return array<string, string>
     */
    function fv_send_template_read_lang_file(array $paths): array
    {
        foreach ($paths as $path) {
            if (! is_readable($path)) {
                continue;
            }
            $data = json_decode((string) file_get_contents($path), true);
            if (! is_array($data) || ! isset($data['cmp-send-templates']) || ! is_array($data['cmp-send-templates'])) {
                continue;
            }
            $out = [];
            foreach ($data['cmp-send-templates'] as $k => $v) {
                if (is_string($k) && (is_string($v) || is_numeric($v))) {
                    $out[$k] = (string) $v;
                }
            }

            return $out;
        }

        return [];
    }
}

if (! function_exists('fv_send_template_wrap_email')) {
    function fv_send_template_wrap_email(string $title, string $innerHtml): string
    {
        $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

        return '<!DOCTYPE html>
<html lang="und">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>' . $titleEsc . '</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;">
<tr><td style="background:#dc2626;padding:18px 24px;color:#ffffff;font-size:18px;font-weight:700;">{{company_name}}</td></tr>
<tr><td style="padding:24px;font-size:15px;line-height:1.55;">' . $innerHtml . '</td></tr>
<tr><td style="padding:16px 24px;background:#f8fafc;color:#6b7280;font-size:12px;">{{company_name}}</td></tr>
</table>
</td></tr>
</table>
</body>
</html>';
    }
}

if (! function_exists('fv_send_template_render')) {
    /**
     * @param array<string, string> $vars
     * @return array{subject: string, body: string}|null
     */
    function fv_send_template_render(int $companyId, string $kind, string $channel, array $vars): ?array
    {
        $tpl = fv_send_template_load($companyId, $kind, $channel);
        if ($tpl === null || trim($tpl['body']) === '') {
            return null;
        }
        $keys = array_keys($vars);
        $values = array_values($vars);

        return [
            'subject' => str_replace($keys, $values, $tpl['subject']),
            'body' => str_replace($keys, $values, $tpl['body']),
        ];
    }
}
