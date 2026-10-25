<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Session;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Session>
 */
final class SessionFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Session::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->word,
            'user_id' => fake()->optional()->randomNumber(),
            'ip_address' => fake()->optional()->word,
            'user_agent' => fake()->optional()->userAgent,
            'payload' => fake()->word,
            'last_activity' => fake()->randomNumber(),
        ];
    }
}
