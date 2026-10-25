<?php

namespace Database\Seeders;

use App\Services\LanguageSyncService;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(LanguageSyncService::class)->sync();
    }
}
