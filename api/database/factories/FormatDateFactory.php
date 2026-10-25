<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FormatDate;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\FormatDate>
 */
final class FormatDateFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = FormatDate::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'format_php' => \App\Support\DisplayDateFormat::PHP_DATE,
            'format_js' => \App\Support\DisplayDateFormat::JS_JQUERY_UI,
            'language_id' => Language::factory(),
            'date_firstmonth' => \App\Support\DisplayDateFormat::DATE_FIRST_MONTH,
            'default' => '1',
            'month_year' => \App\Support\DisplayDateFormat::MONTH_YEAR,
        ];
    }
}
