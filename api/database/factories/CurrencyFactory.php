<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Currency>
 */
final class CurrencyFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Currency::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'name' => fake()->name,
            'code' => fake()->currencyCode,
            'symbol' => fake()->randomLetter,
            'tags' => [
                "main_category" => fake()->word,
                "country_category" => fake()->country,
            ]
        ];
    }
}
