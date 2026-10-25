<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Job>
 */
final class JobFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Job::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'queue' => fake()->word,
            'payload' => fake()->word,
            'attempts' => fake()->randomNumber(1),
            'reserved_at' => fake()->optional()->randomNumber(),
            'available_at' => fake()->randomNumber(),
            'created_at' => fake()->randomNumber(),
        ];
    }
}
