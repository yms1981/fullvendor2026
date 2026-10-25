<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use OpenApi\Attributes as OA;
class TranslationController extends Controller
{
    /**
     * Obtiene el locale basado en el language_id de la compañía
     * @param int|null $companyId
     * @return string
     */
    protected function getLocaleFromCompany($companyId = null): string
    {
        // Si no se proporciona company_id, intentar obtenerlo del request
        if (!$companyId) {
            $companyId = request()->input('company_id');
        }
        
        // Si aún no hay company_id, intentar obtenerlo del usuario autenticado
        if (!$companyId && auth()->check()) {
            $user = auth()->user();
            // Intentar obtener la primera compañía del usuario
            if ($user->companies && $user->companies->isNotEmpty()) {
                $companyId = $user->companies->first()->company_id;
            }
        }
        
        // Si no hay company_id, usar el locale por defecto
        if (!$companyId) {
            return Lang::getLocale() ?: 'en';
        }
        
        // Obtener el language_id de la compañía desde caché o base de datos
        $cacheKey = "company_language_{$companyId}";
        $languageId = Cache::remember($cacheKey, 3600, function () use ($companyId) {
            $company = Company::where('company_id', $companyId)->first();
            return $company ? $company->language_id : 1; // Default a inglés (1)
        });
        
        // Mapear language_id a locale (1=en … 7=zh)
        static $map = [1 => 'en', 2 => 'es', 3 => 'pt', 4 => 'it', 5 => 'ru', 6 => 'de', 7 => 'zh'];

        return $map[(int) $languageId] ?? 'en';
    }

    #[OA\Get(
        path: "/api/v2/t/{term}",
        summary: "Translates the given term.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["Translation"],
        parameters: [
            new OA\Parameter(name: "term", description: "The term key to translate.", in: 'path', required: true),
            new OA\Parameter(name: "locale", description: "The locale to translate for.",in: 'query'),
            new OA\Parameter(name: 'replace', description: "Object with elements for string replacement", in: 'query',schema: new OA\Schema(type: 'object')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "Translation retrieved success"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ])
    ]
    public static function t($key) {
        $replace = \request()->get('replace') ?? [];
        $locale = \request()->get('locale');
        return __($key, $replace, $locale);
    }

    /**
     * Gets the lang array with the keys
     * @param $mdl
     * @return mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[OA\Get(
        path: "/api/v2/langmdl/{key}",
        summary: "Translations for the the given translation key.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["Translation"],
        parameters: [
            new OA\Parameter(name: "term", description: "The term key to translate.", in: 'path', required: true),
            new OA\Parameter(name: "locale", description: "The locale to translate for.",in: 'query'),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "Translations list for key retrieved successfully"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ])
    ]
    function langmdl($mdl)
    {
        $locale = \request()->get('locale');
        if (!$locale) {
            $locale = $this->getLocaleFromCompany();
        }
        $locale = $this->normalizeLocale((string) $locale);

        $jsonFileName = $this->jsonFileNameForLocale($locale);
        $jsonPath = dirname(app_path()) . "/lang/{$jsonFileName}";

        if (!file_exists($jsonPath)) {
            $fallbackPath = dirname(app_path()) . "/lang/{$locale}/" . $mdl . ".php";
            if (file_exists($fallbackPath)) {
                return include $fallbackPath;
            }
            return [];
        }

        $jsonContent = file_get_contents($jsonPath);
        $jsonData = json_decode($jsonContent, true);

        if (isset($jsonData[$mdl])) {
            return $jsonData[$mdl];
        }

        // Fallback a inglés si la clave del módulo no está en el JSON del locale solicitado.
        if ($locale !== 'en') {
            $enPath = dirname(app_path()) . "/lang/en-EN.json";
            if (file_exists($enPath)) {
                $enData = json_decode((string) file_get_contents($enPath), true);
                if (is_array($enData) && isset($enData[$mdl])) {
                    return $enData[$mdl];
                }
            }
        }

        return [];
    }

    /**
     * Devuelve el código de locale corto soportado (en|es|pt|it|ru|de|zh).
     * Acepta también variantes como en-US, es-ES, etc.
     */
    private function normalizeLocale(string $locale): string
    {
        $supported = ['en', 'es', 'pt', 'it', 'ru', 'de', 'zh'];
        if ($locale === 'spa') return 'es';
        if ($locale === 'eng') return 'en';
        $short = strtolower(substr(str_replace('_', '-', $locale), 0, 2));
        if (in_array($short, $supported, true)) {
            return $short;
        }
        return 'en';
    }

    private function jsonFileNameForLocale(string $locale): string
    {
        $map = [
            'en' => 'en-EN.json',
            'es' => 'es-ES.json',
            'pt' => 'pt-PT.json',
            'it' => 'it-IT.json',
            'ru' => 'ru-RU.json',
            'de' => 'de-DE.json',
            'zh' => 'zh-CN.json',
        ];
        return $map[$locale] ?? 'en-EN.json';
    }

    #[OA\Get(
        path: "/api/v2/langmodules/",
        summary: "Complete list of translations grouped by key.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["Translation"],
        parameters: [
            new OA\Parameter(name: "term", description: "The term key to translate.", in: 'path', required: true),
            new OA\Parameter(name: "locale", description: "The locale to translate for.",in: 'query'),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: "Translations list retrieved successfully"),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
        ])
    ]
    function modules()
    {
        $locale = \request()->get('locale');
        if (!$locale) {
            $locale = $this->getLocaleFromCompany();
        }
        $locale = $this->normalizeLocale((string) $locale);
        $jsonFileName = $this->jsonFileNameForLocale($locale);
        $jsonPath = dirname(app_path()) . "/lang/{$jsonFileName}";
        
        // Verificar si el archivo JSON existe
        if (!file_exists($jsonPath)) {
            // Fallback al método anterior si el JSON no existe
            $moduleFiles = File::glob(dirname(app_path())."/lang/{$locale}/*.php");
            $moduleFilesCol = collect($moduleFiles);
            return $moduleFilesCol->map(function ($file) { return basename($file, ".php"); })->toArray();
        }
        
        // Leer el archivo JSON y retornar las claves (nombres de módulos)
        $jsonContent = file_get_contents($jsonPath);
        $jsonData = json_decode($jsonContent, true);
        
        if ($jsonData && is_array($jsonData)) {
            return array_keys($jsonData);
        }
        
        return [];
    }
}
