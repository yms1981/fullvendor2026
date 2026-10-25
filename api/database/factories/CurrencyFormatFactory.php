<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CurrencyFormat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CurrencyFormat>
 */
final class CurrencyFormatFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = CurrencyFormat::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'CurrencyName' => fake()->word,
            'CurrencyCode' => fake()->word,
            'CurrencyFormat' => fake()->word,
            'currOutput' => fake()->optional()->word,
            'CodeRegional' => fake()->optional()->word,
            'order' => fake()->optional()->randomNumber(),
        ];
    }
}
