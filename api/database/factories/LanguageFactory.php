<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Language>
 */
final class LanguageFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Language::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'name' => fake()->optional()->name,
            'iso_639_1' => fake()->optional()->languageCode(),
            'created_at' => fake()->optional()->dateTime(),
            'status' => fake()->randomNumber(1),
            'type' => fake()->optional()->word,
            'default' => fake()->optional()->randomNumber(),
        ];
    }
}
