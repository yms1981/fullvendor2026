<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forza el locale de Laravel según el request del cliente (MVC / app móvil),
 * para que mensajes de Fortify (auth.failed, etc.) y traducciones se sirvan
 * en el idioma del usuario y no en APP_LOCALE.
 *
 * Orden de prioridad:
 *   1. Header  X-App-Locale: es|en|pt|it|ru|de|zh
 *   2. Header  Accept-Language (primera entrada; ej. "es-ES,es;q=0.9")
 *   3. Query   ?locale=es
 *   4. Body    locale=es
 *   5. Body    language_id (1=en, 2=es)
 *   6. Default actual (APP_LOCALE)
 */
class SetLocaleFromRequest
{
    private const SUPPORTED = ['en', 'es', 'pt', 'it', 'ru', 'de', 'zh'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        if ($locale !== null) {
            App::setLocale($locale);
        }

        return $next($request);
    }

    private function resolveLocale(Request $request): ?string
    {
        $candidates = [
            $request->header('X-App-Locale'),
            $this->firstFromAcceptLanguage((string) $request->header('Accept-Language', '')),
            $request->query('locale'),
            $request->input('locale'),
            $this->fromLanguageId($request->input('language_id')),
        ];

        foreach ($candidates as $value) {
            $normalized = $this->normalize($value);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    private function firstFromAcceptLanguage(string $header): ?string
    {
        $header = trim($header);
        if ($header === '') {
            return null;
        }
        $parts = explode(',', $header);
        $first = trim((string) ($parts[0] ?? ''));
        if ($first === '') {
            return null;
        }
        $first = explode(';', $first)[0];

        return trim($first) ?: null;
    }

    private function fromLanguageId($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $id = (int) $value;

        return $id === 2 ? 'es' : 'en';
    }

    private function normalize($value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }
        $lower = strtolower(str_replace('_', '-', $value));
        $short = explode('-', $lower)[0] ?? '';
        if ($short === '') {
            return null;
        }
        if (! in_array($short, self::SUPPORTED, true)) {
            return null;
        }

        return $short;
    }
}
