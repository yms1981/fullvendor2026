<?php

namespace App\Services;

/**
 * Textos de límites de plan desde bloques cmp-* en api/lang/{locale}.json.
 */
final class PlanLimitLang
{
    /** @var array<int, string> */
    private const LANGUAGE_FILES = [
        1 => 'en-EN.json',
        2 => 'es-ES.json',
        3 => 'pt-PT.json',
        4 => 'it-IT.json',
        5 => 'ru-RU.json',
        6 => 'de-DE.json',
        7 => 'zh-CN.json',
    ];

    public static function get(int $languageId, string $section, string $key): string
    {
        $file = self::LANGUAGE_FILES[$languageId] ?? 'en-EN.json';
        $path = base_path('lang/' . $file);
        if (! is_file($path)) {
            $path = base_path('lang/en-EN.json');
        }
        if (! is_file($path)) {
            return $key;
        }
        $json = json_decode((string) file_get_contents($path), true);
        if (! is_array($json)) {
            return $key;
        }
        $block = $json[$section] ?? [];
        if (isset($block[$key]) && is_string($block[$key]) && $block[$key] !== '') {
            return $block[$key];
        }

        if ($languageId !== 1) {
            return self::get(1, $section, $key);
        }

        return $key;
    }

    /**
     * Plantillas que esperan los placeholders en orden (max, current).
     */
    private const TEMPLATES_MAX_CURRENT = [
        'plan_limit_create_blocked',
    ];

    public static function format(int $languageId, string $section, string $key, int $current, int $max): string
    {
        $template = self::get($languageId, $section, $key);

        if (in_array($key, self::TEMPLATES_MAX_CURRENT, true)) {
            return sprintf($template, $max, $current);
        }

        return sprintf($template, $current, $max);
    }
}
