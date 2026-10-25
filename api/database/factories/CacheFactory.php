<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cache;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Cache>
 */
final class CacheFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Cache::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word,
            'value' => fake()->word,
            'expiration' => fake()->randomNumber(),
        ];
    }
}
