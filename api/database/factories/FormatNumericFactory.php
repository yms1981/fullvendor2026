<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FormatNumeric;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\FormatNumeric>
 */
final class FormatNumericFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = FormatNumeric::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'numeric_format' => fake()->optional()->word,
            'radixPoint' => Arr::random([Str::substr(fake()->word, 1, 11),null]),
            'groupSeparator' => Arr::random([Str::substr(fake()->word, 1, 11),null]),
            'digits' => fake()->optional()->randomNumber(),
            'default' => fake()->optional()->randomNumber(),
            'code_regional' => fake()->optional()->word,
        ];
    }
}
