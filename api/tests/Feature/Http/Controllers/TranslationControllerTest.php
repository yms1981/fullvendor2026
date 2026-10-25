<?php

namespace Tests\Feature\Http\Controllers;

use Database\Seeders\Test\DatabaseResourceFeatureSeeder;
use Tests\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Tests\Feature\Http\FeatureTestCase;
use Tests\TestCase;

class TranslationControllerTest extends FeatureTestCase
{

    use RefreshDatabase;
    protected $apiVersion="v2";

    protected $seeder = DatabaseResourceFeatureSeeder::class;

    protected function getAllModulesTranslations($locale='es') {
        $moduleFiles = File::glob(dirname(app_path())."/lang/{$locale}/*.php");
        $moduleFilesCol = collect($moduleFiles);

        return $moduleFilesCol->mapWithKeys(function ($file) use ($locale) {
            $module = basename($file,'.php');
            return [$module=>include dirname(app_path())."/lang/{$locale}/".$module.".php"];
        })->toArray();

    }


    function test_t()
    {
        $this->assertTranslationsForLocale();
        $this->assertTranslationsForLocale('en');
    }

    /**
     * @return void
     */
    protected function assertTranslationsForLocale($locale='es'): void
    {
        $modulesTransaltionsEs = $this->getAllModulesTranslations($locale);

        foreach ($modulesTransaltionsEs as $module => $transaltions) {
            foreach ($transaltions as $key => $transaltion) {
                if (is_string($transaltion)) {
                    $uri = "/api/{$this->apiVersion}/t/{$module}.{$key}";
                    if($locale !== Lang::getLocale())
                        $uri .="?locale=$locale";
                    $response = $this->getJson($uri);
                    $response->assertOk()->assertSeeHtml($transaltion);
                }
            }
        }
    }
}
