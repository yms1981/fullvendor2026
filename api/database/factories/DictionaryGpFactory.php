<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DictionaryGp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\DictionaryGp>
 */
final class DictionaryGpFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = DictionaryGp::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->randomNumber(),
            'eng' => fake()->optional()->word,
            'esp' => fake()->optional()->word,
            'arg' => fake()->optional()->word,
        ];
    }
}
