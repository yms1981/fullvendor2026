<?php

namespace App\Support;

use App\Models\Company;
use App\Support\Languages\SupportedLanguages;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

/**
 * Alinea el locale de Laravel con companies.language_id del request.
 */
final class CompanyRequestLocale
{
    public static function applyFromCompanyId($companyId): int
    {
        $companyId = (int) $companyId;
        if ($companyId < 1) {
            return 1;
        }

        $languageId = (int) Cache::remember(
            "company_language_{$companyId}",
            3600,
            static function () use ($companyId) {
                return (int) (Company::where('company_id', $companyId)->value('language_id') ?? 1);
            }
        );

        if ($languageId < 1 || $languageId > 7) {
            $languageId = 1;
        }

        $locale = SupportedLanguages::definitions()[$languageId]['locale'] ?? 'en';
        App::setLocale($locale);

        return $languageId;
    }
}
