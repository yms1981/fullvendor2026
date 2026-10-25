<?php

use App\Support\DisplayDateFormat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('format_date')) {
            return;
        }

        $payload = [
            'format_php' => DisplayDateFormat::PHP_DATE,
            'format_js' => DisplayDateFormat::JS_JQUERY_UI,
            'date_firstmonth' => DisplayDateFormat::DATE_FIRST_MONTH,
            'month_year' => DisplayDateFormat::MONTH_YEAR,
        ];

        if (DB::table('format_date')->count() === 0) {
            DB::table('format_date')->insert(array_merge($payload, [
                'language_id' => null,
                'default' => '1',
            ]));

            return;
        }

        DB::table('format_date')->update($payload);

        if (! DB::table('format_date')->where('default', 1)->exists()) {
            $firstId = DB::table('format_date')->orderBy('id')->value('id');
            if ($firstId !== null) {
                DB::table('format_date')->where('id', $firstId)->update(['default' => '1']);
            }
        }
    }

    public function down(): void
    {
        // No revert: el formato anterior no estaba unificado en el proyecto.
    }
};
