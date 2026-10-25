<?php

namespace App\Support\Languages;

use Illuminate\Support\Facades\File;

/**
 * Catálogo canónico de idiomas del sistema (alineado con api/lang/*.json y language_id 1..7).
 */
class SupportedLanguages
{
    /**
     * @return array<int, array{
     *     language_id: int,
     *     name: string,
     *     iso_639_1: string,
     *     type: string,
     *     status: int,
     *     locale: string,
     *     json_file: string
     * }>
     */
    public static function definitions(): array
    {
        return [
            1 => [
                'language_id' => 1,
                'name' => 'Inglés',
                'iso_639_1' => 'en',
                'type' => 'eng',
                'status' => 1,
                'locale' => 'en',
                'json_file' => 'en-EN.json',
            ],
            2 => [
                'language_id' => 2,
                'name' => 'Español',
                'iso_639_1' => 'es',
                'type' => 'spa',
                'status' => 1,
                'locale' => 'es',
                'json_file' => 'es-ES.json',
            ],
            3 => [
                'language_id' => 3,
                'name' => 'Portugués',
                'iso_639_1' => 'pt',
                'type' => 'por',
                'status' => 1,
                'locale' => 'pt',
                'json_file' => 'pt-PT.json',
            ],
            4 => [
                'language_id' => 4,
                'name' => 'Italiano',
                'iso_639_1' => 'it',
                'type' => 'ita',
                'status' => 1,
                'locale' => 'it',
                'json_file' => 'it-IT.json',
            ],
            5 => [
                'language_id' => 5,
                'name' => 'Ruso',
                'iso_639_1' => 'ru',
                'type' => 'rus',
                'status' => 1,
                'locale' => 'ru',
                'json_file' => 'ru-RU.json',
            ],
            6 => [
                'language_id' => 6,
                'name' => 'Alemán',
                'iso_639_1' => 'de',
                'type' => 'deu',
                'status' => 1,
                'locale' => 'de',
                'json_file' => 'de-DE.json',
            ],
            7 => [
                'language_id' => 7,
                'name' => 'Chino',
                'iso_639_1' => 'zh',
                'type' => 'zho',
                'status' => 1,
                'locale' => 'zh',
                'json_file' => 'zh-CN.json',
            ],
        ];
    }

    /**
     * @return list<int>
     */
    public static function ids(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * @return list<string> Archivos JSON en api/lang/ sin entrada canónica.
     */
    public static function orphanJsonFiles(): array
    {
        $directory = lang_path();
        if (! is_dir($directory)) {
            return [];
        }

        $known = [];
        foreach (self::definitions() as $definition) {
            $known[$definition['json_file']] = true;
        }

        $orphans = [];
        foreach (File::files($directory) as $file) {
            $basename = $file->getFilename();
            if (! str_ends_with($basename, '.json')) {
                continue;
            }
            if (! isset($known[$basename])) {
                $orphans[] = $basename;
            }
        }

        sort($orphans);

        return $orphans;
    }

    /**
     * @return list<string> Archivos JSON definidos pero ausentes en disco.
     */
    public static function missingJsonFiles(): array
    {
        $missing = [];
        foreach (self::definitions() as $definition) {
            $path = lang_path($definition['json_file']);
            if (! is_file($path)) {
                $missing[] = $definition['json_file'];
            }
        }

        return $missing;
    }

    /**
     * @return list<array{language_id: int, name: string, iso_639_1: string, type: string, status: int}>
     */
    public static function recordsForDatabase(): array
    {
        $records = [];
        foreach (self::definitions() as $definition) {
            $records[] = [
                'language_id' => $definition['language_id'],
                'name' => $definition['name'],
                'iso_639_1' => $definition['iso_639_1'],
                'type' => $definition['type'],
                'status' => $definition['status'],
            ];
        }

        return $records;
    }
}
