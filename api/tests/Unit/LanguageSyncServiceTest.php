<?php

namespace Tests\Unit;

use App\Models\Language;
use App\Services\LanguageSyncService;
use App\Support\Languages\SupportedLanguages;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LanguageSyncServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('languages');
        Schema::create('languages', function (Blueprint $table) {
            $table->integer('language_id')->primary();
            $table->char('name', 49)->nullable();
            $table->char('iso_639_1', 2)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->string('type', 50)->nullable();
            $table->integer('default')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->softDeletes();
        });
    }

    public function test_audit_detects_missing_supported_languages(): void
    {
        $language = new Language();
        $language->language_id = 1;
        $language->name = 'Inglés';
        $language->iso_639_1 = 'en';
        $language->type = 'eng';
        $language->status = 1;
        $language->save();

        $audit = app(LanguageSyncService::class)->audit();

        $this->assertSame(7, $audit['supported']);
        $this->assertContains(2, $audit['missing']);
        $this->assertContains(7, $audit['missing']);
    }

    public function test_sync_inserts_missing_languages_with_canonical_ids(): void
    {
        $language = new Language();
        $language->language_id = 1;
        $language->name = 'English';
        $language->iso_639_1 = 'en';
        $language->type = 'eng';
        $language->status = 1;
        $language->save();

        $result = app(LanguageSyncService::class)->sync();

        $this->assertContains(2, $result['created']);
        $this->assertContains(7, $result['created']);
        $this->assertSame(7, Language::query()->count());

        $spanish = Language::query()->find(2);
        $this->assertNotNull($spanish);
        $this->assertSame('Español', $spanish->name);
        $this->assertSame('es', $spanish->iso_639_1);
    }

    public function test_sync_updates_outdated_language_rows(): void
    {
        $language = new Language();
        $language->language_id = 1;
        $language->name = 'English';
        $language->iso_639_1 = null;
        $language->type = 'eng';
        $language->status = 1;
        $language->save();

        $result = app(LanguageSyncService::class)->sync();

        $this->assertContains(1, $result['updated']);
        $language = Language::query()->findOrFail(1);
        $this->assertSame('Inglés', $language->name);
        $this->assertSame('en', $language->iso_639_1);
    }

    public function test_supported_languages_match_json_catalog(): void
    {
        foreach (SupportedLanguages::definitions() as $definition) {
            $this->assertFileExists(lang_path($definition['json_file']));
        }
    }
}
