<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Rutas públicas desde el correo de orden (confirmar / rechazar).
 * URLs: products/order_confirm/{token}, products/order_reject/{token}
 */
class Products extends MY_Controller
{
    /**
     * Último recurso si no existe api/lang o el JSON no se puede leer (texto en inglés).
     * Las claves alinean con cmp-orders; la vista siempre tendrá contenido visible.
     *
     * @return array<string, string>
     */
    private function cmpOrdersEmergencyFallback(): array
    {
        return [
            'corfimacion_orden' => 'Order confirmation',
            'order_confirmed' => '<p>The order has been confirmed.</p>',
            'order_rejected' => '<p>The order has been rejected.</p>',
            'order_email_link_invalid' => '<p>This link is not valid or has expired.</p>',
            'order_yaconfirmada' => '<p>The order was already confirmed.</p>',
            'order_yarechazada' => '<p>The order was already rejected.</p>',
            'order_yaexpirada' => '<p>This order link has expired.</p>',
            'order_atrass' => 'Back',
            'order_confirmation_page_note' => 'Thank you. Your supplier has recorded your response. You can close this tab when you are done.',
        ];
    }

    /**
     * Directorio api/lang con los JSON (varía según despliegue: FCPATH, APPPATH, etc.).
     */
    private function getApiLangDirectory(): ?string
    {
        $candidates = [];
        // 1) Ruta fija desde este archivo: .../application/controllers → ../../../api/lang
        $fromControllers = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang';
        $real = @realpath($fromControllers);
        if ($real !== false) {
            $candidates[] = $real;
        }
        if (defined('APPPATH')) {
            $candidates[] = dirname(APPPATH, 2) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang';
        }
        if (defined('BASEPATH')) {
            $candidates[] = dirname(BASEPATH, 2) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang';
        }
        if (defined('FCPATH')) {
            $candidates[] = dirname(rtrim(FCPATH, DIRECTORY_SEPARATOR)) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang';
        }
        // api/lang dentro de mvc (algunos despliegues)
        if (defined('FCPATH')) {
            $candidates[] = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang';
        }
        $candidates[] = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang';

        foreach ($candidates as $dir) {
            $check = $dir . DIRECTORY_SEPARATOR . 'en-EN.json';
            if (is_dir($dir) && is_readable($check)) {
                return $dir;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function readCmpOrdersFromLangFile(string $filename): array
    {
        $dir = $this->getApiLangDirectory();
        if ($dir === null) {
            return [];
        }
        $path = $dir . DIRECTORY_SEPARATOR . $filename;
        if (!is_readable($path)) {
            return [];
        }
        $raw = (string) file_get_contents($path);
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        $data = json_decode($raw, true);

        return is_array($data) && isset($data['cmp-orders']) && is_array($data['cmp-orders'])
            ? $data['cmp-orders']
            : [];
    }

    /**
     * Textos cmp-orders para la vista pública order_confirmed.
     *
     * @return array<string, mixed>
     */
    private function loadCmpOrdersLang(?int $language_id = null): array
    {
        $langId = $language_id ?? 1;
        $file = ($langId === 2) ? 'es-ES.json' : 'en-EN.json';
        $cmp = $this->readCmpOrdersFromLangFile($file);
        if ($cmp === [] && $file !== 'en-EN.json') {
            $cmp = $this->readCmpOrdersFromLangFile('en-EN.json');
        }
        if ($cmp === []) {
            $cmp = $this->readCmpOrdersFromLangFile('es-ES.json');
        }

        return $cmp;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function callOrderEmailTokenApi(string $action, string $token): ?array
    {
        $token = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $token);
        if ($token === '') {
            return null;
        }

        $resource = 'order/email-token/' . rawurlencode($token) . '/' . $action;
        $resp = $this->post($resource, [], true);
        if (! is_array($resp) || $this->hasError($resp)) {
            return null;
        }

        $data = $resp['data'] ?? null;

        return is_array($data) ? $data : null;
    }

    /**
     * @param array<string, mixed> $lang
     * @param array<string, string> $orderMeta
     * @return array<string, string>
     */
    private function localizeOrderMeta(array $lang, array $orderMeta): array
    {
        if ($orderMeta === []) {
            return [];
        }
        $statusKey = isset($orderMeta['status_key']) ? (string) $orderMeta['status_key'] : 'pending';
        $orderMeta['status'] = isset($lang[$statusKey]) ? (string) $lang[$statusKey] : $statusKey;
        unset($orderMeta['status_key']);

        return $orderMeta;
    }

    /**
     * @param array<string, mixed> $lang
     */
    private function messageForResult(array $lang, string $result): string
    {
        $map = [
            'invalid' => 'order_email_link_invalid',
            'already_confirmed' => 'order_yaconfirmada',
            'already_rejected' => 'order_yarechazada',
            'expired' => 'order_yaexpirada',
            'confirmed' => 'order_confirmed',
            'rejected' => 'order_rejected',
        ];
        $key = $map[$result] ?? 'order_email_link_invalid';

        return isset($lang[$key]) ? (string) $lang[$key] : '';
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function renderOrderResult(array $lang, string $mensaje, array $orderMeta = []): void
    {
        if ($lang === []) {
            $lang = $this->readCmpOrdersFromLangFile('en-EN.json');
            if ($lang === []) {
                $lang = $this->readCmpOrdersFromLangFile('es-ES.json');
            }
        }
        // JSON válido sobrescribe el fallback; omitir claves vacías para no pisar el fallback.
        $lang = array_merge(
            $this->cmpOrdersEmergencyFallback(),
            array_filter($lang, static function ($v) {
                return $v !== null && $v !== '';
            })
        );

        if (trim((string) $mensaje) === '') {
            $mensaje = (string) ($lang['order_confirmed'] ?? '');
        }
        $lo = $lang + [
            'order_atrass' => $lang['order_atrass'] ?? '',
            'corfimacion_orden' => $lang['corfimacion_orden'] ?? '',
            'order_confirmation_page_note' => $lang['order_confirmation_page_note'] ?? '',
        ];
        $this->load->view('order_confirmed', [
            'mensaje' => $mensaje,
            'lang_order' => $lo,
            'order_meta' => $this->localizeOrderMeta($lang, $orderMeta),
        ]);
    }

    public function order_confirm($token = ''): void
    {
        $apiData = $this->callOrderEmailTokenApi('confirm', (string) $token);
        $langId = is_array($apiData) && isset($apiData['language_id']) ? (int) $apiData['language_id'] : null;
        $lang = $this->loadCmpOrdersLang($langId);

        if ($apiData === null) {
            $this->renderOrderResult($lang, $this->messageForResult($lang, 'invalid'));

            return;
        }

        $result = isset($apiData['result']) ? (string) $apiData['result'] : 'invalid';
        $orderMeta = isset($apiData['order_meta']) && is_array($apiData['order_meta']) ? $apiData['order_meta'] : [];

        $this->renderOrderResult(
            $lang,
            $this->messageForResult($lang, $result),
            $orderMeta
        );
    }

    public function order_reject($token = ''): void
    {
        $apiData = $this->callOrderEmailTokenApi('reject', (string) $token);
        $langId = is_array($apiData) && isset($apiData['language_id']) ? (int) $apiData['language_id'] : null;
        $lang = $this->loadCmpOrdersLang($langId);

        if ($apiData === null) {
            $this->renderOrderResult($lang, $this->messageForResult($lang, 'invalid'));

            return;
        }

        $result = isset($apiData['result']) ? (string) $apiData['result'] : 'invalid';
        $orderMeta = isset($apiData['order_meta']) && is_array($apiData['order_meta']) ? $apiData['order_meta'] : [];

        $this->renderOrderResult(
            $lang,
            $this->messageForResult($lang, $result),
            $orderMeta
        );
    }
}
