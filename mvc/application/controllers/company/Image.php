<?php

if(!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        if ('' === $needle || $needle === $haystack) {
            return true;
        }

        if ('' === $haystack) {
            return false;
        }

        $needleLength = \strlen($needle);

        return $needleLength <= \strlen($haystack) && 0 === substr_compare($haystack, $needle, -$needleLength);
    }
}

class Image extends Company_Controller
{
    use IsAction;

    /** @var string|null token capturado antes de cerrar la sesión (peticiones en paralelo) */
    private $cachedApiToken = null;

    public function __construct()
    {
        parent::__construct();

        // Capturar token YA (con sesión abierta) antes de liberar el lock.
        try {
            $token = $this->getUserToken();
            if (is_string($token) && $token !== '') {
                $this->cachedApiToken = $token;
                $this->userToken = $token;
            }
        } catch (\Throwable $e) {
            log_message('debug', 'Image::__construct token: ' . $e->getMessage());
        }

        $isLoggedIn = false;
        $hasUser = false;
        $hasCompany = false;
        $hasToken = $this->cachedApiToken !== null && $this->cachedApiToken !== '';

        try {
            $isLoggedIn = $this->loggedIn();
        } catch (\Throwable $e) {
            log_message('debug', 'Image::__construct loggedIn: ' . $e->getMessage());
        }

        try {
            $hasUser = $this->hasUserData('user');
        } catch (\Throwable $e) {
            log_message('debug', 'Image::__construct hasUser: ' . $e->getMessage());
        }

        try {
            $hasCompany = $this->hasUserData('company');
        } catch (\Throwable $e) {
            log_message('debug', 'Image::__construct hasCompany: ' . $e->getMessage());
        }

        // Cookie de sesión: suficiente para servir binarios públicos vía API
        // (evita 403 cuando el lock de sesión hace fallar hasUserData en paralelo).
        $hasSessionCookie = ! empty($_COOKIE[config_item('sess_cookie_name') ?: 'ci_session']);

        log_message('debug', "Image::__construct - auth: loggedIn={$isLoggedIn}, hasUser={$hasUser}, hasCompany={$hasCompany}, hasToken=" . ($hasToken ? '1' : '0') . ', cookie=' . ($hasSessionCookie ? '1' : '0'));

        if (! $isLoggedIn && ! $hasUser && ! $hasCompany && ! $hasToken && ! $hasSessionCookie) {
            log_message('error', 'Image::__construct - Acceso denegado');
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Not allowed', 'message' => 'The user should be registered to access this resource']);
            exit;
        }

        // Liberar lock: docenas de <img> en paralelo no deben serializarse por sesión.
        if (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    private $imageExtensions = [
        '.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp', '.avif', '.tif', '.tiff', '.heic', '.heif', '.svg', '.ico',
        '.JPG', '.JPEG', '.PNG', '.GIF', '.WEBP', '.BMP', '.AVIF', '.TIF', '.TIFF', '.HEIC', '.HEIF', '.SVG', '.ICO',
    ];

    public function run($imageId = null)
    {
        if (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $this->benchmark->mark('image_start');
        log_message('debug', 'Image requested: ' . $imageId);

        $resource = ($imageId === null || $imageId === 'index')
            ? 'images/no-image'
            : 'images/' . ltrim((string) $imageId, '/');

        $response = $this->fetchPublicImage($resource);

        if ($response === null) {
            log_message('error', 'Image::run API error for ' . ($imageId ?? 'index'));
            $requestedSpecific = $imageId !== null
                && $imageId !== ''
                && $imageId !== 'index'
                && strcasecmp((string) $imageId, 'no-image') !== 0;
            if (! $requestedSpecific) {
                $fallback = $this->fetchPublicImage('images/no-image');
                if ($fallback !== null) {
                    header('Content-Type: ' . $fallback['content_type']);
                    header('Cache-Control: private, max-age=60');
                    echo $fallback['body'];
                    return;
                }
            }
            header('HTTP/1.1 404 Not Found');
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Not found']);
            return;
        }

        header('Content-Type: ' . $response['content_type']);
        header('Cache-Control: private, max-age=3600');
        echo $response['body'];
    }

    /**
     * GET a rutas públicas de imágenes (no dependen de Sanctum).
     * Evita fallos cuando apiRawRequest no tiene Bearer por sesión ya cerrada.
     *
     * @return array{body: string, content_type: string}|null
     */
    private function fetchPublicImage(string $resource): ?array
    {
        try {
            $url = rtrim((string) $this->API_V2_ENTRYPOINT(), '/') . '/' . $this->apiVersion . '/' . ltrim($resource, '/');
            $headers = [
                'Accept' => '*/*',
            ];
            $token = $this->cachedApiToken;
            if (! is_string($token) || $token === '') {
                $token = is_string($this->userToken ?? null) ? $this->userToken : '';
            }
            if ($token !== '') {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            $response = $this->getHttpClient()->request('GET', $url, [
                'headers' => $this->withAcceptLanguageHeader($headers),
                'http_errors' => false,
                'timeout' => 20,
                'connect_timeout' => 5,
            ]);

            $code = (int) $response->getStatusCode();
            if ($code < 200 || $code >= 300) {
                log_message('error', 'Image::fetchPublicImage HTTP ' . $code . ' for ' . $resource);
                return null;
            }

            $contentType = $response->getHeaderLine('Content-Type');
            if ($contentType === '') {
                $contentType = 'application/octet-stream';
            }
            // No servir JSON de error como si fuera imagen (rompe el <img> → onerror → avatar).
            if (stripos($contentType, 'application/json') !== false || stripos($contentType, 'text/html') !== false) {
                log_message('error', 'Image::fetchPublicImage unexpected content-type ' . $contentType . ' for ' . $resource);
                return null;
            }

            return [
                'body' => (string) $response->getBody(),
                'content_type' => $contentType,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Image::fetchPublicImage: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Logo estático FullVendor (navbar y vistas que usan company/image/isologo).
     */
    public function isologo(): void
    {
        $candidates = [
            FCPATH . 'images/logos/logo.png',
            FCPATH . 'images/logos/isologo.png',
        ];

        foreach ($candidates as $path) {
            if (! is_file($path)) {
                continue;
            }
            $mime = str_ends_with(strtolower($path), '.png') ? 'image/png' : 'image/jpeg';
            header('Content-Type: ' . $mime);
            header('Cache-Control: public, max-age=86400');
            readfile($path);
            exit;
        }

        $this->run('no-image');
    }
}
