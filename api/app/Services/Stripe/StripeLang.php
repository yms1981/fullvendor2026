<?php

namespace App\Services\Stripe;

/**
 * Textos Stripe desde bloques cmp-stripe-* en api/lang/{locale}.json.
 */
class StripeLang
{
    public static function get(string $key, string $section = 'cmp-stripe-invoice', ?string $locale = null): string
    {
        $locale = $locale ?: (string) config('app.locale', 'en');
        $file = base_path('lang/' . $locale . '.json');
        if (! is_file($file)) {
            $file = base_path('lang/en-EN.json');
        }
        if (! is_file($file)) {
            return $key;
        }
        $json = json_decode((string) file_get_contents($file), true);
        if (! is_array($json)) {
            return $key;
        }
        $block = $json[$section] ?? [];
        if (isset($block[$key]) && is_string($block[$key]) && $block[$key] !== '') {
            return $block[$key];
        }

        return $key;
    }
}
