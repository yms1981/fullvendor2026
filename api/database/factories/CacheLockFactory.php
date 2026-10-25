<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CacheLock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CacheLock>
 */
final class CacheLockFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = CacheLock::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word,
            'owner' => fake()->word,
            'expiration' => fake()->randomNumber(),
        ];
    }
}
